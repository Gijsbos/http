<?php
declare(strict_types=1);

namespace gijsbos\Http\Exceptions;

use Exception;

/**
 * InvalidArgumentMissingException
 */
class InvalidArgumentMissingException extends Exception
{
    public $argument;
    public $value;

    /**
     * __construct
     */
    public function __construct(string $argument, $value) 
    {
        parent::__construct("Argument '$argument' is missing");
        $this->argument = $argument;
        $this->value = $value;
    }
}