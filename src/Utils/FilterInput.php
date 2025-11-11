<?php
declare(strict_types=1);

namespace gijsbos\Http\Utils;

use gijsbos\ExtFuncs\Exceptions\FileUploadHandlerException;
use gijsbos\Http\Exceptions\InvalidArgumentErrorException;
use gijsbos\Http\Exceptions\InvalidArgumentInputException;
use gijsbos\Http\Exceptions\InvalidArgumentMissingException;
use gijsbos\Http\Exceptions\InvalidArgumentTypeException;

/**
 * FilterInput
 * 
 * Handle manual/globlal input and apply filters.
 * 
 * Throws exception:
 *  InvalidArgumentException - Incorrect argument provided to function
 *  InvalidArgumentTypeException - Incorrect argument type provided to fetch input
 *  InvalidArgumentInputException - Incorrect argument input provided to fetch input
 *  InvalidArgumentMissingException - Argument missing (value = NULL) provided to fetch input
 * 
 * Static example:
 *  Fetch 'name' string from form 'POST'
 *      $name = FilterInput::fetch(STRING | POST, "name");
 *  Fetch 'zipcode' string from form 'POST' and apply validation regexp
 *      $zipcode = FilterInput::fetch(STRING | POST, "zipcode", "/[0-9]{4}[a-zA-Z]{2}/");
 * 
 * Object instance example (with custom request method):
 *  Fetch 'amount' integer from custom request method 'PUT'
 *      $filterInput = new FilterInput(array(
 *          "customRequestMethods" => array(
 *              "PUT" => INPUT_POST // <= key: method name, value: how custom method behaves
 *          )
 *      ));
 *      $amount = $filterInput->fetch(INT | PUT, 'amount');
 */
class FilterInput
{
    const DEFAULT_INPUT_TYPES = array(
        GET => INPUT_GET,
        POST => INPUT_POST,
        PUT => INPUT_POST,
        PATCH => INPUT_POST,
        DELETE => INPUT_GET,
        COOKIE => INPUT_COOKIE,
        SERVER => INPUT_SERVER,
        ENV => INPUT_ENV
    );

    const REGEXP_INT = "/^-?\d+$/";
    const REGEXP_FLOAT = "/^[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?$/";
    const REGEXP_BOOLEAN = "/^(0|1|true|false)$/";
    const REGEXP_EMAIL = "/^[a-zA-Z0-9\.\_\-%+]+@[a-zA-Z0-9\.\-]+\.[a-zA-Z]{2,}$/";
    const REGEXP_UUID4 = "/^[a-f0-9]{8}\-[a-f0-9]{4}\-4[a-f0-9]{3}\-(8|9|a|b)[a-f0-9]{3}\-[a-f0-9]{12}$/";
    
    const REGEXP_ALPHABETICAL = "/^[a-zA-Z]+$/";
    const REGEXP_ALPHABETICAL_SPACES = "/^[ a-zA-Z]+$/";
    const REGEXP_ALPHANUMERIC = "/^[a-zA-Z0-9]+$/";
    const REGEXP_ALPHANUMERIC_SPACES = "/^[ a-zA-Z0-9]+$/";
    const REGEXP_FILEPATH = "/^[a-zA-Z0-9\.\/\\]+$/";
    
    const REGEXP_DATE = "/^[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}$/";
    const REGEXP_DATETIME = "/^[0-9]{4}-[0-9]{1,2}-[0-9]{1,2} [0-9]{1,2}:[0-9]{1,2}:[0-9]{1,2}$/";
    const REGEXP_DATE_OR_DATETIME = "/^(?:^[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}$)|(?:^[0-9]{4}-[0-9]{1,2}-[0-9]{1,2} [0-9]{1,2}:[0-9]{1,2}:[0-9]{1,2}$)$/";
    const REGEXP_SHA256_HASH = "/^[a-zA-Z0-9]{64}$/";
    const REGEXP_SHA512_HASH = "/^[a-zA-Z0-9]{128}$/";

