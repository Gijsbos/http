<?php
declare(strict_types=1);

namespace gijsbos\Http\Exceptions;

/**
 * RequestHeaderFieldsTooLargeException
 */
class RequestHeaderFieldsTooLargeException extends HTTPRequestException 
{
    /**
     * __construct
     */
    public function __construct(null|string $error = null, null|string $errorDescription = null, array $data = array())
    {
        $error = $error === null ? "requestHeaderFieldsTooLarge" : $error;
        $errorDescription = $errorDescription === null ? "The request's header fields are too large for the server to process" : $errorDescription;
        parent::__construct(431, $error, $errorDescription, $data);
    }
}
