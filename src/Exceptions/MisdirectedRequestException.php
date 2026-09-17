<?php
declare(strict_types=1);

namespace gijsbos\Http\Exceptions;

/**
 * MisdirectedRequestException
 */
class MisdirectedRequestException extends HTTPRequestException 
{
    /**
     * __construct
     */
    public function __construct(null|string $error = null, null|string $errorDescription = null, array $data = array())
    {
        $error = $error === null ? "misdirectedRequest" : $error;
        $errorDescription = $errorDescription === null ? "The request was directed at a server that is not able to produce a response" : $errorDescription;
        parent::__construct(421, $error, $errorDescription, $data);
    }
}