    public $inputTypes;
    public $exceptions;
    public $inputStream;
    public $allowGlobalGETAccess;
    public $throwExceptionOnFailure;

    /**
     * __construct
     */
    public function __construct(array $options = array())
    {
        // Set default values
        $this->inputTypes = array_key_exists("customRequestMethods", $options) && is_array($options["customRequestMethods"]) ? array_merge(self::DEFAULT_INPUT_TYPES, $options["customRequestMethods"]) : self::DEFAULT_INPUT_TYPES;
        $this->exceptions = array();
        $this->inputStream = $this->readInputStream(); 

        // Options
        $this->allowGlobalGETAccess = array_key_exists("allowGlobalGETAccess", $options) ? $options["allowGlobalGETAccess"] : false;
        $this->throwExceptionOnFailure = array_key_exists("throwExceptionOnFailure", $options) ? $options["throwExceptionOnFailure"] : true;
    }

    /**
     * hasHeader
     */
    private function hasHeader(string $key, string $value) : bool
    {
        return array_key_exists($key, $_SERVER) && $_SERVER[$key] == $value;
    }

    /**
     * hasApplicationJSONHeader
     */
    private function hasApplicationJSONHeader() : bool
    {
        return $this->hasHeader("CONTENT_TYPE", "application/json");
    }

    /**
     * getPHPInput
     */
    public function getPHPInput()
    {
        return file_get_contents('php://input');
    }

    /**
     * readInputStream
     */
    private function readInputStream() : array
    {
        if($this->hasApplicationJSONHeader())
            $data = json_decode($this->getPHPInput(), true);
        else
            parse_str(file_get_contents('php://input'), $data);

        return $data === null ? [] : $data;
    }

    /**
     * parseInputServerVariableName
     *  Server input is stored e.g. content-type => CONTENT_TYPE
     *  Headers are stored e.g. my-header => HTTP_MY_HEADER
     */
    private function parseInputServerVariableName(string $variableName) : string
    {
        $variableName = strtoupper(str_replace("-", "_", $variableName));
        return strpos($variableName, "HTTP_") !== 0 ? "HTTP_$variableName" : $variableName;
    }

    /**
     * getFilterInput
     */
    private function getFilterInput(int $flags, int $inputFlagValue, $inputBehavesAs, $variableName)
    {
        if( ($inputBehavesAs == INPUT_GET || $inputBehavesAs == INPUT_POST) && $this->hasApplicationJSONHeader())
            return @$this->inputStream[$variableName];
        else
        {
            // Server input needs parsing variableName
            if($inputBehavesAs === INPUT_SERVER)
                $variableName = $this->parseInputServerVariableName($variableName);
            
            // Any other method than GET, POST
            if(!in_array($inputFlagValue, [GET,POST]) && array_key_exists($inputFlagValue, $this->inputTypes))
            {
                // Custom methods that act like GET, can only access global GET
                if($inputBehavesAs === INPUT_GET)
                    return isset($_GET[$variableName]) ? urldecode($_GET[$variableName]) : null;

                // Custom methods that act like POST, can only access inputStream
                else
                    return @$this->inputStream[$variableName];
            }

            // JSON values are not retrieved by filter_input, we manually read JSON from inputStream
            if($flags & JSON && array_key_exists($variableName, $this->inputStream))
                return $this->inputStream[$variableName];

            // Return default request method
            return filter_input($inputBehavesAs, $variableName);
        }
    }

    /**
     * fetchVariableFromGlobal
     */
    private function fetchVariableFromGlobal(int $flags, string $variableName, $inputFlagValue, $inputBehavesAs)
    {
        $inputValue = null;

        // Check if input type is GET and Global access in enabled
        if($inputBehavesAs === INPUT_GET && $this->allowGlobalGETAccess)
        {
            if(array_key_exists($variableName, $_GET))
            {
                $inputValue = !isset($_GET[$variableName]) ? null : (is_string($_GET[$variableName]) ? urldecode($_GET[$variableName]) : $_GET[$variableName]);
            }
        }
        else
        {
            $filterValue = $this->getFilterInput($flags, $inputFlagValue, $inputBehavesAs, $variableName);

            // Only set inputValue when filterValue is not null (var name not set!)
            if($filterValue !== null)
                $inputValue = $filterValue;
        }

        return $inputValue;
    }

