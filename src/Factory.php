<?php

namespace Foris\Easy\Cache;

use Psr\Cache\CacheItemPoolInterface;
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
     * @var array
     */
    protected $aliases = [];

    /**
     * @var array
     */
    protected $creators = [];

    /**
     * AdapterFactory constructor.
     *
     * @throws InvalidConfigException
     */
    public function __construct()
    {
        $this->registerDefaultCreator();
    }

    /**
     * Register default cache driver creator
     *
     * @throws InvalidConfigException
     */
    protected function registerDefaultCreator()
    {
        $this->extend($this->filesystemCacheAdapterCreator(), 'filesystem', 'file');
        $this->extend($this->memcachedCacheAdapterCreator(), 'memcached', 'memcache');
        $this->extend($this->redisCacheAdapterCreator(), 'redis', 'redis');
        $this->extend($this->chainCacheAdapterCreator(), 'chain', 'stack');
    }

    /**
     * Make and get cache driver instance
     *
     * @param string $name
     * @param array  $config
     * @return CacheItemPoolInterface : mixed
     * @throws RuntimeException
     */
    public function make(string $name, array $config = [])
    {
        $creator = $this->aliases[$name] ?? $name;

        if (isset($this->creators[$creator])) {
            return $this->creators[$creator]($config);
        }

        throw new RuntimeException(sprintf('Can not create cache driver [%s]!', $name));
    }

    /**
     * Extend cache driver creator
     *
     * @param callable    $creator
     * @param string      $name
     * @param string|null $alias
     * @return $this
     * @throws InvalidConfigException
     */
    public function extend(callable $creator, string $name, string $alias = null)
    {
        if (isset($this->creators[$name]) || isset($this->aliases[$alias])) {
            throw new InvalidConfigException(sprintf('Cache driver [%s] already exists!', $name));
        }

        $this->creators[$name] = $creator;
        !empty($alias) && $this->aliases[$alias] = $name;

        return $this;
    }

    /**
     * Alias cache driver creator
     *
     * @param string $name
     * @param string $alias
     * @return $this
     * @throws RuntimeException
     */
    public function alias(string $name, string $alias)
    {
        if (!isset($this->creators[$name])) {
            throw new RuntimeException(sprintf('Creator [%s] not exists!', $name));
        }

        if (isset($this->aliases[$alias])) {
            throw new RuntimeException(sprintf('Creator alias [%s] already exists!', $alias));
        }

        $this->aliases[$alias] = $name;
        return $this;
    }

    /**
     * Get filesystem cache driver adapter instance
     *
     * @return \Closure
     */
    protected function filesystemCacheAdapterCreator()
    {
        return function (array $config = []) {
            return new FilesystemAdapter(
                $config['namespace'] ?? '',
                $config['life_time'] ?? 0,
                $config['path'] ?? sys_get_temp_dir() . '/easy-cache/'
            );
        };
    }

    /**
     * Get redis cache driver adapter instance
     *
     * @return \Closure
     */
    protected function redisCacheAdapterCreator()
    {
        return function (array $config = []) {
            return new RedisAdapter(
                RedisAdapter::createConnection($config['dsn'], $config['options'] ?? []),
                $config['name_space'] ?? '',
                $config['life_time'] ?? 0
            );
        };
    }

    /**
     * Get memcached cache driver adapter instance
     *
     * @return \Closure
     */
    public function memcachedCacheAdapterCreator()
    {
        return function (array $config = []) {
            return new MemcachedAdapter(
                MemcachedAdapter::createConnection($config['dsn'], $config['options'] ?? []),
                $config['name_space'] ?? '',
                $config['life_time'] ?? 0
            );
        };
    }

    /**
     * Get chain cache driver adapter instance
     *
     * @return \Closure
     */
    protected function chainCacheAdapterCreator()
    {
        return function (array $config = []) {
            $adapters = [];
            foreach ($config['drivers'] as $adapter) {
                $driverConfig = $config['total_drivers'][$adapter] ?? [];
                $driverConfig['life_time'] = $config['life_time'] ?? 3600;
                $adapters[] = $this->make($adapter, $driverConfig);
            }

            if (empty($adapters)) {
                throw new InvalidConfigException('Chain adapters can not be empty!');
            }
            return new ChainAdapter($adapters);
        };
    }
}