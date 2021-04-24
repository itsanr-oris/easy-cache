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
     * @param null $driver
     * @return array
     */
    public function getConfig($driver = null)
    {
        return $driver === null
            ? ($this->config)
            : (isset($this->config['drivers'][$driver]) ? $this->config['drivers'][$driver] : []);
    }

    /**
     * Gets the cache driver configuration.
     *
     * @param       $driver
     * @param array $default
     * @return array
     * @deprecated
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
        if ($this->driverExist($name)) {
            return call_user_func_array($this->getFactory($name), [$config]);
        }

        throw new RuntimeException(sprintf('Can not create cache driver [%s]!', $name));
    }

    /**
     * Determine if the cache driver exists.
     *
     * @param $driver
     * @return bool
     */
    protected function driverExist($driver)
    {
        return is_callable($this->getFactory($driver));
    }

    /**
     * Gets the cache driver factory.
     *
     * @param $driver
     * @return mixed|null
     */
    protected function getFactory($driver)
    {
        if (isset($this->aliases[$driver])) {
            $driver = $this->aliases[$driver];
        }

        if (!isset($this->factories[$driver]) || !is_callable($this->factories[$driver])) {
            return null;
        }

        return $this->factories[$driver];
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
        if ($this->driverExist($name) || $this->driverExist($alias)) {
            throw new InvalidConfigException(sprintf('Cache driver [%s] already exists!', $name));
        }

        $this->factories[$name] = $factory;
        !empty($alias) && $this->aliases[$alias] = $name;

        return $this;
    }

    /**
     * Alias the cache driver factory
     *
     * @param string $name
     * @param string $alias
     * @return $this
     * @throws RuntimeException
     * @deprecated
     */
    public function alias($name, $alias)
    {
        if (!$this->driverExist($name)) {
            throw new RuntimeException(sprintf('Driver factory [%s] not exists!', $name));
        }

        if ($this->driverExist($alias)) {
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
            $config = array_merge($this->getConfig('file'), $config);
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
            $config = array_merge($this->getConfig('redis'), $config);
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
            $config = array_merge($this->getConfig('memcached'), $config);
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
            $config = array_merge($this->getConfig('chain'), $config);

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
            $config = array_merge($this->getConfig('array'), $config);
            $storeSerialized = isset($config['store_serialized']) ? $config['store_serialized'] : true;
            return new ArrayAdapter($this->lifetime(), $storeSerialized);
        };
    }
}
