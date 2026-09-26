<?php
declare(strict_types=1);

namespace WDS;

use gijsbos\Http\Http\HTTPRequest;
use gijsbos\Http\Http\HTTPRequestHandle;
use gijsbos\Http\Http\HTTPRequestPool;
use gijsbos\Http\Http\HTTPRequestResultGroup;
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
        $this->assertEquals($results, $pool->getResults());
    }

    public function testAddCurlHandle()
    {
        $pool = new HTTPRequestPool();
        $pool->add(HTTPRequest::get(["uri" => self::ECHO_URL, "data" => ["id" => "curl"]], CURL_HANDLE));
        $this->assertEquals("curl", $this->getId($pool->execute()[0]));
    }

    public function testCallbacks()
    {
        $received = [];
        $pool = new HTTPRequestPool();
        $pool->add($this->createHandle("a"), function($response) use(&$received) { $received[] = $this->getId($response); });
        $pool->addOnResultReceived(function($results) use(&$received) { $received[] = count($results); });
        $pool->execute();
        $this->assertEquals(["a", 1], $received);
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

        // Duplicate is stored as empty handle
        $this->assertNull($pool->getHandles()[1]);

        $results = $pool->execute();
        $this->assertEquals(3, $calls);
        $this->assertEquals("a", $this->getId($results[0]));
        $this->assertEquals("a", $this->getId($results[1]));
        $this->assertEquals("b", $this->getId($results[2]));
    }

    public function testResultGroup()
    {
        $groupResults = null;

        $group = new HTTPRequestResultGroup();
        $group->add($this->createHandle("a"));
        $group->add($this->createHandle("b"));
        $group->setCallback(function($results) use(&$groupResults) { $groupResults = $results; });

        $pool = new HTTPRequestPool();
        $pool->add($this->createHandle("other"));
        $pool->addResultGroup($group);
        $pool->execute();

        $this->assertEquals(["a", "b"], array_map(fn($r) => $this->getId($r), array_values($groupResults)));
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

    public function testDebugFlag()
    {
        $response = HTTPRequest::get(["uri" => self::ECHO_URL], HTTP_REQUEST_DEBUG);
        $this->assertEquals(self::ECHO_URL, $response->getParameter("errorURI"));
    }

    public function testOptimizedDuplicateReturnsOwnIndex()
    {
        $pool = new HTTPRequestPool(true);
        $pool->add($this->createHandle("a"));
        $pool->add($this->createHandle("b"));
        $this->assertEquals(2, $pool->add($this->createHandle("b")));
    }

    public function testOptimizedResultGroupGetsOwnResults()
    {
        $groupResults = null;

        $pool = new HTTPRequestPool(true);
        $pool->add($this->createHandle("a"));
        $pool->add($this->createHandle("b"));

        // Group contains a duplicate of 'b', its result should be 'b'
        $group = new HTTPRequestResultGroup();
        $group->add($this->createHandle("b"));
        $pool->addResultGroup($group, function($results) use(&$groupResults) { $groupResults = $results; });
        $pool->execute();

        $this->assertEquals(["b"], array_map(fn($r) => $this->getId($r), array_values($groupResults)));
    }

    public function testResultGroupWithoutCallback()
    {
        $group = new HTTPRequestResultGroup();
        $group->add($this->createHandle("a"));

        $pool = new HTTPRequestPool();
        $pool->addResultGroup($group);
        $this->assertCount(1, $pool->execute());
    }
}
