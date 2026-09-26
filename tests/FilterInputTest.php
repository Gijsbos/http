<?php
declare(strict_types=1);

namespace gijsbos\Http\Utils;

use gijsbos\Http\Exceptions\InvalidArgumentInputException;
use gijsbos\Http\Exceptions\InvalidArgumentMissingException;
use gijsbos\Http\Exceptions\InvalidArgumentTypeException;
use gijsbos\Http\ExceptionsInvalidArgumentInputException;
use gijsbos\Http\Http\HTTPRequest;
use gijsbos\Http\RequestMethod;
use PHPUnit\Framework\TestCase;

final class FilterInputTest extends TestCase 
{
    public static $input;

    public static function setUpBeforeClass() : void 
    {
        self::$input = new FilterInput();
    }

    protected function setUp() : void
    {
        // Clear GET for FilterInput tests
        $_GET = [];
    }

    public function testHasVar()
    {
        // Create FilterInput
        $filterInput = new FilterInput([
            "allowGlobalGETAccess" => true,
        ]);

        // Set name in GET
        $_GET["name"] = "John";
        $result = $filterInput->hasVar(GET, "name");
        $this->assertTrue($result);
    }

    /**
     * null value will return false (not set)
     */
    public function testHasVarNullFalse()
    {
        // Create FilterInput
        $filterInput = new FilterInput([
            "allowGlobalGETAccess" => true,
        ]);

        // Set name in GET
        $_GET["name"] = null;
        $result = $filterInput->hasVar(GET, "name");
        $this->assertFalse($result);
    }

    public function testHasVarFalseNotSet()
    {
        // Create FilterInput
        $filterInput = new FilterInput([
            "allowGlobalGETAccess" => true,
        ]);

        // Set name in GET
        $result = $filterInput->hasVar(GET, "notset");
        $this->assertFalse($result);
    }

    public function testHasVarFalse()
    {
        // Create FilterInput
        $filterInput = new FilterInput([
            "allowGlobalGETAccess" => true,
        ]);

        // Set name in GET
        $_GET["name"] = "John";
        $result = $filterInput->hasVar(GET, "surname");
        $this->assertFalse($result);
    }

    public function testHasVarInputValue()
    {
        // Create FilterInput
        $filterInput = new FilterInput([
            "allowGlobalGETAccess" => true,
        ]);

        // Set name in GET
        $result = $filterInput->hasVar(GET, "name", "John");
        $this->assertTrue($result);
    }

    public function testHasVarInputValueArrayTrue()
    {
        // Create FilterInput
        $filterInput = new FilterInput([
            "allowGlobalGETAccess" => true,
        ]);

        // Set name in GET
        $result = $filterInput->hasVar(GET, "name", ["name" => "john"]);
        $this->assertTrue($result);
    }

    public function testHasVarInputValueArrayFalse()
    {
        // Create FilterInput
        $filterInput = new FilterInput([
            "allowGlobalGETAccess" => true,
        ]);

        // Set name in GET
        $result = $filterInput->hasVar(GET, "name", ["surname" => "john"]);
        $this->assertFalse($result);
    }

    public function testFetchNoDataTypeSet()
    {
        $this->expectException(\InvalidArgumentException::class);
        $variable1 = "this is an example input";
        $result = self::$input->fetch(0, "variable1", "/^[a-zA-Z ]+$/", $variable1, false);
    }

    public function testFetchGetInputValueArrayInputValueSuccess()
    {
        // Create FilterInput
        $filterInput = new FilterInput([
            "allowGlobalGETAccess" => true,
        ]);

        // Set name in GET
        $result = $filterInput->fetch(GET | STRING, "name", null, ["name" => "john"]);
        $expectedResult = "john";
        $this->assertEquals($expectedResult, $result);
    }

    public function testFetchGetInputValueArrayInputValueFalse()
    {
        $this->expectExceptionMessage("Argument 'name' is missing");
        
        // Create FilterInput
        $filterInput = new FilterInput([
            "allowGlobalGETAccess" => true,
        ]);

        // Set name in GET
        $result = $filterInput->fetch(GET | STRING, "name", null, []);
        $expectedResult = "john";
        $this->assertEquals($expectedResult, $result);
    }

    public function testFetchGetInputValueArrayInputOrFalseSuccess()
    {
        // Create FilterInput
        $filterInput = new FilterInput([
            "allowGlobalGETAccess" => true,
        ]);

        // Set name in GET
        $result = $filterInput->fetch(GET | STRING | FALSE_IF_EMPTY, "name", null, ["name" => "john"]);
        $expectedResult = "john";
        $this->assertEquals($expectedResult, $result);
    }

    public function testFetchGetInputValueArrayInputOrFalseFailure()
    {
        // Create FilterInput
        $filterInput = new FilterInput([
            "allowGlobalGETAccess" => true,
        ]);

        // Set name in GET
        $result = $filterInput->fetch(GET | STRING | FALSE_IF_EMPTY, "name", null, []);
        $expectedResult = false;
        $this->assertEquals($expectedResult, $result);
    }

