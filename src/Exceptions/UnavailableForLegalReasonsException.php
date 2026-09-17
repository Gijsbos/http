<?php
declare(strict_types=1);

namespace gijsbos\Http\Exceptions;

/**
 * UnavailableForLegalReasonsException
 */
class UnavailableForLegalReasonsException extends HTTPRequestException 
{
    /**
     * __construct
     */
    public function __construct(null|string $error = null, null|string $errorDescription = null, array $data = array())
    {
        $error = $error === null ? "unavailableForLegalReasons" : $error;
        $errorDescription = $errorDescription === null ? "The requested resource is unavailable for legal reasons" : $errorDescription;
        parent::__construct(451, $error, $errorDescription, $data);
    }
}
