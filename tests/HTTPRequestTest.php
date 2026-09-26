<?php
declare(strict_types=1);

namespace WDS;

use gijsbos\Http\Http\HTTPRequest;
use gijsbos\Http\RequestMethod;
use PHPUnit\Framework\TestCase;

final class HTTPRequestTest extends TestCase 
{
    // public function testCall()
    // {
    //     $uri = "http://localhost/http/tests/http/HTTPTestController.php";
    //     $returnValue = json_encode(array(
    //         "test" => $input = "test input"
    //     ));
    //     $response = HTTPRequest::call(array(
    //         "type" => RequestMethod::GET,
    //         "uri" => $uri,
    //         "data" => array(
    //             "returnValue" => $returnValue
    //         )
    //     ));
    //     $result = $response->getParameter("test");
    //     $this->assertEquals($input, $result);
    // }

    // public function testGET()
    // {
    //     $uri = "http://localhost/http/tests/http/HTTPTestController.php";
    //     $returnValue = json_encode(array(
    //         "test" => $input = "test input"
    //     ));
    //     $response = HTTPRequest::get(array(
    //         "uri" => $uri,
    //         "data" => array(
    //             "returnValue" => $returnValue
    //         )
    //     ));
    //     $result = $response->getParameter("test");
    //     $this->assertEquals($input, $result);
    // }

    // public function testGETMergeDataWithUriData()
    // {
    //     $uri = "http://localhost/http/tests/http/HTTPTestController.php?returnValue={\"key\":\"value\"}";

    //     $response = HTTPRequest::get(array(
    //         "uri" => $uri,
    //         "data" => array(
    //             "returnValue" => "{\"key\":\"value\"}",
    //         )
    //     ));

    //     $result = $response->getParameter("key");
    //     $this->assertEquals("value", $result);
    // }

    // public function testPOST()
    // {
    //     $uri = "http://localhost/http/tests/http/HTTPTestController.php";
    //     $returnValue = json_encode(array(
    //         "test" => $input = "test input"
    //     ));
    //     $response = HTTPRequest::post(array(
    //         "uri" => $uri,
    //         "data" => array(
    //             "uuid4" => uuid4(),
    //             "returnValue" => $returnValue
    //         )
    //     ));
    //     $result = $response->getParameter("test");
    //     $this->assertEquals($input, $result);
    // }

    // public function testPUT()
    // {
    //     $uri = "http://localhost/http/tests/http/HTTPTestController.php";
    //     $returnValue = json_encode(array(
    //         "test" => $input = "test input"
    //     ));
    //     $response = HTTPRequest::put(array(
    //         "uri" => $uri,
    //         "data" => array(
    //             "returnValue" => $returnValue
    //         )
    //     ));
    //     $result = $response->getParameter("test");
    //     $this->assertEquals($input, $result);
    // }

    // public function testPATCH()
    // {
    //     $uri = "http://localhost/http/tests/http/HTTPTestController.php";
    //     $returnValue = json_encode(array(
    //         "test" => $input = "test input"
    //     ));
    //     $response = HTTPRequest::patch(array(
    //         "uri" => $uri,
    //         "data" => array(
    //             "returnValue" => $returnValue
    //         )
    //     ));
    //     $result = $response->getParameter("test");
    //     $this->assertEquals($input, $result);
    // }

    // public function testDELETE()
    // {
    //     $uri = "http://localhost/http/tests/http/HTTPTestController.php";
    //     $returnValue = json_encode(array(
    //         "test" => $input = "test input"
    //     ));
    //     $response = HTTPRequest::delete(array(
    //         "uri" => $uri,
    //         "data" => array(
    //             "returnValue" => $returnValue
    //         )
    //     ));
    //     $result = $response->getParameter("test");
    //     $this->assertEquals($input, $result);
    // }

    public function testAddBaseURL()
    {
        // Add base url
        HTTPRequest::addBaseURL('test', "http://localhost/http/");

        // Set input
        $returnValue = json_encode(array(
            "test" => $input = "test input"
        ));

        // Execute
        $response = HTTPRequest::test()->get(array(
            "uri" => "/tests/http/HTTPTestController.php",
            "data" => array(
                "returnValue" => $returnValue
            )
        ));
        $result = $response->getParameter("test");
        $this->assertEquals($input, $result);
    }

