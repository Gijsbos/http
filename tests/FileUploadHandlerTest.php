<?php
declare(strict_types=1);

namespace gijsbos\ExtFuncs\Utils;

use CURLFile;
use gijsbos\Http\Http\HTTPRequest;
use PHPUnit\Framework\TestCase;

final class FileUploadHandlerTest extends TestCase
{
    public function testIsFileArray()
    {
        $input = ["fileName" => "file.php", "fileExtension" => "php", "fileSize" => 23944, "filePath" => null, "sha256" => null, "MIME" => "text/php"];
        $result = FileUploadHandler::isFileArray($input);
        $this->assertTrue($result);
    }

    public function testIsFileArrayFalse()
    {
        $input = ["fileName" => "file.php"];
        $result = FileUploadHandler::isFileArray($input);
        $this->assertFalse($result);
    }

    public function testValidateFileArray()
    {
        $this->expectExceptionMessage("Write failed, invalid argument 1 'fileArray' does not contain values 'fileName,fileExtension,fileSize,filePath,sha256,MIME', received 'fileName'");

        $input = ["fileName" => "file.php"];
        $result = FileUploadHandler::validateFileArray($input);
    }

    public function testHandle()
    {
        // Post to FileUploadHandlerInstance
        $response = HTTPRequest::post(array(
            "uri" => "http://localhost/http/tests/http/FileUploadHandlerInstance.php",
            "data" => array(
                "upload-dir" => "./tests/http/upload/",
                "file-name" => "file-upload",
                "file-upload" => new CURLFile("./tests/http/upload/upload.txt", 'text/plain','file-upload'),
            ),
            "headers" => array(
                "Content-Type: multipart/form-data"
            ),
        ));

        // Check response
        $result = $response->getStatusCode();

        // Check result
        $this->assertEquals(200, $result);
    }

    public function testHandleFileNotReceived()
    {
        // Post to FileUploadHandlerInstance
        $response = HTTPRequest::post(array(
            "uri" => "http://localhost/http/tests/http/FileUploadHandlerInstance.php",
            "data" => array(
                "upload-dir" => "./tests/http/upload/",
                "file-name" => "file-upload",
                // "file-upload" => self::$CURLFile, <-- MISSING
            ),
            "headers" => array(
                "Content-Type: multipart/form-data"
            ),
        ));
        
        // Check response
        $result = $response->getParameter("response");
        $expectedResult = "File 'file-upload' has not been received";

        // Check result
        $this->assertEquals($expectedResult, $result);
    }

    public function testHandleFileSizeExceeded()
    {
        // Post to FileUploadHandlerInstance
        $response = HTTPRequest::post(array(
            "uri" => "http://localhost/http/tests/http/FileUploadHandlerInstance.php",
            "data" => array(
                "upload-dir" => "./tests/http/upload/",
                "file-name" => "file-upload",
                "file-upload" => new CURLFile(__FILE__, 'text/plain','file-upload'),
            ),
            "headers" => array(
                "Content-Type: multipart/form-data"
            ),
        ));
        
        // Check response
        $result = str_starts_with($response->getParameter("response"), "Exceeded file size limit '1024B', received ");

        // Check result
        $this->assertTrue($result);
    }

    public function testHandleInvalidMimeTypeCorruptFile()
    {
        // Post to FileUploadHandlerInstance
        $response = HTTPRequest::post(array(
            "uri" => "http://localhost/http/tests/http/FileUploadHandlerInstance.php",
            "data" => array(
                "upload-dir" => "./tests/http/upload/",
                "file-name" => "file-upload",
                "file-upload" => new CURLFile("./tests/http/upload/invalid-mime.png", 'text/plain','file-upload'),
            ),
            "headers" => array(
                "Content-Type: multipart/form-data"
            ),
        ));
        
        // Check response
        $result = $response->getParameter("response");
        $expectedResult = "Invalid file format 'application/x-empty'";

        // Check result
        $this->assertEquals($expectedResult, $result);
    }

    public function testHandleInvalidMimeType()
    {
        // Post to FileUploadHandlerInstance
        $response = HTTPRequest::post(array(
            "uri" => "http://localhost/http/tests/http/FileUploadHandlerInstance.php",
            "data" => array(
                "upload-dir" => "./tests/http/upload/",
                "file-name" => "file-upload",
                "file-upload" => new CURLFile("./tests/http/upload/invalid-mime.php", 'text/plain','file-upload'),
            ),
            "headers" => array(
                "Content-Type: multipart/form-data"
            ),
        ));
        
        // Check response
        $result = $response->getParameter("response");
        $expectedResult = "Invalid file format 'text/x-php'";

        // Check result
        $this->assertEquals($expectedResult, $result);
    }

    public function testHandleFailedToMove()
    {
        // Post to FileUploadHandlerInstance
        $response = HTTPRequest::post(array(
            "uri" => "http://localhost/http/tests/http/FileUploadHandlerInstance.php",
            "data" => array(
                "upload-dir" => "./tests/http/upload/doesnotexist/",
                "file-name" => "file-upload",
                "file-upload" => new CURLFile("./tests/http/upload/file-upload.txt", 'text/plain','file-upload'),
            ),
            "headers" => array(
                "Content-Type: multipart/form-data"
            ),
        ));
        
        // Check response
        $result = $response->getParameter("response");
        $expectedResult = "Could not move file to folder, folder './tests/http/upload/doesnotexist' does not exist";

        // Check result
        $this->assertEquals($expectedResult, $result);
    }

    public function testHandleMoveFunctionCopy()
    {
        // Post to FileUploadHandlerInstance
        $fileName = uniqid();

        // Read file
        FileUploadHandler::readLocal("./tests/http/upload/file-upload.txt", $fileName);

        // FileUploadHandler
        $handler = new FileUploadHandler(true, 'copy');
        
        // Read file
        $data = $handler->handle($fileName, FileUploadHandler::KB*1, [FileUploadHandler::MIME_TXT], "./tests/http/upload/", "file-upload-copy.txt");
        
        // Check 
        $result = is_file($data["filePath"]);

        // Check result
        $this->assertTrue($result);
    }

    public function testHandleMoveFunctionNotCallable()
    {
        $this->expectException(\TypeError::class);

        // Post to FileUploadHandlerInstance
        $fileName = uniqid();

        // Read file
        FileUploadHandler::readLocal("./tests/http/upload/file-upload.txt", $fileName);

        // FileUploadHandler
        new FileUploadHandler(true, []);
    }

    public function testHandleMoveFunctionNotAllowed()
    {
        $this->expectExceptionMessage("Invalid move function 'explode'");

        // Post to FileUploadHandlerInstance
        $fileName = uniqid();

        // Read file
        FileUploadHandler::readLocal("./tests/http/upload/file-upload.txt", $fileName);

        // FileUploadHandler
        $handler = new FileUploadHandler(true, 'explode');

        // Handle
        $handler->handle($fileName, FileUploadHandler::KB*1, [FileUploadHandler::MIME_TXT], "./tests/http/upload/", "file-upload-copy.txt");
    }
}