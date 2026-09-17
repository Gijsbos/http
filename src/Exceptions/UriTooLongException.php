<?php
declare(strict_types=1);

namespace gijsbos\Http\Exceptions;

/**
 * UriTooLongException
 */
class UriTooLongException extends HTTPRequestException 
{
    /**
     * __construct
     */
    public function __construct(null|string $error = null, null|string $errorDescription = null, array $data = array())
    {
        $error = $error === null ? "uriTooLong" : $error;
        $errorDescription = $errorDescription === null ? "The URI provided was too long for the server to process" : $errorDescription;
        parent::__construct(414, $error, $errorDescription, $data);
    }
}
