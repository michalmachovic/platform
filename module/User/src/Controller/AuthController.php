<?php

declare(strict_types=1);

namespace User\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use User\Model\UserTable;

class AuthController extends AbstractActionController
{
    public function __construct(
        private UserTable $userTable
    ) {
    }

    public function indexAction(): ViewModel
    {
        if (isset($_SESSION['auth_user_id'])) {
            return $this->redirect()->toRoute('user');
        }

        $error = '';
        $email = trim((string) $this->params()->fromPost('email', ''));
        $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));

        if ($this->getRequest()->isPost()) {
            $csrfToken = (string) $this->params()->fromPost('csrf_token', '');
            $password = (string) $this->params()->fromPost('password', '');

            if (! hash_equals($_SESSION['csrf_token'], $csrfToken)) {
                $error = 'The form expired. Please try again.';
            } else {
                $user = $this->userTable->getTableGateway()->select(['email' => $email])->current();

                if ($user !== false && password_verify($password, $user->password)) {
                    $role = $this->userTable->getRoleTableGateway()->select(['id' => $user->role_id])->current();

                    if ($role !== false) {
                        session_regenerate_id(true);
                        $_SESSION['auth_user_id'] = (int) $user->id;
                        $_SESSION['auth_role_id'] = (int) $role->id;
                        $_SESSION['auth_role_name'] = (string) $role->name;
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                        return $this->redirect()->toRoute('user');
                    }
                }

                $error = 'Invalid email or password.';
            }
        }

        $view = new ViewModel([
            'email' => $email,
            'error' => $error,
            'csrfToken' => $_SESSION['csrf_token'],
        ]);
        $view->setTemplate('application/index/index');

        return $view;
    }

    public function logoutAction()
    {
        if ($this->getRequest()->isPost()) {
            $csrfToken = (string) $this->params()->fromPost('csrf_token', '');

            if (isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $csrfToken)) {
                $_SESSION = [];
                $cookie = session_get_cookie_params();
                setcookie(session_name(), '', [
                    'expires' => time() - 42000,
                    'path' => $cookie['path'],
                    'domain' => $cookie['domain'],
                    'secure' => $cookie['secure'],
                    'httponly' => $cookie['httponly'],
                    'samesite' => $cookie['samesite'] ?? 'Lax',
                ]);
                session_destroy();
            }
        }

        return $this->redirect()->toRoute('home');
    }
}
