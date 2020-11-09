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
        'file' => [
            'path' => sys_get_temp_dir() . '/cache/',
        ]
    ]
];
