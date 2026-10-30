<?php
declare(strict_types=1);

namespace gijsbos\Http\Http;

use CurlHandle;
use Exception;
use gijsbos\Http\RawResponse;
use gijsbos\Http\RequestMethod;
use gijsbos\Http\Response;
use InvalidArgumentException;
use LogicException;
use TypeError;
use WeakMap;

define("FOLLOW_LOCATION",       flag_id('gijsbos\Http\Http'));
define("GET_REDIRECT_URI",      flag_id('gijsbos\Http\Http'));
define("HTTP_REQUEST_HANDLE",   flag_id('gijsbos\Http\Http'));
define("RAW_RESPONSE",          flag_id('gijsbos\Http\Http'));

/**
 * HTTPRequest
 *
 *  Thin wrapper around curl. Returns a Response, or a HTTPRequestHandle for async use in a HTTPRequestPool.
 *
 *      HTTPRequest::get(["uri" => "https://...", "data" => ["q" => "x"]]);
 *      HTTPRequest::post(["uri" => "https://...", "data" => [...], "headers" => ["Content-Type" => "application/json"]]);
 *
 *  data:
 *      array   GET/DELETE: query string. POST/PUT/PATCH: form encoded, json (Content-Type: application/json) or multipart.
 *      string  Raw query string (GET) or raw body (other methods), sent as-is.
 *
 *  Flags:
 *      FOLLOW_LOCATION      Follow redirects (GET/DELETE)
 *      GET_REDIRECT_URI     Adds the final url as "redirectURI" parameter
 *      HTTP_REQUEST_HANDLE  Returns a HTTPRequestHandle instead of executing, see HTTPRequestPool
 *      RAW_RESPONSE         Returns a RawResponse: body and response headers exactly as received (for proxying)
 *
 * @method static mixed call(array $args = [], $flags = null)
 * @method static mixed get(array $args = [], $flags = null)
 * @method static mixed post(array $args = [], $flags = null)
 * @method static mixed put(array $args = [], $flags = null)
 * @method static mixed patch(array $args = [], $flags = null)
 * @method static mixed delete(array $args = [], $flags = null)
 */
class HTTPRequest
{
    /**
     * @var string $DEFAULT_URL
     *  Base url used when a request has none
     */
    public static $DEFAULT_URL = "";

    /**
     * @var int $DEFAULT_TIMEOUT
     *  Seconds, override per request with option CURLOPT_TIMEOUT
     */
    public static $DEFAULT_TIMEOUT = 10;

    /**
     * Methods that can be called both on the object and statically
     */
    const MAGIC_METHODS = ["call", "get", "post", "put", "patch", "delete"];

    const SUPPORTED_METHODS = ["GET", "POST", "PUT", "PATCH", "DELETE"];

    /**
     * Response headers that describe the transfer, not the content; curl already handled them (e.g. decompressed the body)
     */
    const HOP_BY_HOP_HEADERS = ["connection", "keep-alive", "transfer-encoding", "te", "trailer", "upgrade", "proxy-authenticate", "proxy-authorization", "content-length", "content-encoding"];

    // Defaults are set here, subclasses that don't call the parent constructor still get them
    private $type = null;
    private $uri = "";
    private array|string $data = [];
    private array $headers = [];
    private $flags = null;
    private array $cURLOptions = [];
    private $baseURL = "";
    private $curlProxy = "";

    /**
     * @var WeakMap<CurlHandle, array> Response headers per curl handle, only for RAW_RESPONSE requests
     */
    private static ?WeakMap $rawResponseHeaders = null;

    /**
     * __construct
     */
    public function __construct(null|string|int $type = null, null|string $uri = null, array|string $data = [], array $headers = [], null|int $flags = null, null|array $cURLOptions = null, null|string $baseURL = null)
    {
        $this->init(get_defined_vars());
    }

    /**
     * setDefaultURL
     */
    public static function setDefaultURL(string $url) : void
    {
        self::$DEFAULT_URL = $url;
    }

