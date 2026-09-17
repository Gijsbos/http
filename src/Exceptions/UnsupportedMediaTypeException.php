<?php
declare(strict_types=1);

namespace gijsbos\Http\Exceptions;

/**
 * UnsupportedMediaTypeException
 */
class UnsupportedMediaTypeException extends HTTPRequestException 
{
    /**
     * __construct
     */
    public function __construct(null|string $error = null, null|string $errorDescription = null, array $data = array())
    {
        $error = $error === null ? "unsupportedMediaType" : $error;
        $errorDescription = $errorDescription === null ? "The media format of the request data is not supported" : $errorDescription;
        parent::__construct(415, $error, $errorDescription, $data);
    }
}
