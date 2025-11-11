<?php
declare(strict_types=1);

namespace gijsbos\Http;

use Exception;

/**
 * RequestMethod
 */
abstract class RequestMethod
{
    const GET = GET;
    const POST = POST;
    const PUT = PUT;
    const DELETE = DELETE;
    const PATCH = PATCH;
    const COPY = COPY;
    const HEAD = HEAD;
    const OPTIONS = OPTIONS;
    const LINK = LINK;
    const UNLINK = UNLINK;
    const PURGE = PURGE;
    const LOCK = LOCK;
    const UNLOCK = UNLOCK;
    const PROPFIND = PROPFIND;
    const VIEW = VIEW;

    /**
     * convertToConstant
     */
    public static function convertToConstant($method)
    {
        if(is_string($method))
        {
            if(defined($method))
                return constant($method);
        }
        return $method;
    }

    /**
     * convertToString
     */
    public static function convertToString($method)
    {
        if(is_int($method) || is_numeric($method))
        {
            $method = intval($method);
            
            switch($method)
            {
                case GET: return "GET";
                case POST: return "POST";
                case PUT: return "PUT";
                case DELETE: return "DELETE";
                case PATCH: return "PATCH";
                case COPY: return "COPY";
                case HEAD: return "HEAD";
                case OPTIONS: return "OPTIONS";
                case LINK: return "LINK";
                case UNLINK: return "UNLINK";
                case PURGE: return "PURGE";
                case LOCK: return "LOCK";
                case UNLOCK: return "UNLOCK";
                case PROPFIND: return "PROPFIND";
                case VIEW: return "VIEW";
                default:
                    throw new Exception("Could not convert unknown request method constant '$method'");
            }
        }
        return $method;
    }
}