<?php

namespace PragmaRX\Tracker\Support;

use Cache as IlluminateCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use PragmaRX\Support\Config as Config;

class Cache
{
    private $app;

    private $config;

    public function __construct(Config $config, Application $app)
    {
        $this->config = $config;

        $this->app = $app;
    }

    public function cachePut($cacheKey, $model)
    {
        IlluminateCache::put($cacheKey, $model, 10);
    }

    private function extractAttributes($attributes)
    {
        if (is_array($attributes) || is_string($attributes))
        {
            return $attributes;
        }

        if (is_string($attributes) || is_numeric($attributes))
        {
            return (array) $attributes;
        }

        if ($attributes instanceof Model)
        {
            return $attributes->getAttributes();
        }
    }

    /**
     * @param $attributes
     * @param $keys
     * @return array
     */
    private function extractKeys($attributes, $keys)
    {
        if (!$keys) {
            $keys = array_keys($attributes);
        }

        if (!is_array($keys)) {
            $keys = (array) $keys;

            return $keys;
        }

        return $keys;
    }

    /**
     * @param $key
     * @return array
     */
    public function findCachedWithKey($key)
    {
        return IlluminateCache::get($key);
    }

    public function makeKeyAndPut($model, $key)
    {
        $key = $this->makeCacheKey($model, $key, get_class($model));

        $this->cachePut($key, $model);
    }

    public function findCached($attributes, $keys, $identifier = null)
    {
        $key = $this->makeCacheKey($attributes, $keys, $identifier);

        return [
            $this->findCachedWithKey($key),
            $key
        ];
    }

    public function makeCacheKey($attributes, $keys, $identifier)
    {
        $attributes = $this->extractAttributes($attributes);

        $cacheKey = "className=$identifier;";

        $keys = $this->extractKeys($attributes, $keys, $identifier);

        foreach ($keys as $key)
        {
            if (isset($attributes[$key]))
            {
                $cacheKey .= "$key=$attributes[$key];";
            }
        }

        return sha1($cacheKey);
    }
}
