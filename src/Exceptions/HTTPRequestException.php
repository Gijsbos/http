<?php
declare(strict_types=1);

namespace gijsbos\Http\Exceptions;

use Exception;
use InvalidArgumentException;
use SimpleXMLElement;
use gijsbos\Http\Response;
use gijsbos\Http\Utils\ArrayToXml;

/**
 * HTTPRequestException
 */
class HTTPRequestException extends Exception
{
    /**
     * @var bool $useRfc9457
     *  Send exceptions as RFC 9457 problem details (application/problem+json, application/problem+xml):
     *      type        - about:blank, the problem is described by the status code
     *      title       - the reason phrase of the status code
     *      status      - statusCode
     *      detail      - errorDescription
     *      instance    - this occurrence, when given to send()
     *      error       - error, as extension member, for clients matching on it
     *  Data is added as extension members, it never replaces the members above.
     */
    public static bool $useRfc9457 = false;

    private const PROBLEM_MEMBERS = ["type", "title", "status", "detail", "instance", "error"];

    public null|int $statusCode;
    public $error;
    public $errorDescription;
    public $data;

    /**
     * __construct
     */
    public function __construct(null|int $statusCode = null, null|string $error = null, null|string $errorDescription = null, null|array $data = null) 
    {
        $this->statusCode = $statusCode;
        $this->error = $error;
        $this->errorDescription = $errorDescription;
        $this->data = $data !== null ? $data : array();
        parent::__construct($this->toString(), $statusCode ?? 0);
    }

    /**
     * getStatusCode
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * getError
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * getErrorDescription
     */
    public function getErrorDescription()
    {
        return $this->errorDescription;
    }

    /**
     * getData
     */
    public function getData(null|string $key = null)
    {
        return $key !== null ? @$this->data[$key] : $this->data;
    }

    /**
     * dataToString
     */
    private function dataToString()
    {
        // Check data 
        if($this->data !== null && is_array($this->data))
        {
            // Check if data has items
            if(count($this->data) > 0)
            {
                return json_encode($this->data);
            }
        }

        // Return empty
        return null;
    }

    /**
     * toString
     */
    private function toString() : string
    {
        // Set description
        $description = $this->errorDescription !== null ? " {$this->errorDescription}" : "";

        // Create array from data array
        $dataToString = $this->dataToString() !== null ? " (" . $this->dataToString() . ")" : "";

        // Return message
        return "({$this->statusCode}) {$this->error} -$description$dataToString";
    }

    /**
     * toProblemArray
     *  RFC 9457 problem details, members without a value are left out
     */
    private function toProblemArray(null|string $instance) : array
    {
        $status = $this->statusCode ?? 500;

        $problem = array_filter([
            "type" => "about:blank",
            "title" => Response::$statusTexts[$status] ?? null,
            "status" => $status,
            "detail" => $this->errorDescription,
            "instance" => $instance,
            "error" => $this->error,
        ], fn($value) => $value !== null);

        return $problem + array_diff_key($this->data, array_flip(self::PROBLEM_MEMBERS));
    }

    /**
     * toArray
     *  The response body: statusCode, error and errorDescription, followed by the data.
     *  With $useRfc9457 the problem details instead, see $useRfc9457.
     */
    public function toArray(null|string $instance = null) : array
    {
        if(self::$useRfc9457)
            return $this->toProblemArray($instance);

        $responseData = [
            "statusCode" => $this->statusCode,
            "error" => $this->error,
            "errorDescription" => $this->errorDescription,
        ];

        if(count($this->data))
        {
            $responseData = array_merge($responseData, $this->data);
        }

        return $responseData;
    }

    /**
     * send
     *  Sends the exception in the given format: "json" or "xml". The instance identifies this occurrence
     *  (e.g. urn:uuid:<request id>) and is only sent with $useRfc9457.
     */
    public function send(string $format = "json", null|string $instance = null)
    {
        match(strtolower($format)) {
            "json" => $this->sendJson($instance),
            "xml" => $this->sendXml($instance),
            default => throw new InvalidArgumentException("Unsupported format '$format', expected 'json' or 'xml'"),
        };
    }

    /**
     * sendHeaders
     *  With the content type and nosniff: without them PHP answers text/html, and an error description
     *  that echoes input would be rendered by a browser as HTML
     */
    private function sendHeaders(string $contentType)
    {
        if(!headers_sent())
        {
            header("Content-Type: $contentType; charset=utf-8");
            header("X-Content-Type-Options: nosniff");
        }

        http_response_code($this->statusCode ?? 500);
    }

    /**
     * sendJson
     */
    public function sendJson(null|string $instance = null)
    {
        $this->sendHeaders(self::$useRfc9457 ? "application/problem+json" : "application/json");
        print(json_encode($this->toArray($instance)));
    }

    /**
     * sendXml
     *  RFC 9457 problem details go in <problem> in the urn:ietf:rfc:7807 namespace, otherwise in <root>
     */
    public function sendXml(null|string $instance = null)
    {
        $this->sendHeaders(self::$useRfc9457 ? "application/problem+xml" : "application/xml");

        $root = self::$useRfc9457 ? '<problem xmlns="urn:ietf:rfc:7807"/>' : '<root/>';

        print(ArrayToXml::convert($this->toArray($instance), new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>' . $root))->asXML());
    }
}
