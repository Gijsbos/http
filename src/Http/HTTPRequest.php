<?php
declare(strict_types=1);

namespace gijsbos\Http\Http;

use ErrorException;
use Exception;
use gijsbos\Http\RequestMethod;
use gijsbos\Http\Response;
use InvalidArgumentException;
use TypeError;

use function gijsbos\Logging\Library\log_error;

define("FOLLOW_LOCATION",       flag_id('gijsbos\Http\Http'));
define("GET_REDIRECT_URI",      flag_id('gijsbos\Http\Http'));
define("CURL_HANDLE",           flag_id('gijsbos\Http\Http'));
define("HTTP_REQUEST_HANDLE",   flag_id('gijsbos\Http\Http'));
define("HTTP_REQUEST_DEBUG",    flag_id('gijsbos\Http\Http'));

/**
 * HTTPRequest
 * 
 *  Creates a curl request and turns the result into an object of type Response or CurlHandle.
 * 
 *  ASYNC HTTPRequests:
 * 
 *  1. Create a HTTPRequestPool
 *  2. Add HTTPRequest using flag CURL_HANDLE
 *  3. Execute pool
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
     *  Set default url to prepend
     */
    public static $DEFAULT_URL = "";

    /**
     * @var string $DEFAULT_URL
     *  Set default url to prepend
     */
    public static $DEFAULT_TIMEOUT = 10;

    /**
     * @var string $BASE_URL
     *  Base url prepended to HTTP requests
     */
    public static $BASE_URLS = [];

    /**
     * @var int DEFAULT_FLAGS
     *  Default flags called on execution
     */
    public static $DEFAULT_FLAGS = null;

    /**
     * @var array DEFAULT_HEADERS
     */
    public static $DEFAULT_HEADERS = null;

    /**
     * @var array DEFAULT_OPTIONS
     */
    public static $DEFAULT_OPTIONS = [];

    /**
     * @var int START_TIME
     */
    public static $START_TIME = null;

    /**
     * @var int CALL_COUNT
     */
    public static $CALL_COUNT = 0;

    /**
     * @var string type
     */
    private $type;

    /**
     * @var string uri
     */
    private $uri;

    /**
     * @var array data
     */
    private $data;

    /**
     * @var array headers
     */
    private $headers;

    /**
     * @var int flags
     * Flags called on execution
     */
    private $flags;
    
    /**
     * @var array cURLOptions
     */
    private $cURLOptions;

    /**
     * @var string baseURL
     */
    private $baseURL;

    /* Options */
    private $curlProxy;

    /**
     * __construct
     */
    public function __construct(null|string|int $type = null, null|string $uri = null, array $data = [], array $headers = [], null|int $flags = null, null|array $cURLOptions = null, null|string $baseURL = null)
    {
        $args = get_defined_vars();

        // Set default values
        $this->type = null;
        $this->uri = "";
        $this->data = [];
        $this->headers = [];
        $this->flags = null;
        $this->cURLOptions = [];
        $this->baseURL = "";

        // Options
        $this->curlProxy = "";

        // Init from args
        $this->init($args);
    }

    /**
     * setDefaultURL
     */
    public static function setDefaultURL(string $url) : void
    {
        self::$DEFAULT_URL = $url;
    }

    /**
     * addBaseURL
     *  Adds a base url allowing calls as follows:
     * 
     *  Define base url:
     *      HTTPRequest::addBaseURL("myServer", "http://myserver..");
     * 
     *  Call using base url
     *      HTTPRequest::myServer->get(...)
     */
    public static function addBaseURL(string $key, string $url) : void
    {
        self::$BASE_URLS[$key] = url_strip_slashes($url, false, true);
    }

    /**
     * hasCURLProxy
     */
    public function hasCURLProxy() : bool
    {
        return strlen($this->curlProxy) > 0;
    }

    /**
     * getCURLProxy
     */
    public function getCURLProxy()
    {
        return $this->curlProxy;
    }

    /**
     * setCURLProxy
     */
    public function setCURLProxy(string $address)
    {
        $this->curlProxy = $address;
        $this->cURLOptions[CURLOPT_PROXY] = &$this->curlProxy;
    }
    
    /**
     * parseRequestType
     */
    private function parseRequestType(array $args)
    {
        if(array_key_exists("type", $args))
            return RequestMethod::convertToConstant($args["type"]);
        else if(array_key_exists("method", $args))
            return RequestMethod::convertToConstant($args["method"]);
        else
            return $this->type;
    }

    /**
     * parseRequestURI
     */
    private function parseRequestURI(array $args)
    {
        if(array_key_exists("uri", $args))
            return $args["uri"];
        else if(array_key_exists("url", $args))
            return $args["url"];
        else
            return $this->uri;
    }

    /**
     * parseData
     */
    private function parseData(array $args)
    {
        if(array_key_exists("data", $args))
            return $args["data"];
        else
            return $this->data;
    }

    /**
     * parseHeaders
     */
    private function parseHeaders(array $args)
    {
        $headers = is_array($this->headers) ? $this->headers : [];

        if(array_key_exists("headers", $args) && is_array($args["headers"]))
        {
            $headers = array_merge($headers, $args["headers"]);
        }

        if(is_array(self::$DEFAULT_HEADERS))
        {
            $headers = array_merge($headers, self::$DEFAULT_HEADERS);
        }
        
        return $headers;
    }

    /**
     * parseFlags
     */
    private function parseFlags(array $args)
    {
        $flags = array_key_exists("flags", $args) && $args["flags"] !== null ? $args["flags"] : $this->flags;

        // Get default flags
        $defaultFlags = self::$DEFAULT_FLAGS;

        // Determine flags
        if($flags !== null && $defaultFlags == null)
            return $flags;
        else if($flags !== null && $defaultFlags !== null)
            return $flags | $defaultFlags;
        else if($flags === null && $defaultFlags !== null)
            return $defaultFlags;
        else
            return null;
    }

    /**
     * parseCURLOptions
     */
    private function parseCURLOptions(array $args) : array
    {
        $defaultOptions = self::$DEFAULT_OPTIONS;

        // Merge default with object options
        $cURLOptions = $defaultOptions + (is_array($this->cURLOptions) ? $this->cURLOptions : []);

        // Extract from args
        if(array_key_exists("cURLOptions", $args))
        {
            $options = $args["cURLOptions"];

            if(!is_array($options))
                throw new TypeError("Invalid cURL option type using argument type " . get_type($options));

            $cURLOptions = $cURLOptions + $options;
        }
        else if(array_key_exists("options", $args))
        {
            $options = $args["options"];

            if(!is_array($options))
                throw new TypeError("Invalid cURL option type using argument type " . get_type($options));

            $cURLOptions = $cURLOptions + $options;
        }
        
        return $cURLOptions;
    }

    /**
     * parseBaseURL
     */
    private function parseBaseURL(array $args)
    {
        if(array_key_exists("baseURL", $args))
            return $args["baseURL"];
        else if(is_string($this->baseURL) && strlen($this->baseURL))
            return $this->baseURL;
        else
            return self::$DEFAULT_URL;
    }

    /**
     * init
     */
    public function init(array $args)
    {
        $args = array_filter($args, function($v) { return $v !== null; });

        // Get type
        $this->type = $this->parseRequestType($args);

        // Set request method
        $this->uri = $this->parseRequestURI($args);

        // Set data
        $this->data = $this->parseData($args);

        // Set headers
        $this->headers = $this->parseHeaders($args);

        // Get flags
        $this->flags = $this->parseFlags($args);

        // Get data, headers, options
        $this->cURLOptions = $this->parseCURLOptions($args);

        // Set base URL
        $this->baseURL = $this->parseBaseURL($args);
    }

    /**
     * setType
     */
    public function setType(string|int $type)
    {
        $this->type = $type;
    }

    /**
     * getType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * setURI
     */
    public function setURI(string $uri)
    {
        $this->uri = $uri;
    }

    /**
     * getURI
     */
    public function getURI()
    {
        return $this->uri;
    }

    /**
     * setData
     */
    public function addData(string $key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * mergeData
     */
    public function mergeData(array $data)
    {
        $this->data = array_merge($this->data, $data);
    }

    /**
     * setData
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * getData
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * setHeaders
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    /**
     * getHeaders
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * addFlag
     */
    public function addFlag(int $flag)
    {
        $this->flags = $this->flags | $flag;
    }

    /**
     * setFlags
     */
    public function setFlags($flags)
    {
        $this->flags = $flags;
    }

    /**
     * getFlags
     */
    public function getFlags()
    {
        return $this->flags;
    }

    /**
     * setCURLOptions
     */
    public function setCURLOptions(array $options)
    {
        $this->cURLOptions = $options;
    }

    /**
     * getCURLOptions
     */
    public function getCURLOptions()
    {
        return $this->cURLOptions;
    }

    /**
     * getBaseURL
     */
    public function getBaseURL() : string
    {
        return $this->baseURL;
    }

    /**
     * setBaseURL
     */
    public function setBaseURL(string $url) : void
    {
        $this->baseURL = $url;
    }

    /**
     * removeHeader
     */
    public function removeHeader(string $key)
    {
        if(in_array($key, $this->headers))
            unset($this->headers[$key]);
    }

    /**
     * addHeader
     */
    public function addHeader(string $key, string $value) : void
    {
        // Check if header has already been added
        $this->removeHeader($key);

        // Add header
        $this->headers[$key] = $value;
    }

    /**
     * addHeaders
     */
    public function addHeaders(array $headers) : void
    {
        foreach($headers as $key => $value)
            $this->addHeader($key, $value);
    }

    /**
     * getResponseObject
     */
    private static function getResponseObject($response, $statusCode, $errorDescription) : Response
    {
        if($errorDescription)
            return new Response(array("error" => $errorDescription), $statusCode);
        else if(is_json($response))
            return new Response(json_decode($response, true), $statusCode);
        else
        {
            if(strlen($response) > 0)
                return new Response(array("response" => $response), 400);
            else
            {
                if($statusCode === 0)
                    return new Response(["error" => "httpRequestFailed", "errorDescription" => "The request failed with http code 0"], 500);
                else
                    return new Response([], $statusCode);
            }
        }
    }

    /**
     * handleCurlResult
     */
    public static function handleCurlResult($curl, $data, null|int $flags = null)
    {
        // Get response
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        $errno = curl_errno($curl);
        $url = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
        
        // No success
        if($data === false)
        {
            // Get errno information
            $errno = self::getErrnoByCode($errno);

            // Create error message
            $message = sprintf("%d %s %s (%d requests) (after %d s) (url: %s)", $errno["code"], $errno["short"], $errno["long"], self::$CALL_COUNT, time() - self::$START_TIME, $url);

            // Failed
            throw new Exception("Curl failed: $message");
        }
        
        // Get response
        $response = self::getResponseObject($data, $statusCode, $error);

        // Check flags
        if($flags & GET_REDIRECT_URI)
            $response->setParameter("redirectURI", curl_getinfo($curl, CURLINFO_EFFECTIVE_URL));

        // Debug
        if($statusCode === "0" || $statusCode === 0 || $flags & HTTP_REQUEST_DEBUG)
        {
            log_error(__METHOD__ . " failed: " . json_encode(curl_getinfo($curl), JSON_PRETTY_PRINT));

            $response->setParameter("errorURI", $url);
        }

        // Close curl
        curl_close($curl);

        // return response
        return $response;
    }

    /**
     * convertToCURLHeaders
     *  Turns assoc headers into sequential headers
     */
    public static function convertToCURLHeaders(array $headers) : array
    {
        $parsed = [];

        foreach($headers as $key => $value)
        {
            if(is_int($key))
                array_push($parsed, $value);
            else
                array_push($parsed, sprintf("%s: %s", str_replace("_", "-", $key), $value));
        }

        return $parsed;
    }

    /**
     * callGet
     * 
     * @param string $url - Curl URL
     * @param array $headers - Request headers
     * @param int $flags - OPT Flags: FOLLOW_LOCATION, GET_REDIRECT_URI
     * @param string $customMethod - Custom request method using GET logic
     */
    public function callGet(string $url, array $headers, array $options, null|int $flags = null, $customMethod = null)
    {
        // Convert headers
        $headers = $this->convertToCURLHeaders($headers);

        // Convert
        $customMethod = RequestMethod::convertToString($customMethod);
        
        // Init curl
        $curl = curl_init();

        // Check if fails
        if($curl === false)
            throw new Exception("Curl init failed");

        // Set options
        $curlOptions = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ACCEPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => self::$DEFAULT_TIMEOUT,
        );

        // Check options
        if(is_array($options) && count($options) > 0)
            $curlOptions = $curlOptions + $options;

        // Set options
        curl_setopt_array($curl, $curlOptions);

        // Request Method set => Not GET? => Set Custom
        if($customMethod !== null && $customMethod != GET)
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $customMethod);

        // FOLLOW_LOCATION => Set CURLOPT_FOLLOWLOCATION TRUE
        if($flags & FOLLOW_LOCATION)
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);

        // Return handle
        if($flags & CURL_HANDLE)
        {
            return $curl;
        }
        else if($flags & HTTP_REQUEST_HANDLE)
        {
            return new HTTPRequestHandle($curl, null, HTTPRequestHandle::createHash($url, null, $headers));
        }

        // Get result
        $result = curl_exec($curl);

        // Get result
        return self::handleCurlResult($curl, $result, $flags);
    }

    /**
     * callDelete
     */
    public function callDelete(string $url, array $headers, array $options, null|int $flags = null)
    {
        return $this->callGet($url, $headers, $options, $flags, RequestMethod::DELETE);
    }

    /**
     * hasHeader
     *  
     *  Content-Type: multipart/form-data:
     *      If the 'Content-Type: multipart/form-data' header is sent it cannot contain any other characters in the header.
     *      Example: using JavaScript FormData adds '; boundary=----WebKitFormBoundary5GvG5ylC0tWwu8hV' to the header
     *      causing PHP POST input fields ($_POST = empty!) not to parse, therefore the 'Content-Type: multipart/form-data' header must use 'assumeSearchValue' => true.
     * 
     * @param string $header - Search header
     * @param array $headers - Header array
     * @param bool $assumeHeaderValue - Replaces search result value with search query
     */
    public function hasHeader(string $header, array &$headers, bool $assumeSearchValue = false) : bool
    {
        // RegExp for detecting key:value or key header
        $isKeyValue = "/^[\w-]+:\s*.+$/";

        // Key value e.g Content-Type: multiform or key only e.g. Authorization
        $searchArray = preg_match($isKeyValue, $header) ? $headers : array_keys($headers);

        // Get result
        $search = preg_grep("/$header/i", $searchArray);

        // assumeSearchValue overwrites the header if the header is found
        if($assumeSearchValue && count($search))
        {
            $key = key($search);
            $value = reset($search);
        
            if($header != $value)
                $headers[$key] = stripslashes($header);

        }

        // Return bool
        return count($search) > 0;
    }

    /**
     * validatePostData
     */
    private function validatePostData($data) : void
    {
        if(is_array($data))
            if(count(($filter = array_filter($data, 'is_array'))) > 0)
                throw new InvalidArgumentException(sprintf("HTTPRequest CURLOPT_POSTFIELDS contains invalid fields '%s' of type 'array'", implode(",", array_keys($filter))));
    }

    /**
     * callPost
     */
    public function callPost(string $url, $data, array $headers, array $options, null|int $flags = null, $customMethod = RequestMethod::POST)
    {
        $headers = $this->convertToCURLHeaders($headers);

        // Convert
        $customMethod = RequestMethod::convertToString($customMethod);

        // Modify data when headers are set
        if($this->hasHeader("Content-Type: multipart\/form-data", $headers, true))
            $data = $data;
        else if($this->hasHeader("Content-Type: application\/json", $headers))
            $data = json_encode($data);
        else
            $data = http_build_query($data);

        // Validate data
        $this->validatePostData($data);

        // Start curl
        $curl = curl_init();

        // Set options
        $curlOptions = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ACCEPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $customMethod,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => self::$DEFAULT_TIMEOUT,
        );

        // Check options
        if(is_array($options) && count($options) > 0)
            $curlOptions = $curlOptions + $options;

        // Set options
        curl_setopt_array($curl, $curlOptions);

        // Return handle
        if($flags & CURL_HANDLE)
        {
            return $curl;
        }
        else if($flags & HTTP_REQUEST_HANDLE)
        {
            return new HTTPRequestHandle($curl, null, HTTPRequestHandle::createHash($url, $data, $headers));
        }

        // Execute curl
        $result = curl_exec($curl);

        // Return response
        return self::handleCurlResult($curl, $result, $flags);
    }

    /**
     * callPut
     */
    public function callPut(string $url, $data, array $options, array $headers)
    {
        return $this->callPost($url, $data, $headers, $options, RequestMethod::PUT);
    }

    /**
     * createURLWithParams
     */
    public static function createURLWithParams(string &$url, array &$data)
    {
        // Parse url
        $parsed = parse_url($url);
                
        // Get query
        if(@$parsed["query"] !== null)
        {
            parse_str($parsed["query"], $urlData);
            $data = array_merge($data, $urlData);
        }

        // Set in url
        if(count($data) > 0)
        {
            $newURL = [];

            if(is_string(@$parsed["scheme"]) && strlen($parsed["scheme"]))
                $newURL[] = sprintf("%s://", $parsed["scheme"]);

            if(is_string(@$parsed["host"]) && strlen($parsed["host"]))
                $newURL[] = $parsed["host"];

            if(is_int(@$parsed["port"]))
                $newURL[] = sprintf(":%d", $parsed["port"]);

            if(is_string(@$parsed["path"]) && strlen($parsed["path"]))
                $newURL[] = $parsed["path"];

            $newURL[] = sprintf("?%s", http_build_query($data));

            $url = implode("", $newURL);

            $data = [];
        }
    }

    /**
     * prependBaseURL
     */
    private function prependBaseURL(string $url)
    {
        if(is_string($this->baseURL) && strlen($this->baseURL) && !str_starts_with($url, $this->baseURL))
        {
            $baseURL = str_ends_with($this->baseURL, "/") ? substr($this->baseURL, 0, strlen($this->baseURL) - 1) : $this->baseURL;
            $url = str_starts_with($url, "/") ? substr($url, 1) : $url;
            $url = "$baseURL/$url";
        }
        return $url;
    }

    /**
     * addErrorURI
     */
    private function addErrorURI(Response $response, string $uri)
    {
        $errorURI = $response->getParameter("errorURI");

        // String
        if(is_string($errorURI))
        {
            $uri = [$errorURI, $uri];
        }

        // Array
        else if(is_array($errorURI))
        {
            array_push($errorURI, $uri);
            $uri = $errorURI;
        }

        // Set response
        $response->setParameter("errorURI", $uri);

        // Return
        return $response;
    }

    /**
     * sendRequest
     */
    public function sendRequest()
    {
        // Record time for debugging
        if(self::$START_TIME == null)
            self::$START_TIME = time();

        // Increment call count
        self::$CALL_COUNT += 1;

        // Set vars
        $type = $this->type;
        $uri = $this->uri;
        $data = $this->data;
        $headers = $this->headers;
        $flags = $this->flags;
        $cURLOptions = $this->cURLOptions;

        // Check type
        if($type === null)
            throw new Exception(sprintf("%s failed, type missing", __METHOD__));

        // Check url
        if($uri === null)
            throw new Exception(sprintf("%s failed, url missing", __METHOD__));

        // Prepend url prefix
        $uri = $this->prependBaseURL($uri);

        // Perform the curl
        switch($this->type)
        {
            case RequestMethod::GET:
                self::createURLWithParams($uri, $data);
                $response = $this->callGet($uri, $headers, $cURLOptions, $flags);
            break;
            case RequestMethod::POST:
                $response = $this->callPost($uri, $data, $headers, $cURLOptions, $flags);
            break;
            case RequestMethod::PUT:
                $response = $this->callPost($uri, $data, $headers, $cURLOptions, $flags, RequestMethod::PUT);
            break;
            case RequestMethod::PATCH:
                $response = $this->callPost($uri, $data, $headers, $cURLOptions, $flags, RequestMethod::PATCH);
            break;
            case RequestMethod::DELETE:
                self::createURLWithParams($uri, $data);
                $response = $this->callGet($uri, $headers, $cURLOptions, $flags, RequestMethod::DELETE);
            break;
            default:
                throw new Exception(__METHOD__ . ": Request method '$type' not supported");
        }

        // Return handle
        if($flags & CURL_HANDLE || $flags & HTTP_REQUEST_HANDLE)
            return $response;
        
        // Add url when response is not successful
        if(!$response->isSuccessful())
            $response = $this->addErrorURI($response, $uri);

        // Return response
        return $response;
    }

    /**
     * call
     */
    protected function call(array $args = array(), $flags = null)
    {
        $this->init(array_merge($args, [
            "flags" => $flags !== null ? $flags : @$args["flags"]
        ]));

        // Send request
        return $this->sendRequest();
    }

    /**
     * get
     */
    protected function get(array $args = array(), $flags = null)
    {
        $args["type"] = RequestMethod::GET;
        return $this->call($args, $flags);
    }

    /**
     * post
     */
    protected function post(array $args = array(), $flags = null)
    {
        $args["type"] = RequestMethod::POST;
        return $this->call($args, $flags);
    }

    /**
     * put
     */
    protected function put(array $args = array(), $flags = null)
    {
        $args["type"] = RequestMethod::PUT;
        return $this->call($args, $flags);
    }

    /**
     * patch
     */
    protected function patch(array $args = array(), $flags = null)
    {
        $args["type"] = RequestMethod::PATCH;
        return $this->call($args, $flags);
    }

    /**
     * delete
     */
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
        // Allow object methods to be called as static
        if(method_exists(self::class, $name))
            return $this->$name(...$arguments);

        throw new Exception("Call to undefined method $name");
    }

    /**
     * __callStatic
     */
    public static function __callStatic($name, $arguments)
    {
        // Allow object methods to be called as static
        if(method_exists(self::class, $name))
            return (new HTTPRequest())->$name(...$arguments);
        
        // Search for base url in BASE_URLS
        if(!array_key_exists($name, self::$BASE_URLS))
            throw new Exception("Could not resolve base url '$name'");

        // Create request
        $httpRequest = new HTTPRequest(...$arguments);

        // Set url
        $httpRequest->setBaseURL(self::$BASE_URLS[$name]);

        // Return object
        return $httpRequest;
    }

    /**
     * createFromArgs
     */
    public static function createFromArgs(array $args) : HTTPRequest
    {
        return new HTTPRequest(
            @$args["type"],
            @$args["uri"],
            is_array(@$args["data"]) ? $args["data"] : [],
            is_array(@$args["headers"]) ? $args["headers"] : [],
            @$args["flags"],
            @$args["cURLOptions"],
            @$args["baseURL"],
        );
    }

    /**
     * getErrnoInfo
     */
    public static function getErrnoInfo() : array
    {
        $data = <<<JSON
                [
                    {
                        "code": 0,
                        "short": "OK",
                        "long": "All fine. Proceed as usual."
                    },
                    {
                        "code": 1,
                        "short": "UNSUPPORTED_PROTOCOL",
                        "long": "The URL you passed to libcurl used a protocol that this \u201ccurl.exe\u201d does not support. it can be a misspelled protocol string or just a protocol that curl has no code for."
                    },
                    {
                        "code": 2,
                        "short": "FAILED_INIT",
                        "long": "Very early initialization code failed. This is likely to be an internal error or problem, or a resource problem where something fundamental couldn't get done at init time."
                    },
                    {
                        "code": 3,
                        "short": "URL_MALFORMAT",
                        "long": "The URL was not properly formatted."
                    },
                    {
                        "code": 4,
                        "short": "NOT_BUILT_IN",
                        "long": "A requested feature, protocol or option was not found built-in in this curl.exe. This means that a feature or option was not enabled or explicitly disabled when curl.exe was built and in order to get it to function you have to get a download another \u201ccurl.exe\u201d executable."
                    },
                    {
                        "code": 5,
                        "short": "COULDNT_RESOLVE_PROXY",
                        "long": "Couldn't resolve proxy. The given proxy host could not be resolved."
                    },
                    {
                        "code": 6,
                        "short": "COULDNT_RESOLVE_HOST",
                        "long": "Couldn't resolve host. The given remote host was not resolved."
                    },
                    {
                        "code": 7,
                        "short": "COULDNT_CONNECT",
                        "long": "Failed to connect() to host or proxy."
                    },
                    {
                        "code": 8,
                        "short": "FTP_WEIRD_SERVER_REPLY",
                        "long": "The server sent data libcurl couldn't parse. This error code is used for more than just FTP and is aliased as CURLE_WEIRD_SERVER_REPLY since 7.51.0."
                    },
                    {
                        "code": 9,
                        "short": "REMOTE_ACCESS_DENIED",
                        "long": "We were denied access to the resource given in the URL. For FTP, this occurs while trying to change to the remote directory."
                    },
                    {
                        "code": 10,
                        "short": "FTP_ACCEPT_FAILED",
                        "long": "While waiting for the server to connect back when an active FTP session is used, an error code was sent over the control connection or similar."
                    },
                    {
                        "code": 11,
                        "short": "FTP_WEIRD_PASS_REPLY",
                        "long": "After having sent the FTP password to the server, libcurl expects a proper reply. This error code indicates that an unexpected code was returned."
                    },
                    {
                        "code": 12,
                        "short": "FTP_ACCEPT_TIMEOUT",
                        "long": "During an active FTP session while waiting for the server to connect, the CURLOPT_ACCEPTTIMEOUT_MS (or the internal default) timeout expired."
                    },
                    {
                        "code": 13,
                        "short": "FTP_WEIRD_PASV_REPLY",
                        "long": "libcurl failed to get a sensible result back from the server as a response to either a PASV or a EPSV command. The server is flawed."
                    },
                    {
                        "code": 14,
                        "short": "FTP_WEIRD_227_FORMAT",
                        "long": "FTP servers return a 227-line as a response to a PASV command. If libcurl fails to parse that line, this return code is passed back."
                    },
                    {
                        "code": 15,
                        "short": "FTP_CANT_GET_HOST",
                        "long": "An internal failure to lookup the host used for the new connection."
                    },
                    {
                        "code": 16,
                        "short": "HTTP2",
                        "long": "A problem was detected in the HTTP2 framing layer. This is somewhat generic and can be one out of several problems, see the error buffer for details."
                    },
                    {
                        "code": 17,
                        "short": "FTP_COULDNT_SET_TYPE",
                        "long": "Received an error when trying to set the transfer mode to binary or ASCII."
                    },
                    {
                        "code": 18,
                        "short": "PARTIAL_FILE",
                        "long": "A file transfer was shorter or larger than expected. This happens when the server first reports an expected transfer size, and then delivers data that doesn't match the previously given size."
                    },
                    {
                        "code": 19,
                        "short": "FTP_COULDNT_RETR_FILE",
                        "long": "This was either a weird reply to a 'RETR' command or a zero byte transfer complete."
                    },
                    {
                        "code": 21,
                        "short": "QUOTE_ERROR",
                        "long": "When sending custom \"QUOTE\" commands to the remote server, one of the commands returned an error code that was 400 or higher (for FTP) or otherwise indicated unsuccessful completion of the command."
                    },
                    {
                        "code": 22,
                        "short": "HTTP_RETURNED_ERROR",
                        "long": "This is returned if CURLOPT_FAILONERROR is set TRUE and the HTTP server returns an error code that is >= 400."
                    },
                    {
                        "code": 23,
                        "short": "WRITE_ERROR",
                        "long": "An error occurred when writing received data to a local file, or an error was returned to libcurl from a write callback."
                    },
                    {
                        "code": 25,
                        "short": "UPLOAD_FAILED",
                        "long": "Failed starting the upload. For FTP, the server typically denied the STOR command. The error buffer usually contains the server's explanation for this."
                    },
                    {
                        "code": 26,
                        "short": "READ_ERROR",
                        "long": "There was a problem reading a local file or an error returned by the read callback."
                    },
                    {
                        "code": 27,
                        "short": "OUT_OF_MEMORY",
                        "long": "A memory allocation request failed. This is serious badness and things are severely screwed up if this ever occurs."
                    },
                    {
                        "code": 28,
                        "short": "OPERATION_TIMEDOUT",
                        "long": "Operation timeout. The specified time-out period was reached according to the conditions."
                    },
                    {
                        "code": 30,
                        "short": "FTP_PORT_FAILED",
                        "long": "The FTP PORT command returned error. This mostly happens when you haven't specified a good enough address for libcurl to use. See CURLOPT_FTPPORT."
                    },
                    {
                        "code": 31,
                        "short": "FTP_COULDNT_USE_REST",
                        "long": "The FTP REST command returned error. This should never happen if the server is sane."
                    },
                    {
                        "code": 33,
                        "short": "RANGE_ERROR",
                        "long": "The server does not support or accept range requests."
                    },
                    {
                        "code": 34,
                        "short": "HTTP_POST_ERROR",
                        "long": "This is an odd error that mainly occurs due to internal confusion."
                    },
                    {
                        "code": 35,
                        "short": "SSL_CONNECT_ERROR",
                        "long": "A problem occurred somewhere in the SSL\/TLS handshake. You really want the error buffer and read the message there as it pinpoints the problem slightly more. Could be certificates (file formats, paths, permissions), passwords, and others."
                    },
                    {
                        "code": 36,
                        "short": "BAD_DOWNLOAD_RESUME",
                        "long": "The download could not be resumed because the specified offset was out of the file boundary."
                    },
                    {
                        "code": 37,
                        "short": "FILE_COULDNT_READ_FILE",
                        "long": "A file given with FILE:\/\/ couldn't be opened. Most likely because the file path doesn't identify an existing file. Did you check file permissions?"
                    },
                    {
                        "code": 38,
                        "short": "LDAP_CANNOT_BIND",
                        "long": "LDAP cannot bind. LDAP bind operation failed."
                    },
                    {
                        "code": 39,
                        "short": "LDAP_SEARCH_FAILED",
                        "long": "LDAP search failed."
                    },
                    {
                        "code": 41,
                        "short": "FUNCTION_NOT_FOUND",
                        "long": "Function not found. A required zlib function was not found."
                    },
                    {
                        "code": 42,
                        "short": "ABORTED_BY_CALLBACK",
                        "long": "Aborted by callback. A callback returned \"abort\" to libcurl."
                    },
                    {
                        "code": 43,
                        "short": "BAD_FUNCTION_ARGUMENT",
                        "long": "Internal error. A function was called with a bad parameter."
                    },
                    {
                        "code": 45,
                        "short": "INTERFACE_FAILED",
                        "long": "Interface error. A specified outgoing interface could not be used. Set which interface to use for outgoing connections' source IP address with CURLOPT_INTERFACE."
                    },
                    {
                        "code": 47,
                        "short": "TOO_MANY_REDIRECTS",
                        "long": "Too many redirects. When following redirects, libcurl hit the maximum amount. Set your limit with CURLOPT_MAXREDIRS."
                    },
                    {
                        "code": 48,
                        "short": "UNKNOWN_OPTION",
                        "long": "An option passed to libcurl is not recognized\/known. Refer to the appropriate documentation. This is most likely a problem in the program that uses libcurl. The error buffer might contain more specific information about which exact option it concerns."
                    },
                    {
                        "code": 49,
                        "short": "TELNET_OPTION_SYNTAX",
                        "long": "A telnet option string was Illegally formatted."
                    },
                    {
                        "code": 51,
                        "short": "PEER_FAILED_VERIFICATION",
                        "long": "The remote server's SSL certificate or SSH md5 fingerprint was deemed not OK."
                    },
                    {
                        "code": 52,
                        "short": "GOT_NOTHING",
                        "long": "Nothing was returned from the server, and under the circumstances, getting nothing is considered an error."
                    },
                    {
                        "code": 53,
                        "short": "SSL_ENGINE_NOTFOUND",
                        "long": "The specified crypto engine wasn't found."
                    },
                    {
                        "code": 54,
                        "short": "SSL_ENGINE_SETFAILED",
                        "long": "Failed setting the selected SSL crypto engine as default!"
                    },
                    {
                        "code": 55,
                        "short": "SEND_ERROR",
                        "long": "Failed sending network data."
                    },
                    {
                        "code": 56,
                        "short": "RECV_ERROR",
                        "long": "Failure with receiving network data."
                    },
                    {
                        "code": 58,
                        "short": "SSL_CERTPROBLEM",
                        "long": "problem with the local client certificate."
                    },
                    {
                        "code": 59,
                        "short": "SSL_CIPHER",
                        "long": "Couldn't use specified cipher."
                    },
                    {
                        "code": 60,
                        "short": "SSL_CACERT",
                        "long": "Peer certificate cannot be authenticated with known CA certificates."
                    },
                    {
                        "code": 61,
                        "short": "BAD_CONTENT_ENCODING",
                        "long": "Unrecognized transfer encoding."
                    },
                    {
                        "code": 62,
                        "short": "LDAP_INVALID_URL",
                        "long": "Invalid LDAP URL."
                    },
                    {
                        "code": 63,
                        "short": "FILESIZE_EXCEEDED",
                        "long": "Maximum file size exceeded."
                    },
                    {
                        "code": 64,
                        "short": "USE_SSL_FAILED",
                        "long": "Requested FTP SSL level failed."
                    },
                    {
                        "code": 65,
                        "short": "SEND_FAIL_REWIND",
                        "long": "When doing a send operation curl had to rewind the data to retransmit, but the rewinding operation failed."
                    },
                    {
                        "code": 66,
                        "short": "SSL_ENGINE_INITFAILED",
                        "long": "Initiating the SSL Engine failed."
                    },
                    {
                        "code": 67,
                        "short": "LOGIN_DENIED",
                        "long": "The remote server denied curl to login (Added in 7.13.1)"
                    },
                    {
                        "code": 68,
                        "short": "TFTP_NOTFOUND",
                        "long": "File not found on TFTP server."
                    },
                    {
                        "code": 69,
                        "short": "TFTP_PERM",
                        "long": "Permission problem on TFTP server."
                    },
                    {
                        "code": 70,
                        "short": "REMOTE_DISK_FULL",
                        "long": "Out of disk space on the server."
                    },
                    {
                        "code": 71,
                        "short": "TFTP_ILLEGAL",
                        "long": "Illegal TFTP operation."
                    },
                    {
                        "code": 72,
                        "short": "TFTP_UNKNOWNID",
                        "long": "Unknown TFTP transfer ID."
                    },
                    {
                        "code": 73,
                        "short": "REMOTE_FILE_EXISTS",
                        "long": "File already exists and will not be overwritten."
                    },
                    {
                        "code": 74,
                        "short": "TFTP_NOSUCHUSER",
                        "long": "This error should never be returned by a properly functioning TFTP server."
                    },
                    {
                        "code": 75,
                        "short": "CONV_FAILED",
                        "long": "Character conversion failed."
                    },
                    {
                        "code": 76,
                        "short": "CONV_REQD",
                        "long": "Caller must register conversion callbacks."
                    },
                    {
                        "code": 77,
                        "short": "SSL_CACERT_BADFILE",
                        "long": "Problem with reading the SSL CA cert (path? access rights?)"
                    },
                    {
                        "code": 78,
                        "short": "REMOTE_FILE_NOT_FOUND",
                        "long": "The resource referenced in the URL does not exist."
                    },
                    {
                        "code": 79,
                        "short": "SSH",
                        "long": "An unspecified error occurred during the SSH session."
                    },
                    {
                        "code": 80,
                        "short": "SSL_SHUTDOWN_FAILED",
                        "long": "Failed to shut down the SSL connection."
                    },
                    {
                        "code": 81,
                        "short": "AGAIN",
                        "long": "Socket is not ready for send\/recv wait till it's ready and try again. This return code is only returned from curl_easy_recv and curl_easy_send (Added in 7.18.2)"
                    },
                    {
                        "code": 82,
                        "short": "SSL_CRL_BADFILE",
                        "long": "Failed to load CRL file (Added in 7.19.0)"
                    },
                    {
                        "code": 83,
                        "short": "SSL_ISSUER_ERROR",
                        "long": "Issuer check failed (Added in 7.19.0)"
                    },
                    {
                        "code": 84,
                        "short": "FTP_PRET_FAILED",
                        "long": "The FTP server does not understand the PRET command at all or does not support the given argument. Be careful when using CURLOPT_CUSTOMREQUEST, a custom LIST command will be sent with PRET CMD before PASV as well. (Added in 7.20.0)"
                    },
                    {
                        "code": 85,
                        "short": "RTSP_CSEQ_ERROR",
                        "long": "Mismatch of RTSP CSeq numbers."
                    },
                    {
                        "code": 86,
                        "short": "RTSP_SESSION_ERROR",
                        "long": "Mismatch of RTSP Session Identifiers."
                    },
                    {
                        "code": 87,
                        "short": "FTP_BAD_FILE_LIST",
                        "long": "Unable to parse FTP file list (during FTP wildcard downloading)."
                    },
                    {
                        "code": 88,
                        "short": "CHUNK_FAILED",
                        "long": "Chunk callback reported error."
                    },
                    {
                        "code": 89,
                        "short": "NO_CONNECTION_AVAILABLE",
                        "long": "(For internal use only, will never be returned by libcurl) No connection available, the session will be queued. (added in 7.30.0)"
                    },
                    {
                        "code": 90,
                        "short": "SSL_PINNEDPUBKEYNOTMATCH",
                        "long": "Failed to match the pinned key specified with CURLOPT_PINNEDPUBLICKEY."
                    },
                    {
                        "code": 91,
                        "short": "SSL_INVALIDCERTSTATUS",
                        "long": "Status returned failure when asked with CURLOPT_SSL_VERIFYSTATUS."
                    }
                ]
                JSON;

        return json_decode($data, true);
    }

    /**
     * getErrnoByCode
     */
    public static function getErrnoByCode(int $code)
    {
        foreach(self::getErrnoInfo() as $errno)
            if($errno["code"] == $code)
                return $errno;

        return false;
    }
}

