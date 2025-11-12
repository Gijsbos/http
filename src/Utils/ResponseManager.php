<?php
declare(strict_types=1);

namespace gijsbos\Http\Utils;

use gijsbos\Http\Exceptions\BadRequestException;
use gijsbos\Http\Exceptions\ConflictException;
use gijsbos\Http\Exceptions\ForbiddenException;
use gijsbos\Http\Exceptions\HTTPRequestException;
use gijsbos\Http\Exceptions\HTTPRequestSentToHTTPSPortException;
use gijsbos\Http\Exceptions\InternalServerErrorException;
use gijsbos\Http\Exceptions\InvalidArgumentErrorException;
use gijsbos\Http\Exceptions\InvalidArgumentInputException;
use gijsbos\Http\Exceptions\InvalidArgumentMissingException;
use gijsbos\Http\Exceptions\InvalidArgumentTypeException;
use gijsbos\Http\Exceptions\MethodNotAllowedException;
use gijsbos\Http\Exceptions\NotAcceptableException;
use gijsbos\Http\Exceptions\ResourceNotFoundException;
use gijsbos\Http\Exceptions\UnauthorizedException;
use gijsbos\Http\Response;

/**
 * ResponseManager
 */
abstract class ResponseManager
{
    /**
     * success
     *  StatusCode: (200) 
     */
    public static function success(array $data = array()) : Response
    {
        return new Response($data, 200);
    }

    /**
     * created
     *  StatusCode: (201) 
     */
    public static function created(array $data = array()) : Response
    {
        return new Response($data, 201);
    }

    /**
     * badRequest
     *  StatusCode: (400) 
     */
    public static function badRequest(null|string $error = null, null|string $errorDescription = null, null|array $data = null) : Response
    {
        $response = new Response();

        $response->setError(400,
            $error === null ? (new BadRequestException())->error : $error,
            $errorDescription === null ? (new BadRequestException())->errorDescription : $errorDescription
        );

        // Add optional data to response
        if($data !== null) $response->addParameters($data);

        return $response;
    }

    /**
     * unauthorized
     *  StatusCode: (401) 
     */
    public static function unauthorized(null|string $error = null, null|string $errorDescription = null, null|array $data = null) : Response
    {
        $response = new Response();

        $response->setError(401,
            $error === null ? (new UnauthorizedException())->error : $error,
            $errorDescription === null ? (new UnauthorizedException())->errorDescription : $errorDescription
        );

        // Add optional data to response
        if($data !== null) $response->addParameters($data);

        return $response;
    }

    /**
     * forbidden
     *  StatusCode: (403) 
     */
    public static function forbidden(null|string $error = null, null|string $errorDescription = null, null|array $data = null) : Response
    {
        $response = new Response();

        $response->setError(403,
            $error === null ? (new ForbiddenException())->error : $error,
            $errorDescription === null ? (new ForbiddenException())->errorDescription : $errorDescription
        );

        // Add optional data to response
        if($data !== null) $response->addParameters($data);

        return $response;
    }

    /**
     * resourceNotFound
     *  StatusCode: (404) 
     */
    public static function resourceNotFound(null|string $error = null, null|string $errorDescription = null, null|array $data = null) : Response
    {
        $response = new Response();

        $response->setError(404,
            $error === null ? (new ResourceNotFoundException())->error : $error,
            $errorDescription === null ? (new ResourceNotFoundException())->errorDescription : $errorDescription
        );

        // Add optional data to response
        if($data !== null) $response->addParameters($data);

        return $response;
    }

    /**
     * extractEntityName
     *  Extracts the entity name from entity in case it contains a namespace separated by underscores
     *  e.g. API_Log => returns Log, THIS_IS_A_NAMESPACE => returns NAMESPACE
     */
    private static function extractEntityName(string $entityName) : string
    {
        // Remove namespaces
        if(preg_match("/^.*_([a-zA-Z]+)$/", $entityName, $matches) === 1)
            $entityName = $matches[1];
        
        return $entityName;
    }

    /**
     * entityNotFound
     *  StatusCode: (404) 
     */
    public static function entityNotFound(string $entityName, null|string $errorDescription = null, null|array $data = null) : Response
    {
        $response = new Response();

        // Parse entity name
        $entityName = self::extractEntityName($entityName);

        // Set error
        $response->setError(404,
            lcfirst($entityName) . "NotFound",
            $errorDescription === null ? "Entity '$entityName' could not be found" : $errorDescription
        );

        // Add optional data to response
        if($data !== null) $response->addParameters($data);

        return $response;
    }

    /**
     * methodNotAllowed
     *  StatusCode: (405) 
     */
    public static function methodNotAllowed(null|string $error = null, null|string $errorDescription = null, null|array $data = null) : Response
    {
        $response = new Response();

        $response->setError(405,
            $error === null ? (new MethodNotAllowedException())->error : $error,
            $errorDescription === null ? (new MethodNotAllowedException())->errorDescription : $errorDescription
        );

        // Add optional data to response
        if($data !== null) $response->addParameters($data);

        return $response;
    }

    /**
     * notAcceptable
     *  StatusCode: (406) 
     */
    public static function notAcceptable(null|string $error = null, null|string $errorDescription = null, null|array $data = null) : Response
    {
        $response = new Response();

        $response->setError(406,
            $error === null ? (new NotAcceptableException())->error : $error,
            $errorDescription === null ? (new NotAcceptableException())->errorDescription : $errorDescription
        );

        // Add optional data to response
        if($data !== null) $response->addParameters($data);

        return $response;
    }

