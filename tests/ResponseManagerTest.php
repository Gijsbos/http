<?php
declare(strict_types=1);

namespace WDS;

use gijsbos\Http\Exceptions\BadRequestException;
use gijsbos\Http\Exceptions\ConflictException;
use gijsbos\Http\Exceptions\ForbiddenException;
use gijsbos\Http\Exceptions\HTTPRequestException;
use gijsbos\Http\Exceptions\HTTPRequestSentToHTTPSPortException;
use gijsbos\Http\Exceptions\InternalServerErrorException;
use gijsbos\Http\Exceptions\InvalidArgumentErrorException;
use gijsbos\Http\Exceptions\InvalidArgumentInputException;
use gijsbos\Http\Exceptions\InvalidArgumentMissingException;
use gijsbos\Http\Exceptions\InvalidArgumentTypeException;
use gijsbos\Http\Exceptions\MethodNotAllowedException;
use gijsbos\Http\Exceptions\NotAcceptableException;
use gijsbos\Http\Exceptions\ResourceNotFoundException;
use gijsbos\Http\Exceptions\UnauthorizedException;
use gijsbos\Http\Exceptions\UnprocessableContentException;
use gijsbos\Http\Response;
use gijsbos\Http\Utils\ResponseManager;
use PHPUnit\Framework\TestCase;

final class ResponseManagerTest extends TestCase
{
    public function testSuccess()
    {
        $data = array("input" => "test");
        $result = ResponseManager::success($data);
        $expectedResult = new Response($data, 200);
        $this->assertEquals($expectedResult, $result);
    }

    public function testCreated()
    {
        $data = array("input" => "test");
        $result = ResponseManager::created($data);
        $expectedResult = new Response($data, 201);
        $this->assertEquals($expectedResult, $result);
    }

    public function testBadRequest()
    {
        $data = array("input" => "test");
        $response = ResponseManager::badRequest(null, null, $data);
        $result = $response->getStatusCode() === 400 && $response->getError() === "badRequest" && $response->getParameter("input") === "test";
        $this->assertTrue($result);
    }

    public function testUnauthorized()
    {
        $data = array("input" => "test");
        $response = ResponseManager::unauthorized(null, null, $data);
        $result = $response->getStatusCode() === 401 && $response->getError() === "unauthorized" && $response->getParameter("input") === "test";
        $this->assertTrue($result);
    }

    public function testForbidden()
    {
        $data = array("input" => "test");
        $response = ResponseManager::forbidden(null, null, $data);
        $result = $response->getStatusCode() === 403 && $response->getError() === "forbidden" && $response->getParameter("input") === "test";
        $this->assertTrue($result);
    }

    public function testResourceNotFound()
    {
        $data = array("input" => "test");
        $response = ResponseManager::resourceNotFound(null, null, $data);
        $result = $response->getStatusCode() === 404 && $response->getError() === "notFound" && $response->getParameter("input") === "test";
        $this->assertTrue($result);
    }

    public function testEntityNotFound()
    {
        $data = array("input" => "test");
        $response = ResponseManager::entityNotFound("CompanyAdmin", null, $data);
        $result = $response->getStatusCode() === 404 && $response->getError() === "companyAdminNotFound" && $response->getParameter("input") === "test";
        $this->assertTrue($result);
    }

    public function testEntityNotFoundWithNamespace()
    {
        $data = array("input" => "test");
        $response = ResponseManager::entityNotFound("NAMESPACE_CompanyAdmin", null, $data);
        $result = $response->getStatusCode() === 404 && $response->getError() === "companyAdminNotFound" && $response->getParameter("input") === "test";
        $this->assertTrue($result);
    }

    public function testMethodNotAllowed()
    {
        $data = array("input" => "test");
        $response = ResponseManager::methodNotAllowed(null, null, $data);
        $result = $response->getStatusCode() === 405 && $response->getError() === "methodNotAllowed" && $response->getParameter("input") === "test";
        $this->assertTrue($result);
    }

    public function testConflict()
    {
        $data = array("input" => "test");
        $response = ResponseManager::conflict(null, null, $data);
        $result = $response->getStatusCode() === 409 && $response->getError() === "conflict" && $response->getParameter("input") === "test";
        $this->assertTrue($result);
    }

    public function testHTTPRequestSentToHTTPSPort()
    {
        $data = array("input" => "test");
        $response = ResponseManager::httpRequestSentToHTTPSPort(null, null, $data);
        $result = $response->getStatusCode() === 497 && $response->getError() === "httpRequestSentToHTTPSPort" && $response->getParameter("input") === "test";
        $this->assertTrue($result);
    }

    public function testInternalServerError()
    {
        $data = array("input" => "test");
        $response = ResponseManager::internalServerError(null, null, $data);
        $result = $response->getStatusCode() === 500 && $response->getError() === "internalServerError" && $response->getParameter("input") === "test";
        $this->assertTrue($result);
    }

    public function testExceptionToResponse1()
    {
        $exception = new BadRequestException();
        $result = ResponseManager::exceptionToResponse($exception);
        $expectedResult = ResponseManager::badRequest();
        $this->assertEquals($expectedResult, $result);
    }

