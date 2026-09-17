<?php
declare(strict_types=1);

namespace gijsbos\Http\Exceptions;

/**
 * UnprocessableContentException
 */
class UnprocessableContentException extends HTTPRequestException 
{
    /**
     * __construct
     */
    public function __construct(null|string $error = null, null|string $errorDescription = null, array $data = array())
    {
        $error = $error === null ? "unprocessableContent" : $error;
        $errorDescription = $errorDescription === null ? "The request was well-formed but contained semantic errors" : $errorDescription;
        parent::__construct(422, $error, $errorDescription, $data);
    }
}
