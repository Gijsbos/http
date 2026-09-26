<?php
declare(strict_types=1);

namespace WDS;

use gijsbos\Http\Response;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ResponseTest extends TestCase
{
    public function testDefaults()
    {
        $response = new Response();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("OK", $response->getStatusText());
        $this->assertEquals([], $response->getParameters());
        $this->assertEquals([], $response->getHttpHeaders());
    }

    public function testStatusCodeIsCastToInt()
    {
        $this->assertSame(404, (new Response([], "404"))->getStatusCode());
    }

    public function testCustomStatusText()
    {
        $response = new Response();
        $response->setStatusCode(200, "Fine");
        $this->assertEquals("Fine", $response->getStatusText());
        $response->setStatusCode(200, false);
        $this->assertEquals("", $response->getStatusText());
    }

    public function testInvalidStatusCodeLow()
    {
        $this->expectException(InvalidArgumentException::class);
        new Response([], 99);
    }

    public function testInvalidStatusCodeHigh()
    {
        $this->expectException(InvalidArgumentException::class);
        new Response([], 600);
    }

    public static function statusRangeProvider() : array
    {
        return [
            [100, "isInformational"],
            [204, "isSuccessful"],
            [301, "isRedirection"],
            [404, "isClientError"],
            [503, "isServerError"],
        ];
    }

    /**
     * @dataProvider statusRangeProvider
     */
    public function testStatusRanges(int $statusCode, string $method)
    {
        $response = new Response([], $statusCode);
        foreach(["isInformational", "isSuccessful", "isRedirection", "isClientError", "isServerError"] as $check)
            $this->assertSame($check === $method, $response->$check(), "$check for $statusCode");
        $this->assertSame($method === "isSuccessful", $response->ok());
    }

    public function testParameters()
    {
        $response = new Response(["a" => 1]);
        $response->addParameters(["b" => 2]);
        $response->setParameter("c", 3);
        $this->assertEquals(["a" => 1, "b" => 2, "c" => 3], $response->getParameters());
        $this->assertTrue($response->hasParameter("a"));
        $this->assertFalse($response->hasParameter("d"));
        $this->assertEquals("default", $response->getParameter("d", "default"));
    }

    public function testHttpHeaders()
    {
        $response = new Response([], 200, ["X-A" => "1"]);
        $response->setHttpHeader("X-B", "2");
        $response->addHttpHeaders(["X-A" => "3"]);
        $this->assertEquals(["X-A" => "3", "X-B" => "2"], $response->getHttpHeaders());
        $this->assertEquals("2", $response->getHttpHeader("X-B"));
        $this->assertNull($response->getHttpHeader("X-C"));
    }

    public function testResponseBodyJSON()
    {
        $this->assertEquals('{"a":1}', (new Response(["a" => 1]))->getResponseBody());
        $this->assertEquals('', (new Response())->getResponseBody());
    }

    public function testResponseBodyXML()
    {
        $this->assertStringContainsString("<response><a>value</a></response>", (new Response(["a" => "value"]))->getResponseBody("xml"));
    }

    public function testResponseBodyXMLNonStringValue()
    {
        // Non-string values are converted, SimpleXMLElement::addChild only accepts strings under strict_types
        $this->assertStringContainsString("<response><a>1</a></response>", (new Response(["a" => 1]))->getResponseBody("xml"));
    }

    public function testResponseBodyUnsupportedFormat()
    {
        $this->expectException(InvalidArgumentException::class);
        (new Response())->getResponseBody("yaml");
    }

    public function testToString()
    {
        $response = new Response(["a" => 1], 201, ["content-type" => "application/json"]);
        $this->assertEquals("HTTP/1.1 201 Created\r\nContent-Type: application/json\r\n\r\n{\"a\":1}", (string) $response);
    }

    public function testSetError()
    {
        $response = new Response();
        $response->setError(400, "invalidInput", "Input is invalid", "#section-4.1");
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals("invalidInput", $response->getError());
        $this->assertEquals("Input is invalid", $response->getErrorDescription());
        $this->assertEquals("http://tools.ietf.org/html/rfc6749#section-4.1", $response->getParameter("errorUri"));
        $this->assertEquals("no-store", $response->getHttpHeader("Cache-Control"));
    }

    public function testSetErrorNonErrorStatusCode()
    {
        $this->expectException(InvalidArgumentException::class);
        (new Response())->setError(200, "error");
    }

    public function testSetRedirect()
    {
        $response = new Response();
        $response->setRedirect(302, "https://example.com/cb?x=1", "state123");
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals("https://example.com/cb?x=1&state=state123", $response->getHttpHeader("Location"));
    }

    public function testSetRedirectEmptyURL()
    {
        $this->expectException(InvalidArgumentException::class);
        (new Response())->setRedirect(302, "");
    }

    public function testSetRedirectNonRedirectStatusCode()
    {
        $this->expectException(InvalidArgumentException::class);
        (new Response())->setRedirect(200, "https://example.com");
    }

    public function testGetErrorString()
    {
        $response = new Response(["error" => "notFound", "errorDescription" => "Not here", "errorCode" => "E1"], 404);
        $this->assertEquals("notFound (404) - Not here - code: E1", $response->getErrorString());
        $this->assertEquals("", (new Response(["error" => "x"], 200))->getErrorString());
    }

    public static function unlistedStatusCodeProvider() : array
    {
        return [[308], [421], [425], [426], [431], [451], [511]];
    }

    /**
     * @dataProvider unlistedStatusCodeProvider
     */
    public function testValidStatusCodeWithoutStatusText(int $statusCode)
    {
        $response = new Response([], $statusCode);
        $this->assertIsString($response->getStatusText());
    }

    public function testStatusText428()
    {
        $this->assertEquals("Precondition Required", (new Response([], 428))->getStatusText());
    }
}
