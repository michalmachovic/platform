<?php

declare(strict_types=1);

return [
    'dbname' => getenv('DB_DATABASE') ?: 'platform',
    'user' => getenv('DB_USERNAME') ?: 'platform',
    'password' => getenv('DB_PASSWORD') ?: 'platform',
    'host' => getenv('DB_HOST') ?: 'db',
    'port' => getenv('DB_PORT') ?: 3306,
    'driver' => 'pdo_mysql',
];
