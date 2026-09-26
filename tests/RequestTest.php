<?php
declare(strict_types=1);

namespace WDS;

use gijsbos\Http\Http\HTTPRequestHandle;
use gijsbos\Http\Request;
use gijsbos\Http\RequestMethod;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    /**
     * Request
     */
    public function testAccessors()
    {
        $request = new Request(["q" => "1"], ["p" => "2"], [], [], [], ["REQUEST_METHOD" => "POST"]);
        $this->assertEquals("1", $request->query("q"));
        $this->assertEquals("2", $request->request("p"));
        $this->assertEquals("POST", $request->server("REQUEST_METHOD"));
        $this->assertEquals("default", $request->query("missing", "default"));
        $this->assertEquals(["q" => "1"], $request->getAllQueryParameters());
    }

    public function testHeadersFromServer()
    {
        $request = new Request([], [], [], [], [], [
            "HTTP_X_CUSTOM" => "value",
            "CONTENT_TYPE" => "application/json",
            "SERVER_NAME" => "localhost",
        ]);
        $this->assertEquals("value", $request->headers("x_custom"));
        $this->assertEquals("application/json", $request->headers("Content_Type"));
        $this->assertNull($request->headers("SERVER_NAME"));
    }

    public function testExplicitHeadersTakePrecedence()
    {
        $request = new Request([], [], [], [], [], ["HTTP_X_A" => "server"], null, ["X_A" => "explicit"]);
        $this->assertEquals("explicit", $request->headers("X_A"));
    }

    public function testBasicAuthorizationHeaderIsDecoded()
    {
        $request = new Request([], [], [], [], [], ["HTTP_AUTHORIZATION" => "Basic " . base64_encode("john:secret")]);
        $this->assertEquals("john", $request->headers("PHP_AUTH_USER"));
        $this->assertEquals("secret", $request->headers("PHP_AUTH_PW"));
    }

    public function testPHPAuthUserCreatesAuthorizationHeader()
    {
        $request = new Request([], [], [], [], [], ["PHP_AUTH_USER" => "john", "PHP_AUTH_PW" => "secret"]);
        $this->assertEquals("Basic " . base64_encode("john:secret"), $request->headers("Authorization"));
    }

    public function testBearerAuthorizationHeader()
    {
        $request = new Request([], [], [], [], [], ["REDIRECT_HTTP_AUTHORIZATION" => "Bearer token"]);
        $this->assertEquals("Bearer token", $request->headers("AUTHORIZATION"));
        $this->assertNull($request->headers("PHP_AUTH_USER"));
    }

    public function testContent()
    {
        $request = new Request([], [], [], [], [], [], "raw body");
        $this->assertEquals("raw body", $request->getContent());
    }

    /**
     * RequestMethod
     */
    public function testConvertToConstant()
    {
        $this->assertEquals(RequestMethod::POST, RequestMethod::convertToConstant("POST"));
        $this->assertEquals(RequestMethod::POST, RequestMethod::convertToConstant(RequestMethod::POST));
        $this->assertEquals("UNKNOWN_METHOD", RequestMethod::convertToConstant("UNKNOWN_METHOD"));
    }

    public function testConvertToString()
    {
        foreach(["GET","POST","PUT","DELETE","PATCH","COPY","HEAD","OPTIONS","LINK","UNLINK","PURGE","LOCK","UNLOCK","PROPFIND","VIEW"] as $method)
        {
            $this->assertEquals($method, RequestMethod::convertToString(constant(RequestMethod::class . "::$method")));
            $this->assertEquals($method, RequestMethod::convertToString((string) constant($method)));
        }
        $this->assertEquals("GET", RequestMethod::convertToString("GET"));
        $this->assertNull(RequestMethod::convertToString(null));
    }

    public function testConvertToStringUnknownConstant()
    {
        $this->expectExceptionMessage("Could not convert unknown request method constant '3'");
        RequestMethod::convertToString(3);
    }

    /**
     * HTTPRequestHandle
     */
    public function testCreateHash()
    {
        $hash = HTTPRequestHandle::createHash("/a", ["k" => "v"], ["X: 1"]);
        $this->assertEquals($hash, HTTPRequestHandle::createHash("/a", ["k" => "v"], ["X: 1"]));
        $this->assertNotEquals($hash, HTTPRequestHandle::createHash("/a", ["k" => "w"], ["X: 1"]));
        $this->assertNotEquals($hash, HTTPRequestHandle::createHash("/a", ["k" => "v"], ["X: 2"]));
        $this->assertNotEquals($hash, HTTPRequestHandle::createHash("/b", ["k" => "v"], ["X: 1"]));
        $this->assertNotEquals(HTTPRequestHandle::createHash("/a", "k=v"), HTTPRequestHandle::createHash("/a", "k=w"));
    }

    public function testExecuteCallback()
    {
        $handle = new HTTPRequestHandle(curl_init(), fn($result) => $result * 2);
        $this->assertEquals(4, $handle->executeCallback(2));
        $this->assertEquals(2, (new HTTPRequestHandle(curl_init()))->executeCallback(2));
    }
}
