<?php
return [
    'backend' => [
        'frontName' => 'admin'
    ],
    'crypt' => [
        'key' => '10c1d2307f8817507ab27f587ee582af'
    ],
    'db' => [
        'table_prefix' => '',
        'connection' => [
            'default' => [
                'host' => '',
                'dbname' => 'magento',
                'username' => 'root',
                'password' => 'password',
                'model' => 'mysql4',
                'engine' => 'innodb',
                'initStatements' => 'SET NAMES utf8;',
                'active' => '1'
            ]
        ]
    ],
    'resource' => [
        'default_setup' => [
            'connection' => 'default'
        ]
    ],
    'x-frame-options' => 'SAMEORIGIN',
    'MAGE_MODE' => 'default',
    'session' => [
        'save' => 'files'
    ],
    'cache' => [
        'frontend' => [
            'default' => [
                'backend' => 'Cm_Cache_Backend_Redis',
                'backend_options' => [
                    'server' => '',
                    'database' => '0',
                    'port' => '6379'
                ],
            ],
            'page_cache' => [
                'backend' => 'Cm_Cache_Backend_Redis',
                'backend_options' => [
                    'server' => '',
                    'port' => '6379',
                    'database' => '1',
                    'compress_data' => '0'
                ]
            ]
        ]
    ],
    'session' => [
      'save' => 'redis',
      'redis' => [
        'host' => '',
        'port' => '6379',
        'password' => '',
        'timeout' => '2.5',
        'persistent_identifier' => '',
        'database' => '2',
        'compression_threshold' => '2048',
        'compression_library' => 'gzip',
        'log_level' => '3',
        'max_concurrency' => '6',
        'break_after_frontend' => '5',
        'break_after_adminhtml' => '30',
        'first_lifetime' => '600',
        'bot_first_lifetime' => '60',
        'bot_lifetime' => '7200',
        'disable_locking' => '0',
        'min_lifetime' => '60',
        'max_lifetime' => '2592000'
      ]
    ],
    'lock' => [
        'provider' => 'db',
        'config' => [
            'prefix' => NULL
        ]
    ],
    'cache_types' => [
        'config' => 1,
        'layout' => 1,
        'block_html' => 1,
        'collections' => 1,
        'reflection' => 1,
        'db_ddl' => 1,
        'compiled_config' => 1,
        'eav' => 1,
        'customer_notification' => 1,
        'config_integration' => 1,
        'config_integration_api' => 1,
        'full_page' => 1,
        'config_webservice' => 1,
        'translate' => 1,
        'vertex' => 1
    ],
    'install' => [
        'date' => 'Wed, 24 Jul 2019 06:00:12 +0000'
    ]
];