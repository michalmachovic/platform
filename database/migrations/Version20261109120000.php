<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261109120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add first and last names to users';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('users');

        $table->addColumn('firstname', 'string', [
            'length' => 255,
            'notnull' => false,
        ]);

        $table->addColumn('lastname', 'string', [
            'length' => 255,
            'notnull' => false,
        ]);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('users');
        $table->dropColumn('firstname');
        $table->dropColumn('lastname');
    }
}