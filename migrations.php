<?php

declare(strict_types=1);

use Doctrine\Migrations\Configuration\Migration\PhpFile;

return [
    'table_storage' => [
        'table_name' => 'doctrine_migration_versions',
    ],

    'migrations_paths' => [
        'Application\Migrations' => __DIR__ . '/database/migrations',
    ],

    'all_or_nothing' => true,
];
