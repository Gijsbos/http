<?php
declare(strict_types=1);

namespace gijsbos\Http\Exceptions;

/**
 * UnauthorizedException
 */
class UnauthorizedException extends HTTPRequestException 
{
    /**
     * __construct
     */
    public function __construct(null|string $error = null, null|string $errorDescription = null, array $data = array()) 
    {
        $error = $error === null ? "unauthorized" : $error;
        $errorDescription = $errorDescription === null ? "The server could not process the request due to insufficient permissions" : $errorDescription;
        parent::__construct(401, $error, $errorDescription, $data);
    }
}