    /**
     * fetchFromGlobals
     *  Returns false when value was not set
     * 
     * @return false|mixed
     */
    private function fetchFromGlobals(int $flags, string $variableNames)
    {
        $globalValue = null;

        foreach($this->inputTypes as $inputFlagValue => $inputBehavesAs)
        {
            if($flags & $inputFlagValue)
            {
                foreach(explode(",", $variableNames) as $variableName)
                {
                    $value = $this->fetchVariableFromGlobal($flags, $variableName, $inputFlagValue, $inputBehavesAs);

                    if($value !== null)
                    {
                        $globalValue = $value;
                        break; // Stop search
                    }
                }
            }
        }

        if($globalValue === null)
            return ($flags & FALSE_IF_EMPTY ? false : null);
        else
            return $globalValue;
    }

    /**
     * getInputValue
     */
    private function getInputValue(int $flags, string $variableNames, $input = null)
    {
        $inputValue = null;

        // Check if inputValue is array that contains 'variableName'
        if(is_array($input))
        {
            foreach(explode(",", $variableNames) as $variableName)
            {
                if(array_key_exists($variableName, $input))
                {
                    $inputValue = $input[$variableName];
                    break; // Stop search
                }
            }
        }
        else if(!is_null($input))
        {
            $inputValue = $input;
        }
        
        // Fetch from globals
        if($inputValue === null)
        {
            $inputValue = $this->fetchFromGlobals($flags, $variableNames);
        }

        // Parse input value
        if(is_string($inputValue) && ( ($flags & INT) || ($flags & INTEGER) ) && mb_strlen($inputValue) === 0)
            $inputValue = null;
        else if(is_string($inputValue) && ( ($flags & FLOAT) || ($flags & DOUBLE) ) && mb_strlen($inputValue) === 0)
            $inputValue = null;

        // Return value
        return $inputValue;
    }

    /**
     * hasVar
     * 
     * @param int flags - GET,POST,PUT,DELETE
     * @param string variableName - name of variable
     * @param mixed inputValue - optional input value, if array, checks for variableName as assoc value
     * @return bool
     */
    private function hasVar(int $flags, string $variableNames, $inputValue = null) : bool
    {
        return $this->getInputValue($flags, $variableNames, $inputValue) === null ? false : true;
    }

    /**
     * handleException
     */
    private function handleException($exception) : void
    {
        // Add to exceptions
        array_push($this->exceptions, $exception);

        // Throw exception
        if($this->throwExceptionOnFailure)
            throw $exception;
    }

    /**
     * formatStringOptionsInput
     */
    private function formatStringOptionsInput($options)
    {
        $options = (string) $options;
        $options = \preg_replace("/\n/", " ", $options);
        return $options;
    }

    /**
     * isValidRegExp
     */
    private function isValidRegExp($pattern)
    {
        return @preg_match($pattern, "") !== false && preg_match("/^\/.+[^\\\]\/[a-z]*$/", $pattern) === 1;
    }

    /**
     * applyRegexOption
     */
    private function applyRegexOption(string $variableName, $inputValue = null, $options = null)
    {
        // Check if options parameter is a regular expression
        if(!$this->isValidRegExp($options))
            throw new \InvalidArgumentException("Invalid regexp options input '{$this->formatStringOptionsInput($options)}' provided for argument '$variableName'");

        // Apply filter
        $filterInputValue = filter_var($inputValue, FILTER_VALIDATE_REGEXP, array("options" => array("regexp" => $options)));

        // Check filter result
        if($filterInputValue === false)
        {
            $this->handleException(new InvalidArgumentInputException($variableName, $inputValue, $options));
        }

        // Return value
        return $filterInputValue;
    }