    public function testExceptionToResponse2()
    {
        $exception = new BadRequestException("custom_message", "More details", array("input" => "test"));
        $result = ResponseManager::exceptionToResponse($exception);
        $expectedResult = ResponseManager::badRequest("custom_message", "More details", array("input" => "test"));
        $this->assertEquals($expectedResult, $result);
    }

    public function testResponseToString()
    {
        $response = ResponseManager::badRequest();
        $result = ResponseManager::responseToString($response);
        $expectedResult = '[400] {"error":"badRequest","errorDescription":"The server could not process the request due to a client error"}';
        $this->assertEquals($expectedResult, $result);
    }

    public function testResponseToException()
    {
        $response = ResponseManager::badRequest();
        $result = ResponseManager::responseToException($response);
        $expectedResult = new BadRequestException("badRequest", "The server could not process the request due to a client error");
        $this->assertEquals($expectedResult, $result);
    }

    public function testNotAcceptable()
    {
        $response = ResponseManager::notAcceptable(null, null, ["input" => "test"]);
        $this->assertEquals(406, $response->getStatusCode());
        $this->assertEquals("notAcceptable", $response->getError());
        $this->assertEquals("test", $response->getParameter("input"));
    }

    public function testCustomErrorAndDescription()
    {
        $response = ResponseManager::forbidden("noAccess", "You have no access");
        $this->assertEquals("noAccess", $response->getError());
        $this->assertEquals("You have no access", $response->getErrorDescription());
    }

    public function testExceptionToResponseKeepsStatusCode()
    {
        $response = ResponseManager::exceptionToResponse(new UnprocessableContentException(null, null, ["field" => "email"]));
        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals("unprocessableContent", $response->getError());
        $this->assertEquals("email", $response->getParameter("field"));
    }

    public function testExceptionToResponseInvalidStatusCode()
    {
        $response = ResponseManager::exceptionToResponse(new HTTPRequestException(999, "weird"));
        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testExceptionToResponseInvalidArgumentType()
    {
        $response = ResponseManager::exceptionToResponse(new InvalidArgumentTypeException("age", "abc", "int", "string"));
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals("ageIncorrectType", $response->getError());
    }

    public function testExceptionToResponseInvalidArgumentInput()
    {
        $response = ResponseManager::exceptionToResponse(new InvalidArgumentInputException("first_name", "", "/.+/"));
        $this->assertEquals("first_name_incorrect_input", $response->getError());
    }

    public function testExceptionToResponseInvalidArgumentMissing()
    {
        $response = ResponseManager::exceptionToResponse(new InvalidArgumentMissingException("first-name", null));
        $this->assertEquals("first-name-missing", $response->getError());
    }

    public function testExceptionToResponseInvalidArgumentError()
    {
        $response = ResponseManager::exceptionToResponse(new InvalidArgumentErrorException("ageTooLow", "Too low"));
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals("ageTooLow", $response->getError());
        $this->assertEquals("Too low", $response->getErrorDescription());
    }

    public function testExceptionToResponseGenericException()
    {
        $response = ResponseManager::exceptionToResponse(new \RuntimeException("Something broke"));
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals("Something broke", $response->getErrorDescription());
    }

    public static function responseToExceptionProvider() : array
    {
        return [
            [401, UnauthorizedException::class],
            [403, ForbiddenException::class],
            [404, ResourceNotFoundException::class],
            [405, MethodNotAllowedException::class],
            [406, NotAcceptableException::class],
            [409, ConflictException::class],
            [497, HTTPRequestSentToHTTPSPortException::class],
            [418, \gijsbos\Http\Exceptions\ImATeapotException::class],
            [422, UnprocessableContentException::class],
            [429, \gijsbos\Http\Exceptions\TooManyRequestsException::class],
            [499, HTTPRequestException::class],
            [500, InternalServerErrorException::class],
            [503, \gijsbos\Http\Exceptions\ServiceUnavailableException::class],
            [599, InternalServerErrorException::class],
        ];
    }

    /**
     * @dataProvider responseToExceptionProvider
     */
    public function testResponseToExceptionMapping(int $statusCode, string $class)
    {
        $exception = ResponseManager::responseToException(new Response(["error" => "e", "errorDescription" => "d", "extra" => 1], $statusCode));
        $this->assertInstanceOf($class, $exception);
        $this->assertEquals("e", $exception->getError());
        $this->assertEquals("d", $exception->getErrorDescription());
        $this->assertEquals(["extra" => 1], $exception->getData());
    }

    public static function unmappedStatusCodeProvider() : array
    {
        return [[402], [410], [422], [429], [418]];
    }

    /**
     * @dataProvider unmappedStatusCodeProvider
     */
    public function testResponseToExceptionReturnsNullForUnmappedStatus(int $statusCode)
    {
        // Every status code without a dedicated class still results in an exception
        $this->assertInstanceOf(\Exception::class, ResponseManager::responseToException(new Response(["error" => "e"], $statusCode)));
    }

    public function testExceptionToResponseTooEarly()
    {
        $response = ResponseManager::exceptionToResponse(new \gijsbos\Http\Exceptions\TooEarlyException());
        $this->assertEquals("Too Early", $response->getStatusText());
    }
}