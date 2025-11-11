<?php
declare(strict_types=1);

namespace gijsbos\Http\Exceptions;

use Exception;

/**
 * InvalidArgumentTypeException
 */
class InvalidArgumentTypeException extends Exception
{
    public $argument;
    public $value;
    public $expectedType;
    public $actualType;

    /**
     * __construct
     */
    public function __construct(string $argument, $value, string $expectedType, string $actualType) 
    {
        $this->argument = $argument;
        $this->value = $value;
        $this->expectedType = $expectedType;
        $this->actualType = $actualType;

        // Check if value can be printed
        $printValue = "";
        if(!is_object($this->value) && !is_array($this->value) && !is_null($this->value) && !is_bool($this->value))
        {
            $printValue = " using value '{$this->value}'";
        }
        else if(is_bool($this->value))
        {
            $printValue = ($this->value === 1 || $this->value === true ? " using value 'true'": " using value 'false'");
        }
        else if(is_null($this->value))
        {
            $printValue = " using value 'NULL'";
        }
        
        // Call parent constructor
        parent::__construct("Argument type for argument '$argument' is incorrect, received '$actualType', expected '$expectedType'$printValue");
    }
}