    public function testBaseURLNotFound()
    {
        $this->expectExceptionMessage("Could not resolve base url 'notfound'");

        // Add base url
        HTTPRequest::addBaseURL('test', "http://localhost/http/");

        // Set input
        $returnValue = json_encode(array(
            "test" => $input = "test input"
        ));

        // Execute
        $response = HTTPRequest::notfound()->get(array(
            "uri" => "/tests/http/HTTPTestController.php",
            "data" => array(
                "returnValue" => $returnValue
            )
        ));
    }

    const ECHO_URL = "http://localhost/http/tests/http/EchoController.php";

    protected function tearDown() : void
    {
        // Reset global state changed by tests
        HTTPRequest::$DEFAULT_URL = "";
        HTTPRequest::$DEFAULT_FLAGS = null;
        HTTPRequest::$DEFAULT_HEADERS = null;
        HTTPRequest::$DEFAULT_OPTIONS = [];
    }

    /**
     * invokePrivate
     */
    private function invokePrivate(object|string $objectOrClass, string $method, array $args = [])
    {
        $reflection = new \ReflectionMethod($objectOrClass, $method);
        return $reflection->invokeArgs(is_object($objectOrClass) ? $objectOrClass : null, $args);
    }

    /**
     * prependBaseURL
     */
    public function testPrependBaseURLRelative()
    {
        $request = new HTTPRequest(null, null, [], [], null, null, "http://localhost/http/");
        $this->assertEquals("http://localhost/http/tests/x.php", $this->invokePrivate($request, "prependBaseURL", ["/tests/x.php"]));
        $this->assertEquals("http://localhost/http/tests/x.php", $this->invokePrivate($request, "prependBaseURL", ["tests/x.php"]));
    }

    public function testPrependBaseURLAlreadyPrefixed()
    {
        $request = new HTTPRequest(null, null, [], [], null, null, "http://localhost/http");
        $this->assertEquals("http://localhost/http/x.php", $this->invokePrivate($request, "prependBaseURL", ["http://localhost/http/x.php"]));
    }

    public function testPrependBaseURLAbsoluteURLIsNotPrefixed()
    {
        $request = new HTTPRequest(null, null, [], [], null, null, "http://localhost/http");
        $this->assertEquals("https://other.server/api", $this->invokePrivate($request, "prependBaseURL", ["https://other.server/api"]));
        $this->assertEquals("HTTP://other.server/api", $this->invokePrivate($request, "prependBaseURL", ["HTTP://other.server/api"]));
    }

    public function testPrependBaseURLWithoutBaseURL()
    {
        $request = new HTTPRequest();
        $this->assertEquals("/x.php", $this->invokePrivate($request, "prependBaseURL", ["/x.php"]));
    }

    public function testDefaultURLIsUsedAsBaseURL()
    {
        HTTPRequest::setDefaultURL("http://localhost/http");
        $request = new HTTPRequest();
        $this->assertEquals("http://localhost/http", $request->getBaseURL());
    }

    public function testBaseURLCallsAbsoluteURLOnOtherServer()
    {
        HTTPRequest::addBaseURL('test', "http://127.0.0.1/does-not-exist/");
        $response = HTTPRequest::test()->get(["uri" => self::ECHO_URL]);
        $this->assertEquals("GET", $response->getParameter("method"));
    }

    /**
     * init
     */
    public function testInitArgumentAliases()
    {
        $request = new HTTPRequest();
        $request->init(["method" => "POST", "url" => "/a"]);
        $this->assertEquals(RequestMethod::POST, $request->getType());
        $this->assertEquals("/a", $request->getURI());
    }

    public function testInitKeepsValuesNotProvided()
    {
        $request = new HTTPRequest(GET, "/a", ["k" => "v"]);
        $request->init(["uri" => "/b"]);
        $this->assertEquals(GET, $request->getType());
        $this->assertEquals("/b", $request->getURI());
        $this->assertEquals(["k" => "v"], $request->getData());
    }

    public function testInitInvalidCURLOptionsType()
    {
        $this->expectException(\TypeError::class);
        (new HTTPRequest())->init(["cURLOptions" => "invalid"]);
    }

    public function testDefaultFlagsAreCombined()
    {
        HTTPRequest::$DEFAULT_FLAGS = FOLLOW_LOCATION;
        $request = new HTTPRequest(GET, "/", [], [], GET_REDIRECT_URI);
        $this->assertEquals(FOLLOW_LOCATION | GET_REDIRECT_URI, $request->getFlags());
    }

    public function testDefaultFlagsWithoutFlags()
    {
        HTTPRequest::$DEFAULT_FLAGS = FOLLOW_LOCATION;
        $this->assertEquals(FOLLOW_LOCATION, (new HTTPRequest())->getFlags());
    }

