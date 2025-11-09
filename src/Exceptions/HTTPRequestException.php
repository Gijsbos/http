<?php
declare(strict_types=1);

namespace gijsbos\Http\Exceptions;

use Exception;

/**
 * HTTPRequestException
 */
class HTTPRequestException extends Exception 
{
    public $status;
    public $error;
    public $errorDescription;
    public $data;

    /**
     * __construct
     */
    public function __construct(null|int $status = null, null|string $error = null, null|string $errorDescription = null, null|array $data = null) 
    {
        $this->status = $status;
        $this->error = $error;
        $this->errorDescription = $errorDescription;
        $this->data = $data !== null ? $data : array();
        parent::__construct($this->toString(), $status);
    }

    /**
     * getStatusCode
     */
    public function getStatusCode()
    {
        return $this->status;
    }

    /**
     * getError
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * getErrorDescription
     */
    public function getErrorDescription()
    {
        return $this->errorDescription;
    }

    /**
     * getData
     */
    public function getData(null|string $key = null)
    {
        return $key !== null ? @$this->data[$key] : $this->data;
    }

    /**
     * dataToString
     */
    private function dataToString()
    {
        // Check data 
        if($this->data !== null && is_array($this->data))
        {
            // Check if data has items
            if(count($this->data) > 0)
            {
                return json_encode($this->data);
            }
        }

        // Return empty
        return null;
    }

    /**
     * toString
     */
    private function toString() : string
    {
        // Set description
        $description = $this->errorDescription !== null ? " {$this->errorDescription}" : "";

        // Create array from data array
        $dataToString = $this->dataToString() !== null ? " (" . $this->dataToString() . ")" : "";

        // Return message
        return "({$this->status}) {$this->error} -$description$dataToString";
    }

    /**
     * printJson
     */
    public function printJson()
    {
        print(json_encode([
            "status" => $this->status,
            "error" => $this->error,
            "errorDescription" => $this->errorDescription,
        ]));
    }
}