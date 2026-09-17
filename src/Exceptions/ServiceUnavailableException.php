<?php
declare(strict_types=1);

namespace gijsbos\Http\Exceptions;

/**
 * ServiceUnavailableException
 */
class ServiceUnavailableException extends HTTPRequestException 
{
    /**
     * __construct
     */
    public function __construct(null|string $error = null, null|string $errorDescription = null, array $data = array())
    {
        $error = $error === null ? "serviceUnavailable" : $error;
        $errorDescription = $errorDescription === null ? "The server is currently unable to handle the request" : $errorDescription;
        parent::__construct(503, $error, $errorDescription, $data);
    }
}
