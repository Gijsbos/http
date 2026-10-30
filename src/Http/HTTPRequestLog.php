<?php
declare(strict_types=1);

namespace gijsbos\Http\Http;

use gijsbos\Http\Request;

/**
 * HTTPRequestLog
 *  Logs an incoming request to the PHP error log, secrets masked.
 *
 *  Log levels: 1 = method + uri (+ fields), 2 = + data, 3 = + headers
 */
class HTTPRequestLog
{
    private string $requestMethod;
    private string $requestURI;
    private array $data;
    private array $headers;
    private array $fields;

    /**
     * __construct
     */
    public function __construct(string $requestMethod, string $requestURI, array $data = [], array $headers = [])
    {
        $this->requestMethod = $requestMethod;
        $this->requestURI = $requestURI;
        $this->data = $data;
        $this->headers = $headers;
        $this->fields = [];
    }

    /**
     * setFields
     *  Extra key/values added after method and uri
     */
    public function setFields(array $fields) : void
    {
        $this->fields = $fields;
    }

    /**
     * applySecretFilter
     */
    public static function applySecretFilter(array $data)
    {
        foreach($data as $key => $value)
        {
            // "pass" also covers "password"
            if(is_string($key) && preg_match("/pass|secret|token|authorization/i", $key))
                $data[$key] = "********";

            // Nested data, e.g. {"user": {"password": ..}}
            else if(is_array($value))
                $data[$key] = self::applySecretFilter($value);

            else if(is_int($key) && is_string($value))
                $data[$key] = preg_replace(["/Authorization:.*/i", "/AUTHORIZATION=.*/i"], ["Authorization: ********", "AUTHORIZATION= ********"], $value);
        }
        return $data;
    }

    /**
     * logRequest
     */
    public function logRequest(int $logLevel)
    {
        if($logLevel <= 0)
            return;

        $log = ["type" => $this->requestMethod, "uri" => $this->requestURI] + $this->fields;

        if($logLevel >= 2 && count($this->data))
            $log["data"] = self::applySecretFilter($this->data);

        if($logLevel >= 3 && count($this->headers))
            $log["headers"] = self::applySecretFilter($this->headers);

        error_log(implode_key_value_array($log, "=", ", ", "[", "]"));
    }

    /**
     * createFromGlobals
     */
    public static function createFromGlobals()
    {
        $request = Request::createFromGlobals();

        $requestMethod = $request->server["REQUEST_METHOD"];
        $data = $requestMethod == "POST" || $requestMethod == "PUT" ? $request->request : $request->query;

        return new self($requestMethod, $request->server["REQUEST_URI"], is_array($data) ? $data : [], is_array($request->headers) ? $request->headers : []);
    }
}