/**
 * http_request
 */
function http_request(string $method, string $url, array $data = [], array $headers = [], null|int $flags = null, bool $verbose = false, bool $debug = false)
{
    $method = strtoupper($method);

    // Init curl
    $curl = curl_init();

    // Check if fails
    if($curl === false)
        throw new Exception("Curl init failed");

    // Has header
    $hasHeader = function($key, $value, $headers) {
        return @$headers[$key] == strtolower($value);
    };

    // Process data
    if(count($data))
    {
        if($method === "POST" || $method === "PUT")
        {
            if($hasHeader("Content-Type", "multipart\/form-data", $headers))
                $data = $data;
            else if($hasHeader("Content-Type", "application/json", $headers))
                $data = json_encode($data);
            else
                $data = http_build_query($data, "", null, PHP_QUERY_RFC3986);
        }
        else if($method === "GET" || "DELETE")
        {
            $url .= "?" . http_build_query($data, "", null, PHP_QUERY_RFC3986);
        }
    }

    // Convert headers
    if(count($headers))
    {
        $_headers = [];
        foreach($headers as $key => $value)
            $_headers[] = "$key: $value";
    }

    // Set options
    $curlOptions = array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ACCEPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_HTTPHEADER => $_headers,
    );

    // Add params
    if($method === "POST" || $method === "PUT")
    {
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    }

    if($verbose)
    {
        curl_setopt($curl, CURLOPT_VERBOSE, 1);
    }

    // Set options
    curl_setopt_array($curl, $curlOptions);

    // Return handle
    if($flags & CURL_HANDLE)
    {
        return $curl;
    }
    else if($flags & HTTP_REQUEST_HANDLE)
    {
        return new HTTPRequestHandle($curl, null, HTTPRequestHandle::createHash($url, null, $headers));
    }

    // Get result
    $result = curl_exec($curl);

    // Handle result
    if($result === false)
    {
        if(!$debug)
            throw new ErrorException("Curl failed");

        // Debug
        $debugData = [
            "error" => curl_error($curl),
            "errno" => curl_errno($curl),
            "curlinfo" => curl_getinfo($curl),
            "private" => curl_getinfo($curl, CURLINFO_PRIVATE),
            "request" => [
                "method" => $method,
                "url" => $url,
                "data" => $data,
                "headers" => $headers,
            ]
        ];

        // Error
        echo(json_encode($debugData, JSON_PRETTY_PRINT));
        exit();
    }
    else
    {
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        // Get result
        $response = is_json($result) ? new Response(json_decode($result, true), $statusCode) : $result;

        // Check response
        if($response instanceof Response)
        {
            if(!$response->isSuccessful())
                $response->setParameter("errorURI", $url);
        }

        // Return response
        return $response;
    }
}