    public function testAddFlag()
    {
        $request = new HTTPRequest();
        $request->addFlag(FOLLOW_LOCATION);
        $request->addFlag(GET_REDIRECT_URI);
        $this->assertEquals(FOLLOW_LOCATION | GET_REDIRECT_URI, $request->getFlags());
    }

    public function testDataSetters()
    {
        $request = new HTTPRequest();
        $request->setData(["a" => 1]);
        $request->addData("b", 2);
        $request->mergeData(["a" => 3, "c" => 4]);
        $this->assertEquals(["a" => 3, "b" => 2, "c" => 4], $request->getData());
    }

    public function testAddHeaders()
    {
        $request = new HTTPRequest();
        $request->addHeaders(["X-A" => "1", "X-B" => "2"]);
        $request->addHeader("X-A", "3");
        $this->assertEquals(["X-A" => "3", "X-B" => "2"], $request->getHeaders());
    }

    public function testCURLProxy()
    {
        $request = new HTTPRequest();
        $this->assertFalse($request->hasCURLProxy());
        $request->setCURLProxy("127.0.0.1:8080");
        $this->assertTrue($request->hasCURLProxy());
        $this->assertEquals("127.0.0.1:8080", $request->getCURLOptions()[CURLOPT_PROXY]);
    }

    public function testCreateFromArgs()
    {
        $request = HTTPRequest::createFromArgs(["type" => PUT, "uri" => "/a", "data" => ["k" => "v"], "headers" => "invalid"]);
        $this->assertEquals(PUT, $request->getType());
        $this->assertEquals("/a", $request->getURI());
        $this->assertEquals(["k" => "v"], $request->getData());
        $this->assertEquals([], $request->getHeaders());
    }

    /**
     * convertToCURLHeaders
     */
    public function testConvertToCURLHeaders()
    {
        $result = HTTPRequest::convertToCURLHeaders(["Content_Type" => "application/json", "X-Custom: value"]);
        $this->assertEquals(["Content-Type: application/json", "X-Custom: value"], $result);
    }

    /**
     * hasHeader
     */
    public function testHasHeaderKey()
    {
        $headers = ["Authorization" => "Bearer x"];
        $this->assertTrue((new HTTPRequest())->hasHeader("Authorization", $headers));
        $this->assertFalse((new HTTPRequest())->hasHeader("Content-Type", $headers));
    }

    public function testHasHeaderKeyValue()
    {
        $headers = ["content-type: application/json"];
        $this->assertTrue((new HTTPRequest())->hasHeader("Content-Type: application\/json", $headers));
    }

    public function testHasHeaderAssumeSearchValueStripsBoundary()
    {
        $headers = ["Content-Type: multipart/form-data; boundary=----abc"];
        $this->assertTrue((new HTTPRequest())->hasHeader("Content-Type: multipart\/form-data", $headers, true));
        $this->assertEquals(["Content-Type: multipart/form-data"], $headers);
    }

    /**
     * createURLWithParams
     */
    public function testCreateURLWithParams()
    {
        $url = "http://localhost:8080/path";
        $data = ["a" => "1", "b" => "x y"];
        HTTPRequest::createURLWithParams($url, $data);
        $this->assertEquals("http://localhost:8080/path?a=1&b=x+y", $url);
        $this->assertEquals([], $data);
    }

    public function testCreateURLWithParamsMergesExistingQuery()
    {
        $url = "http://localhost/path?a=1";
        $data = ["b" => "2"];
        HTTPRequest::createURLWithParams($url, $data);
        $this->assertEquals("http://localhost/path?b=2&a=1", $url);
    }

    public function testCreateURLWithParamsWithoutData()
    {
        $url = "http://localhost/path#frag";
        $data = [];
        HTTPRequest::createURLWithParams($url, $data);
        $this->assertEquals("http://localhost/path#frag", $url);
    }

    /**
     * getResponseObject
     */
    public function testGetResponseObjectJSON()
    {
        $response = $this->invokePrivate(HTTPRequest::class, "getResponseObject", ['{"a":1,"b":{}}', 201, ""]);
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(1, $response->getParameter("a"));
        $this->assertEquals("{}", json_encode($response->getParameter("b")));
    }

    public function testGetResponseObjectCurlError()
    {
        $response = $this->invokePrivate(HTTPRequest::class, "getResponseObject", ["", 500, "Connection reset"]);
        $this->assertEquals("Connection reset", $response->getError());
    }

