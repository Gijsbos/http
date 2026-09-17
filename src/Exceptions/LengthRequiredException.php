<?php
declare(strict_types=1);

namespace gijsbos\Http\Exceptions;

/**
 * LengthRequiredException
 */
class LengthRequiredException extends HTTPRequestException 
{
    /**
     * __construct
     */
    public function __construct(null|string $error = null, null|string $errorDescription = null, array $data = array())
    {
        $error = $error === null ? "lengthRequired" : $error;
        $errorDescription = $errorDescription === null ? "The request did not specify the length of its content, which is required" : $errorDescription;
        parent::__construct(411, $error, $errorDescription, $data);
    }
}
