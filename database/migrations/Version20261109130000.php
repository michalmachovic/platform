<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261109130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add roles and role assignments to users';
    }

    public function up(Schema $schema): void
    {
        $rolesExist = $this->sm->tablesExist(['roles']);

        if (! $rolesExist) {
            $this->addSql(
                'CREATE TABLE roles (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, '
                . 'UNIQUE INDEX uniq_roles_name (name), PRIMARY KEY(id)) ENGINE = InnoDB'
            );
        }

        $this->addSql(
            "INSERT INTO roles (id, name) VALUES "
            . "(1, 'superadmin'), (2, 'admin'), (3, 'user') "
            . 'ON DUPLICATE KEY UPDATE name = VALUES(name)'
        );

        $usersExist = $this->sm->tablesExist(['users']);

        if (! $usersExist) {
            $this->addSql(
                'CREATE TABLE users ('
                . 'id INT AUTO_INCREMENT NOT NULL, '
                . 'firstname VARCHAR(255) DEFAULT NULL, '
                . 'lastname VARCHAR(255) DEFAULT NULL, '
                . 'email VARCHAR(255) NOT NULL, '
                . 'password VARCHAR(255) NOT NULL, '
                . 'created_at DATETIME NOT NULL, '
                . 'updated_at DATETIME NOT NULL, '
                . 'role_id INT NOT NULL DEFAULT 3, '
                . 'UNIQUE INDEX uniq_users_email (email), '
                . 'INDEX idx_users_role_id (role_id), '
                . 'PRIMARY KEY(id), '
                . 'CONSTRAINT fk_users_role_id FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE RESTRICT'
                . ') ENGINE = InnoDB'
            );

            return;
        }

        $columns = $this->sm->listTableColumns('users');

        if (! isset($columns['firstname'])) {
            $this->addSql('ALTER TABLE users ADD firstname VARCHAR(255) DEFAULT NULL');
        }

        if (! isset($columns['lastname'])) {
            $this->addSql('ALTER TABLE users ADD lastname VARCHAR(255) DEFAULT NULL');
        }

        if (! isset($columns['role_id'])) {
            $this->addSql('ALTER TABLE users ADD role_id INT NOT NULL DEFAULT 3');
        }

        $hasRoleForeignKey = false;

        if (isset($columns['role_id'])) {
            foreach ($this->sm->listTableForeignKeys('users') as $foreignKey) {
                if (in_array('role_id', $foreignKey->getLocalColumns(), true)) {
                    $hasRoleForeignKey = true;
                    break;
                }
            }
        }

        if (! $hasRoleForeignKey) {
            $this->addSql(
                'ALTER TABLE users ADD CONSTRAINT fk_users_role_id '
                . 'FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE RESTRICT'
            );
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->sm->tablesExist(['users'])) {
            $columns = $this->sm->listTableColumns('users');

            if (isset($columns['role_id'])) {
                foreach ($this->sm->listTableForeignKeys('users') as $foreignKey) {
                    if (in_array('role_id', $foreignKey->getLocalColumns(), true)) {
                        $name = str_replace('`', '``', $foreignKey->getName());
                        $this->addSql('ALTER TABLE users DROP FOREIGN KEY `' . $name . '`');
                    }
                }

                $this->addSql('ALTER TABLE users DROP role_id');
            }
        }

        if ($this->sm->tablesExist(['roles'])) {
            $this->addSql('DROP TABLE roles');
        }
    }
}
