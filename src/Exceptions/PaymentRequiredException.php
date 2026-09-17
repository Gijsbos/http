<?php
declare(strict_types=1);

namespace gijsbos\Http\Exceptions;

/**
 * PaymentRequiredException
 */
class PaymentRequiredException extends HTTPRequestException 
{
    /**
     * __construct
     */
    public function __construct(null|string $error = null, null|string $errorDescription = null, array $data = array())
    {
        $error = $error === null ? "paymentRequired" : $error;
        $errorDescription = $errorDescription === null ? "Payment is required to access the requested resource" : $errorDescription;
        parent::__construct(402, $error, $errorDescription, $data);
    }
}
