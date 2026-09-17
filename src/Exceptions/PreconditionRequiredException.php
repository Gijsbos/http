<?php
declare(strict_types=1);

namespace gijsbos\Http\Exceptions;

/**
 * PreconditionRequiredException
 */
class PreconditionRequiredException extends HTTPRequestException 
{
    /**
     * __construct
     */
    public function __construct(null|string $error = null, null|string $errorDescription = null, array $data = array())
    {
        $error = $error === null ? "preconditionRequired" : $error;
        $errorDescription = $errorDescription === null ? "The request must include valid conditional header fields" : $errorDescription;
        parent::__construct(428, $error, $errorDescription, $data);
    }
}
