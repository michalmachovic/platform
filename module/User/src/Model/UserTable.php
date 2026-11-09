<?php

declare(strict_types=1);

namespace User\Model;

use Laminas\Db\TableGateway\TableGatewayInterface;

class UserTable
{
    public function __construct(
        private TableGatewayInterface $tableGateway,
        private TableGatewayInterface $roleTableGateway
    ) {
    }

    public function getTableGateway(): TableGatewayInterface
    {
        return $this->tableGateway;
    }

    public function getRoleTableGateway(): TableGatewayInterface
    {
        return $this->roleTableGateway;
    }
}
