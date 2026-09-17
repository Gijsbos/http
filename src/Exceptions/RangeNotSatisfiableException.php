<?php
declare(strict_types=1);

namespace gijsbos\Http\Exceptions;

/**
 * RangeNotSatisfiableException
 */
class RangeNotSatisfiableException extends HTTPRequestException 
{
    /**
     * __construct
     */
    public function __construct(null|string $error = null, null|string $errorDescription = null, array $data = array())
    {
        $error = $error === null ? "rangeNotSatisfiable" : $error;
        $errorDescription = $errorDescription === null ? "The range specified in the request could not be satisfied" : $errorDescription;
        parent::__construct(416, $error, $errorDescription, $data);
    }
}
