<?php
declare(strict_types=1);

namespace WDS;

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
        $this->expectExceptionMessage("(400) error - errorDescription ([key-1] => value-1, [key-2] => [key-3] => value-2)");

        // Throw exception
        throw new HTTPRequestException(400, "error", "errorDescription", array(
            "key-1" => "value-1",
            "key-2" => array(
                "key-3" => "value-2"
            )
        ));
    }
}