    /**
     * isValidArrayInput
     */
    private function isValidArrayInput($input, array $allowedValues, null|int $flags = null) : bool
    {
        // Turn string into array
        if(is_string($input))
            $input = array_filter(explode(",", $input));

        // Turn strings to lower
        $input = array_map('strtolower', $input);
        $allowedValues = array_map('strtolower', $allowedValues);

        // SINGLE_VALUE: Allows one input value checked against allowed values
        if($flags & SINGLE_VALUE)
        {
            $count = count($input);

            if($count == 0 || $count > 1)
                return false;
            else
                return in_array(reset($input), $allowedValues);

        }

        // MULTI_VALUE (default): Allows multiple values
        else
        {
            return array_in_array($allowedValues, $input);
        }
    }

    /**
     * applyTextFlags
     */
    private function applyTextFlags($input, null|int $flags = null)
    {
        if(is_string($input))
        {
            if($flags & TO_LOWERCASE)
                $input = strtolower($input);
            else if($flags & TO_UPPERCASE)
                $input = strtoupper($input);
        }
        return $input;
    }

    /**
     * fetchString
     */
    private function fetchString(string $variableName, $inputValue = null, $options = null, null|int $flags = null, null|bool $isOptional = null)
    {
        // Check if value is of type string
        if(!is_string($inputValue) && !is_numeric($inputValue))
            $this->handleException(new InvalidArgumentTypeException($variableName, $inputValue, 'string', gettype($inputValue)));

        // Convert
        $inputValue = strval($inputValue);

        // Check if string is empty
        if($isOptional && mb_strlen($inputValue) === 0 && ($flags & FALSE_IF_EMPTY))
            return $inputValue;

        // Apply string flags
        $inputValue = $this->applyTextFlags($inputValue, $flags);

        // Check if options are set
        if($options !== null)
        {
            // Check if the options are a regexp string or array
            if(is_string($options))
            {
                if($this->applyRegexOption($variableName, $inputValue, $options) === false && ($flags & FALSE_IF_EMPTY))
                    return false;
            }
            else
            {
                // Check if options is not an array
                if(!is_array($options))
                    throw new \InvalidArgumentException("Invalid options provided for fetch of type 'STRING' for argument '$variableName', expected regexp options array or regular expression");

                // Check if regexp option is set
                if(isset($options["options"]["regexp"]))
                {
                    // Apply filter
                    $filterInputValue = filter_var($inputValue, FILTER_VALIDATE_REGEXP, $options);

                    // Check filter result
                    if($filterInputValue === false)
                    {
                        $this->handleException(new InvalidArgumentInputException($variableName, $inputValue, $options['options']['regexp']));
                    }
                }
                else
                {
                    if(!$this->isValidArrayInput($inputValue, $options, $flags))
                    {
                        if(!$isOptional)
                            $this->handleException(new InvalidArgumentInputException($variableName, $inputValue, implode("|", $options)));
                    }
                }
            }
        }

        return (string) $inputValue;
    }

    /**
     * fetchInteger
     */
    private function fetchInteger(string $variableName, $inputValue = null, $options = null) : int
    {
        // Cast input to int if possible
        $inputValue = is_numeric($inputValue) && !is_float($inputValue) && !is_double($inputValue) ? (int) $inputValue : $inputValue;

        // Check if value is of type string
        if(!is_int($inputValue))
            $this->handleException(new InvalidArgumentTypeException($variableName, $inputValue, 'int', gettype($inputValue)));

        // Check if options are set
        if($options !== null)
        {
            // Check if options parameter is an array
            if(!is_array($options))
                throw new \InvalidArgumentException("Invalid options provided for fetch of type 'INT' for argument '$variableName', expected array");

            // Apply filter
            $filterInputValue = filter_var($inputValue, FILTER_VALIDATE_INT, array("options" => array_filter($options, function($value){ return !is_null($value); })));

            // Check filter result
            if($filterInputValue === false)
            {
                $min = array_key_exists("min_range", $options) ? $options["min_range"] : "-";
                $max = array_key_exists("max_range", $options) ? $options["max_range"] : "-";
                $requirements = "min range: $min, max range: $max";
                $this->handleException(new InvalidArgumentInputException($variableName, $inputValue, $requirements));
            }
        }

        return (int) $inputValue;
    }

