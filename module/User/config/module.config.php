<?php

declare(strict_types=1);

namespace User;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\TableGateway\TableGateway;
use Psr\Container\ContainerInterface;
use User\Model\UserTable;

return [
    'service_manager' => [
        'factories' => [
            UserTable::class => function (ContainerInterface $container): UserTable {
                $adapter = $container->get(AdapterInterface::class);

                return new UserTable(
                    new TableGateway('users', $adapter),
                    new TableGateway('roles', $adapter)
                );
            },
        ],
    ],

    'controllers' => [
        'factories' => [
            Controller\UserController::class => function (ContainerInterface $container): Controller\UserController {
                return new Controller\UserController(
                    $container->get(Model\UserTable::class)
                );
            },
            Controller\AuthController::class => function (ContainerInterface $container): Controller\AuthController {
                return new Controller\AuthController(
                    $container->get(Model\UserTable::class)
                );
            },
        ],
    ],

    'router' => [
        'routes' => [
            'logout' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/logout',
                    'defaults' => [
                        'controller' => Controller\AuthController::class,
                        'action' => 'logout',
                    ],
                ],
            ],
            'user-add' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/users/add',
                    'defaults' => [
                        'controller' => Controller\UserController::class,
                        'action' => 'add',
                    ],
                ],
            ],
            'user' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/users',
                    'defaults' => [
                        'controller' => Controller\UserController::class,
                        'action' => 'index',
                    ],
                ],
            ],
        ],
    ],

    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];
