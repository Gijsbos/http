<?php
declare(strict_types=1);

namespace gijsbos\Http\Exceptions;

/**
 * HTTPRequestSentToHTTPSPortException
 */
class HTTPRequestSentToHTTPSPortException extends HTTPRequestException 
{
    /**
     * __construct
     */
    public function __construct(null|string $error = null, null|string $errorDescription = null, array $data = array())
    {
        $error = $error === null ? "httpRequestSentToHTTPSPort" : $error;
        $errorDescription = $errorDescription === null ? "The client has made a HTTP request to a port listening for HTTPS requests" : $errorDescription;
        parent::__construct(497, $error, $errorDescription, $data);
    }
}