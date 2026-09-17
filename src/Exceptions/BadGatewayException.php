<?php
declare(strict_types=1);

namespace gijsbos\Http\Exceptions;

/**
 * BadGatewayException
 */
class BadGatewayException extends HTTPRequestException 
{
    /**
     * __construct
     */
    public function __construct(null|string $error = null, null|string $errorDescription = null, array $data = array())
    {
        $error = $error === null ? "badGateway" : $error;
        $errorDescription = $errorDescription === null ? "The server received an invalid response from an upstream server" : $errorDescription;
        parent::__construct(502, $error, $errorDescription, $data);
    }
}
