<?php
declare(strict_types=1);

namespace gijsbos\Http\Exceptions;

/**
 * InternalServerErrorException
 */
class InternalServerErrorException extends HTTPRequestException 
{
    /**
     * __construct
     */
    public function __construct(null|string $error = null, null|string $errorDescription = null, array $data = array())  
    {
        $error = $error === null ? "internalServerError" : $error;
        $errorDescription = $errorDescription === null ? "The server could not process the request due to an internal server error" : $errorDescription;
        parent::__construct(500, $error, $errorDescription, $data);
    }
}