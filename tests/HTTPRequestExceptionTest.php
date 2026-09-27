<?php
declare(strict_types=1);

namespace gijsbos\Http\Exceptions;

use PHPUnit\Framework\TestCase;

final class HTTPRequestExceptionTest extends TestCase 
{
    public function testException()
    {
        $this->expectException(HTTPRequestException::class);

        // Throw exception
        throw new HTTPRequestException(400, "error", "errorDescription", array(
            "key-1" => "value-1",
            "key-2" => array(
                "key-3" => "value-2"
            )
        ));
    }

    public function testExceptionMessage()
    {
        $this->expectExceptionMessage("(400) error - errorDescription ({\"key-1\":\"value-1\",\"key-2\":{\"key-3\":\"value-2\"}})");

        // Throw exception
        throw new HTTPRequestException(400, "error", "errorDescription", array(
            "key-1" => "value-1",
            "key-2" => array(
                "key-3" => "value-2"
            )
        ));
    }

    public function testExceptionMessageWithoutDescriptionAndData()
    {
        $exception = new HTTPRequestException(400, "error");
        $this->assertEquals("(400) error -", $exception->getMessage());
        $this->assertEquals(400, $exception->getCode());
    }

    public function testGetters()
    {
        $exception = new HTTPRequestException(409, "error", "description", ["key" => "value"]);
        $this->assertEquals(409, $exception->getStatusCode());
        $this->assertEquals("error", $exception->getError());
        $this->assertEquals("description", $exception->getErrorDescription());
        $this->assertEquals(["key" => "value"], $exception->getData());
        $this->assertEquals("value", $exception->getData("key"));
        $this->assertNull($exception->getData("missing"));
    }

    /**
     * @runInSeparateProcess
     */
    public function testSendJson()
    {
        $exception = new HTTPRequestException(409, "error", "description", ["key" => "value"]);
        ob_start();
        $exception->sendJson();
        $output = ob_get_clean();
        $this->assertEquals('{"statusCode":409,"error":"error","errorDescription":"description","key":"value"}', $output);
    }

