<?php

namespace Foris\Easy\Cache;

use Psr\Cache\CacheItemPoolInterface;

/**
 * Class Cache
 */
class Cache
{
    /**
     * Cache driver factory instance.
     *
     * @var Factory
     */
    protected $factory;

    /**
     * Cache configuration.
     *
     * @var array
     */
    protected $config = [];

    /**
     * Cache driver instance.
     *
     * @var CacheItemPoolInterface
     */
    protected $driver;

    /**
     * Cache constructor.
     *
     * @param Factory $factory
     * @param array   $config
     * @throws InvalidConfigException
     */
    public function __construct(Factory $factory = null, array $config = [])
    {
        $this->setDriverFactory($factory)->setConfig(array_merge($this->defaultConfig(), $config));
    }

    /**
     * Gets the default cache configuration.
     *
     * @return array
     */
    protected function defaultConfig()
    {
        return [
            'default' => 'file',
            'life_time' => 1800,
            'drivers' => [
                'file' => [
                    'path' => sys_get_temp_dir() . '/cache/',
                ]
            ],
        ];
    }

    /**
     * Sets the cache driver factory.
     *
     * @param Factory|null $factory
     * @return $this
     */
    public function setDriverFactory(Factory $factory = null)
    {
        $this->factory = $factory;
        return $this;
    }

    /**
     * Gets the cache driver factory.
     *
     * @return Factory
     * @throws InvalidConfigException
     */
    public function getDriverFactory()
    {
        return empty($this->factory) ? new Factory($this->config) : $this->factory;
    }

    /**
     * Sets the cache configuration.
     *
     * @param array $config
     * @return $this
     * @throws InvalidConfigException
     */
    public function setConfig(array $config = [])
    {
        $this->config = $config;
        $this->getDriverFactory()->setConfig($config);
        return $this;
    }

    /**
     * Sets the cache driver.
     *
     * @param string|null $driver
     * @return $this
     * @throws InvalidConfigException
     * @throws RuntimeException
     */
    public function driver($driver = null)
    {
        $this->driver = $this->getDriverFactory()->make($driver);
        return $this;
    }

    /**
     * Gets the cache driver instance.
     *
     * @return CacheItemPoolInterface
     * @throws InvalidConfigException
     * @throws RuntimeException
     */
    public function getDriver()
    {
        if (empty($this->driver)) {
            $this->driver = $this->getDriverFactory()->make($this->config['default']);
        }

        return $this->driver;
    }

    /**
     * Store cache
     *
     * @param      $key
     * @param      $value
     * @param null $ttl
     * @return $this
     * @throws InvalidConfigException
     * @throws RuntimeException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function put($key, $value, $ttl = null)
    {
        $ttl = isset($ttl) ? $ttl : $this->config['life_time'];
        $item = $this->getDriver()->getItem($key)->set($value)->expiresAfter($ttl);
        $this->getDriver()->save($item);
        return $this;
    }

    /**
     * Store many cache
     *
     * @param      $values
     * @param null $ttl
     * @return $this
     * @throws InvalidConfigException
     * @throws RuntimeException
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
     * @throws InvalidConfigException
     * @throws RuntimeException
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
     * @throws InvalidConfigException
     * @throws RuntimeException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function get($key)
    {
        $item = $this->getDriver()->getItem($key);
        return $item->isHit() ? $item->get() : null;
    }

    /**
     * Remember cache with ttl
     *
     * @param          $key
     * @param          $ttl
     * @param \Closure $callback
     * @return mixed
     * @throws InvalidConfigException
     * @throws RuntimeException
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
     * @throws InvalidConfigException
     * @throws RuntimeException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function forget($key)
    {
        return $this->getDriver()->deleteItem($key);
    }

    /**
     * Delete cache
     *
     * @param $key
     * @return bool
     * @throws InvalidConfigException
     * @throws RuntimeException
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
     * @throws InvalidConfigException
     * @throws RuntimeException
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
     * @throws InvalidConfigException
     * @throws RuntimeException
     */
    public function flush()
    {
        return $this->getDriver()->clear();
    }

    /**
     * Clear all cache
     *
     * @return bool
     * @throws InvalidConfigException
     * @throws RuntimeException
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
     * @throws InvalidConfigException
     * @throws RuntimeException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function has($key)
    {
        return $this->getDriver()->getItem($key)->isHit();
    }
}
