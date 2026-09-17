<?php
declare(strict_types=1);

namespace gijsbos\Http\Exceptions;

/**
 * RequestTimeoutException
 */
class RequestTimeoutException extends HTTPRequestException 
{
    /**
     * __construct
     */
    public function __construct(null|string $error = null, null|string $errorDescription = null, array $data = array())
    {
        $error = $error === null ? "requestTimeout" : $error;
        $errorDescription = $errorDescription === null ? "The server timed out waiting for the request" : $errorDescription;
        parent::__construct(408, $error, $errorDescription, $data);
    }
}