    /**
     * valueIsWithinRange
     *  Simulates the filter_var/input FILTER_VALIDATE_INT options(min_range/max_range) for float values
     */
    private function valueIsWithinRange(float $value, array $options) 
    {
        $minRange = array_key_exists('min_range', $options) ? (float) $options['min_range'] : null;
        $maxRange = array_key_exists('max_range', $options) ? (float) $options['max_range'] : null;

        if($minRange !== null && $maxRange !== null) 
        {
            $valueIsWithinRange = $value >= $minRange && $value <= $maxRange;
            if(!$valueIsWithinRange) $value = false;
        }
        else if($minRange !== null && $maxRange === null)
        {
            $valueIsWithinRange = $value >= $minRange;
            if(!$valueIsWithinRange) $value = false;
        }
        else if($minRange === null && $maxRange !== null)
        {
            $valueIsWithinRange = $value <= $maxRange;
            if(!$valueIsWithinRange) $value = false;
        }
        return $value;
    }

    /**
     * fetchFloat
     */
    private function fetchFloat(string $variableName, $inputValue = null, $options = null) : float
    {
        // Check if input is string
        $inputValue = is_string($inputValue) ? str_replace(",", ".", $inputValue) : $inputValue;

        // Cast input to int if possible
        $inputValue = is_numeric($inputValue) ? (float) $inputValue : $inputValue;

        // Check if value is of type float or double
        if(!is_numeric($inputValue))
        {
            $this->handleException(new InvalidArgumentTypeException($variableName, $inputValue, 'float|double', gettype($inputValue)));
        }

        // Check if options are set
        if($options !== null)
        {
            // Check if options parameter is an array
            if(!is_array($options))
                throw new \InvalidArgumentException("Invalid options provided for fetch of type 'FLOAT|DOUBLE' for argument '$variableName', expected array");

            // Apply filter
            $filterInputValue = $this->valueIsWithinRange((float) $inputValue, array_filter($options, function($value){ return !is_null($value); }));

            // Check filter result
            if($filterInputValue === false)
            {
                $min = array_key_exists("min_range", $options) ? $options["min_range"] : "-";
                $max = array_key_exists("max_range", $options) ? $options["max_range"] : "-";
                $requirements = "min range: $min, max range: $max";
                $this->handleException(new InvalidArgumentInputException($variableName, $inputValue, $requirements));
            }
        }

        return (float) $inputValue;
    }

    /**
     * fetchBoolean
     */
    private function fetchBoolean(string $variableName, $inputValue = null, null|int $flags = null)
    {
        // Check if value is of type float or double
        if(!is_bool($inputValue) && preg_match("/^(0|1|true|false)$/i", (string) $inputValue) == 0)
        {
            $this->handleException(new InvalidArgumentTypeException($variableName, $inputValue, 'boolean', gettype($inputValue)));

            // Return incorrect value
            return $inputValue;
        }

        // Get value
        $value = (bool) $inputValue === 'true' || $inputValue === '1' || $inputValue === true || $inputValue === 1 ? true : false;

        // Return value
        return ($flags & FALSE_IF_EMPTY) ? intval($value) : $value;
    }

    /**
     * fetchEmail
     */
    private function fetchEmail(string $variableName, $inputValue = null) : string
    {
        // Check if value is of type string
        if(!is_string($inputValue))
        {
            $this->handleException(new InvalidArgumentTypeException($variableName, $inputValue, 'email', gettype($inputValue)));
        }
        else
        {
            // Check if the var is an email
            $filterInputValue = filter_var($inputValue, FILTER_VALIDATE_EMAIL);

            // Check filter result
            if($filterInputValue === false)
            {
                $this->handleException(new InvalidArgumentTypeException($variableName, $inputValue, 'email', gettype($inputValue)));
            }
        }

        // Return lowered result
        return strtolower($inputValue);
    }

