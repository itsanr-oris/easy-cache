<?php

namespace Foris\Easy\Cache\Tests;

use Foris\Easy\Cache\Factory;
use Foris\Easy\Cache\InvalidConfigException;
use Foris\Easy\Cache\RuntimeException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\ChainAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;

/**
 * Class FactoryTest
 */
class FactoryTest extends TestCase
{
    /**
     * Test make a filesystem cache driver instance.
     *
     * @throws InvalidConfigException
     * @throws RuntimeException
     */
    public function testMakeFilesystemDriver()
    {
        $factory = new Factory();
        $this->assertInstanceOf(FilesystemAdapter::class, $factory->make('file', []));
    }

    /**
     * Test make a memcache cache driver instance.
     *
     * @throws InvalidConfigException
     * @throws RuntimeException
     */
    public function testMakeMemcachedDriver()
    {
        $factory = new Factory();
        $config = [
            'dsn' => [
                'memcached://localhost:11211'
            ]
        ];
        $this->assertInstanceOf(MemcachedAdapter::class, $factory->make('memcached', $config));
    }

    /**
     * Test make a redis cache driver.
     *
     * @throws InvalidConfigException
     * @throws RuntimeException
     */
    public function testMakeRedisDriver()
    {
        $factory = new Factory();
        $config = [
            'dsn' => 'redis://localhost:6379'
        ];
        $this->assertInstanceOf(RedisAdapter::class, $factory->make('redis', $config));
    }

    /**
     * Test make a chain cache driver instance.
     *
     * @throws InvalidConfigException
     * @throws RuntimeException
     */
    public function testMakeChainDriver()
    {
        $config = [
            'default' => 'file',
            'life_time' => 1800,
            'drivers' => [
                'chain' => [
                    'drivers' => ['file', 'memcached', 'redis'],
                ],
                'file' => [
                    'path' => sys_get_temp_dir() . '/cache/',
                ],
                'memcached' => [
                    'dsn' => [
                        'memcached://localhost:11211'
                    ]
                ],
                'redis' => [
                    'dsn' => 'redis://localhost:6379',
                ]
            ]
        ];

        $factory = new Factory($config);
        $this->assertInstanceOf(ChainAdapter::class, $factory->make('stack'));
    }

    /**
     * Test make a chain cache driver instance without available driver.
     *
     * @throws InvalidConfigException
     * @throws RuntimeException
     */
    public function testMakeChainDriverWithoutAvailableDriver()
    {
        $factory = new Factory();
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Chain adapters can not be empty!');

        $factory->make('stack', ['drivers' => []]);
    }

    /**
     * Test make a not exists driver instance,
     *
     * @throws InvalidConfigException
     * @throws RuntimeException
     */
    public function testMakeNotExistsDriver()
    {
        $factory = new Factory();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Can not create cache driver [not-exists-driver]!');

        $factory->make('not-exists-driver', []);
    }

    /**
     * Test extend a custom cache driver.
     *
     * @return Factory
     * @throws InvalidConfigException
     * @throws RuntimeException
     */
    public function testExtendCustomCacheDriver()
    {
        $factory = new Factory();
        $callable = function (array $config = []) {
            return $config['config'];
        };

        $factory->extend($callable, 'extend-creator');

        $config = [
            'config' => 'test config',
        ];

        $this->assertSame('test config', $factory->make('extend-creator', $config));

        return $factory;
    }

    /**
     * Test re-extend a duplicate cache driver.
     *
     * @param Factory $factory
     * @throws InvalidConfigException
     * @depends     testExtendCustomCacheDriver
     */
    public function testExtendDuplicateCacheDriver(Factory $factory)
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Cache driver [extend-creator] already exists!');

        $callable = function (array $config = []) {
            return $config['config'] . ' 2';
        };

        $factory->extend($callable, 'extend-creator');
    }

    /**
     * Test alias a cache driver.
     *
     * @param Factory $factory
     * @return Factory
     * @throws RuntimeException
     * @depends     testExtendCustomCacheDriver
     */
    public function testAliasCacheDriver(Factory $factory)
    {
        $factory->alias('extend-creator', 'extend');

        $config = [
            'config' => 'test config',
        ];

        $this->assertSame('test config', $factory->make('extend', $config));

        return $factory;
    }

    /**
     * Test alias a cache driver with duplicate name.
     *
     * @param Factory $factory
     * @throws RuntimeException
     * @depends      testAliasCacheDriver
     */
    public function testAliasDuplicateCacheDriver(Factory $factory)
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Driver factory alias [extend] already exists!');
        $factory->alias('extend-creator', 'extend');
    }

    /**
     * Test alias a not exists cache driver.
     *
     * @throws InvalidConfigException
     * @throws RuntimeException
     */
    public function testAliasNotExistsCacheDriver()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Driver factory [not-exists-creator] not exists!');

        $factory = new Factory();
        $factory->alias('not-exists-creator', 'not-exists');
    }

    /**
     * Test make a php array cache driver instance.
     *
     * @throws InvalidConfigException
     * @throws RuntimeException
     */
    public function testMakeArrayDriver()
    {
        $factory = new Factory();
        $config = [
            'max_lifetime' => 1800, 'max_items' => 0
        ];
        $this->assertInstanceOf(ArrayAdapter::class, $factory->make('array', $config));
    }
}
