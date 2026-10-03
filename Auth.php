<?php
declare(strict_types=1);

namespace App\Core;

use App\Repositories\UserRepository;

final class Auth
{
    public function __construct(private UserRepository $users) {}

    public function login(string $email, string $password): bool
    {
        $user = $this->users->findByEmail($email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }
        session_regenerate_id(true);   // กัน Session Fixation
        $_SESSION['user'] = [
            'id'    => (int) $user['id'],
            'name'  => $user['name'],
            'email' => $user['email'],
            'role'  => $user['role'],
        ];
        return true;
    }

    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public function check(): bool   { return isset($_SESSION['user']); }
    public function user(): ?array  { return $_SESSION['user'] ?? null; }
    public function id(): ?int      { return $_SESSION['user']['id'] ?? null; }
    public function role(): ?string { return $_SESSION['user']['role'] ?? null; }
}
