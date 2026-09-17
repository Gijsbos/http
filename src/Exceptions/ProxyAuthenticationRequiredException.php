<?php
declare(strict_types=1);

namespace gijsbos\Http\Exceptions;

/**
 * ProxyAuthenticationRequiredException
 */
class ProxyAuthenticationRequiredException extends HTTPRequestException 
{
    /**
     * __construct
     */
    public function __construct(null|string $error = null, null|string $errorDescription = null, array $data = array())
    {
        $error = $error === null ? "proxyAuthenticationRequired" : $error;
        $errorDescription = $errorDescription === null ? "Authentication with the proxy is required before the request can proceed" : $errorDescription;
        parent::__construct(407, $error, $errorDescription, $data);
    }
}
