<?php
declare(strict_types=1);

namespace gijsbos\Http\Exceptions;

/**
 * ResourceNotFoundException
 */
class ResourceNotFoundException extends HTTPRequestException 
{
    /**
     * __construct
     */
    public function __construct(null|string $error = null, null|string $errorDescription = null, array $data = array()) 
    {
        $error = $error === null ? "notFound" : $error;
        $errorDescription = $errorDescription === null ? "The requested resource could not be found" : $errorDescription;
        parent::__construct(404, $error, $errorDescription, $data);
    }
}