<?php

namespace Foris\Easy\Cache\Tests;

use Foris\Easy\Cache\Cache;
use Foris\Easy\Cache\Factory;
use PHPUnit\Framework\TestCase;
use Foris\Easy\Cache\InvalidConfigException;
use Foris\Easy\Cache\RuntimeException;
use Mockery;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;

/**
 * Class CacheTest
 */
class CacheTest extends TestCase
{
    /**
     * @return Mockery\MockInterface|FilesystemAdapter
     */
    protected function mockFilesystemCacheAdapter()
    {
        $adapter = Mockery::mock(FilesystemAdapter::class);

        $hitCache = Mockery::mock(CacheItemInterface::class);
        $hitCache->shouldReceive('isHit')->andReturnTrue();
        $hitCache->shouldReceive('set')->andReturn($hitCache);
        $hitCache->shouldReceive('expiresAfter')->andReturn($hitCache);
        $hitCache->shouldReceive('get')->andReturn('hit_cache');
        $adapter->shouldReceive('getItem')->withArgs(['hit_cache'])->andReturn($hitCache);

        $notHitCache = Mockery::mock(CacheItemInterface::class);
        $notHitCache->shouldReceive('isHit')->andReturnFalse();
        $notHitCache->shouldReceive('set')->andReturn($hitCache);
        $notHitCache->shouldReceive('expiresAfter')->andReturn($hitCache);
        $notHitCache->shouldReceive('get')->andReturnNull();
        $adapter->shouldReceive('getItem')->withArgs(['not_hit_cache'])->andReturn($notHitCache);

        $adapter->shouldReceive('save')->andReturnTrue();
        $adapter->shouldReceive('deleteItem')->andReturnTrue();
        $adapter->shouldReceive('clear')->andReturnTrue();

        return $adapter;
    }

    /**
     * @return Mockery\MockInterface|MemcachedAdapter
     */
    protected function mockMemcachedCacheAdapter()
    {
        return Mockery::mock(MemcachedAdapter::class);
    }

    /**
     * @return Cache
     * @throws InvalidConfigException
     * @throws RuntimeException
     */
    protected function cache()
    {
        $config = [
            'default' => 'file',

            'life_time' => 3600,

            'drivers' => [
                'file' => [
                    'path' => sys_get_temp_dir() . '/cache/'
                ],

                'memcached' => [
                    'dsn' => ['memcached://localhost:11211'],
                ],
            ],
        ];

        $factory = Mockery::mock(Factory::class);

        $factory->shouldReceive('make')->withArgs(
            function ($driver) {
                return $driver == 'file';
            }
        )->andReturn($this->mockFilesystemCacheAdapter());

        $factory->shouldReceive('make')->withArgs(
            function ($driver) {
                return $driver == 'memcached';
            }
        )->andReturn($this->mockMemcachedCacheAdapter());

        return new Cache($factory, $config);
    }

    /**
     * @throws InvalidConfigException
     * @throws RuntimeException
     */
    public function testGetDefaultCacheDriver()
    {
        $this->assertInstanceOf(CacheItemPoolInterface::class, $this->cache()->getDriver());
    }

    /**
     * @throws InvalidConfigException
     * @throws RuntimeException
     */
    public function testSpecificCacheDriver()
    {
        $this->assertInstanceOf(MemcachedAdapter::class, $this->cache()->driver('memcached')->getDriver());

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('No cache driver configuration was found!');
        $this->cache()->driver('not_exists_driver');
    }

    /**
     * @throws InvalidConfigException
     * @throws RuntimeException
     */
    public function testGetDriverConfig()
    {
        $this->assertSame(
            [
                'default' => 'file',

                'life_time' => 3600,

                'drivers' => [
                    'file' => [
                        'path' => sys_get_temp_dir() . '/cache/'
                    ],

                    'memcached' => [
                        'dsn' => ['memcached://localhost:11211'],
                    ],
                ],
            ],
            $this->cache()->getConfig()
        );

        $this->assertSame(
            [
                'path' => sys_get_temp_dir() . '/cache/',
                'default' => 'file',
                'life_time' => 3600,
            ],
            $this->cache()->getConfig('file')
        );

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('No cache driver configuration was found!');
        $this->cache()->getConfig('not_exists_driver');
    }

    /**
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
     * @throws InvalidConfigException
     * @throws RuntimeException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testGetCacheData()
    {
        $cache = $this->cache();
        $this->assertSame('hit_cache', $cache->get('hit_cache'));
        $this->assertNull($cache->get('not_hit_cache'));
    }

    /**
     * @throws InvalidConfigException
     * @throws RuntimeException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testHasCacheData()
    {
        $this->assertTrue($this->cache()->has('hit_cache'));
    }

    /**
     * @throws InvalidConfigException
     * @throws RuntimeException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testDeleteCacheData()
    {
        $this->assertTrue($this->cache()->delete('hit_cache'));
        $this->assertTrue($this->cache()->deleteMany(['hit_cache']));
    }

    /**
     * @throws InvalidConfigException
     * @throws RuntimeException
     */
    public function testClearCache()
    {
        $this->assertTrue($this->cache()->clear());
    }

    /**
     * @throws InvalidConfigException
     * @throws RuntimeException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testRememberCache()
    {
        $this->assertSame('remember', $this->cache()->remember('not_hit_cache', 1800, function (){
            return 'remember';
        }));

        $this->assertSame('hit_cache',$this->cache()->remember('hit_cache', 1800, function (){
            return 'remember';
        }));
    }
}
