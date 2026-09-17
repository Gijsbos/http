<?php
declare(strict_types=1);

namespace gijsbos\Http\Exceptions;

/**
 * TooEarlyException
 */
class TooEarlyException extends HTTPRequestException 
{
    /**
     * __construct
     */
    public function __construct(null|string $error = null, null|string $errorDescription = null, array $data = array())
    {
        $error = $error === null ? "tooEarly" : $error;
        $errorDescription = $errorDescription === null ? "The server is unwilling to process a request that might be replayed" : $errorDescription;
        parent::__construct(425, $error, $errorDescription, $data);
    }
}