    public function testFetchGetInputValueSuccess()
    {
        // Create FilterInput
        $filterInput = new FilterInput([
            "allowGlobalGETAccess" => true,
        ]);

        // Set name in GET
        $result = $filterInput->fetch(GET | STRING, "name", null, "john");
        $expectedResult = "john";
        $this->assertEquals($expectedResult, $result);
    }

    public function testFetchGetInputValueOrFalseSuccess()
    {
        // Create FilterInput
        $filterInput = new FilterInput([
            "allowGlobalGETAccess" => true,
        ]);

        // Set name in GET
        $result = $filterInput->fetch(GET | STRING | FALSE_IF_EMPTY, "name", null, "john");
        $expectedResult = "john";
        $this->assertEquals($expectedResult, $result);
    }

    public function testFetchGetInputValueOrFalseFailure()
    {
        // Create FilterInput
        $filterInput = new FilterInput([
            "allowGlobalGETAccess" => true,
        ]);

        // Set name in GET
        $result = $filterInput->fetch(GET | STRING | FALSE_IF_EMPTY, "name", null, null);
        $expectedResult = false;
        $this->assertEquals($expectedResult, $result);
    }

    public function testFetchGetGlobalInputSuccess()
    {
        // Create FilterInput
        $filterInput = new FilterInput([
            "allowGlobalGETAccess" => true,
        ]);

        // Get john
        $_GET["name"] = "john";

        // Set name in GET
        $result = $filterInput->fetch(GET | STRING, "name");
        $expectedResult = "john";
        $this->assertEquals($expectedResult, $result);
    }

    public function testFetchGetGlobalInputFalse()
    {
        $this->expectExceptionMessage("Argument 'name' is missing");

        // Create FilterInput
        $filterInput = new FilterInput([
            "allowGlobalGETAccess" => true,
        ]);

        // Get john
        $_GET["surname"] = "john";

        // Set name in GET
        $result = $filterInput->fetch(GET | STRING, "name");
        $expectedResult = "john";
        $this->assertEquals($expectedResult, $result);
    }

    public function testFetchGetGlobalInputOrFalseSuccess()
    {
        // Create FilterInput
        $filterInput = new FilterInput([
            "allowGlobalGETAccess" => true,
        ]);

        // Get john
        $_GET["name"] = "john";

        // Set name in GET
        $result = $filterInput->fetch(GET | STRING | FALSE_IF_EMPTY, "name");
        $expectedResult = "john";
        $this->assertEquals($expectedResult, $result);
    }

    public function testFetchGetGlobalInputOrFalseFailure()
    {
        // Create FilterInput
        $filterInput = new FilterInput([
            "allowGlobalGETAccess" => true,
        ]);

        // Get john
        $_GET["surname"] = "john";

        // Set name in GET
        $result = $filterInput->fetch(GET | STRING | FALSE_IF_EMPTY, "name");
        $expectedResult = false;
        $this->assertEquals($expectedResult, $result);
    }
    
    public function testFetchString()
    {
        $variable1 = "this is an example input";
        $result = self::$input->fetch(STRING, "variable1", null, $variable1, false);
        $this->assertEquals($variable1, $result);
    }

    public function testFetchStringStatic()
    {
        $variable1 = "this is an example input";
        $result = Filterinput::fetch(STRING, "variable1", null, $variable1, false);
        $this->assertEquals($variable1, $result);
    }

    public function testFetchStringIntToString()
    {
        $variable1 = 1;
        $result = self::$input->fetch(STRING, "variable1", null, $variable1, false);
        $this->assertEquals($variable1, $result);
    }

    public function testFetchStringRegexpSuccess()
    {
        $variable1 = "this is an example input";
        $result = self::$input->fetch(STRING, "variable1", "/^[a-zA-Z ]+$/", $variable1, false);
        $this->assertEquals($variable1, $result);
    }

    public function testFetchStringRegexpFailure()
    {
        $this->expectException(InvalidArgumentInputException::class);
        $variable1 = "this is an example input";
        $result = self::$input->fetch(STRING, "variable1", "/^[a-zA-Z]+$/", $variable1, false);
    }

    public function testFetchStringArrayOptionsSuccess()
    {
        $variable1 = "int";
        $options = array("integer","int");
        $result = self::$input->fetch(STRING, "variable1", $options, $variable1, false);
        $this->assertEquals($variable1, $result);
    }

    public function testFetchStringArrayOptionsSINGLE_VALUESuccess()
    {
        $variable1 = "int";
        $options = array("integer","int");
        $result = self::$input->fetch(STRING | SINGLE_VALUE, "variable1", $options, $variable1, false);
        $this->assertEquals($variable1, $result);
    }

    public function testFetchStringArrayOptionsSINGLE_VALUEFailure()
    {
        $this->expectExceptionMessage("Argument input 'int,integer' for argument 'variable1' does not meet requirement 'integer|int'");
        $variable1 = "int,integer";
        $options = array("integer","int");
        $result = self::$input->fetch(STRING | SINGLE_VALUE, "variable1", $options, $variable1, false);
    }

