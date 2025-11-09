<?php
declare(strict_types=1);

namespace gijsbos\Http\Exceptions;

/**
 * MethodNotAllowedException
 */
class MethodNotAllowedException extends HTTPRequestException 
{
    /**
     * __construct
     */
    public function __construct(null|string $error = null, null|string $errorDescription = null, array $data = array()) 
    {
        $error = $error === null ? "methodNotAllowed" : $error;
        $errorDescription = $errorDescription === null ? "The server could not process the request due to the use of an invalid method" : $errorDescription;
        parent::__construct(405, $error, $errorDescription, $data);
    }
}