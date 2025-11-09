<?php
declare(strict_types=1);

namespace gijsbos\Http\Exceptions;

/**
 * ForbiddenException
 */
class ForbiddenException extends HTTPRequestException 
{
    /**
     * __construct
     */
    public function __construct(null|string $error = null, null|string $errorDescription = null, array $data = array())
    {
        $error = $error === null ? "forbidden" : $error;
        $errorDescription = $errorDescription === null ? "The server denied access to the requested resource" : $errorDescription;
        parent::__construct(403, $error, $errorDescription, $data);
    }
}