<?php
declare(strict_types=1);

namespace gijsbos\Http\Exceptions;

/**
 * UpgradeRequiredException
 */
class UpgradeRequiredException extends HTTPRequestException
{
    /**
     * __construct
     */
    public function __construct(null|string $error = null, null|string $errorDescription = null, array $data = array())
    {
        $error = $error === null ? "upgradeRequired" : $error;
        $errorDescription = $errorDescription === null ? "The request must be retried using HTTPS" : $errorDescription;
        parent::__construct(426, $error, $errorDescription, $data);
    }
}
