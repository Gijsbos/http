<?php
declare(strict_types=1);

namespace WDS;

use gijsbos\Http\Response;
use gijsbos\Http\Utils\FilterInput;

chdir("../../");

(function($setup = "tests/Autoload.php") { if(!is_file($setup)) { exit("Could not load app initializer."); } else { require_once($setup); }})();

/**
 * HTTPTestController
 */
class HTTPTestController extends FilterInput
{
    /**
     * __construct
     */
    public function __construct()
    {
        parent::__construct();

        $this->init();
    }

    /**
     * init
     */
    private function init()
    {
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        switch($requestMethod)
        {
            case "GET":
                $this->testGETInput();
            break;
            case "POST":
                $this->testPOSTInput();
            break;
            case "PUT":
                $this->testPUTInput();
            break;
            case "PATCH":
                $this->testPATCHInput();
            break;
            case "DELETE":
                $this->testDELETEInput();
            break;
        }
    }

    /**
     * sendResponse
     */
    private function sendResponse(array $args) : Response
    {
        $statusCode = @$args["statusCode"];
        if($statusCode === null)
        {
            $statusCode = 200;
        }

        $returnValue = @$args["returnValue"];
        if(is_json($returnValue))
        {
            $returnValue = json_decode($returnValue, true);
        }
    
        return new Response($returnValue, $statusCode);
    }

    /**
     * testGETInput
     */
    public function testGETInput()
    {
        $returnValue = $this->fetch(JSON | GET, "returnValue", null, null, true);
        $statusCode = $this->fetch(STRING | GET, "statusCode", null, null, true);
        $this->sendResponse(\get_defined_vars())->send();
    }

    /**
     * testPOSTInput
     */
    public function testPOSTInput()
    {
        $returnValue = $this->fetch(JSON | POST, "returnValue", null, null, true);
        $statusCode = $this->fetch(STRING | POST, "statusCode", null, null, true);
        $this->sendResponse(\get_defined_vars())->send();
    }

    /**
     * testPUTInput
     */
    public function testPUTInput()
    {
        $returnValue = $this->fetch(JSON | PUT, "returnValue", null, null, true);
        $statusCode = $this->fetch(STRING | PUT, "statusCode", null, null, true);
        $this->sendResponse(\get_defined_vars())->send();
    }

    /**
     * testPATCHInput
     */
    public function testPATCHInput()
    {
        $returnValue = $this->fetch(JSON | PATCH, "returnValue", null, null, true);
        $statusCode = $this->fetch(STRING | PATCH, "statusCode", null, null, true);
        $this->sendResponse(\get_defined_vars())->send();
    }

    /**
     * testDELETEInput
     */
    public function testDELETEInput()
    {
        $returnValue = $this->fetch(JSON | DELETE, "returnValue", null, null, true);
        $statusCode = $this->fetch(STRING | DELETE, "statusCode", null, null, true);
        $this->sendResponse(\get_defined_vars())->send();
    }
}

// Start the controller
new HTTPTestController();