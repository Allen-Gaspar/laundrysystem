<?php

class User
{
    private PDO $db;

    public function __construct(?PDO $pdo = null)
    {
        $this->db = $pdo ?? Database::getConnection();
    }

    public function authenticate(string $username, string $password): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, username, email, password_hash, full_name, phone, role FROM users WHERE username = :u OR email = :e LIMIT 1'
        );
        $stmt->execute(['u' => $username, 'e' => $username]);
        $row = $stmt->fetch();
        $hash = isset($row['password_hash']) ? trim((string) $row['password_hash']) : '';
        // Plain text or wrong algorithm in DB will fail verify — use bcrypt from PHP or sql/fix_admin_password.sql
        if (!$row || $hash === '' || !password_verify($password, $hash)) {
            return null;
        }
        unset($row['password_hash']);
        return $row;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, username, email, full_name, phone, role, created_at FROM users WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public function listByRole(string $role): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, username, email, full_name, phone, role, created_at FROM users WHERE role = :r ORDER BY id DESC'
        );
        $stmt->execute(['r' => $role]);
        return $stmt->fetchAll();
    }

    /** Customers + optional filter */
    public function listCustomers(): array
    {
        $stmt = $this->db->query(
            "SELECT id, username, email, full_name, phone, created_at FROM users WHERE role = 'Customer' ORDER BY id DESC"
        );
        return $stmt->fetchAll();
    }

    /** Admin + Staff */
    public function listStaff(): array
    {
        $stmt = $this->db->query(
            "SELECT id, username, email, full_name, phone, role, created_at FROM users WHERE role IN ('Admin','Staff') ORDER BY id DESC"
        );
        return $stmt->fetchAll();
    }

    public function create(string $username, string $email, string $password, string $fullName, string $phone, string $role): int
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare(
            'INSERT INTO users (username, email, password_hash, full_name, phone, role) VALUES (:u,:e,:p,:f,:ph,:r)'
        );
        $stmt->execute([
            'u' => $username,
            'e' => $email,
            'p' => $hash,
            'f' => $fullName,
            'ph' => $phone,
            'r' => $role,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateProfile(int $id, string $fullName, string $phone, ?string $email = null): bool
    {
        if ($email !== null) {
            $stmt = $this->db->prepare(
                'UPDATE users SET full_name = :f, phone = :p, email = :e WHERE id = :id'
            );
            return $stmt->execute(['f' => $fullName, 'p' => $phone, 'e' => $email, 'id' => $id]);
        }
        $stmt = $this->db->prepare('UPDATE users SET full_name = :f, phone = :p WHERE id = :id');
        return $stmt->execute(['f' => $fullName, 'p' => $phone, 'id' => $id]);
    }

    public function updateStaff(int $id, string $fullName, string $phone, string $email, string $role): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET full_name = :f, phone = :p, email = :e, role = :r WHERE id = :id AND role IN (\'Admin\',\'Staff\')'
        );
        return $stmt->execute(['f' => $fullName, 'p' => $phone, 'e' => $email, 'r' => $role, 'id' => $id]);
    }

    public function setPassword(int $id, string $newPassword): bool
    {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare('UPDATE users SET password_hash = :h WHERE id = :id');
        return $stmt->execute(['h' => $hash, 'id' => $id]);
    }

    /** Returns false if current password is wrong. */
    public function verifyAndChangePassword(int $id, string $currentPassword, string $newPassword): bool
    {
        $stmt = $this->db->prepare('SELECT password_hash FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        $hash = isset($row['password_hash']) ? trim((string) $row['password_hash']) : '';
        if ($hash === '' || !password_verify($currentPassword, $hash)) {
            return false;
        }
        return $this->setPassword($id, $newPassword);
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, username, email, full_name, phone, role FROM users WHERE email = :e LIMIT 1'
        );
        $stmt->execute(['e' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByValidPasswordResetToken(string $token): ?array
    {
        if ($token === '') {
            return null;
        }
        $stmt = $this->db->prepare(
            'SELECT id, username, email, full_name FROM users
             WHERE password_reset_token = :t AND password_reset_expires IS NOT NULL AND password_reset_expires > NOW()
             LIMIT 1'
        );
        $stmt->execute(['t' => $token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function setPasswordResetToken(int $id, string $token, \DateTimeInterface $expires): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET password_reset_token = :t, password_reset_expires = :e WHERE id = :id'
        );
        return $stmt->execute([
            't' => $token,
            'e' => $expires->format('Y-m-d H:i:s'),
            'id' => $id,
        ]);
    }

    public function clearPasswordResetToken(int $id): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET password_reset_token = NULL, password_reset_expires = NULL WHERE id = :id'
        );
        return $stmt->execute(['id' => $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM users WHERE id = :id AND role != :admin');
        return $stmt->execute(['id' => $id, 'admin' => 'Admin']);
    }

    public function usernameExists(string $username, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM users WHERE username = :u';
        $params = ['u' => $username];
        if ($exceptId !== null) {
            $sql .= ' AND id != :id';
            $params['id'] = $exceptId;
        }
        $stmt = $this->db->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }

    public function emailExists(string $email, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM users WHERE email = :e';
        $params = ['e' => $email];
        if ($exceptId !== null) {
            $sql .= ' AND id != :id';
            $params['id'] = $exceptId;
        }
        $stmt = $this->db->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }
}
