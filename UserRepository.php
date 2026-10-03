<?php
declare(strict_types=1);

namespace App\Repositories;

final class UserRepository extends BaseRepository
{
    protected string $table = 'users';
    protected array $fillable = ['name', 'email', 'password_hash', 'role', 'line_user_id'];

    public function findByEmail(string $email): ?array
    {
        $row = $this->db->query('SELECT * FROM users WHERE email = ?', [$email])->fetch();
        return $row ?: null;
    }

    public function byRole(string $role): array
    {
        return $this->db->query('SELECT id, name, email FROM users WHERE role = ? ORDER BY name', [$role])->fetchAll();
    }

    public function all(): array
    {
        return $this->db->query('SELECT id, name, email, role, line_user_id, created_at FROM users ORDER BY id')->fetchAll();
    }
}
