<?php
/**
 * Created by PhpStorm.
 * User: f-oris
 * Date: 2019/8/21
 * Time: 4:42 PM
 */

namespace Foris\Easy\Cache\Tests;

use Foris\Easy\Cache\Factory;
use Foris\Easy\Cache\InvalidConfigException;
use Foris\Easy\Cache\RuntimeException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ChainAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;

/**
 * Class FactoryTest
 * @package Foris\Easy\Cache\Tests
 * @author  f-oris <us@f-oris.me>
 * @version 1.0.0
 */
class FactoryTest extends TestCase
{
    /**
     * @throws InvalidConfigException
     * @throws RuntimeException
     */
    public function testMakeFilesystemDriver()
    {
        $factory = new Factory();
        $this->assertInstanceOf(FilesystemAdapter::class, $factory->make('file', []));
    }

    /**
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
     * @throws InvalidConfigException
     * @throws RuntimeException
     */
    public function testMakeChainDriver()
    {
        $factory = new Factory();
        $config = [
            'drivers' => ['file', 'memcached', 'redis'],
            'total_drivers' => [
                'file' => [],
                'memcached' => [
                    'dsn' => [
                        'memcached://localhost:11211'
                    ]
                ],
                'redis' => [
                    'dsn' => 'redis://localhost:6379',
                ]
            ],
        ];
        $this->assertInstanceOf(ChainAdapter::class, $factory->make('stack', $config));
    }

    /**
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
     * @param Factory $factory
     * @throws RuntimeException
     * @depends      testAliasCacheDriver
     */
    public function testAliasDuplicateCacheDriver(Factory $factory)
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Creator alias [extend] already exists!');
        $factory->alias('extend-creator', 'extend');
    }

    /**
     * @throws InvalidConfigException
     * @throws RuntimeException
     */
    public function testAliasNotExistsCacheDriver()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Creator [not-exists-creator] not exists!');

        $factory = new Factory();
        $factory->alias('not-exists-creator', 'not-exists');
    }
}
