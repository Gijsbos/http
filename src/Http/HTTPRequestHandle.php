<?php
declare(strict_types=1);

namespace gijsbos\Http\Http;

use CurlHandle;

/**
 * HTTPRequestHandle
 */
class HTTPRequestHandle
{
    /**
     * @var CurlHandle handle
     */
    private $handle;

    /**
     * @var callable callback
     */
    private $callback;

    /**
     * @var string hash
     */
    private $hash;

    /**
     * __construct
     */
    public function __construct(CurlHandle $handle, null|callable $callback = null, null|string $hash = null)
    {
        $this->handle = $handle;
        $this->callback = $callback;
        $this->hash = $hash;
    }

    /**
     * getHandle
     */
    public function getHandle()
    {
        return $this->handle;
    }

    /**
     * getCallback
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * getHash
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * executeCallback
     */
    public function executeCallback($result)
    {
        $callback = $this->callback;

        if($callback === null)
            return $result;

        return $callback($result);
    }

    /**
     * createHash
     */
    public static function createHash(string $uri, null|string|array $params = null, null|string|array $headers = null)
    {
        $hashSeed = [$uri];
        
        if(is_array($params))
        {
            foreach($params as $key => $value)
                $hashSeed[] = "$key-$value";
        }
        else if(is_string($params))
            $hashSeed[] = $params;
            
        if(is_array($headers))
        {
            foreach($headers as $key => $value)
                $hashSeed[] = "$key-$value";
        }
        else if(is_string($headers))
            $hashSeed[] = $headers;

        return hash('xxh3', implode("|", $hashSeed));
    }
}