    /**
     * @runInSeparateProcess
     */
    public function testSendXml()
    {
        $exception = new HTTPRequestException(409, "error", "<b>description</b>", ["key" => "value", "list" => [1, 2], "flag" => false]);
        ob_start();
        $exception->sendXml();
        $output = ob_get_clean();
        $this->assertEquals(
            "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<root><statusCode>409</statusCode><error>error</error><errorDescription>&lt;b&gt;description&lt;/b&gt;</errorDescription><key>value</key><list><item>1</item><item>2</item></list><flag>false</flag></root>\n",
            $output
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testSendDispatchesOnFormat()
    {
        $exception = new HTTPRequestException(400, "error", "description");

        ob_start();
        $exception->send();
        $this->assertEquals('{"statusCode":400,"error":"error","errorDescription":"description"}', ob_get_clean());

        ob_start();
        $exception->send("XML");
        $this->assertStringContainsString("<root><statusCode>400</statusCode>", ob_get_clean());
    }

    public function testProblemDetails()
    {
        HTTPRequestException::$useRfc9457 = true;

        try
        {
            $exception = new HTTPRequestException(404, "routeNotFound", "Resource could not be found", ["key" => "value", "status" => 200, "type" => "x"]);

            $this->assertSame([
                "type" => "about:blank",
                "title" => "Not Found",
                "status" => 404,
                "detail" => "Resource could not be found",
                "instance" => "urn:uuid:1",
                "error" => "routeNotFound",
                "key" => "value",
            ], $exception->toArray("urn:uuid:1"));

            // Members without a value are left out, an unknown status has no title
            $this->assertSame(["type" => "about:blank", "title" => "Internal Server Error", "status" => 500], (new HTTPRequestException())->toArray());
            $this->assertSame(["type" => "about:blank", "status" => 599], (new HTTPRequestException(599))->toArray());
        }
        finally
        {
            HTTPRequestException::$useRfc9457 = false;
        }
    }

    /**
     * @runInSeparateProcess
     */
    public function testSendProblemXml()
    {
        HTTPRequestException::$useRfc9457 = true;

        $exception = new HTTPRequestException(409, "conflict", "description");
        ob_start();
        $exception->send("xml", "urn:uuid:1");
        $xml = simplexml_load_string(ob_get_clean());

        $this->assertSame("problem", $xml->getName());
        $this->assertSame(["" => "urn:ietf:rfc:7807"], $xml->getDocNamespaces());

        $members = $xml->children("urn:ietf:rfc:7807");
        $this->assertSame("Conflict", (string) $members->title);
        $this->assertSame("409", (string) $members->status);
        $this->assertSame("urn:uuid:1", (string) $members->instance);
        $this->assertSame("conflict", (string) $members->error);
    }

    public function testInstanceIsOnlyUsedForProblemDetails()
    {
        $this->assertArrayNotHasKey("instance", (new HTTPRequestException(400, "error"))->toArray("urn:uuid:1"));
    }

    public function testSendRejectsAnUnknownFormat()
    {
        $this->expectException(\InvalidArgumentException::class);
        (new HTTPRequestException(400, "error"))->send("yaml");
    }

    public static function exceptionProvider() : array
    {
        return [
            [BadRequestException::class, 400],
            [UnauthorizedException::class, 401],
            [PaymentRequiredException::class, 402],
            [ForbiddenException::class, 403],
            [ResourceNotFoundException::class, 404],
            [MethodNotAllowedException::class, 405],
            [NotAcceptableException::class, 406],
            [ProxyAuthenticationRequiredException::class, 407],
            [RequestTimeoutException::class, 408],
            [ConflictException::class, 409],
            [GoneException::class, 410],
            [LengthRequiredException::class, 411],
            [PreconditionFailedException::class, 412],
            [ContentTooLargeException::class, 413],
            [UriTooLongException::class, 414],
            [UnsupportedMediaTypeException::class, 415],
            [RangeNotSatisfiableException::class, 416],
            [ExpectationFailedException::class, 417],
            [ImATeapotException::class, 418],
            [MisdirectedRequestException::class, 421],
            [UnprocessableContentException::class, 422],
            [TooEarlyException::class, 425],
            [UpgradeRequiredException::class, 426],
            [PreconditionRequiredException::class, 428],
            [TooManyRequestsException::class, 429],
            [RequestHeaderFieldsTooLargeException::class, 431],
            [UnavailableForLegalReasonsException::class, 451],
            [HTTPRequestSentToHTTPSPortException::class, 497],
            [InternalServerErrorException::class, 500],
            [NotImplementedException::class, 501],
            [BadGatewayException::class, 502],
            [ServiceUnavailableException::class, 503],
            [GatewayTimeoutException::class, 504],
            [HTTPVersionNotSupportedException::class, 505],
        ];
    }

    /**
     * @dataProvider exceptionProvider
     */
    public function testExceptionDefaults(string $class, int $statusCode)
    {
        $exception = new $class();
        $this->assertInstanceOf(HTTPRequestException::class, $exception);
        $this->assertSame($statusCode, $exception->getStatusCode());
        $this->assertSame($statusCode, $exception->getCode());
        $this->assertMatchesRegularExpression("/^[a-z][a-zA-Z]+$/", $exception->getError());
        $this->assertNotEmpty($exception->getErrorDescription());
        $this->assertSame([], $exception->getData());
    }

    /**
     * @dataProvider exceptionProvider
     */
    public function testExceptionOverrides(string $class, int $statusCode)
    {
        $exception = new $class("customError", "Custom description", ["key" => "value"]);
        $this->assertSame($statusCode, $exception->getStatusCode());
        $this->assertSame("customError", $exception->getError());
        $this->assertSame("Custom description", $exception->getErrorDescription());
        $this->assertSame(["key" => "value"], $exception->getData());
    }

    public function testInvalidArgumentInputExceptionMessage()
    {
        $this->assertEquals("Argument input 'abc' for argument 'age' does not meet requirement 'int'", (new InvalidArgumentInputException("age", "abc", "int"))->getMessage());
        $this->assertEquals("Argument input 'true' for argument 'age' does not meet requirement 'int'", (new InvalidArgumentInputException("age", true, "int"))->getMessage());
        $this->assertEquals("Argument input 'NULL' for argument 'age' does not meet requirement 'int'", (new InvalidArgumentInputException("age", null, "int"))->getMessage());
        $this->assertEquals("Argument input for argument 'age' does not meet requirement 'int'", (new InvalidArgumentInputException("age", [], "int"))->getMessage());
    }

    public function testInvalidArgumentTypeExceptionMessage()
    {
        $this->assertEquals("Argument type for argument 'age' is incorrect, received 'string', expected 'int' using value 'abc'", (new InvalidArgumentTypeException("age", "abc", "int", "string"))->getMessage());
        $this->assertEquals("Argument type for argument 'age' is incorrect, received 'boolean', expected 'int' using value 'false'", (new InvalidArgumentTypeException("age", false, "int", "boolean"))->getMessage());
    }

    public function testInvalidArgumentMissingExceptionMessage()
    {
        $exception = new InvalidArgumentMissingException("age", null);
        $this->assertEquals("Argument 'age' is missing", $exception->getMessage());
        $this->assertEquals("age", $exception->argument);
    }

    public function testInvalidArgumentErrorException()
    {
        $exception = new InvalidArgumentErrorException("ageTooLow", "Age must be at least 18");
        $this->assertEquals("ageTooLow", $exception->error);
        $this->assertEquals("Age must be at least 18", $exception->getMessage());
    }

    public function testConstructWithoutStatusCode()
    {
        $exception = new HTTPRequestException();
        $this->assertNull($exception->getStatusCode());
    }
}