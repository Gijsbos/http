<?php
declare(strict_types=1);

namespace WDS;

use gijsbos\Http\Http\HTTPRequestLog;
use PHPUnit\Framework\TestCase;

final class HTTPRequestLogTest extends TestCase
{
    private string $logFile;
    private string|false $previousLogFile;

    protected function setUp() : void
    {
        // HTTPRequestLog writes to error_log(), redirect it to a temp file
        $this->logFile = tempnam(sys_get_temp_dir(), "http-log-test");
        $this->previousLogFile = ini_set("error_log", $this->logFile);
    }

    protected function tearDown() : void
    {
        ini_set("error_log", (string) $this->previousLogFile);
        @unlink($this->logFile);
    }

    /**
     * messages
     *  Logged lines without the error_log timestamp prefix
     */
    private function messages() : array
    {
        $lines = array_filter(explode("\n", (string) file_get_contents($this->logFile)));
        return array_values(array_map(fn($line) => preg_replace("/^\[[^\]]+\] /", "", $line), $lines));
    }

    private function createLog(array $data = [], array $headers = []) : HTTPRequestLog
    {
        return new HTTPRequestLog("POST", "/api/users", $data, $headers);
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

    public function testApplySecretFilterNested()
    {
        $this->assertEquals(["user" => ["name" => "a", "password" => "********"]], HTTPRequestLog::applySecretFilter(["user" => ["name" => "a", "password" => "p"]]));
    }

    public function testApplySecretFilterSequentialServerStyle()
    {
        $this->assertEquals(["AUTHORIZATION= ********"], HTTPRequestLog::applySecretFilter(["AUTHORIZATION=Bearer xyz"]));
    }

    public function testApplySecretFilterSequentialAuthorizationHeader()
    {
        $this->assertEquals(["Authorization: ********", "X-A: 1"], HTTPRequestLog::applySecretFilter(["Authorization: Bearer xyz", "X-A: 1"]));
    }

    public function testLogLevelZeroDoesNotLog()
    {
        $this->createLog()->logRequest(0);
        $this->assertEquals([], $this->messages());
    }

    public function testLogLevelOneLogsMethodAndURI()
    {
        $this->createLog(["a" => 1], ["X-A" => "1"])->logRequest(1);
        $this->assertEquals(["[type=POST, uri=/api/users]"], $this->messages());
    }

    public function testLogLevelTwoLogsFilteredData()
    {
        $this->createLog(["a" => 1, "password" => "p"], ["X-A" => "1"])->logRequest(2);
        $this->assertEquals(["[type=POST, uri=/api/users, data=[a=1, password=********]]"], $this->messages());
    }

    public function testLogLevelThreeLogsFilteredHeaders()
    {
        $this->createLog([], ["Authorization" => "Bearer x"])->logRequest(3);
        $this->assertEquals(["[type=POST, uri=/api/users, headers=[Authorization=********]]"], $this->messages());
    }

    public function testFields()
    {
        $log = $this->createLog();
        $log->setFields(["origin" => "web", "id" => "1"]);
        $log->logRequest(1);
        $this->assertEquals(["[type=POST, uri=/api/users, origin=web, id=1]"], $this->messages());
    }
}
