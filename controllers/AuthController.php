<?php
class AuthController
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // POST /auth/register
    public function register(): void
    {
        $body = $this->getBody();

        $username = trim($body['username'] ?? '');
        $email    = trim($body['email'] ?? '');
        $password = $body['password'] ?? '';

        // Validasi
        if (!$username) {
            Response::error('Username wajib diisi');
        }
        if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
            Response::error('Username hanya boleh huruf, angka, underscore, 3-30 karakter');
        }
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('Email tidak valid');
        }
        if (strlen($password) < 6) {
            Response::error('Password minimal 6 karakter');
        }

        // Cek duplikat email
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            Response::error('Email sudah terdaftar', 409);
        }

        // Cek duplikat username
        $stmt = $this->db->prepare("SELECT id FROM profiles WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            Response::error('Username sudah digunakan', 409);
        }

        $hashed  = password_hash($password, PASSWORD_BCRYPT);
        $token   = $this->generateToken();
        $expired = date('Y-m-d H:i:s', strtotime('+30 days'));

        // Insert user
        $stmt = $this->db->prepare("
            INSERT INTO users (email, password, token, token_expired_at)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$email, $hashed, $token, $expired]);
        $userId = (int)$this->db->lastInsertId();

        // Otomatis buat profil dengan username
        $stmt = $this->db->prepare("
            INSERT INTO profiles (user_id, username) VALUES (?, ?)
        ");
        $stmt->execute([$userId, $username]);

        Response::success([
            'user_id'          => $userId,
            'email'            => $email,
            'username'         => $username,
            'token'            => $token,
            'token_expired_at' => $expired,
        ], 'Registrasi berhasil', 201);
    }

    // POST /auth/login
    public function login(): void
    {
        $body     = $this->getBody();
        $email    = trim($body['email'] ?? '');
        $password = $body['password'] ?? '';

        if (!$email || !$password) {
            Response::error('Email dan password wajib diisi');
        }

        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            Response::error('Email atau password salah', 401);
        }

        $token   = $this->generateToken();
        $expired = date('Y-m-d H:i:s', strtotime('+30 days'));

        $stmt = $this->db->prepare("UPDATE users SET token = ?, token_expired_at = ? WHERE id = ?");
        $stmt->execute([$token, $expired, $user['id']]);

        Response::success([
            'user_id'          => (int)$user['id'],
            'email'            => $user['email'],
            'token'            => $token,
            'token_expired_at' => $expired,
        ], 'Login berhasil');
    }

    // POST /auth/logout
    public function logout(array $user): void
    {
        $stmt = $this->db->prepare("UPDATE users SET token = NULL, token_expired_at = NULL WHERE id = ?");
        $stmt->execute([$user['id']]);
        Response::success(null, 'Logout berhasil');
    }

    // ---- Helpers ----
    private function getBody(): array
    {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?? $_POST;
    }

    private function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}
