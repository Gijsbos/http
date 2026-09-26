<?php
declare(strict_types=1);

/**
 * EchoController
 *  Returns the request as received by the server, used to verify what HTTPRequest actually sends.
 */
$headers = [];
foreach($_SERVER as $key => $value)
    if(str_starts_with($key, "HTTP_X_"))
        $headers[strtolower(str_replace("_", "-", substr($key, 5)))] = $value;

header("Content-Type: application/json");

if(isset($_GET["statusCode"]))
    http_response_code(intval($_GET["statusCode"]));

// Plain text response
if(isset($_GET["text"]))
{
    header("Content-Type: text/plain", true);
    exit($_GET["text"]);
}

// Empty response
if(isset($_GET["empty"]))
    exit();

echo json_encode([
    "method" => $_SERVER["REQUEST_METHOD"],
    "query" => $_GET,
    "contentType" => $_SERVER["CONTENT_TYPE"] ?? null,
    "body" => file_get_contents("php://input"),
    "bodyBase64" => base64_encode(file_get_contents("php://input")), // Body that is not altered by response parsing
    "headers" => $headers,
]);