    /**
     * conflict
     *  StatusCode: (409) 
     */
    public static function conflict(null|string $error = null, null|string $errorDescription = null, null|array $data = null) : Response
    {
        $response = new Response();

        $response->setError(409,
            $error === null ? (new ConflictException())->error : $error,
            $errorDescription === null ? (new ConflictException())->errorDescription : $errorDescription
        );

        // Add optional data to response
        if($data !== null) $response->addParameters($data);

        return $response;
    }

    /**
     * httpRequestSentToHTTPSPort
     *  StatusCode: (497) 
     */
    public static function httpRequestSentToHTTPSPort(null|string $error = null, null|string $errorDescription = null, null|array $data = null) : Response
    {
        $response = new Response();

        $response->setError(497,
            $error === null ? (new HTTPRequestSentToHTTPSPortException())->error : $error,
            $errorDescription === null ? (new HTTPRequestSentToHTTPSPortException())->errorDescription : $errorDescription
        );

        // Add optional data to response
        if($data !== null) $response->addParameters($data);

        return $response;
    }

    /**
     * internalServerError
     *  StatusCode: (500) 
     */
    public static function internalServerError(null|string $error = null, null|string $errorDescription = null, null|array $data = null) : Response
    {
        $response = new Response();

        $response->setError(500,
            $error === null ? (new InternalServerErrorException())->error : $error,
            $errorDescription === null ? (new InternalServerErrorException())->errorDescription : $errorDescription
        );

        // Add optional data to response
        if($data !== null) $response->addParameters($data);

        return $response;
    }

    /**
     * formatError
     *  Formats error codes, default in camel case.
     */
    private static function formatError($error, $type)
    {
        if(strpos($error, "-") !== false)
            return $error . strtolower(preg_replace("/([A-Z])/", "-\$1", $type));
        else if(strpos($error, "_") !== false)
            return $error . strtolower(preg_replace("/([A-Z])/", "_\$1", $type));
        else
            return $error.$type;
    }

    /**
     * exceptionToResponse
     */
    public static function exceptionToResponse($exception) : Response
    {
        // Check if the exception is a http request exception
        if($exception instanceof HTTPRequestException)
        {
            $response = new Response();

            // Check statusCode
            if($exception->statusCode < 100 || $exception->statusCode >= 600)
                $exception->statusCode = 500;

            // Check if errors were set
            if($exception->statusCode > 201)
            {
                // Set error details
                $response->setError($exception->statusCode, $exception->error, $exception->errorDescription);

                // Add data to response
                $response->addParameters($exception->data === null ? array() : $exception->data);
            }

            return $response;
        }
        else if($exception instanceof InvalidArgumentTypeException)
        {
            return ResponseManager::badRequest(self::formatError($exception->argument, "IncorrectType"), $exception->getMessage());
        }
        else if($exception instanceof InvalidArgumentInputException)
        {
            return ResponseManager::badRequest(self::formatError($exception->argument, "IncorrectInput"), $exception->getMessage());
        }
        else if($exception instanceof InvalidArgumentMissingException)
        {
            return ResponseManager::badRequest(self::formatError($exception->argument, "Missing"), $exception->getMessage());
        }
        else if($exception instanceof InvalidArgumentErrorException)
        {
            return ResponseManager::badRequest($exception->error, $exception->description);
        }
        else
        {
            return ResponseManager::internalServerError(null, $exception->getMessage());
        }
    }

    /**
     * responseToString
     */
    public static function responseToString(Response $response) : string
    {
        return sprintf("[%s] %s", $response->getStatusCode(), implode_key_value_array($response->getParameters()));
    }

    /**
     * responseToException
     */
    public static function responseToException(Response $response)
    {
        $statusCode = $response->getStatusCode();

        // Get Exception Parameters
        $error = $response->getParameter("error");
        $errorDescription = $response->getParameter("errorDescription");

        // Get data without error and errorDescription because they are added separately
        $data = $response->getParameters();

        if(array_key_exists("error", $data))
            unset($data["error"]);

        if(array_key_exists("errorDescription", $data))
            unset($data["errorDescription"]);

        // Resolve
        switch(true)
        {
            case $statusCode === 400; return new BadRequestException($error, $errorDescription, $data);
            case $statusCode === 400; return new BadRequestException($error, $errorDescription, $data);
            case $statusCode === 401; return new UnauthorizedException($error, $errorDescription, $data);
            case $statusCode === 403; return new ForbiddenException($error, $errorDescription, $data);
            case $statusCode === 404; return new ResourceNotFoundException($error, $errorDescription, $data);
            case $statusCode === 405; return new MethodNotAllowedException($error, $errorDescription, $data);
            case $statusCode === 406; return new NotAcceptableException($error, $errorDescription, $data);
            case $statusCode === 409; return new ConflictException($error, $errorDescription, $data);
            case $statusCode === 497; return new HTTPRequestSentToHTTPSPortException($error, $errorDescription, $data);
            case $statusCode < 100 || $statusCode >= 500; return new InternalServerErrorException($error, $errorDescription, $data);
            default: new \Exception(self::responseToString($response));
        }
        
        return null;
    }
}