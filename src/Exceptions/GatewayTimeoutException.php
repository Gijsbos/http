<?php
declare(strict_types=1);

namespace gijsbos\Http\Exceptions;

/**
 * GatewayTimeoutException
 */
class GatewayTimeoutException extends HTTPRequestException 
{
    /**
     * __construct
     */
    public function __construct(null|string $error = null, null|string $errorDescription = null, array $data = array())
    {
        $error = $error === null ? "gatewayTimeout" : $error;
        $errorDescription = $errorDescription === null ? "The server did not receive a timely response from an upstream server" : $errorDescription;
        parent::__construct(504, $error, $errorDescription, $data);
    }
}
