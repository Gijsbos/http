<?php
declare(strict_types=1);

namespace WDS;

use gijsbos\Http\Http\HTTPRequest;
use gijsbos\Http\Http\HTTPRequestHandle;
use gijsbos\Http\Http\HTTPRequestPool;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests, requires localhost to serve this repository at /http
 */
final class HTTPRequestPoolTest extends TestCase
{
    const ECHO_URL = "http://localhost/http/tests/http/EchoController.php";

    /**
     * createHandle
     */
    private function createHandle(string $id) : HTTPRequestHandle
    {
        return HTTPRequest::get(["uri" => self::ECHO_URL, "data" => ["id" => $id]], HTTP_REQUEST_HANDLE);
    }

    /**
     * getId
     */
    private function getId($response)
    {
        return ((array) $response->getParameter("query"))["id"];
    }

    public function testExecuteReturnsResultsByIndex()
    {
        $pool = HTTPRequestPool::create();
        $this->assertFalse($pool->hasHandles());

        $this->assertEquals(0, $pool->add($this->createHandle("a")));
        $this->assertEquals(1, $pool->add($this->createHandle("b")));
        $this->assertEquals(2, $pool->count());

        $results = $pool->execute();
        $this->assertEquals("a", $this->getId($results[0]));
        $this->assertEquals("b", $this->getId($results[1]));
    }

    public function testAddCurlHandle()
    {
        $pool = new HTTPRequestPool();
        $pool->add(HTTPRequest::get(["uri" => self::ECHO_URL, "data" => ["id" => "curl"]], HTTP_REQUEST_HANDLE)->getHandle());
        $this->assertEquals("curl", $this->getId($pool->execute()[0]));
    }

    public function testCallbacks()
    {
        $received = [];
        $pool = new HTTPRequestPool();
        $pool->add($this->createHandle("a"), function($response) use(&$received) { $received[] = $this->getId($response); });
        $pool->execute();
        $this->assertEquals(["a"], $received);
    }

    public function testHandleCallbackIsUsed()
    {
        $received = null;
        $handle = $this->createHandle("a");
        $pool = new HTTPRequestPool();
        $pool->add(new HTTPRequestHandle($handle->getHandle(), function($response) use(&$received) { $received = $this->getId($response); }));
        $pool->execute();
        $this->assertEquals("a", $received);
    }

    public function testOptimizeRequestsCallsDuplicateRequestOnce()
    {
        $calls = 0;
        $callback = function() use(&$calls) { $calls++; };

        $pool = new HTTPRequestPool(true);
        $pool->add($this->createHandle("a"), $callback);
        $pool->add($this->createHandle("a"), $callback);
        $pool->add($this->createHandle("b"), $callback);


        $results = $pool->execute();
        $this->assertEquals(3, $calls);
        $this->assertEquals("a", $this->getId($results[0]));
        $this->assertEquals("a", $this->getId($results[1]));
        $this->assertEquals("b", $this->getId($results[2]));
    }

    public function testFailedRequestReturnsErrorResponse()
    {
        // A failed request must not prevent the other results from being returned
        $pool = new HTTPRequestPool();
        $pool->add(HTTPRequest::get(["uri" => "http://127.0.0.1:1/"], HTTP_REQUEST_HANDLE));
        $pool->add($this->createHandle("a"));
        $results = $pool->execute();
        $this->assertEquals(500, $results[0]->getStatusCode());
        $this->assertEquals("a", $this->getId($results[1]));
    }

    public function testOptimizedDuplicateReturnsOwnIndex()
    {
        $pool = new HTTPRequestPool(true);
        $pool->add($this->createHandle("a"));
        $pool->add($this->createHandle("b"));
        $this->assertEquals(2, $pool->add($this->createHandle("b")));
    }

    public function testOptimizeRequestsKeepsDifferentMethodsApart()
    {
        // Same url and headers, but a GET and a DELETE are different requests
        $pool = new HTTPRequestPool(true);
        $pool->add(HTTPRequest::get(["uri" => self::ECHO_URL], HTTP_REQUEST_HANDLE));
        $pool->add(HTTPRequest::delete(["uri" => self::ECHO_URL], HTTP_REQUEST_HANDLE));
        $results = $pool->execute();
        $this->assertEquals("GET", $results[0]->getParameter("method"));
        $this->assertEquals("DELETE", $results[1]->getParameter("method"));
    }

    public function testFailedRequestDescribesCurlError()
    {
        $pool = new HTTPRequestPool();
        $pool->add(HTTPRequest::get(["uri" => "http://127.0.0.1:1/"], HTTP_REQUEST_HANDLE));
        $this->assertStringStartsWith("7 ", $pool->execute()[0]->getErrorDescription());
    }

    public function testRawResponseInPool()
    {
        $pool = new HTTPRequestPool();
        $pool->add(HTTPRequest::get(["uri" => self::ECHO_URL, "data" => ["text" => "hi"]], HTTP_REQUEST_HANDLE | RAW_RESPONSE));
        $result = $pool->execute()[0];
        $this->assertInstanceOf(\gijsbos\Http\RawResponse::class, $result);
        $this->assertEquals("hi", $result->getBody());
    }
}