    public function testFetchStringArrayOptionsMULTI_VALUESuccess()
    {
        $variable1 = "int,integer";
        $options = array("integer","int");
        $result = self::$input->fetch(STRING | MULTI_VALUE, "variable1", $options, $variable1, false);
        $this->assertEquals($variable1, $result);
    }

    public function testFetchStringArrayOptionsMULTI_VALUEFailure()
    {
        $this->expectExceptionMessage("Argument input 'int,intager' for argument 'variable1' does not meet requirement 'integer|int'");
        $variable1 = "int,intager";
        $options = array("integer","int");
        $result = self::$input->fetch(STRING | MULTI_VALUE, "variable1", $options, $variable1, false);
    }

    public function testFetchStringArrayOptionsFailure()
    {
        $this->expectExceptionMessage("Argument input 'string' for argument 'variable1' does not meet requirement 'integer|int'");
        $variable1 = "string";
        $options = array("integer","int");
        $result = self::$input->fetch(STRING, "variable1", $options, $variable1, false);
        $this->assertEquals($variable1, $result);
    }

    public function testFetchStringRegexpOptionsArray()
    {
        $this->expectException(InvalidArgumentInputException::class);
        $variable1 = "this is an example input";
        $result = self::$input->fetch(STRING, "variable1", array("options" => array("regexp" => "/^[a-zA-Z]+$/")), $variable1, false);
    }

    public function testFetchStringNullValueMissing()
    {
        $this->expectException(InvalidArgumentMissingException::class);
        $variable1 = null;
        $result = self::$input->fetch(STRING, "variable1", "/^[a-zA-Z ]+$/", $variable1, false);
        $this->assertEquals($variable1, $result);
    }

    public function testFetchStringNullValueNotMissing()
    {
        $variable1 = null;
        $result = self::$input->fetch(STRING, "variable1", "/^[a-zA-Z ]+$/", $variable1, true);
        $this->assertEquals($variable1, $result);
    }

    public function testFetchInt()
    {
        $variable1 = 1;
        $result = self::$input->fetch(INT, "variable1", null, $variable1, false);
        $this->assertEquals($variable1, $result);
    }

    public function testFetchInteger()
    {
        $variable1 = 1;
        $result = self::$input->fetch(INTEGER, "variable1", null, $variable1, false);
        $this->assertEquals($variable1, $result);
    }

    public function testFetchIntInvalidType()
    {
        $this->expectException(InvalidArgumentTypeException::class);
        $variable1 = 1.5;
        $result = self::$input->fetch(INT, "variable1", array("max_range" => 0), $variable1, false);
    }

    public function testFetchIntInvalidInputRangeMinRangeFailure()
    {
        $this->expectException(InvalidArgumentInputException::class);
        $variable1 = 1;
        $result = self::$input->fetch(INT, "variable1", array("min_range" => 2), $variable1, false);
    }

    public function testFetchIntInvalidInputRangeMaxRangeFailure()
    {
        $this->expectException(InvalidArgumentInputException::class);
        $variable1 = 1;
        $result = self::$input->fetch(INT, "variable1", array("max_range" => 0), $variable1, false);
    }

    public function testFetchIntNullValueMissing()
    {
        $this->expectException(InvalidArgumentMissingException::class);
        $variable1 = null;
        $result = self::$input->fetch(INT, "variable1", null, $variable1, false);
    }

    public function testFetchIntNullValueNotMissing()
    {
        $variable1 = null;
        $result = self::$input->fetch(INT, "variable1", null, $variable1, true);
        $this->assertEquals($variable1, $result);
    }

    public function testFetchFloat()
    {
        $variable1 = 1.1;
        $result = self::$input->fetch(FLOAT, "variable1", null, $variable1, false);
        $this->assertEquals($variable1, $result);
    }

    public function testFetchFloatStringDot()
    {
        $variable1 = "1.1";
        $result = self::$input->fetch(FLOAT, "variable1", null, $variable1, false);
        $this->assertEquals($variable1, $result);
    }

    public function testFetchFloatStringComma()
    {
        $variable1 = "1,1";
        $result = self::$input->fetch(FLOAT, "variable1", null, $variable1, false);
        $expectedResult = 1.1;
        $this->assertEquals($expectedResult, $result);
    }

    public function testFetchDouble()
    {
        $variable1 = 1.1;
        $result = self::$input->fetch(DOUBLE, "variable1", null, $variable1, false);
        $this->assertEquals($variable1, $result);
    }

    public function testFetchFloatIntegerType()
    {
        $variable1 = 1;
        $result = self::$input->fetch(FLOAT, "variable1", array("min_range" => 0), $variable1, false);
        $this->assertEquals($variable1, $result);
    }

    public function testFetchFloatInvalidType()
    {
        $this->expectException(InvalidArgumentTypeException::class);
        $variable1 = 'not float';
        $result = self::$input->fetch(FLOAT, "variable1", array("min_range" => 2), $variable1, false);
    }

    public function testFetchFloatInvalidInputRangeMinRangeFailure()
    {
        $this->expectException(InvalidArgumentInputException::class);
        $variable1 = 1;
        $result = self::$input->fetch(FLOAT, "variable1", array("min_range" => 2), $variable1, false);
    }

