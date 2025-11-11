<?php
declare(strict_types=1);

namespace WDS;

use gijsbos\Http\Exceptions\BadRequestException;
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
}