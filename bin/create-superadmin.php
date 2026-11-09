<?php

declare(strict_types=1);

use Doctrine\DBAL\DriverManager;

require dirname(__DIR__) . '/vendor/autoload.php';

$prompt = static function (string $label): string {
    fwrite(STDOUT, $label);
    return trim((string) fgets(STDIN));
};

$promptPassword = static function (string $label): string {
    if (! stream_isatty(STDIN)) {
        throw new RuntimeException('Run this command in an interactive terminal.');
    }

    fwrite(STDOUT, $label);
    exec('stty -g 2>/dev/null', $terminalState, $status);

    if ($status !== 0 || ! isset($terminalState[0])) {
        throw new RuntimeException('Unable to read a hidden password in this terminal.');
    }

    exec('stty -echo');

    try {
        $password = trim((string) fgets(STDIN));
    } finally {
        exec('stty ' . escapeshellarg(trim($terminalState[0])));
        fwrite(STDOUT, PHP_EOL);
    }

    return $password;
};

try {
    $connection = DriverManager::getConnection(require dirname(__DIR__) . '/migrations-db.php');
    $superadminRoleId = $connection->fetchOne("SELECT id FROM roles WHERE name = 'superadmin'");

    if ($superadminRoleId === false) {
        throw new RuntimeException('Roles are missing. Run Doctrine migrations first.');
    }

    $superadminCount = $connection->fetchOne(
        'SELECT COUNT(*) FROM users WHERE role_id = ?',
        [$superadminRoleId]
    );

    if ((int) $superadminCount > 0) {
        throw new RuntimeException('A superadmin account already exists.');
    }

    $firstname = $prompt('First name: ');
    $lastname = $prompt('Last name: ');
    $email = $prompt('Email: ');

    if ($firstname === '' || $lastname === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        throw new RuntimeException('Enter a first name, last name, and valid email address.');
    }

    $password = $promptPassword('Password (minimum 8 characters): ');
    $passwordConfirmation = $promptPassword('Confirm password: ');

    if (strlen($password) < 8 || $password !== $passwordConfirmation) {
        throw new RuntimeException('Passwords must match and be at least 8 characters long.');
    }

    $now = date('Y-m-d H:i:s');
    $existingUser = $connection->fetchAssociative('SELECT id FROM users WHERE email = ?', [$email]);

    if ($existingUser !== false) {
        $connection->executeStatement(
            'UPDATE users SET firstname = ?, lastname = ?, password = ?, role_id = ?, updated_at = ? WHERE id = ?',
            [
                $firstname,
                $lastname,
                password_hash($password, PASSWORD_DEFAULT),
                $superadminRoleId,
                $now,
                $existingUser['id'],
            ]
        );
    } else {
        $connection->insert('users', [
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role_id' => $superadminRoleId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    fwrite(STDOUT, 'Superadmin account ready.' . PHP_EOL);
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