    /**
     * fetchURI
     */
    private function fetchURI(string $variableName, $inputValue = null, $options = null, null|int $flags = null, null|bool $isOptional = null)
    {
        // Check if value is of type string
        if(!is_string($inputValue))
        {
            $this->handleException(new InvalidArgumentTypeException($variableName, $inputValue, 'uri', gettype($inputValue)));
        }
        else
        {
            // Check if string is empty
            if($isOptional && mb_strlen($inputValue) === 0 && ($flags & FALSE_IF_EMPTY))
                return $inputValue;

            // Apply string flags
            $inputValue = $this->applyTextFlags($inputValue, $flags);

            // Check if the var is an email
            $filterInputValue = filter_var($inputValue, FILTER_VALIDATE_URL);

            // Check filter result
            if($filterInputValue === false)
            {
                $this->handleException(new InvalidArgumentTypeException($variableName, $inputValue, 'uri', gettype($inputValue)));
            }
            else
            {
                // Apply regex options
                if(is_string($options))
                    $this->applyRegexOption($variableName, $inputValue, $options);
            }
        }
        
        return $inputValue;
    }

    /**
     * fetchIPAddress
     */
    private function fetchIPAddress(string $variableName, $inputValue = null)
    {
        // Check if value is of type string
        if(!is_string($inputValue))
        {
            $this->handleException(new InvalidArgumentTypeException($variableName, $inputValue, 'ip address', gettype($inputValue)));
        }
        
        // Check if the var is an email
        $filterInputValue = filter_var($inputValue, FILTER_VALIDATE_IP);

        // Check filter result
        if($filterInputValue === false)
        {
            $this->handleException(new InvalidArgumentTypeException($variableName, $inputValue, 'ip address', gettype($inputValue)));
        }

        // Return result
        return $inputValue;
    }

    /**
     * fetchJSON
     */
    private function fetchJSON(string $variableName, $inputValue = null)
    {
        if(is_string($inputValue))
        {
            if(!is_json($inputValue))
                $this->handleException(new InvalidArgumentTypeException($variableName, $inputValue, 'json', gettype($inputValue)));
            else
                $inputValue = json_decode($inputValue, true);
        }
        else if(!is_array($inputValue))
        {
            $this->handleException(new InvalidArgumentTypeException($variableName, $inputValue, 'json', gettype($inputValue)));
        }

        // Return result
        return $inputValue;
    }

    /**
     * fetchUUID4
     */
    private function fetchUUID4(string $variableName, $inputValue = null)
    {
        // Check if value is of type string
        if(!is_string($inputValue))
        {
            $this->handleException(new InvalidArgumentTypeException($variableName, $inputValue, 'uuid4', gettype($inputValue)));
        }
        else
        {
            // Check if value is of type string
            if(!is_uuid4($inputValue))
            {
                $this->handleException(new InvalidArgumentTypeException($variableName, $inputValue, 'uuid4', gettype($inputValue)));
            }
        }

        // Return lowered result
        return strtolower($inputValue);
    }

