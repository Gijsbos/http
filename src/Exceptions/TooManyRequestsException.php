<?php
declare(strict_types=1);

namespace gijsbos\Http\Exceptions;

/**
 * TooManyRequestsException
 */
class TooManyRequestsException extends HTTPRequestException 
{
    /**
     * __construct
     */
    public function __construct(null|string $error = null, null|string $errorDescription = null, array $data = array())
    {
        $error = $error === null ? "tooManyRequests" : $error;
        $errorDescription = $errorDescription === null ? "The client has sent too many requests in a given amount of time" : $errorDescription;
        parent::__construct(429, $error, $errorDescription, $data);
    }
}
