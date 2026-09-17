<?php
declare(strict_types=1);

namespace gijsbos\Http\Exceptions;

/**
 * ExpectationFailedException
 */
class ExpectationFailedException extends HTTPRequestException 
{
    /**
     * __construct
     */
    public function __construct(null|string $error = null, null|string $errorDescription = null, array $data = array())
    {
        $error = $error === null ? "expectationFailed" : $error;
        $errorDescription = $errorDescription === null ? "The server could not meet the requirements of the request's Expect header" : $errorDescription;
        parent::__construct(417, $error, $errorDescription, $data);
    }
}