    public function testFetchFloatInvalidInputRangeMaxRangeFailure()
    {
        $this->expectException(InvalidArgumentInputException::class);
        $variable1 = 1;
        $result = self::$input->fetch(FLOAT, "variable1", array("max_range" => 0), $variable1, false);
    }

    public function testFetchFloatNullValueMissing()
    {
        $this->expectException(InvalidArgumentMissingException::class);
        $variable1 = null;
        $result = self::$input->fetch(FLOAT, "variable1", null, $variable1, false);
    }

    public function testFetchFloatNullValueNotMissing()
    {
        $variable1 = null;
        $result = self::$input->fetch(FLOAT, "variable1", null, $variable1, true);
        $this->assertEquals($variable1, $result);
    }

    public function testFetchBool()
    {
        $variable1 = 1;
        $result = self::$input->fetch(BOOL, "variable1", null, $variable1, false);
        $this->assertTrue($result);
    }

    public function testFetchBoolean()
    {
        $variable1 = 1;
        $result = self::$input->fetch(BOOLEAN, "variable1", null, $variable1, false);
        $this->assertTrue($result);
    }

    public function testFetchBooleanBooleanValue()
    {
        $variable1 = 1;
        $value = self::$input->fetch(BOOLEAN, "variable1", null, $variable1, false);
        $result = is_bool($value);
        $this->assertTrue($result);
    }

    public function testFetchBooleanOrFalseInt()
    {
        $variable1 = 1;
        $value = self::$input->fetch(BOOLEAN | FALSE_IF_EMPTY, "variable1", null, $variable1, false);
        $result = is_int($value);
        $this->assertTrue($result);
    }

    public function testFetchBoolInvalidType()
    {
        $this->expectException(InvalidArgumentTypeException::class);
        $variable1 = 'not a bool';
        $result = self::$input->fetch(BOOL, "variable1", null, $variable1, false);
    }

    public function testFetchBooleanNullValueMissing()
    {
        $this->expectException(InvalidArgumentMissingException::class);
        $variable1 = null;
        $result = self::$input->fetch(BOOLEAN, "variable1", null, $variable1, false);
    }

    public function testFetchBooleanNullValueNotMissing()
    {
        $variable1 = null;
        $result = self::$input->fetch(BOOLEAN, "variable1", null, $variable1, true);
        $this->assertEquals($variable1, $result);
    }

    public function testFetchEmail()
    {
        $variable1 = "john@example.com";
        $result = self::$input->fetch(EMAIL, "variable1", null, $variable1, false);
        $this->assertEquals($variable1, $result);
    }

    public function testFetchEmailInvalidType()
    {
        $this->expectException(InvalidArgumentTypeException::class);
        $variable1 = "john@example";
        $result = self::$input->fetch(EMAIL, "variable1", null, $variable1, false);
    }

    public function testFetchEmailNullValueMissing()
    {
        $this->expectException(InvalidArgumentMissingException::class);
        $variable1 = null;
        $result = self::$input->fetch(EMAIL, "variable1", null, $variable1, false);
    }

    public function testFetchEmailNullValueNotMissing()
    {
        $variable1 = null;
        $result = self::$input->fetch(EMAIL, "variable1", null, $variable1, true);
        $this->assertEquals($variable1, $result);
    }

    public function testFetchURI()
    {
        $variable1 = "http://www.example.com/";
        $result = self::$input->fetch(URI, "variable1", null, $variable1, false);
        $this->assertEquals($variable1, $result);
    }

    public function testFetchURIInvalidType()
    {
        $this->expectException(InvalidArgumentTypeException::class);
        $variable1 = "example.com/";
        $result = self::$input->fetch(URI, "variable1", null, $variable1, false);
    }

    public function testFetchURINullValueMissing()
    {
        $this->expectException(InvalidArgumentMissingException::class);
        $variable1 = null;
        $result = self::$input->fetch(URI, "variable1", null, $variable1, false);
    }

    public function testFetchURINullValueNotMissing()
    {
        $variable1 = null;
        $result = self::$input->fetch(URI, "variable1", null, $variable1, true);
        $this->assertEquals($variable1, $result);
    }

    public function testFetchURIRegexpOptionSuccess()
    {
        $variable1 = "http://www.example.com/";
        $result = self::$input->fetch(URI, "variable1", "/^.{1,256}$/", $variable1, false);
        $this->assertEquals($variable1, $result);
    }

    public function testFetchURIRegexpOptionFailure()
    {
        $this->expectException(InvalidArgumentInputException::class);
        $variable1 = "http://www.example.com/";
        $result = self::$input->fetch(URI, "variable1", "/^.{1,2}$/", $variable1, false);
    }

    public function testFetchIP()
    {
        $variable1 = "1.1.1.1";
        $result = self::$input->fetch(IP, "variable1", null, $variable1, false);
        $this->assertEquals($variable1, $result);
    }

    public function testFetchIPAddress()
    {
        $variable1 = "1.1.1.1";
        $result = self::$input->fetch(IP_ADDRESS, "variable1", null, $variable1, false);
        $this->assertEquals($variable1, $result);
    }

