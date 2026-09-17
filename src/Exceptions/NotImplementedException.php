<?php
declare(strict_types=1);

namespace gijsbos\Http\Exceptions;

/**
 * NotImplementedException
 */
class NotImplementedException extends HTTPRequestException 
{
    /**
     * __construct
     */
    public function __construct(null|string $error = null, null|string $errorDescription = null, array $data = array())
    {
        $error = $error === null ? "notImplemented" : $error;
        $errorDescription = $errorDescription === null ? "The server does not support the functionality required to fulfill the request" : $errorDescription;
        parent::__construct(501, $error, $errorDescription, $data);
    }
}