    /**
     * init
     *  Sets the values that are passed, keeps the others. Keys: type|method, uri|url, data, headers, flags, cURLOptions|options, baseURL
     */
    public function init(array $args)
    {
        $args = array_filter($args, fn($v) => $v !== null);

        if(array_key_exists("type", $args) || array_key_exists("method", $args))
            $this->type = RequestMethod::convertToConstant($args["type"] ?? $args["method"]);

        $this->uri = $args["uri"] ?? $args["url"] ?? $this->uri;
        $this->data = $args["data"] ?? $this->data;
        $this->flags = $args["flags"] ?? $this->flags;

        if(is_array($args["headers"] ?? null))
            $this->headers = array_merge($this->headers, $args["headers"]);

        $options = $args["cURLOptions"] ?? $args["options"] ?? [];

        if(!is_array($options))
            throw new TypeError("Invalid cURL option type using argument type " . get_type($options));

        $this->cURLOptions = $options + $this->cURLOptions;

        $this->baseURL = $args["baseURL"] ?? (is_string($this->baseURL) && strlen($this->baseURL) ? $this->baseURL : self::$DEFAULT_URL);
    }

    /**
     * CURL proxy
     */
    public function hasCURLProxy() : bool
    {
        return strlen($this->curlProxy) > 0;
    }

    public function getCURLProxy()
    {
        return $this->curlProxy;
    }

    public function setCURLProxy(string $address)
    {
        $this->curlProxy = $address;
        $this->cURLOptions[CURLOPT_PROXY] = $address;
    }