    public function testFetchIPInvalidType()
    {
        $this->expectException(InvalidArgumentTypeException::class);
        $variable1 = "not an ip address";
        $result = self::$input->fetch(IP, "variable1", null, $variable1, false);
    }

    public function testFetchIPAddressNullValueMissing()
    {
        $this->expectException(InvalidArgumentMissingException::class);
        $variable1 = null;
        $result = self::$input->fetch(IP_ADDRESS, "variable1", null, $variable1, false);
    }

    public function testFetchIPAddressNullValueNotMissing()
    {
        $variable1 = null;
        $result = self::$input->fetch(IP_ADDRESS, "variable1", null, $variable1, true);
        $this->assertEquals($variable1, $result);
    }

    public function testFetchJSON()
    {
        $args = [
            "variable1" => $variable1 = array(
                "item" => "value"
            )
        ];
        $result = self::$input->fetch(JSON, "variable1", null, $args, false);
        $this->assertEquals($variable1, $result);
    }

    public function testFetchJSONInvalidTypeString()
    {
        $this->expectException(InvalidArgumentTypeException::class);
        $variable1 = "not json";
        $result = self::$input->fetch(JSON, "variable1", null, $variable1, false);
    }

    public function testFetchJSONInvalidTypeInt()
    {
        $this->expectException(InvalidArgumentTypeException::class);
        $variable1 = 1;
        $result = self::$input->fetch(JSON, "variable1", null, $variable1, false);
    }

    public function testFetchJSONInvalidTypeBool()
    {
        $this->expectException(InvalidArgumentTypeException::class);
        $variable1 = true;
        $result = self::$input->fetch(JSON, "variable1", null, $variable1, false);
    }

    public function testFetchJSONNullValueMissing()
    {
        $this->expectException(InvalidArgumentMissingException::class);
        $variable1 = null;
        $result = self::$input->fetch(JSON, "variable1", null, $variable1, false);
    }

    public function testFetchJSONNullValueNotMissing()
    {
        $variable1 = null;
        $result = self::$input->fetch(JSON, "variable1", null, $variable1, true);
        $this->assertEquals($variable1, $result);
    }

    public function testFetchUUID4()
    {
        $variable1 = uuid4();
        $result = self::$input->fetch(UUID4, "variable1", null, $variable1, false);
        $this->assertEquals($variable1, $result);
    }

    public function testFetchUUID4InvalidTypeString()
    {
        $this->expectException(InvalidArgumentTypeException::class);
        $variable1 = "not uuid4";
        $result = self::$input->fetch(UUID4, "variable1", null, $variable1, false);
    }

    public function testFetchUUID4InvalidTypeInt()
    {
        $this->expectException(InvalidArgumentTypeException::class);
        $variable1 = 1;
        $result = self::$input->fetch(UUID4, "variable1", null, $variable1, false);
    }

    public function testFetchUUID4InvalidTypeBool()
    {
        $this->expectException(InvalidArgumentTypeException::class);
        $variable1 = true;
        $result = self::$input->fetch(UUID4, "variable1", null, $variable1, false);
    }

    public function testFetchUUID4NullValueMissing()
    {
        $this->expectException(InvalidArgumentMissingException::class);
        $variable1 = null;
        $result = self::$input->fetch(UUID4, "variable1", null, $variable1, false);
    }

    public function testFetchUUID4NullValueNotMissing()
    {
        $variable1 = null;
        $result = self::$input->fetch(UUID4, "variable1", null, $variable1, true);
        $this->assertEquals($variable1, $result);
    }

    public function testFetchFile()
    {
        $filePath = "./tests/http/upload/file-upload.txt";
        $result = self::$input->fetch(FILE, "inputFile", ['maxSize' => FileUploadHandler::MB, 'mimeTypes' => [FileUploadHandler::MIME_TXT]], $filePath, false);
        $expectedResult = [
            "fileName" => "inputFile",
            "fileExtension" => "txt",
            "fileSize" => 10,
            "filePath" => "./tests/http/upload/file-upload.txt",
            "MIME" => "text/plain",
            "sha256" => "236f6fa6e8a7c6307437e83b0781a0a3f5f83c03695552b1e5504e2f8cea1030"
        ];
        $this->assertEquals($expectedResult, $result);
    }

    public function testFetchFileOptional()
    {
        $result = self::$input->fetch(FILE, "inputFile", ['maxSize' => FileUploadHandler::MB, 'mimeTypes' => [FileUploadHandler::MIME_TXT]], null, true);
        $expectedResult = null;
        $this->assertEquals($expectedResult, $result);
    }

    public function testFetchFileMaxSizeMissing()
    {
        $this->expectExceptionMessage("Argument 'inputFileMaxSize' is missing");

        $filePath = "./tests/http/upload/file-upload.txt";
        $result = self::$input->fetch(FILE, "inputFile", ['mimeTypes' => [FileUploadHandler::MIME_TXT]], $filePath, false);
    }

