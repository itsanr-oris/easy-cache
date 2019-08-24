<?php

namespace Foris\Easy\Cache;

use Psr\Cache\CacheItemPoolInterface;

/**
 * Class Cache
 */
class Cache
{
    /**
     * @var Factory
     */
    protected $factory;

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var CacheItemPoolInterface
     */
    protected $driver;

    /**
     * Cache constructor.
     *
     * @param Factory $factory
     * @param array   $config
     * @throws InvalidConfigException
     * @throws RuntimeException
     */
    public function __construct(Factory $factory, array $config = [])
    {
        $this->factory = $factory;
        $this->config = $config;

        $this->driver($this->config['default']);
    }

    /**
     * Set cache driver
     *
     * @param string|null $driver
     * @return $this
     * @throws InvalidConfigException
     * @throws RuntimeException
     */
    public function driver(string $driver = null)
    {
        if ($driver && !isset($this->config['drivers'][$driver])) {
            throw new InvalidConfigException('No cache driver configuration was found!');
        }

        $driver = $driver ?? $this->config['default'];
        $this->driver = $this->factory->make($driver, $this->getConfig($driver));
        return $this;
    }

    /**
     * Get driver instance
     *
     * @return CacheItemPoolInterface
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * Get cache config
     *
     * @param string|null $driver
     * @return array
     * @throws InvalidConfigException
     */
    public function getConfig(string $driver = null)
    {
        if ($driver && !isset($this->config['drivers'][$driver])) {
            throw new InvalidConfigException('No cache driver configuration was found!');
        }

        if ($driver === null) {
            return $this->config;
        }

        $commonConfig = $this->config;
        unset($commonConfig['drivers']);
        return array_merge($this->config['drivers'][$driver], $commonConfig);
    }

    /**
     * Store cache
     *
     * @param      $key
     * @param      $value
     * @param null $ttl
     * @return $this
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function put($key, $value, $ttl = null)
    {
        $ttl = $ttl ?? $this->config['life_time'];
        $item = $this->driver->getItem($key)->set($value)->expiresAfter($ttl);
        $this->driver->save($item);
        return $this;
    }

    /**
     * Store many cache
     *
     * @param      $values
     * @param null $ttl
     * @return $this
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function putMany($values, $ttl = null)
    {
        foreach ($values as $key => $value) {
            $this->put($key, $value, $ttl);
        }

        return $this;
    }

    /**
     * Set cache
     *
     * @param      $key
     * @param      $value
     * @param null $ttl
     * @return Cache
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function set($key, $value, $ttl = null)
    {
        return $this->put($key, $value, $ttl);
    }

    /**
     * Get cache
     *
     * @param $key
     * @return mixed|null
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function get($key)
    {
        $item = $this->driver->getItem($key);
        return $item->isHit() ? $item->get() : null;
    }

    /**
     * Remember cache with ttl
     *
     * @param          $key
     * @param          $ttl
     * @param \Closure $callback
     * @return mixed
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function remember($key, $ttl, \Closure $callback)
    {
        $value = $this->get($key);

        if (!is_null($value)) {
            return $value;
        }

        $this->put($key, $value = $callback(), $ttl);

        return $value;
    }

    /**
     * Forget cache
     *
     * @param $key
     * @return bool
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function forget($key)
    {
        return $this->driver->deleteItem($key);
    }

    /**
     * Delete cache
     *
     * @param $key
     * @return bool
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function delete($key)
    {
        return $this->forget($key);
    }

    /**
     * Delete many cache
     *
     * @param array $keys
     * @return bool
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function deleteMany(array $keys)
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    /**
     * Flush all cache
     *
     * @return bool
     */
    public function flush()
    {
        return $this->driver->clear();
    }

    /**
     * Clear all cache
     *
     * @return bool
     */
    public function clear()
    {
        return $this->flush();
    }

    /**
     * Determine whether the cache exist
     *
     * @param $key
     * @return bool
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function has($key)
    {
        return $this->driver->getItem($key)->isHit();
    }
}