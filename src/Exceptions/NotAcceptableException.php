<?php
declare(strict_types=1);

namespace gijsbos\Http\Exceptions;

/**
 * NotAcceptableException
 */
class NotAcceptableException extends HTTPRequestException 
{
    /**
     * __construct
     */
    public function __construct(null|string $error = null, null|string $errorDescription = null, array $data = array()) 
    {
        $error = $error === null ? "notAcceptable" : $error;
        $errorDescription = $errorDescription === null ? "The server could not produce a response matching the list of acceptable values" : $errorDescription;
        parent::__construct(406, $error, $errorDescription, $data);
    }
}