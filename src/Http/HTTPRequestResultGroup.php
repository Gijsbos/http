<?php
declare(strict_types=1);

namespace gijsbos\Http\Http;

use CurlHandle;

/**
 * HTTPRequestResultGroup
 */
class HTTPRequestResultGroup
{
    private array $handles;
    private $callback;

    /**
     * __construct
     */
    public function __construct()
    {
        $this->handles = [];
        $this->callback = null;
    }

    /**
     * getHandles
     */
    public function getHandles()
    {
        return $this->handles;
    }

    /**
     * setCallback
     */
    public function setCallback(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * hasCallback
     */
    public function hasCallback() : bool
    {
        return $this->callback !== null;
    }

    /**
     * getCallback
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * executeCallback
     */
    public function executeCallback($data)
    {
        $callback = $this->callback;

        // Execute
        return $callback($data);
    }

    /**
     * add
     */
    public function add(HTTPRequestHandle | CurlHandle $handle)
    {
        $this->handles[] = $handle;
    }
}