    /**
     * fetchFile
     */
    private function fetchFile(string $variableName, $inputValue, $options)
    {
        // Check if value is of type string
        if(!is_null($inputValue) && !is_string($inputValue))
        {
            $this->handleException(new InvalidArgumentTypeException($variableName, $inputValue, 'string', gettype($inputValue)));
        }
        else
        {
            try
            {
                // Check if inputValue has been set
                if($inputValue !== null)
                    FileUploadHandler::readLocal($inputValue, $variableName);

                // Get maxSize
                $maxSize = @$options["maxSize"];

                // Check if options has been set
                if($maxSize === null)
                    throw new InvalidArgumentMissingException("{$variableName}MaxSize", $maxSize);

                // Check maxSize type
                if(!is_int($maxSize))
                    throw new InvalidArgumentTypeException("{$variableName}MaxSize", $maxSize, 'int', gettype($maxSize));

                // Get mimeTypes
                $mimeTypes = @$options["mimeTypes"];

                // Check if options has been set
                if($mimeTypes === null)
                    throw new InvalidArgumentMissingException("{$variableName}MimeTypes", $mimeTypes);

                // Set mimeTypes string to array
                if(is_array($mimeTypes) && array_is_assoc($mimeTypes))
                    $mimeTypes = [$mimeTypes];

                // Check maxSize type
                if(!is_array($mimeTypes))
                    throw new InvalidArgumentTypeException("{$variableName}MimeTypes", $mimeTypes, 'string|array', gettype($mimeTypes));

                // Create upload handler
                $fileUploadHandler = new FileUploadHandler();

                // Handle
                return $fileUploadHandler->read($variableName, $maxSize, $mimeTypes);
            }
            catch(FileUploadHandlerException $ex)
            {
                if($ex->error == "fileNotReceived")
                    throw new InvalidArgumentMissingException($variableName, "file");
                else
                    $this->handleException(new InvalidArgumentErrorException(sprintf("%s%s", $variableName, ucfirst($ex->error)), $ex->getMessage()));
            }
            
            return null;
        }
    }

    /**
     * getFirstVariableName
     */
    private function getFirstVariableName(string $variableNames)
    {
        if(strpos($variableNames, ",") !== false)
            return explode(",", $variableNames)[0];
        else
            return $variableNames;
    }

    /**
     * fetchVar
     * 
     * @param int flags - method => GET,POST,PUT,DELETE, data type => STRING|INT|FLOAT|URI|EMAIL|JSON|FILE
     * @param string variableNames - Variable names to retrieve from global parameters or input
     * @param array options - INT|FLOAT=>[min_value,max_value],STRING|URI=>RegExp
     * @param mixed input - optional input, if array, checks for variableName as assoc value, overwrites global value if value exist in input
     * @param bool isOptional - true is optional, false is not optional and throws missing exception when omitted
     * @return mixed
     */
    private function fetchVar(int $flags, string $variableNames, $options = null, $input = null, bool $isOptional = false)
    {
        // Get inputValue
        $inputValue = $this->getInputValue($flags, $variableNames, $input);

        // Get first variable name used for error reporting
        $variableName = self::getFirstVariableName($variableNames);

        // Check inputValue
        if(is_object($inputValue))
            throw new \InvalidArgumentException(sprintf("Invalid input value for argument '%s' using type '%s'", $variableNames, get_type($inputValue)));

        // Check if null value is set
        if($isOptional && ($inputValue === null || ($inputValue === false && $flags & FALSE_IF_EMPTY)))
            return $inputValue;
            
        // Check if null value is set
        if(!($flags & FILE))
        {
            if(!$isOptional && ($inputValue === null || ($inputValue === false && $flags & FALSE_IF_EMPTY)))
            {
                if($inputValue === null)
                    $this->handleException(new InvalidArgumentMissingException($variableName, $inputValue));

                return $inputValue;
            }
        }
        
        // Process input value as determined in flags 
        if($flags & STRING)
        {
            $inputValue = $this->fetchString($variableName, $inputValue, $options, $flags, $isOptional);
        }
        else if($flags & INT || $flags & INTEGER)
        {
            $inputValue = $this->fetchInteger($variableName, $inputValue, $options);
        }
        else if($flags & FLOAT || $flags & DOUBLE)
        {
            $inputValue = $this->fetchFloat($variableName, $inputValue, $options);
        }
        else if($flags & BOOL || $flags & BOOLEAN)
        {
            $inputValue = $this->fetchBoolean($variableName, $inputValue, $flags);
        }
        else if($flags & EMAIL)
        {
            $inputValue = $this->fetchEmail($variableName, $inputValue);
        }
        else if($flags & URI)
        {
            $inputValue = $this->fetchURI($variableName, $inputValue, $options, $flags, $isOptional);
        }
        else if($flags & IP || $flags & IP_ADDRESS)
        {
            $inputValue = $this->fetchIPAddress($variableName, $inputValue);
        }
        else if($flags & JSON)
        {
            $inputValue = $this->fetchJSON($variableName, $inputValue);
        }
        else if($flags & UUID4)
        {
            $inputValue = $this->fetchUUID4($variableName, $inputValue);
        }
        else if($flags & FILE)
        {
            $inputValue = $this->fetchFile($variableName, $inputValue, $options);
        }
        else
        {
            throw new \InvalidArgumentException("Fetch data type flag is missing for variable name '$variableName'");
        }

        return $inputValue;
    }