    public function testFetchFileMaxSizeInvalid()
    {
        $this->expectExceptionMessage("Argument type for argument 'inputFileMaxSize' is incorrect, received 'string', expected 'int' using value 'text'");

        $filePath = "./tests/http/upload/file-upload.txt";
        $result = self::$input->fetch(FILE, "inputFile", ['maxSize' => 'text', 'mimeTypes' => [FileUploadHandler::MIME_TXT]], $filePath, false);
    }

    public function testFetchFileMimeTypesString()
    {
        $filePath = "./tests/http/upload/file-upload.txt";
        $result = self::$input->fetch(FILE, "inputFile", ['maxSize' => FileUploadHandler::MB, 'mimeTypes' => FileUploadHandler::MIME_TXT], $filePath, false);
        $this->assertTrue(is_array($result));
    }

    public function testFetchFileMimeTypesArray()
    {
        $filePath = "./tests/http/upload/file-upload.txt";
        $result = self::$input->fetch(FILE, "inputFile", ['maxSize' => FileUploadHandler::MB, 'mimeTypes' => [FileUploadHandler::MIME_TXT]], $filePath, false);
        $this->assertTrue(is_array($result));
    }

    public function testFetchFileMimeTypesMissing()
    {
        $this->expectExceptionMessage("Argument 'inputFileMimeTypes' is missing");

        $filePath = "./tests/http/upload/file-upload.txt";
        $result = self::$input->fetch(FILE, "inputFile", ['maxSize' => FileUploadHandler::MB], $filePath, false);
    }

    public function testFetchFileFileSizeExceeded()
    {
        $this->expectExceptionMessage("Exceeded file size limit '1B', received '10B'");

        $filePath = "./tests/http/upload/file-upload.txt";
        $result = self::$input->fetch(FILE, "inputFile", ['maxSize' => 1, 'mimeTypes' => [FileUploadHandler::MIME_TXT]], $filePath, false);
    }

    public function testFetchFileInvalidMimeTypeInput()
    {
        $this->expectExceptionMessage("Invalid file format 'text/plain'");

        $filePath = "./tests/http/upload/file-upload.txt";
        $result = self::$input->fetch(FILE, "inputFile", ['maxSize' => FileUploadHandler::MB, 'mimeTypes' => [FileUploadHandler::MIME_PNG]], $filePath, false);
    }

    public function testFetchFileInvalidMimeTypeType()
    {
        $this->expectExceptionMessage("Argument type for argument 'inputFileMimeTypes' is incorrect, received 'string', expected 'string|array' using value 'text'");

        $filePath = "./tests/http/upload/file-upload.txt";
        $result = self::$input->fetch(FILE, "inputFile", ['maxSize' => FileUploadHandler::MB, 'mimeTypes' => 'text'], $filePath, false);
    }

    public function testFetchStringFromGETGlobal() 
    {
        $uri = "http://localhost/http/tests/http/HTTPTestController.php";
        $returnValue = json_encode(array(
            "test" => $input = "input value"
        ));
        $response = HTTPRequest::call(array(
            "type" => RequestMethod::GET,
            "uri" => $uri,
            "data" => $data = array(
                "returnValue" => $returnValue
            )
        ));
        $result = $response->getParameter("test");
        $this->assertEquals($input, $result);
    }

    public function testFetchStringFromPOSTGlobal() 
    {
        $uri = "http://localhost/http/tests/http/HTTPTestController.php";
        $data = json_encode([
            'returnValue' => 'value'
        ]);
        $response = HTTPRequest::call(array(
            "type" => RequestMethod::POST,
            "uri" => $uri,
            "data" => array(
                "returnValue" => $data
            ),
        ));
        $result = $response->getParameter("returnValue");
        $this->assertEquals('value', $result);
    }

    public function testFetchStringFromPUTGlobal() 
    {
        $uri = "http://localhost/http/tests/http/HTTPTestController.php";
        $data = json_encode([
            'returnValue' => 'value'
        ]);
        $response = HTTPRequest::call(array(
            "type" => RequestMethod::PUT,
            "uri" => $uri,
            "data" => array(
                "returnValue" => $data
            ),
        ));

        $result = $response->getParameter("returnValue");
        $this->assertEquals('value', $result);
    }

    public function testFetchStringFromDELETEGlobal() 
    {
        $uri = "http://localhost/http/tests/http/HTTPTestController.php";
        $returnValue = json_encode(array(
            "test" => $input = "input value"
        ));
        $response = HTTPRequest::call(array(
            "type" => RequestMethod::DELETE,
            "uri" => $uri,
            "data" => $data = array(
                "returnValue" => $returnValue
            )
        ));
        $result = $response->getParameter("test");
        $this->assertEquals($input, $result);
    }

    public function testThrowExceptionsOnFailureFalse()
    {
        $filterInput = new FilterInput(array(
            "throwExceptionOnFailure" => false
        ));
        $variable1 = "@";
        $result = $filterInput->fetch(STRING, "variable1", "/^[a-zA-Z]+$/", $variable1, false);
        $this->assertEquals($variable1, $result);
    }

