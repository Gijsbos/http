<?php
declare(strict_types=1);

namespace gijsbos\Http;

/**
 * RawResponse
 *  A response passed on exactly as it was received: the raw body and the response headers (hop-by-hop headers removed).
 *  Returned by HTTPRequest with flag RAW_RESPONSE, e.g. for proxying file downloads or non-JSON content.
 *
 *  Parameters hold the decoded body when it is JSON, so isSuccessful(), getError() and getParameter() keep working,
 *  but send() always outputs the original body.
 */
class RawResponse extends Response
{
    private string $body;

    /**
     * __construct
     */
    public function __construct(string $body = "", int $statusCode = 200, array $headers = [])
    {
        $decoded = is_json($body) ? json_decode($body, true) : null;

        parent::__construct(is_array($decoded) ? $decoded : [], $statusCode, $headers);

        $this->body = $body;
    }

    /**
     * getBody
     */
    public function getBody() : string
    {
        return $this->body;
    }

    /**
     * getHttpHeader
     *  Case insensitive, services send e.g. "Content-type" or "content-type"
     */
    public function getHttpHeader($name, $default = null)
    {
        foreach($this->getHttpHeaders() as $key => $value)
            if(strcasecmp((string) $key, (string) $name) === 0)
                return $value;

        return $default;
    }

    /**
     * getResponseBody
     *  Always the original body, the format is ignored
     */
    public function getResponseBody($format = 'json')
    {
        return $this->body;
    }

    /**
     * send
     *  Sends status, headers and body as received, without adding a Content-Type
     */
    public function send($format = null)
    {
        if(headers_sent())
            return;

        header(sprintf('HTTP/%s %s %s', $this->version, $this->statusCode, $this->statusText));

        foreach($this->getHttpHeaders() as $name => $value)
            header(sprintf('%s: %s', $name, $value));

        echo $this->body;
    }
}
