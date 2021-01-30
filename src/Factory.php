<?php

namespace Foris\Easy\Cache;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\ChainAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;

/**
 * Class Factory
 */
class Factory
{
    /**
     * Cache driver configuration
     *
     * @var array
     */
    protected $config = [];

    /**
     * Cache driver aliases.
     *
     * @var array
     */
    protected $aliases = [];

    /**
     * Cache driver factories.
     *
     * @var array
     */
    protected $factories = [];

    /**
     * AdapterFactory constructor.
     *
     * @param array $config
     * @throws InvalidConfigException
     */
    public function __construct($config = [])
    {
        $this->setConfig($config)->registerDefaultFactory();
    }

    /**
     * Sets the cache driver configuration.
     *
     * @param array $config
     * @return $this
     */
    public function setConfig($config = [])
    {
        $this->config = $config;
        return $this;
    }

    /**
     * Gets the cache driver configuration.
     *
     * @param       $driver
     * @param array $default
     * @return array
     */
    public function getDriverConfig($driver, $default = [])
    {
        return isset($this->config['drivers'][$driver]) ? $this->config['drivers'][$driver] : $default;
    }

    /**
     * Gets the cache lifetime.
     *
     * @return int|mixed
     */
    protected function lifetime()
    {
        return isset($this->config['lifetime']) ? $this->config['lifetime'] : 3600;
    }

    /**
     * Gets the cache namespace.
     *
     * @return mixed|string
     */
    protected function getNamespace()
    {
        return isset($this->config['namespace']) ? $this->config['namespace'] : 'easy-cache';
    }

    /**
     * Register default cache driver factory.
     *
     * @throws InvalidConfigException
     */
    protected function registerDefaultFactory()
    {
        $this->extend($this->filesystemCacheAdapterFactory(), 'filesystem', 'file');
        $this->extend($this->memcachedCacheAdapterFactory(), 'memcached', 'memcache');
        $this->extend($this->redisCacheAdapterFactory(), 'redis', 'redis');
        $this->extend($this->chainCacheAdapterFactory(), 'chain', 'stack');
        $this->extend($this->arrayCacheAdapterFactory(), 'array', 'array');
    }

    /**
     * Make and get cache driver instance
     *
     * @param string $name
     * @param array  $config
     * @return CacheItemPoolInterface : mixed
     * @throws RuntimeException
     */
    public function make($name, array $config = [])
    {
        $name = isset($this->aliases[$name]) ? $this->aliases[$name] : $name;

        if (isset($this->factories[$name])) {
            return $this->factories[$name](array_merge($this->getDriverConfig($name), $config));
        }

        throw new RuntimeException(sprintf('Can not create cache driver [%s]!', $name));
    }

    /**
     * Extend cache driver factory.
     *
     * @param callable    $factory
     * @param string      $name
     * @param string|null $alias
     * @return $this
     * @throws InvalidConfigException
     */
    public function extend(callable $factory, $name, $alias = null)
    {
        if (isset($this->factories[$name]) || isset($this->aliases[$alias])) {
            throw new InvalidConfigException(sprintf('Cache driver [%s] already exists!', $name));
        }

        $this->factories[$name] = $factory;
        !empty($alias) && $this->aliases[$alias] = $name;

        return $this;
    }

    /**
     * Alias cache driver factory
     *
     * @param string $name
     * @param string $alias
     * @return $this
     * @throws RuntimeException
     */
    public function alias($name, $alias)
    {
        if (!isset($this->factories[$name])) {
            throw new RuntimeException(sprintf('Driver factory [%s] not exists!', $name));
        }

        if (isset($this->aliases[$alias])) {
            throw new RuntimeException(sprintf('Driver factory alias [%s] already exists!', $alias));
        }

        $this->aliases[$alias] = $name;
        return $this;
    }

    /**
     * Gets the filesystem cache driver adapter factory.
     *
     * @return \Closure
     */
    protected function filesystemCacheAdapterFactory()
    {
        return function (array $config = []) {
            $directory = isset($config['path']) ? $config['path'] : sys_get_temp_dir() . '/easy-cache/';
            return new FilesystemAdapter($this->getNamespace(), $this->lifetime(), $directory);
        };
    }

    /**
     * Get the redis cache driver adapter factory.
     *
     * @return \Closure
     */
    protected function redisCacheAdapterFactory()
    {
        return function (array $config = []) {
            $options = isset($config['options']) ? $config['options'] : [];
            return new RedisAdapter(
                RedisAdapter::createConnection($config['dsn'], $options),
                $this->getNamespace(),
                $this->lifetime()
            );
        };
    }

    /**
     * Gets the memcached cache driver adapter factory.
     *
     * @return \Closure
     */
    protected function memcachedCacheAdapterFactory()
    {
        return function (array $config = []) {
            $options = isset($config['options']) ? $config['options'] : [];
            return new MemcachedAdapter(
                MemcachedAdapter::createConnection($config['dsn'], $options),
                $this->getNamespace(),
                $this->lifetime()
            );
        };
    }

    /**
     * Gets the chain cache driver adapter factory
     *
     * @return \Closure
     */
    protected function chainCacheAdapterFactory()
    {
        return function (array $config = []) {
            $adapters = [];
            foreach ($config['drivers'] as $adapter) {
                $adapters[] = $this->make($adapter);
            }

            if (empty($adapters)) {
                throw new InvalidConfigException('Chain adapters can not be empty!');
            }
            return new ChainAdapter($adapters);
        };
    }

    /**
     * Gets the array cache driver adapter factory.
     *
     * @return \Closure
     */
    protected function arrayCacheAdapterFactory()
    {
        return function (array $config = []) {
            $storeSerialized = isset($config['store_serialized']) ? $config['store_serialized'] : true;
            return new ArrayAdapter($this->lifetime(), $storeSerialized);
        };
    }
}
