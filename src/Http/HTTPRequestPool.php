<?php
declare(strict_types=1);

namespace gijsbos\Http\Http;

use CurlHandle;

/**
 * HTTPRequestPool
 *  Executes multiple http requests at the same time (curl multi).
 *
 *      $pool = new HTTPRequestPool();
 *      $pool->add(HTTPRequest::get(["uri" => "..."], HTTP_REQUEST_HANDLE), fn(Response $r) => ...);
 *      $results = $pool->execute(); // Response per index, in the order the requests were added
 *
 *  optimizeRequests: identical requests (same method, url, data and headers) are sent once,
 *  each caller still gets its own index and callback.
 */
class HTTPRequestPool
{
    /**
     * @var \CurlMultiHandle $curlMultiHandle
     */
    private $curlMultiHandle;

    /**
     * @var (CurlHandle|null)[] $handles
     *  Null for a duplicate that is served by another handle
     */
    private array $handles = [];

    /**
     * @var array[] $callbacks
     *  Per handle index: [["index" => int, "callback" => ?callable], ...]
     */
    private array $callbacks = [];

    /**
     * @var (string|null)[] $requestHashes
     */
    private array $requestHashes = [];

    /**
     * @var array $results
     */
    private array $results = [];

    /**
     * @var bool $optimizeRequests
     *  Only works with HTTPRequestHandle objects or when a hash is passed to add()
     */
    private bool $optimizeRequests;

    /**
     * __construct
     */
    public function __construct(bool $optimizeRequests = false)
    {
        $this->curlMultiHandle = curl_multi_init();
        $this->optimizeRequests = $optimizeRequests;
    }

    /**
     * create
     */
    public static function create(bool $optimizeRequests = false)
    {
        return new self($optimizeRequests);
    }

    /**
     * count
     */
    public function count() : int
    {
        return count($this->handles);
    }

    /**
     * hasHandles
     */
    public function hasHandles() : bool
    {
        return $this->count() > 0;
    }

    /**
     * add
     *
     * @param HTTPRequestHandle|CurlHandle $handle - A HTTPRequestHandle provides its own callback and hash, the parameters override them
     * @param callable $callback - Called with the Response when the request is done
     * @param string $hash - Request hash used by optimizeRequests
     * @return int index of the result
     */
    public function add(HTTPRequestHandle | CurlHandle $handle, null|callable $callback = null, null|string $hash = null)
    {
        if($handle instanceof HTTPRequestHandle)
        {
            $callback ??= $handle->getCallback();
            $hash ??= $handle->getHash();
            $handle = $handle->getHandle();
        }

        $index = count($this->handles);

        // Duplicate request: no new handle, the result of the existing handle is also stored at this index
        $existingIndex = $this->optimizeRequests && $hash !== null ? array_search($hash, $this->requestHashes, true) : false;

        if($existingIndex !== false)
        {
            $this->handles[$index] = null;
            $this->requestHashes[$index] = null;
            $this->callbacks[$existingIndex][] = ["index" => $index, "callback" => $callback];

            return $index;
        }

        $this->handles[$index] = $handle;
        $this->requestHashes[$index] = $hash;
        $this->callbacks[$index] = [["index" => $index, "callback" => $callback]];

        curl_multi_add_handle($this->curlMultiHandle, $handle);

        return $index;
    }

    /**
     * execute
     *
     * @return array Response per index
     */
    public function execute(null|int $flags = null)
    {
        $active = 0;

        // Run until all transfers are done; select() waits for network activity instead of spinning.
        // select() can return -1 right away on some systems, sleep briefly then to avoid a 100% CPU busy loop.
        do
        {
            $status = curl_multi_exec($this->curlMultiHandle, $active);

            if($active && curl_multi_select($this->curlMultiHandle, 1.0) === -1)
                usleep(1000);
        }
        while($active && $status === CURLM_OK);

        $this->closeHandles($flags);

        return $this->results;
    }

    /**
     * closeHandles
     */
    private function closeHandles(null|int $flags = null) : void
    {
        // curl_error() is empty for pooled handles, the curl result code of each handle is only available here
        $errnos = [];
        while($info = curl_multi_info_read($this->curlMultiHandle))
            $errnos[spl_object_id($info["handle"])] = $info["result"];

        foreach($this->handles as $i => $handle)
        {
            if($handle === null)
                continue;

            curl_multi_remove_handle($this->curlMultiHandle, $handle);

            $result = HTTPRequest::handleCurlResult($handle, curl_multi_getcontent($handle) ?? "", $flags, $errnos[spl_object_id($handle)] ?? 0);

            foreach($this->callbacks[$i] as ["index" => $index, "callback" => $callback])
            {
                $this->results[$index] = $result;

                if($callback)
                    $callback($result);
            }
        }

        curl_multi_close($this->curlMultiHandle);

        ksort($this->results);
    }
}