    public function testAllowGlobalGetAccess()
    {
        $filterInput = new FilterInput(array(
            "allowGlobalGETAccess" => true
        ));
        $variable1 = $_GET["variable1"] = "global access success";
        $result = $filterInput->fetch(STRING | GET, "variable1", "/^[a-zA-Z ]+$/");
        $this->assertEquals($variable1, $result);
    }

    # @bugfix object input causing problems when outputting format in predetermined types
    public function testInsertObject()
    {
        $this->expectExceptionMessage("Invalid input value for argument 'variable1' using type 'DateTime'");
        $result = self::$input->fetch(STRING, "variable1", "/^[a-zA-Z ]+$/", new \DateTime());
    }

    /**
     * createWithBody
     *  Creates a FilterInput reading a request body with the given content type
     */
    private function createWithBody(string $contentType, string $body) : FilterInput
    {
        $_SERVER["CONTENT_TYPE"] = $contentType;

        try
        {
            return new class($body) extends FilterInput
            {
                private $body;
                public function __construct(string $body) { $this->body = $body; parent::__construct(); }
                public function getPHPInput() { return $this->body; }
            };
        }
        finally
        {
            unset($_SERVER["CONTENT_TYPE"]);
        }
    }

    public function testFetchFromJSONBody()
    {
        $filterInput = $this->createWithBody("application/json", '{"name":"John","age":"30"}');
        $_SERVER["CONTENT_TYPE"] = "application/json";
        $this->assertEquals("John", $filterInput->fetch(STRING | POST, "name"));
        $this->assertEquals(30, $filterInput->fetch(INT | PUT, "age"));
        unset($_SERVER["CONTENT_TYPE"]);
    }

    public function testFetchMultipleVariableNames()
    {
        $this->assertEquals("b", self::$input->fetch(STRING, "first,second", null, ["second" => "b"]));
    }

    public function testFetchMissingReportsFirstVariableName()
    {
        $this->expectExceptionMessage("Argument 'first' is missing");
        self::$input->fetch(STRING, "first,second", null, ["other" => "b"]);
    }

    public function testFetchStringToLowerAndUpperCase()
    {
        $this->assertEquals("abc", self::$input->fetch(STRING | TO_LOWERCASE, "v", null, "AbC"));
        $this->assertEquals("ABC", self::$input->fetch(STRING | TO_UPPERCASE, "v", null, "AbC"));
    }

    public function testFetchStringArrayOptionsCaseInsensitive()
    {
        $this->assertEquals("Active", self::$input->fetch(STRING | SINGLE_VALUE, "v", ["active", "inactive"], "Active"));
    }

    public function testFetchIntEmptyStringIsMissing()
    {
        $this->expectException(InvalidArgumentMissingException::class);
        self::$input->fetch(INT, "v", null, "");
    }

    public function testFetchIntNegative()
    {
        $this->assertSame(-5, self::$input->fetch(INT, "v", null, "-5"));
    }

    public function testFetchIntWithinRange()
    {
        $this->assertSame(5, self::$input->fetch(INT, "v", ["min_range" => 1, "max_range" => 10], "5"));
    }

    public function testFetchFloatWithinRange()
    {
        $this->assertSame(1.5, self::$input->fetch(FLOAT, "v", ["min_range" => 1, "max_range" => 2], "1,5"));
    }

    public function testFetchBooleanStringFalse()
    {
        $this->assertFalse(self::$input->fetch(BOOL, "v", null, "false"));
        $this->assertFalse(self::$input->fetch(BOOL, "v", null, "0"));
        $this->assertTrue(self::$input->fetch(BOOL, "v", null, "1"));
        $this->assertTrue(self::$input->fetch(BOOL, "v", null, true));
    }

    public function testFetchEmailIsLowercased()
    {
        $this->assertEquals("john@example.com", self::$input->fetch(EMAIL, "v", null, "John@Example.com"));
    }

    public function testFetchIPv6()
    {
        $this->assertEquals("::1", self::$input->fetch(IP, "v", null, "::1"));
    }

    public function testFetchInvalidIP()
    {
        $this->expectException(InvalidArgumentTypeException::class);
        self::$input->fetch(IP, "v", null, "999.1.1.1");
    }

    public function testFetchJSONArrayInput()
    {
        $this->assertEquals(["a" => 1], self::$input->fetch(JSON, "v", null, ["v" => ["a" => 1]]));
    }

    public function testFetchUUID4Uppercase()
    {
        // UUIDs are case-insensitive
        $this->assertEquals("f47ac10b-58cc-4372-a567-0e02b2c3d479", self::$input->fetch(UUID4, "v", null, "F47AC10B-58CC-4372-A567-0E02B2C3D479"));
    }

    public function testFetchInvalidRegexpOption()
    {
        $this->expectException(\InvalidArgumentException::class);
        self::$input->fetch(STRING, "v", "not a regexp", "value");
    }

    public function testFailedExceptionsAreCollected()
    {
        $filterInput = new FilterInput(["throwExceptionOnFailure" => false]);
        $filterInput->fetch(INT, "a", null, "x");
        $filterInput->fetch(EMAIL, "b", null, "not-an-email");
        $this->assertCount(2, $filterInput->exceptions);
    }

