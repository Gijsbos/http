<?php
declare(strict_types=1);

namespace gijsbos\Http\Exceptions;

/**
 * ContentTooLargeException
 */
class ContentTooLargeException extends HTTPRequestException 
{
    /**
     * __construct
     */
    public function __construct(null|string $error = null, null|string $errorDescription = null, array $data = array())
    {
        $error = $error === null ? "contentTooLarge" : $error;
        $errorDescription = $errorDescription === null ? "The request body is larger than the server is willing or able to process" : $errorDescription;
        parent::__construct(413, $error, $errorDescription, $data);
    }
}