    public function testGetResponseObjectStatusCodeZero()
    {
        $response = $this->invokePrivate(HTTPRequest::class, "getResponseObject", ["", 0, ""]);
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals("httpRequestFailed", $response->getError());
    }

    public function testGetResponseObjectEmptyBody()
    {
        $response = $this->invokePrivate(HTTPRequest::class, "getResponseObject", ["", 204, ""]);
        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals([], $response->getParameters());
    }

    /**
     * getErrnoByCode
     */
    public function testGetErrnoByCode()
    {
        $this->assertEquals("COULDNT_RESOLVE_HOST", HTTPRequest::getErrnoByCode(6)["short"]);
        $this->assertFalse(HTTPRequest::getErrnoByCode(9999));
    }

    /**
     * sendRequest
     */
    public function testSendRequestTypeMissing()
    {
        $this->expectExceptionMessage("type missing");
        (new HTTPRequest(null, self::ECHO_URL))->sendRequest();
    }

    public function testSendRequestUnsupportedMethod()
    {
        $this->expectExceptionMessage("not supported");
        (new HTTPRequest(HEAD, self::ECHO_URL))->sendRequest();
    }

    public function testUndefinedMethod()
    {
        $this->expectExceptionMessage("Call to undefined method doesNotExist");
        (new HTTPRequest())->doesNotExist();
    }

    /**
     * Integration: requests as received by the server (requires localhost to serve this repository at /http)
     */
    public function testGETSendsDataAsQuery()
    {
        $response = HTTPRequest::get(["uri" => self::ECHO_URL . "?a=1", "data" => ["b" => "2"]]);
        $this->assertEquals("GET", $response->getParameter("method"));
        $this->assertEquals(["a" => "1", "b" => "2"], (array) $response->getParameter("query"));
    }

    public function testPOSTSendsFormEncodedBody()
    {
        $response = HTTPRequest::post(["uri" => self::ECHO_URL, "data" => ["a" => "1", "b" => "x y"]]);
        $this->assertEquals("POST", $response->getParameter("method"));
        $this->assertEquals("a=1&b=x+y", $response->getParameter("body"));
    }

    public function testPOSTSendsJSONBody()
    {
        $response = HTTPRequest::post([
            "uri" => self::ECHO_URL,
            "data" => ["a" => 1, "nested" => ["b" => true]],
            "headers" => ["Content-Type" => "application/json"],
        ]);
        $this->assertEquals('{"a":1,"nested":{"b":true}}', base64_decode($response->getParameter("bodyBase64")));
        $this->assertEquals("application/json", $response->getParameter("contentType"));
    }

    public function testPUTMethod()
    {
        $response = HTTPRequest::put(["uri" => self::ECHO_URL, "data" => ["a" => "1"]]);
        $this->assertEquals("PUT", $response->getParameter("method"));
        $this->assertEquals("a=1", $response->getParameter("body"));
    }

    public function testPATCHMethod()
    {
        $response = HTTPRequest::patch(["uri" => self::ECHO_URL, "data" => ["a" => "1"]]);
        $this->assertEquals("PATCH", $response->getParameter("method"));
        $this->assertEquals("a=1", $response->getParameter("body"));
    }

    public function testDELETESendsDataAsQuery()
    {
        $response = HTTPRequest::delete(["uri" => self::ECHO_URL, "data" => ["id" => "5"]]);
        $this->assertEquals("DELETE", $response->getParameter("method"));
        $this->assertEquals(["id" => "5"], (array) $response->getParameter("query"));
    }

    public function testHeadersAreSent()
    {
        $response = HTTPRequest::get(["uri" => self::ECHO_URL, "headers" => ["X-Assoc" => "1", "X_Underscore" => "2", "X-Sequential: 3"]]);
        $this->assertEquals(["x-assoc" => "1", "x-underscore" => "2", "x-sequential" => "3"], (array) $response->getParameter("headers"));
    }

    public function testNonSuccessResponseContainsErrorURI()
    {
        $response = HTTPRequest::get(["uri" => self::ECHO_URL, "data" => ["statusCode" => "404"]]);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertStringStartsWith(self::ECHO_URL, $response->getParameter("errorURI"));
    }

    public function testEmptyResponseKeepsStatusCode()
    {
        $response = HTTPRequest::get(["uri" => self::ECHO_URL, "data" => ["statusCode" => "204", "empty" => "1"]]);
        $this->assertEquals(204, $response->getStatusCode());
    }

