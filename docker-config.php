<?php

return array_replace_recursive(
    file_exists(__DIR__ . '/config.vagrant.php') ? require __DIR__ . '/config.vagrant.php' : [],
    [
        'url' => 'http://host.docker.internal:8000',
        'search' => [
            'v3' => [
                'base_url' => 'http://host.docker.internal:9000',
                'scheme' => 'http',
                'port' => 9000,
            ],
        ],
        'database' => [
            'host' => 'mysql',
        ],
        'cache' => [
            'redis' => [
                'host' => 'redis',
            ],
        ],
        'amqp' => [
            'host' => 'rabbitmq',
        ],
        'rdf' => [
            'enabled' => false,
        ]
    ]
);
