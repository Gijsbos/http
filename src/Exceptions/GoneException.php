<?php
declare(strict_types=1);

namespace gijsbos\Http\Exceptions;

/**
 * GoneException
 */
class GoneException extends HTTPRequestException 
{
    /**
     * __construct
     */
    public function __construct(null|string $error = null, null|string $errorDescription = null, array $data = array())
    {
        $error = $error === null ? "gone" : $error;
        $errorDescription = $errorDescription === null ? "The requested resource is no longer available and will not be available again" : $errorDescription;
        parent::__construct(410, $error, $errorDescription, $data);
    }
}
