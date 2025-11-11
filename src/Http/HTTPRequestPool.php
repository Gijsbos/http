<?php
declare(strict_types=1);

namespace gijsbos\Http\Http;

use CurlHandle;

/**
 * HTTPRequestPool
 *  Handles multiple http requests (curl) at the same time.
 *  Can be optimized when http requests include a request hash, calling requests that are identical only once.
 */
class HTTPRequestPool
{
    /**
     * @var \CurlMultiHandle $curlMultiHandle
     */
    private $curlMultiHandle;

    /**
     * @var \CurlHandle[] $handles
     */
    private $handles;

    /**
     * @var mixed[] $callbacks
     *  An array of callback arrays
     */
    private $callbacks;

    /**
     * @var mixed[] $requestHashes
     */
    private $requestHashes;

    /**
     * @var array[] $results
     */
    private $results;

    /**
     * @var callable[] $onResultReceived
     */
    private $onResultReceived;

    /**
     * @var bool $optimizeRequests
     *  Will remove duplicate requests from the pool.
     *  This only works when HTTPRequestHandle objects are passed to the 'add' method.
     */
    private $optimizeRequests;

    /**
     * __construct
     */
    public function __construct(bool $optimizeRequests = false)
    {
        $this->curlMultiHandle = curl_multi_init();
        $this->handles = [];
        $this->callbacks = [];
        $this->requestHashes = [];
        $this->results = [];
        $this->onResultReceived = [];
        $this->optimizeRequests = $optimizeRequests;
    }

    /**
     * getHandles
     */
    public function getHandles()
    {
        return $this->handles;
    }

    /**
     * getCallbacks
     */
    public function getCallbacks()
    {
        return $this->callbacks;
    }

    /**
     * addOnResultReceived
     */
    public function addOnResultReceived(callable $callback) : void
    {
        $this->onResultReceived[] = $callback;
    }

    /**
     * getResults
     */
    public function getResults()
    {
        return $this->results;
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
     * getExistingHandleIndex
     */
    private function getExistingHandleIndex(null|string $hash = null)
    {
        if($hash === null)
            return false;

        if(in_array($hash, $this->requestHashes))
            return array_search($hash, $this->requestHashes);

        return false;
    }

    /**
     * add
     * 
     * @param HTTPRequestHandle|CurlHandle $handle used for multi request
     * @param callback $callback function called after completion
     * @param string $hash CurlHandle request hash used for optimization
     * @return int handle index
     */
    public function add(HTTPRequestHandle | CurlHandle $handle, null|callable $callback = null, null|string $hash = null)
    {
        // If an HTTP Request Handle is added, if will assume its values for callback and hash, function parameters will act as override
        if($handle instanceof HTTPRequestHandle)
        {
            if($callback === null)
                $callback = $handle->getCallback();

            if($hash === null)
                $hash = $handle->getHash();

            $handle = $handle->getHandle();
        }

        // Get handle count
        $index = count($this->handles);

        // Optimize
        if($this->optimizeRequests)
        {
            // Get id
            $existingHandleIndex = $this->getExistingHandleIndex($hash);

            // An existing request handle has been found, with its index returned.
            if($existingHandleIndex !== false)
            {
                // Add empty handle to make sure the index is used
                $this->handles[$index] = null;

                // Add callback to existing handle
                $this->callbacks[$existingHandleIndex][] = [
                    "index" => $index,
                    "callback" => $callback,
                ];

                // Add empty request hash so it will not be used in further lookups
                $this->requestHashes[] = null;

                // Add to multi curl handle
                return 0; // 0 = Same as curl_multi_add_handle returns on success
            }
        }

        // Add handle with an index, used to find the handle later on
        $this->handles[$index] = $handle;

        // Add callback to callbacks array; this is executed when the handle resolves and allows multiple callbacks to act on a single handle.
        $this->callbacks[$index] = [
            [
                "index" => $index, // Index is added so we can set the result of multiple requests when closing the handle
                "callback" => $callback,
            ]
        ];

        // Add a hash for lookup
        $this->requestHashes[$index] = $hash;

        // Add to multi curl handle
        $addResult = curl_multi_add_handle($this->curlMultiHandle, $handle);

        // Return index
        return $index;
    }

    /**
     * addResultGroup
     *  Adds a HTTPRequestResultGroup and callback for group result
     */
    public function addResultGroup(HTTPRequestResultGroup $httpRequestResultGroup, null|callable $callback = null) : void
    {
        $indices = [];

        // Add handles to pool, store indices for retrieving results later
        foreach($httpRequestResultGroup->getHandles() as $handle)
            $indices[] = $this->add($handle);

        // Set callback if overwrite has not been provided
        if($callback === null && $httpRequestResultGroup->hasCallback())
            $callback = $httpRequestResultGroup->getCallback();

        // Add onResultReceived callable, extracting relevant results using indices
        $this->addOnResultReceived(function($results) use ($indices, $callback)
        {
            // Filter out relevant results
            $groupResults = array_filter_keys($results, $indices);

            // Sort keys
            $groupResults = array_sort_keys($groupResults);

            // Execute
            $callback($groupResults);
        });
    }

    /**
     * executeOnResultReceived
     */
    private function executeOnResultReceived()
    {
        foreach($this->onResultReceived as $callback)
            $callback($this->results);
    }

    /**
     * closeHandles
     */
    private function closeHandles(null|int $flags = null)
    {
        foreach($this->handles as $i => $handle)
        {
            if($handle !== null)
            {
                // Remove handle
                curl_multi_remove_handle($this->curlMultiHandle, $handle);

                // Store results
                $result = HTTPRequest::handleCurlResult($handle, curl_multi_getcontent($handle), $flags);

                // Iterate over callbacks
                foreach($this->callbacks[$i] as $callbackInfo)
                {
                    $index = $callbackInfo["index"];
                    $callback = $callbackInfo["callback"];

                    $this->results[$index] = $result;

                    if($callback)
                        $callback($result);
                }
            }
        }

        // Close multi curl
        curl_multi_close($this->curlMultiHandle);

        // Execute callbacks
        $this->executeOnResultReceived();
    }

    /**
     * execute
     */
    public function execute(null|int $flags = null)
    {
        $active = null;

        // Execute the handles
        do
        {
            $mrc = curl_multi_exec($this->curlMultiHandle, $active);
        }
        while ($mrc == CURLM_CALL_MULTI_PERFORM);

        // Wait a while longer
        while ($active && $mrc == CURLM_OK)
        {
            if (curl_multi_select($this->curlMultiHandle) != -1)
            {
                do
                {
                    $mrc = curl_multi_exec($this->curlMultiHandle, $active);
                }
                while ($mrc == CURLM_CALL_MULTI_PERFORM);
            }
        }

        // Close handles
        $this->closeHandles($flags);

        // Return result
        return $this->results;
    }

    /**
     * create
     */
    public static function create(bool $optimizeRequests = false)
    {
        return new self($optimizeRequests);
    }
}