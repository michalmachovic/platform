<?php

declare(strict_types=1);

namespace User\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use User\Model\UserTable;

class UserController extends AbstractActionController
{
    public function __construct(
        private UserTable $userTable
    ) {
    }

    public function indexAction(): ViewModel
    {
        if (! isset($_SESSION['auth_user_id'])) {
            return $this->redirect()->toRoute('home');
        }

        $roleNames = [];

        foreach ($this->userTable->getRoleTableGateway()->select() as $role) {
            $roleNames[(int) $role->id] = (string) $role->name;
        }

        return new ViewModel([
            'users' => $this->userTable->getTableGateway()->select(),
            'roleNames' => $roleNames,
        ]);
    }

    public function addAction()
    {
        if (! isset($_SESSION['auth_user_id'])) {
            return $this->redirect()->toRoute('home');
        }

        if (! in_array($_SESSION['auth_role_name'] ?? '', ['admin', 'superadmin'], true)) {
            return $this->redirect()->toRoute('user');
        }

        $firstname = trim((string) $this->params()->fromPost('firstname', ''));
        $lastname = trim((string) $this->params()->fromPost('lastname', ''));
        $email = trim((string) $this->params()->fromPost('email', ''));
        $password = (string) $this->params()->fromPost('password', '');
        $roles = [];

        foreach ($this->userTable->getRoleTableGateway()->select() as $role) {
            if ($role->name !== 'superadmin') {
                $roles[] = [
                    'id' => (int) $role->id,
                    'name' => (string) $role->name,
                ];
            }
        }

        $roleId = (int) $this->params()->fromPost('role_id', 3);
        $allowedRoleIds = array_column($roles, 'id');
        $errors = [];
        $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));

        if ($this->getRequest()->isPost()) {
            $csrfToken = (string) $this->params()->fromPost('csrf_token', '');

            if (! hash_equals($_SESSION['csrf_token'], $csrfToken)) {
                $errors[] = 'The form expired. Please try again.';
            }

            if ($firstname === '' || $lastname === '') {
                $errors[] = 'First name and last name are required.';
            }

            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                $errors[] = 'Enter a valid email address.';
            }

            if (strlen($password) < 8) {
                $errors[] = 'Password must be at least 8 characters long.';
            }

            if (! in_array($roleId, $allowedRoleIds, true)) {
                $errors[] = 'Select a valid role.';
            }

            if ($errors === []) {
                $existingUsers = $this->userTable->getTableGateway()->select(['email' => $email]);

                if (count($existingUsers) > 0) {
                    $errors[] = 'A user with this email address already exists.';
                } else {
                    $now = date('Y-m-d H:i:s');
                    $this->userTable->getTableGateway()->insert([
                        'firstname' => $firstname,
                        'lastname' => $lastname,
                        'email' => $email,
                        'password' => password_hash($password, PASSWORD_DEFAULT),
                        'role_id' => $roleId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    return $this->redirect()->toRoute('user');
                }
            }
        }

        return new ViewModel([
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'roles' => $roles,
            'roleId' => $roleId,
            'errors' => $errors,
            'csrfToken' => $_SESSION['csrf_token'],
        ]);
    }
}
