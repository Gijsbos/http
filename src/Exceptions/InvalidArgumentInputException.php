<?php
declare(strict_types=1);

namespace gijsbos\Http\Exceptions;

use Exception;

/**
 * InvalidArgumentInputException
 */
class InvalidArgumentInputException extends Exception
{
    public $argument;
    public $value;
    public $requirement;

    /**
     * __construct
     */
    public function __construct(string $argument, $value, string $requirement) 
    {
        $this->argument = $argument;
        $this->value = $value;
        $this->requirement = $requirement;

        // Check if value can be printed
        $printValue = "";
        if(!is_object($this->value) && !is_array($this->value) && !is_null($this->value) && !is_bool($this->value))
        {
            $printValue = " '{$this->value}'";
        }
        else if(is_bool($this->value))
        {
            $printValue = ($this->value === 1 || $this->value === true ? " 'true'": " 'false'");
        }
        else if(is_null($this->value))
        {
            $printValue = " 'NULL'";
        }

        // Call parent constructor
        parent::__construct("Argument input$printValue for argument '$argument' does not meet requirement '$requirement'");
    }
}