    public function testRegexpConstants()
    {
        $this->assertEquals(1, preg_match(FilterInput::REGEXP_DATE, "2026-09-26"));
        $this->assertEquals(1, preg_match(FilterInput::REGEXP_DATETIME, "2026-09-26 12:00:00"));
        $this->assertEquals(1, preg_match(FilterInput::REGEXP_DATE_OR_DATETIME, "2026-09-26"));
        $this->assertEquals(1, preg_match(FilterInput::REGEXP_DATE_OR_DATETIME, "2026-09-26 12:00:00"));
        $this->assertEquals(0, preg_match(FilterInput::REGEXP_DATE_OR_DATETIME, "2026-09-26T12:00:00"));
        $this->assertEquals(1, preg_match(FilterInput::REGEXP_UUID4, "f47ac10b-58cc-4372-a567-0e02b2c3d479"));
        $this->assertEquals(1, preg_match(FilterInput::REGEXP_ALPHANUMERIC_SPACES, "abc 123"));
        $this->assertEquals(0, preg_match(FilterInput::REGEXP_ALPHANUMERIC, "abc 123"));
    }

    public function testGetDataType()
    {
        $this->assertEquals("STRING", FilterInput::getDataType("string"));
        $this->assertEquals("INT", FilterInput::getDataType("integer"));
        $this->assertEquals("FLOAT", FilterInput::getDataType("double"));
        $this->assertEquals("BOOL", FilterInput::getDataType("bool"));
        $this->assertEquals("IP", FilterInput::getDataType("ip_address"));
        $this->assertEquals("INT", FilterInput::getDataType(\INTEGER));
        $this->assertEquals("FILE", FilterInput::getDataType(FILE));
    }

    public function testFetchBooleanStringTrue()
    {
        $this->assertTrue(self::$input->fetch(BOOL, "v", null, "true"));
        $this->assertTrue(self::$input->fetch(BOOL, "v", null, "TRUE"));
    }

    public function testFetchIntRejectsDecimalString()
    {
        $this->expectException(InvalidArgumentTypeException::class);
        self::$input->fetch(INT, "v", null, "1.5");
    }

    public function testOptionalValueIsStillValidated()
    {
        // An optional value that is set must still be valid
        $this->expectException(InvalidArgumentInputException::class);
        self::$input->fetch(STRING | SINGLE_VALUE, "v", ["a", "b"], "not-allowed", true);
    }

    public function testRegexpFilePathCompiles()
    {
        $this->assertEquals(1, preg_match(FilterInput::REGEXP_FILEPATH, "dir/file.txt"));
    }

    public function testRegexpSHA256OnlyAllowsHex()
    {
        $this->assertEquals(0, preg_match(FilterInput::REGEXP_SHA256_HASH, str_repeat("z", 64)));
    }

    public function testJSONContentTypeWithCharset()
    {
        $filterInput = $this->createWithBody("application/json; charset=utf-8", '{"name":"John"}');
        $this->assertEquals(["name" => "John"], $filterInput->inputStream);
    }

    public function testJSONScalarBody()
    {
        // A body that is not a JSON object/array contains no variables
        $filterInput = $this->createWithBody("application/json", '5');
        $this->assertEquals([], $filterInput->inputStream);
    }

    public function testGetDataTypeFileString()
    {
        $this->assertEquals("FILE", FilterInput::getDataType("file"));
    }

    /**
     * FileUploadHandler::convertSizeStringToBytes
     */
    public function testConvertSizeStringToBytes()
    {
        $this->assertSame(100, FileUploadHandler::convertSizeStringToBytes("100"));
        $this->assertSame(5 * FileUploadHandler::MB, FileUploadHandler::convertSizeStringToBytes("5M"));
        $this->assertSame(5 * FileUploadHandler::MB, FileUploadHandler::convertSizeStringToBytes("5MB"));
        $this->assertSame(2 * FileUploadHandler::GB, FileUploadHandler::convertSizeStringToBytes("2G"));
        $this->assertSame(FileUploadHandler::TB, FileUploadHandler::convertSizeStringToBytes("1TB"));
    }

    public function testConvertSizeStringToBytesInvalid()
    {
        $this->expectException(\InvalidArgumentException::class);
        FileUploadHandler::convertSizeStringToBytes("large");
    }

    public function testConvertSizeStringToBytesKilobytesAndLowercase()
    {
        // php.ini style values e.g. upload_max_filesize=10m
        $this->assertSame(512 * FileUploadHandler::KB, FileUploadHandler::convertSizeStringToBytes("512K"));
        $this->assertSame(512 * FileUploadHandler::KB, FileUploadHandler::convertSizeStringToBytes("512KB"));
        $this->assertSame(10 * FileUploadHandler::MB, FileUploadHandler::convertSizeStringToBytes("10m"));
    }

    public function testConvertSizeStringToBytesDecimal()
    {
        $this->assertSame((int) (1.5 * FileUploadHandler::MB), FileUploadHandler::convertSizeStringToBytes("1.5M"));
    }
}