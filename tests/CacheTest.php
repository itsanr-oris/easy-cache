<?php

namespace Foris\Easy\Cache\Tests;

use Foris\Easy\Cache\Cache;
use Foris\Easy\Cache\Factory;
use Foris\Easy\Cache\InvalidConfigException;
use Foris\Easy\Cache\RuntimeException;
use Psr\Cache\CacheItemPoolInterface;
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
     * Test gets the cache instance.
     *
     * @throws InvalidConfigException
     */
    public function testGetCacheInstanceWithDriverFactory()
    {
        $factory = \Mockery::mock(Factory::class);
        $factory->makePartial();

        $cache = new Cache($factory);
        $this->assertSame($factory, $cache->getDriverFactory());
    }

    /**
     * Test gets the cache instance.
     *
     * @throws InvalidConfigException
     */
    public function testGetCacheInstanceWithConfiguration()
    {
        $defaultConfig = [
            'default' => 'file',
            'life_time' => 1800,
            'drivers' => [
                'file' => [
                    'path' => sys_get_temp_dir() . '/cache/',
                ]
            ],
        ];

        $cache = new Cache($this->config());
        $this->assertEquals(array_merge($defaultConfig, $this->config()), $cache->getConfig());
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

        $this->assertFalse($cache->getDriver()->getItem('put_cache')->isHit());
        $this->assertFalse($cache->getDriver()->getItem('set_cache')->isHit());
        $this->assertFalse($cache->getDriver()->getItem('many_cache_1')->isHit());
        $this->assertFalse($cache->getDriver()->getItem('many_cache_2')->isHit());

        $this->assertSame($cache, $cache->put('put_cache', 'put_cache'));
        $this->assertSame($cache, $cache->set('set_cache', 'set_cache'));
        $this->assertSame($cache, $cache->putMany(['many_cache_1' => 'many_cache_1', 'many_cache_2' => 'many_cache_2']));

        $this->assertTrue($cache->getDriver()->getItem('put_cache')->isHit());
        $this->assertTrue($cache->getDriver()->getItem('set_cache')->isHit());
        $this->assertTrue($cache->getDriver()->getItem('many_cache_1')->isHit());
        $this->assertTrue($cache->getDriver()->getItem('many_cache_2')->isHit());

        return $cache;
    }

    /**
     * Test get data from cache.
     *
     * @param Cache $cache
     * @return Cache
     * @throws InvalidConfigException
     * @throws RuntimeException
     * @throws \Psr\Cache\InvalidArgumentException
     * @depends testPutCacheData
     */
    public function testGetCacheData(Cache $cache)
    {
        $this->assertSame('put_cache', $cache->get('put_cache'));
        $this->assertNull($cache->get('not_exist_cache_item'));
        return $cache;
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
        $this->assertTrue($cache->has('put_cache'));
        $this->assertFalse($cache->has('not_exist_cache_item'));
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
        $this->assertTrue($cache->delete('put_cache'));
        $this->assertTrue($cache->deleteMany(['many_cache_1', 'many_cache_2']));

        $this->assertFalse($cache->getDriver()->getItem('put_cache')->isHit());
        $this->assertFalse($cache->getDriver()->getItem('many_cache_1')->isHit());
        $this->assertFalse($cache->getDriver()->getItem('many_cache_2')->isHit());
    }

    /**
     * Test clear all cache data.
     *
     * @param Cache $cache
     * @throws InvalidConfigException
     * @throws RuntimeException
     * @throws \Psr\Cache\InvalidArgumentException
     * @depends testPutCacheData
     */
    public function testClearCache(Cache $cache)
    {
        $this->assertTrue($cache->clear());
        $this->assertFalse($cache->getDriver()->getItem('put_cache')->isHit());
        $this->assertFalse($cache->getDriver()->getItem('set_cache')->isHit());
        $this->assertFalse($cache->getDriver()->getItem('many_cache_1')->isHit());
        $this->assertFalse($cache->getDriver()->getItem('many_cache_2')->isHit());
    }

    /**
     * Test store a closure result into cache-pool.
     *
     * @param Cache $cache
     * @return Cache
     * @throws InvalidConfigException
     * @throws RuntimeException
     * @throws \Psr\Cache\InvalidArgumentException
     * @depends testPutCacheData
     */
    public function testCacheClosureResult(Cache $cache)
    {
        $callback = function () {
            return 'remember';
        };

        $this->assertFalse($cache->getDriver()->getItem('closure_result_cache')->isHit());
        $this->assertSame('remember', $cache->remember('closure_result_cache', 1800, $callback));
        $this->assertTrue($cache->getDriver()->getItem('closure_result_cache')->isHit());

        return $cache;
    }

    /**
     * Test replace an exists cache item with closure result.
     *
     * @param Cache $cache
     * @throws InvalidConfigException
     * @throws RuntimeException
     * @throws \Psr\Cache\InvalidArgumentException
     * @depends testCacheClosureResult
     */
    public function testReplaceExistsCacheItemWithClosure(Cache $cache)
    {
        $callback = function () {
            return 'remember-2';
        };

        $cacheResult = $cache->get('closure_result_cache');
        $this->assertEquals($cacheResult, $cache->remember('closure_result_cache', 1800, $callback));
    }

    /**
     * Test create cache component without cache configuration.
     *
     * @throws InvalidConfigException
     * @throws RuntimeException
     */
    public function testCreateCacheComponentInstanceWithoutConfiguration()
    {
        $cache = new Cache();
        $this->assertInstanceOf(FilesystemAdapter::class, $cache->getDriver());
    }

    /**
     * Test extend cache driver.
     *
     * @throws InvalidConfigException
     * @throws RuntimeException
     */
    public function testExtendCacheDriver()
    {
        $cache = new Cache();
        $driver = \Mockery::mock(CacheItemPoolInterface::class);

        $cache->extend('mock_cache', function () use ($driver) {
            return $driver;
        });

        $this->assertSame($driver, $cache->driver('mock_cache')->getDriver());
    }
}
