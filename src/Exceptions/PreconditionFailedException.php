<?php
declare(strict_types=1);

namespace gijsbos\Http\Exceptions;

/**
 * PreconditionFailedException
 */
class PreconditionFailedException extends HTTPRequestException 
{
    /**
     * __construct
     */
    public function __construct(null|string $error = null, null|string $errorDescription = null, array $data = array())
    {
        $error = $error === null ? "preconditionFailed" : $error;
        $errorDescription = $errorDescription === null ? "One or more conditions in the request header fields evaluated to false" : $errorDescription;
        parent::__construct(412, $error, $errorDescription, $data);
    }
}
