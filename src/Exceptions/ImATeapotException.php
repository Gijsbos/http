<?php
declare(strict_types=1);

namespace gijsbos\Http\Exceptions;

/**
 * ImATeapotException
 */
class ImATeapotException extends HTTPRequestException 
{
    /**
     * __construct
     */
    public function __construct(null|string $error = null, null|string $errorDescription = null, array $data = array())
    {
        $error = $error === null ? "imATeapot" : $error;
        $errorDescription = $errorDescription === null ? "The server refuses to brew coffee because it is, permanently, a teapot" : $errorDescription;
        parent::__construct(418, $error, $errorDescription, $data);
    }
}
