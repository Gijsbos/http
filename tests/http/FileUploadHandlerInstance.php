<?php

use gijsbos\ExtFuncs\Exceptions\FileUploadHandlerException;
use gijsbos\Http\Utils\FileUploadHandler;

chdir("../../");

(function($setup = "tests/Autoload.php") { if(!is_file($setup)) { exit("Could not load app initializer."); } else { require_once($setup); }})();

try
{
    // Create parser
    $envFileParser = new FileUploadHandler(false);

    // Handle file
    $envFileParser->handle($_POST["file-name"], FileUploadHandler::KB * 1, [FileUploadHandler::MIME_TXT], $_POST["upload-dir"], @$_POST["storage-name"]);
}
catch(FileUploadHandlerException $ex)
{
    echo($ex->getMessage());
}
