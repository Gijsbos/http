<?php
declare(strict_types=1);

namespace gijsbos\Http\Exceptions;

use Exception;

/**
 * InvalidArgumentErrorException
 */
class InvalidArgumentErrorException extends Exception
{
    public $error;
    public $description;

    /**
     * __construct
     */
    public function __construct($error, string $description) 
    {
        $this->error = $error;
        $this->description = $description;

        // Call parent constructor
        parent::__construct($description);
    }
}