    /**
     * Getters / setters
     */
    public function setType(string|int $type)
    {
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setURI(string $uri)
    {
        $this->uri = $uri;
    }

    public function getURI()
    {
        return $this->uri;
    }

    public function addData(string $key, $value)
    {
        $this->assertArrayData(__METHOD__);
        $this->data[$key] = $value;
    }

    public function mergeData(array $data)
    {
        $this->assertArrayData(__METHOD__);
        $this->data = array_merge($this->data, $data);
    }

    public function setData(array|string $data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    private function assertArrayData(string $method) : void
    {
        if(is_string($this->data))
            throw new LogicException("$method failed: data is a raw string body, use setData() to replace it");
    }

    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function addHeader(string $key, string $value) : void
    {
        $this->removeHeader($key);
        $this->headers[$key] = $value;
    }

    public function addHeaders(array $headers) : void
    {
        foreach($headers as $key => $value)
            $this->addHeader($key, $value);
    }

    public function removeHeader(string $key)
    {
        unset($this->headers[$key]);
    }

    public function addFlag(int $flag)
    {
        $this->flags = $this->flags | $flag;
    }

    public function setFlags($flags)
    {
        $this->flags = $flags;
    }

    public function getFlags()
    {
        return $this->flags;
    }

    public function setCURLOptions(array $options)
    {
        $this->cURLOptions = $options;
    }

    public function getCURLOptions()
    {
        return $this->cURLOptions;
    }

    public function getBaseURL() : string
    {
        return $this->baseURL;
    }

    public function setBaseURL(string $url) : void
    {
        $this->baseURL = $url;
    }

    /**
     * convertToCURLHeaders
     *  ["Content-Type" => "x", "X-A: 1"] => ["Content-Type: x", "X-A: 1"], underscores in keys become dashes ($_SERVER style keys)
     */
    public static function convertToCURLHeaders(array $headers) : array
    {
        $parsed = [];

        foreach($headers as $key => $value)
            $parsed[] = is_int($key) ? $value : sprintf("%s: %s", str_replace("_", "-", $key), $value);

        return $parsed;
    }

    /**
     * hasHeader
     *  Searches keys ("Authorization") or "key: value" lines ("Content-Type: application\/json", regex, case insensitive).
     *
     * @param bool $assumeSearchValue - Replaces the found header with the search value, e.g. strips the browser
     *  boundary from 'Content-Type: multipart/form-data; boundary=...' so curl can set its own.
     */
    public function hasHeader(string $header, array &$headers, bool $assumeSearchValue = false) : bool
    {
        $isKeyValue = preg_match("/^[\w-]+:\s*.+$/", $header);
        $search = preg_grep("/$header/i", $isKeyValue ? $headers : array_keys($headers));

        if($assumeSearchValue && count($search))
        {
            $key = key($search);

            if($header != reset($search))
                $headers[$key] = stripslashes($header);
        }

        return count($search) > 0;
    }

    /**
     * sendRequest
     *
     * @return Response|RawResponse|HTTPRequestHandle
     */
    public function sendRequest()
    {
        if($this->type === null)
            throw new Exception(sprintf("%s failed, type missing", __METHOD__));

        $method = strtoupper((string) RequestMethod::convertToString($this->type));

        if(!in_array($method, self::SUPPORTED_METHODS, true))
            throw new Exception(__METHOD__ . ": Request method '$method' not supported");

        $url = $this->prependBaseURL((string) $this->uri);
        $headers = self::convertToCURLHeaders($this->headers);
        $flags = (int) $this->flags;

        // Array data goes in the query string for GET/DELETE (contract), a string body is always sent as body (except GET)
        $hasBody = in_array($method, ["POST", "PUT", "PATCH"], true) || ($method === "DELETE" && is_string($this->data));

        if($hasBody)
            $postFields = $this->createPostFields($this->data, $headers);
        else
        {
            $url = self::addQuery($url, $this->data);
            $postFields = null;
        }

        $curl = $this->createCurlHandle($url, $headers, $method, $postFields);

        if($flags & FOLLOW_LOCATION && !$hasBody)
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

        if($flags & RAW_RESPONSE)
            self::captureResponseHeaders($curl);

        // Async: execute later in a HTTPRequestPool
        if($flags & HTTP_REQUEST_HANDLE)
            return new HTTPRequestHandle($curl, null, HTTPRequestHandle::createHash("$method $url", $postFields, $headers));

        $response = self::handleCurlResult($curl, curl_exec($curl), $flags);

        // Debug aid between services; raw responses are passed on as-is
        if(!$response->isSuccessful() && !$response instanceof RawResponse)
            $this->addErrorURI($response, $url);

        return $response;
    }

    /**
     * createPostFields
     *  string => raw body. Array => multipart (curl builds the body), json or form encoded depending on Content-Type.
     */
    private function createPostFields(array|string $data, array &$curlHeaders)
    {
        if(is_string($data))
            return $data;

        if($this->hasHeader("Content-Type: multipart\/form-data", $curlHeaders, true))
        {
            if(count(($nested = array_filter($data, 'is_array'))) > 0)
                throw new InvalidArgumentException(sprintf("HTTPRequest CURLOPT_POSTFIELDS contains invalid fields '%s' of type 'array'", implode(",", array_keys($nested))));

            return $data;
        }

        return $this->hasHeader("Content-Type:\s*application\/json", $curlHeaders) ? json_encode($data) : http_build_query($data);
    }

    /**
     * addQuery
     *  Adds data to the url query string, parameters already in the url take precedence.
     */
    private static function addQuery(string $url, array|string $data) : string
    {
        if(is_string($data))
            return strlen($data) ? $url . (str_contains($url, "?") ? "&" : "?") . $data : $url;

        if(count($data) === 0)
            return $url;

        [$url, $fragment] = array_pad(explode("#", $url, 2), 2, null);
        [$path, $query] = array_pad(explode("?", $url, 2), 2, "");

        parse_str($query, $urlData);

        return $path . "?" . http_build_query(array_merge($data, $urlData)) . ($fragment !== null ? "#$fragment" : "");
    }

    /**
     * createCurlHandle
     *  User options take precedence over the defaults.
     */
    private function createCurlHandle(string $url, array $curlHeaders, string $method, mixed $postFields) : CurlHandle
    {
        $curl = curl_init();

        if($curl === false)
            throw new Exception("Curl init failed");

        $curlOptions = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ACCEPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_HTTPHEADER => $curlHeaders,
            CURLOPT_TIMEOUT => self::$DEFAULT_TIMEOUT,
        ];

        if($method !== "GET")
            $curlOptions[CURLOPT_CUSTOMREQUEST] = $method;

        if($postFields !== null)
            $curlOptions[CURLOPT_POSTFIELDS] = $postFields;

        curl_setopt_array($curl, $this->cURLOptions + $curlOptions);

        return $curl;
    }

    /**
     * captureResponseHeaders
     *  Collects the response headers of the final response (after redirects / 100-continue).
     */
    private static function captureResponseHeaders(CurlHandle $curl) : void
    {
        self::$rawResponseHeaders ??= new WeakMap();
        self::$rawResponseHeaders[$curl] = [];

        curl_setopt($curl, CURLOPT_HEADERFUNCTION, static function(CurlHandle $curl, string $line) : int
        {
            $headers = self::$rawResponseHeaders[$curl];

            // Status line starts a new header block
            if(str_starts_with($line, "HTTP/"))
                $headers = [];

            else if(str_contains($line, ":"))
            {
                [$name, $value] = array_map('trim', explode(":", $line, 2));

                if(!in_array(strtolower($name), self::HOP_BY_HOP_HEADERS, true))
                    $headers[$name] = isset($headers[$name]) ? $headers[$name] . ", " . $value : $value;
            }

            self::$rawResponseHeaders[$curl] = $headers;

            return strlen($line);
        });
    }

    /**
     * handleCurlResult
     *  Turns a finished curl handle into a Response. Used for single requests and by HTTPRequestPool.
     *
     * @param int $errno - Curl error code, pooled requests pass the code from curl_multi_info_read
     */
    public static function handleCurlResult(CurlHandle $curl, $data, null|int $flags = null, int $errno = 0)
    {
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $url = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
        $errno = $errno ?: curl_errno($curl);
        $error = $errno ? sprintf("%d %s (url: %s)", $errno, curl_error($curl) ?: curl_strerror($errno), $url) : "";

        if($data === false)
            throw new Exception("Curl failed: $error");

        // Raw response (proxying); a request that never completed still gets a normal error response
        if(isset(self::$rawResponseHeaders[$curl]) && $statusCode > 0)
            $response = new RawResponse((string) $data, $statusCode, self::$rawResponseHeaders[$curl]);
        else
            $response = self::getResponseObject((string) $data, $statusCode, $error);

        if($flags & GET_REDIRECT_URI)
            $response->setParameter("redirectURI", $url);

        return $response;
    }

    /**
     * getResponseObject
     */
    private static function getResponseObject(string $response, int $statusCode, string $errorDescription) : Response
    {
        // No http status means the request never completed, status 0 is not a valid Response status
        if($statusCode === 0)
            return new Response(["error" => "httpRequestFailed", "errorDescription" => $errorDescription ?: "The request failed with http code 0"], 500);

        if($errorDescription)
            return new Response(["error" => $errorDescription], $statusCode);

        if(is_json($response))
        {
            $decoded = json_decode_preserve_empty_objects($response); // assoc 'true' would turn '{}' into '[]'

            // A top level '{}' stays an object, a scalar ("text", 123) can't be parameters
            if(!is_array($decoded))
                $decoded = is_object($decoded) ? [] : ["response" => $response];

            return new Response($decoded, $statusCode);
        }

        return new Response(strlen($response) > 0 ? ["response" => $response] : [], $statusCode);
    }

    /**
     * prependBaseURL
     */
    private function prependBaseURL(string $url)
    {
        // An absolute url (with scheme) is never prefixed, e.g. a call to another server
        if(is_string($this->baseURL) && strlen($this->baseURL) && !str_starts_with($url, $this->baseURL) && !preg_match("#^[a-z][a-z0-9+.\-]*://#i", $url))
            $url = rtrim($this->baseURL, "/") . "/" . ltrim($url, "/");

        return $url;
    }

    /**
     * addErrorURI
     *  Nested requests keep a list of the urls that failed
     */
    private function addErrorURI(Response $response, string $uri) : void
    {
        $errorURI = $response->getParameter("errorURI");

        if(is_string($errorURI))
            $uri = [$errorURI, $uri];
        else if(is_array($errorURI))
            $uri = [...$errorURI, $uri];

        $response->setParameter("errorURI", $uri);
    }

    /**
     * call / get / post / put / patch / delete
     */
    protected function call(array $args = array(), $flags = null)
    {
        $this->init(array_merge($args, [
            "flags" => $flags ?? ($args["flags"] ?? null)
        ]));

        return $this->sendRequest();
    }

    protected function get(array $args = array(), $flags = null)
    {
        $args["type"] = RequestMethod::GET;
        return $this->call($args, $flags);
    }

    protected function post(array $args = array(), $flags = null)
    {
        $args["type"] = RequestMethod::POST;
        return $this->call($args, $flags);
    }

    protected function put(array $args = array(), $flags = null)
    {
        $args["type"] = RequestMethod::PUT;
        return $this->call($args, $flags);
    }

    protected function patch(array $args = array(), $flags = null)
    {
        $args["type"] = RequestMethod::PATCH;
        return $this->call($args, $flags);
    }

    protected function delete(array $args = array(), $flags = null)
    {
        $args["type"] = RequestMethod::DELETE;
        return $this->call($args, $flags);
    }

    /**
     * __call
     */
    public function __call($name, $arguments)
    {
        if(in_array($name, self::MAGIC_METHODS, true))
            return $this->$name(...$arguments);

        throw new Exception("Call to undefined method $name");
    }

    /**
     * __callStatic
     */
    public static function __callStatic($name, $arguments)
    {
        if(in_array($name, self::MAGIC_METHODS, true))
            return (new HTTPRequest())->$name(...$arguments);

        throw new Exception("Call to undefined method $name");
    }
}
