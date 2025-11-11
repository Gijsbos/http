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
}