<?php

namespace Foris\Easy\Cache\Tests;

use Foris\Easy\Cache\Cache;
use PHPUnit\Framework\TestCase;
use Foris\Easy\Cache\InvalidConfigException;
use Foris\Easy\Cache\RuntimeException;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

/**
 * Class CacheTest
 */
class CacheTest extends TestCase
{
    /**
     * Gets the cache configuration.
     *
     * @return array
     */
    protected function config()
    {
        return [
            'default' => 'array',
            'life_time' => 1800,
            'drivers' => [
                'file' => [
                    'path' => sys_get_temp_dir() . '/cache/',
                ]
            ]
        ];
    }

    /**
     * Gets the cache instance.
     *
     * @return Cache
     * @throws InvalidConfigException
     */
    protected function cache()
    {
        return new Cache(null, $this->config());
    }

    /**
     * Test gets the default cache driver.
     *
     * @throws InvalidConfigException
     * @throws RuntimeException
     */
    public function testGetDefaultCacheDriver()
    {
        $cache = $this->cache();
        $this->assertInstanceOf(ArrayAdapter::class, $cache->getDriver());
        return $cache;
    }

    /**
     * Test change the cache driver.
     *
     * @param Cache $cache
     * @throws InvalidConfigException
     * @throws RuntimeException
     * @depends testGetDefaultCacheDriver
     */
    public function testChangeTheCacheDriver(Cache $cache)
    {
        $cache->driver('file');
        $this->assertInstanceOf(FilesystemAdapter::class, $cache->getDriver());
    }

    /**
     * Test store the cache data.
     *
     * @return Cache
     * @throws InvalidConfigException
     * @throws RuntimeException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testPutCacheData()
    {
        $cache = $this->cache();
        $this->assertSame($cache, $cache->set('hit_cache', 'hit_cache'));
        $this->assertSame($cache, $cache->putMany(['hit_cache' => 'hit_cache']));
        return $cache;
    }

    /**
     * Test get data from cache.
     *
     * @param Cache $cache
     * @throws InvalidConfigException
     * @throws RuntimeException
     * @throws \Psr\Cache\InvalidArgumentException
     * @depends testPutCacheData
     */
    public function testGetCacheData(Cache $cache)
    {
        $this->assertSame('hit_cache', $cache->get('hit_cache'));
        $this->assertNull($cache->get('not_hit_cache'));
    }

    /**
     * Test if the cache-pool has the give cache.
     *
     * @param Cache $cache
     * @throws InvalidConfigException
     * @throws RuntimeException
     * @throws \Psr\Cache\InvalidArgumentException
     * @depends testPutCacheData
     */
    public function testHasCacheData(Cache $cache)
    {
        $this->assertTrue($cache->has('hit_cache'));
    }

    /**
     * Test delete a given cache data.
     *
     * @param Cache $cache
     * @throws InvalidConfigException
     * @throws RuntimeException
     * @throws \Psr\Cache\InvalidArgumentException
     * @depends testPutCacheData
     */
    public function testDeleteCacheData(Cache $cache)
    {
        $this->assertTrue($cache->delete('hit_cache'));
        $this->assertTrue($cache->deleteMany(['hit_cache']));
    }

    /**
     * Test clear all cache data.
     *
     * @throws InvalidConfigException
     * @throws RuntimeException
     */
    public function testClearCache()
    {
        $this->assertTrue($this->cache()->clear());
    }

    /**
     * Test store a closure result into cache-pool.
     *
     * @param Cache $cache
     * @throws InvalidConfigException
     * @throws RuntimeException
     * @throws \Psr\Cache\InvalidArgumentException
     * @depends testPutCacheData
     */
    public function testRememberCache(Cache $cache)
    {
        $this->assertSame('remember', $cache->remember('not_hit_cache', 1800, function (){
            return 'remember';
        }));

        $cache->put('hit_cache', 'hit_cache');
        $this->assertSame('hit_cache', $cache->remember('hit_cache', 1800, function (){
            return 'remember';
        }));
    }
}
