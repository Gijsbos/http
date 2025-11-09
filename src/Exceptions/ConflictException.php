<?php
declare(strict_types=1);

namespace gijsbos\Http\Exceptions;

/**
 * ConflictException
 */
class ConflictException extends HTTPRequestException 
{
    /**
     * __construct
     */
    public function __construct(null|string $error = null, null|string $errorDescription = null, array $data = array()) 
    {
        $error = $error === null ? "conflict" : $error;
        $errorDescription = $errorDescription === null ? "The server could not process the request due to a conflict" : $errorDescription;
        parent::__construct(409, $error, $errorDescription, $data);
    }
}