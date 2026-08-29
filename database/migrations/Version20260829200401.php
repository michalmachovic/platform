<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260829200401 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create users table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('users');

        $table->addColumn('id', 'integer', [
            'autoincrement' => true,
        ]);

        $table->addColumn('email', 'string', [
            'length' => 255,
        ]);

        $table->addColumn('password', 'string', [
            'length' => 255,
        ]);

        $table->addColumn('created_at', 'datetime');

        $table->addColumn('updated_at', 'datetime');

        $table->setPrimaryKey(['id']);

        $table->addUniqueIndex(['email'], 'uniq_users_email');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('users');
    }
}
