<?php
declare(strict_types=1);

namespace gijsbos\Http\Exceptions;

/**
 * BadRequestException
 */
class BadRequestException extends HTTPRequestException 
{
    /**
     * __construct
     */
    public function __construct(null|string $error = null, null|string $errorDescription = null, array $data = array())
    {
        $error = $error === null ? "badRequest" : $error;
        $errorDescription = $errorDescription === null ? "The server could not process the request due to a client error" : $errorDescription;
        parent::__construct(400, $error, $errorDescription, $data);
    }
}