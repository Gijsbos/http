<?php
declare(strict_types=1);

namespace gijsbos\Http\Http;

use gijsbos\Http\Request;

/**
 * HTTPRequest
 */
class HTTPRequestLog
{
    private string $requestMethod;
    private string $requestURI;
    private array $data;
    private array $headers;
    private array $fields;
    private string $format;
    private string $function;
    private null|string $prefix;

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
        $this->format = 'default';
        $this->prefix = null;
    }

    /**
     * setPrefix
     */
    public function setPrefix(string $prefix) : void
    {
        $this->prefix = $prefix;
    }

    /**
     * setFields
     */
    public function setFields(array $fields) : void
    {
        $this->fields = $fields;
    }

    /**
     * addField
     */
    public function addField(string $name, string $value) : void
    {
        $this->fields[$name] = $value;
    }

    /**
     * prependField
     */
    public function prependField(string $name, string $value) : void
    {
        $fields = $this->fields;
        $this->fields = array_merge([$name => $value], $fields);
    }

    /**
     * addOrigin
     */
    public function addOrigin(string $origin) : void
    {
        $this->addField('origin', $origin);
    }

    /**
     * addFrom
     */
    public function addFrom(string $from) : void
    {
        $this->addField('from', $from);
    }

    /**
     * addTo
     */
    public function addTo(string $to) : void
    {
        $this->addField('to', $to);
    }

    /**
     * setFormat
     */
    public function setFormat(string $format) : void
    {
        $this->format = $format;
    }

    /**
     * setFunction
     */
    public function setFunction(string $function) : void
    {
        $this->function = $function;
    }

    /**
     * applySecretFilter
     */
    public static function applySecretFilter(array $data)
    {
        foreach($data as $key => $value)
        {
            if(is_string($key))
            {
                if(
                    str_contains(strtolower($key), "password")
                    ||
                    str_contains(strtolower($key), "pass")
                    ||
                    str_contains(strtolower($key), "secret")
                    ||
                    str_contains(strtolower($key), "token")
                    ||
                    str_contains(strtolower($key), "authorization")
                )
                {
                    $data[$key] = "********";
                }
            }
            else
            {
                $data[$key] = preg_replace("/Authorization:.*/i", "Authorization: ********", $value);
                $data[$key] = preg_replace("/AUTHORIZATION=.*/i", "AUTHORIZATION= ********", $value);
            }
        }
        return $data;
    }

    /**
     * writeLog
     */
    private function writeLog(array $log)
    {
        $message = "";

        // Select format
        switch($this->format)
        {
            case 'json':
                $message = json_encode($log);
            break;
            default:
                $message = implode_key_value_array($log, "=", ", ", "[", "]");
        }

        // Add prefix
        if(is_string($this->prefix))
            $message = $this->prefix.$message;

        // Log
        $function = $this->function;

        // Log
        $function($message);
    }

    /**
     * logRequest
     */
    public function logRequest(int $logLevel)
    {
        if($logLevel > 0)
        {
            $log = [];

            // Add method and uri
            $log["type"] = $this->requestMethod;
            $log["uri"] = $this->requestURI;

            // Add fields
            foreach($this->fields as $key => $value)
                $log[$key] = $value;
    
            // Log data
            if($logLevel >= 2)
            {
                if(count($this->data))
                    $log["data"] = $this->applySecretFilter($this->data);
            }

            // Log data
            if($logLevel >= 3)
            {
                if(count($this->headers))
                    $log["headers"] = $this->applySecretFilter($this->headers);
            }
    
            // Log
            if(count($log))
            {
                $this->writeLog($log);
            }
        }
    }

    /**
     * createFromGlobals
     */
    public static function createFromGlobals()
    {
        // Create from globals
        $request = Request::createFromGlobals();

        // Get method
        $requestMethod = $request->server["REQUEST_METHOD"];
        $requestURL = $request->server["REQUEST_URI"];

        // Get data
        $data = $requestMethod == "POST" || $requestMethod == "PUT" ? $request->request : $request->query;

        // Get headers
        $headers = $request->headers;

        // Create object
        return new self($requestMethod, $requestURL, is_array($data) ? $data : [], is_array($headers) ? $headers : []);
    }
}