    public function testGetRedirectURIFlag()
    {
        $response = HTTPRequest::get(["uri" => self::ECHO_URL], GET_REDIRECT_URI);
        $this->assertEquals(self::ECHO_URL, $response->getParameter("redirectURI"));
    }

    public function testCURLHandleFlag()
    {
        $this->assertInstanceOf(\CurlHandle::class, HTTPRequest::get(["uri" => self::ECHO_URL], CURL_HANDLE));
    }

    public function testHTTPRequestHandleFlag()
    {
        $this->assertInstanceOf(\gijsbos\Http\Http\HTTPRequestHandle::class, HTTPRequest::post(["uri" => self::ECHO_URL], HTTP_REQUEST_HANDLE));
    }

    public function testConnectionFailureThrows()
    {
        $this->expectExceptionMessage("Curl failed: 7 COULDNT_CONNECT");
        HTTPRequest::get(["uri" => "http://127.0.0.1:1/"]);
    }

    public function testPOSTNestedArrayMultipartThrows()
    {
        $this->expectException(\InvalidArgumentException::class);
        HTTPRequest::post(["uri" => self::ECHO_URL, "data" => ["a" => ["b" => 1]], "headers" => ["Content-Type: multipart/form-data"]]);
    }

    public function testRemoveHeader()
    {
        $request = new HTTPRequest(null, null, [], ["X-A" => "1"]);
        $request->removeHeader("X-A");
        $this->assertEquals([], $request->getHeaders());
    }

    public function testRequestHeadersOverrideDefaultHeaders()
    {
        HTTPRequest::$DEFAULT_HEADERS = ["Accept" => "text/html"];
        $request = new HTTPRequest(GET, "/", [], ["Accept" => "application/json"]);
        $this->assertEquals("application/json", $request->getHeaders()["Accept"]);
    }

    public function testRequestOptionsOverrideDefaultOptions()
    {
        HTTPRequest::$DEFAULT_OPTIONS = [CURLOPT_TIMEOUT => 5];
        $request = new HTTPRequest(GET, "/", [], [], null, [CURLOPT_TIMEOUT => 60]);
        $this->assertEquals(60, $request->getCURLOptions()[CURLOPT_TIMEOUT]);
    }

    public function testCallPutSendsPUT()
    {
        $response = (new HTTPRequest())->callPut(self::ECHO_URL, ["a" => "1"], [], []);
        $this->assertEquals("PUT", $response->getParameter("method"));
    }

    public function testJSONHeaderWithoutSpace()
    {
        $response = HTTPRequest::post(["uri" => self::ECHO_URL, "data" => ["a" => 1], "headers" => ["content-type:application/json"]]);
        $this->assertEquals('{"a":1}', base64_decode($response->getParameter("bodyBase64")));
    }

    public function testResponseStringValuesKeepTheirType()
    {
        $response = $this->invokePrivate(HTTPRequest::class, "getResponseObject", ['{"id":"123","flag":"false","name":"null","tags":"[1,2]"}', 200, ""]);
        $this->assertSame(["id" => "123", "flag" => "false", "name" => "null", "tags" => "[1,2]"], $response->getParameters());
    }

    public function testPlainTextSuccessKeepsStatusCode()
    {
        $response = HTTPRequest::get(["uri" => self::ECHO_URL, "data" => ["text" => "hello"]]);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCreateURLWithParamsKeepsCredentialsAndFragment()
    {
        $url = "http://user:pw@localhost/path#frag";
        $data = ["a" => "1"];
        HTTPRequest::createURLWithParams($url, $data);
        $this->assertEquals("http://user:pw@localhost/path?a=1#frag", $url);
    }

    public function testPrivateMethodsNotCallableFromOutside()
    {
        $this->expectExceptionMessage("Call to undefined method prependBaseURL");
        (new HTTPRequest())->prependBaseURL("/x");
    }

    public function testHttpRequestFunctionWithoutHeaders()
    {
        $response = \gijsbos\Http\Http\http_request("GET", self::ECHO_URL);
        $this->assertEquals("GET", $response->getParameter("method"));
    }

    public function testHttpRequestFunctionPATCHSendsBody()
    {
        $response = \gijsbos\Http\Http\http_request("PATCH", self::ECHO_URL, ["a" => "1"], ["X-A" => "1"]);
        $this->assertEquals("PATCH", $response->getParameter("method"));
        $this->assertEquals("a=1", $response->getParameter("body"));
    }
}