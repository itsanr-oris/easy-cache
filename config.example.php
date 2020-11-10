<?php

return [
    /**
     * default cache driver
     */
    'default' => 'file',

    /**
     * cache life time
     */
    'life_time' => 1800,

    /**
     * available cache drivers
     */
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
