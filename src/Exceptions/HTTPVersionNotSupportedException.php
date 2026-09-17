<?php
declare(strict_types=1);

namespace gijsbos\Http\Exceptions;

/**
 * HTTPVersionNotSupportedException
 */
class HTTPVersionNotSupportedException extends HTTPRequestException 
{
    /**
     * __construct
     */
    public function __construct(null|string $error = null, null|string $errorDescription = null, array $data = array())
    {
        $error = $error === null ? "httpVersionNotSupported" : $error;
        $errorDescription = $errorDescription === null ? "The server does not support the HTTP protocol version used in the request" : $errorDescription;
        parent::__construct(505, $error, $errorDescription, $data);
    }
}
