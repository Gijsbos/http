<?php
declare(strict_types=1);

namespace WDS;

use gijsbos\Http\Http\HTTPRequestLog;
use PHPUnit\Framework\TestCase;

/**
 * capture_http_request_log
 *  HTTPRequestLog::setFunction only accepts function names
 */
function capture_http_request_log(string $message) : void
{
    HTTPRequestLogTest::$messages[] = $message;
}

final class HTTPRequestLogTest extends TestCase
{
    public static array $messages = [];

    protected function setUp() : void
    {
        self::$messages = [];
    }

    /**
     * createLog
     */
    private function createLog(array $data = [], array $headers = []) : HTTPRequestLog
    {
        $log = new HTTPRequestLog("POST", "/api/users", $data, $headers);
        $log->setFunction(__NAMESPACE__ . '\capture_http_request_log');
        $log->setFormat("json");
        return $log;
    }

    public function testApplySecretFilterAssocKeys()
    {
        $result = HTTPRequestLog::applySecretFilter([
            "username" => "john",
            "password" => "secret1",
            "PassCode" => "1234",
            "client_secret" => "abc",
            "accessToken" => "xyz",
            "Authorization" => "Bearer xyz",
        ]);
        $this->assertEquals([
            "username" => "john",
            "password" => "********",
            "PassCode" => "********",
            "client_secret" => "********",
            "accessToken" => "********",
            "Authorization" => "********",
        ], $result);
    }

    public function testApplySecretFilterSequentialServerStyle()
    {
        $this->assertEquals(["AUTHORIZATION= ********"], HTTPRequestLog::applySecretFilter(["AUTHORIZATION=Bearer xyz"]));
    }

    public function testLogLevelZeroDoesNotLog()
    {
        $this->createLog()->logRequest(0);
        $this->assertEquals([], self::$messages);
    }

    public function testLogLevelOneLogsMethodAndURI()
    {
        $this->createLog(["a" => 1], ["X-A" => "1"])->logRequest(1);
        $this->assertEquals(['{"type":"POST","uri":"\/api\/users"}'], self::$messages);
    }

    public function testLogLevelTwoLogsFilteredData()
    {
        $this->createLog(["a" => 1, "password" => "p"], ["X-A" => "1"])->logRequest(2);
        $this->assertEquals(['{"type":"POST","uri":"\/api\/users","data":{"a":1,"password":"********"}}'], self::$messages);
    }

    public function testLogLevelThreeLogsFilteredHeaders()
    {
        $this->createLog([], ["Authorization" => "Bearer x"])->logRequest(3);
        $this->assertEquals(['{"type":"POST","uri":"\/api\/users","headers":{"Authorization":"********"}}'], self::$messages);
    }

    public function testFieldsAndPrefix()
    {
        $log = $this->createLog();
        $log->addOrigin("web");
        $log->addFrom("a");
        $log->addTo("b");
        $log->prependField("id", "1");
        $log->setPrefix("[api] ");
        $log->logRequest(1);
        $this->assertEquals(['[api] {"type":"POST","uri":"\/api\/users","id":"1","origin":"web","from":"a","to":"b"}'], self::$messages);
    }

    public function testDefaultFormat()
    {
        $log = $this->createLog();
        $log->setFormat("default");
        $log->logRequest(1);
        $this->assertStringContainsString("type=POST", self::$messages[0]);
        $this->assertStringContainsString("uri=/api/users", self::$messages[0]);
    }

    public function testApplySecretFilterSequentialAuthorizationHeader()
    {
        $this->assertEquals(["Authorization: ********", "X-A: 1"], HTTPRequestLog::applySecretFilter(["Authorization: Bearer xyz", "X-A: 1"]));
    }

    public function testLogWithoutFunction()
    {
        $log = new HTTPRequestLog("GET", "/");
        $log->logRequest(1);
        $this->addToAssertionCount(1);
    }
}