    /**
     * __call
     * Make fetch callable as both object/static call
     */
    public function __call($name, $arguments)
    {
        if($name === "fetch")
            return call_user_func_array(array($this, "fetchVar"), $arguments);
        else if($name === "hasVar")
            return call_user_func_array(array($this, "hasVar"), $arguments);
    }

    /**
     * __callStatic
     * Make fetch callable as both object/static call
     */
    public static function __callStatic($name, $arguments)
    {
        if(in_array($name, ["fetch","hasVar"]))
        {
            // Check if filter input options are provided
            $flags = isset($arguments[0]) ? $arguments[0] : null;
            $variableName = isset($arguments[1]) ? $arguments[1] : null;
            $options = isset($arguments[2]) ? $arguments[2] : null;
            $inputValue = isset($arguments[3]) ? $arguments[3] : null;
            $isOptional = isset($arguments[4]) ? $arguments[4] : false;
            $filterInputOptions = isset($arguments[5]) ? $arguments[5] : array();

            // Create object instance and execute fetch
            $filterInput = new FilterInput($filterInputOptions);

            // Return 
            if($name === "fetch")
                return $filterInput->fetch($flags, $variableName, $options, $inputValue, $isOptional);
            else if($name === "hasVar")
                return $filterInput->hasVar($flags, $variableName);
        }
    }

    /**
     * getDataType
     */
    public static function getDataType($input)
    {
        if(is_string($input))
        {
            switch($input)
            {
                case \preg_match("/STRING/i", $input) ? true : false:
                    return "STRING";
                case \preg_match("/INTEGER|INT/i", $input) ? true : false:
                    return "INT";
                case \preg_match("/FLOAT|DOUBLE/i", $input) ? true : false:
                    return "FLOAT";
                case \preg_match("/BOOLEAN|BOOL/i", $input) ? true : false:
                    return "BOOL";
                case \preg_match("/EMAIL/i", $input) ? true : false:
                    return "EMAIL";
                case \preg_match("/URI/i", $input) ? true : false:
                    return "URI";
                case \preg_match("/IP_ADDRESS|IP/i", $input) ? true : false:
                    return "IP";
                case \preg_match("/JSON/i", $input) ? true : false:
                    return "JSON";
                case \preg_match("/UUID4/i", $input) ? true : false:
                    return "UUID4";
                default:
                    throw new \Exception(sprintf("Could not get filter input type using string '%s'", $input));
            }
        }
        else if(is_int($input))
        {
            switch($input)
            {
                case $input == STRING:
                    return "STRING";
                case $input == INT || $input == INTEGER:
                    return "INT";
                case $input == FLOAT || $input == DOUBLE:
                    return "FLOAT";
                case $input == BOOLEAN || $input == BOOL:
                    return "BOOL";
                case $input == EMAIL:
                    return "EMAIL";
                case $input == URI;
                    return "URI";
                case $input == IP_ADDRESS || $input == IP:
                    return "IP";
                case $input == JSON:
                    return "JSON";
                case $input == UUID4:
                    return "UUID4";
                case $input == FILE:
                    return "FILE";
                default:
                    throw new \Exception(sprintf("Could not get filter input type using int '%s'", $input));
            }
        }
    }
}