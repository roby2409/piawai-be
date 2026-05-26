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
        $fullname = trim($body['full_name'] ?? '');
        $username = trim($body['username'] ?? '');
        $email    = trim($body['email'] ?? '');
        $password = $body['password'] ?? '';

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

        $stmt = $this->db->prepare("
            INSERT INTO users (email, password, token, token_expired_at)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$email, $hashed, $token, $expired]);
        $userId = (int)$this->db->lastInsertId();

        $stmt = $this->db->prepare("
            INSERT INTO profiles (user_id, username, full_name) VALUES (?, ?, ?)
        ");
        $stmt->execute([$userId, $username, $fullname]);

        Response::success([
            'user_id'          => $userId,
            'email'            => $email,
            'username'         => $username,
            'full_name'        => $fullname,
            'token'            => $token,
            'token_expired_at' => $expired,
        ], 'Registrasi berhasil', 201);
    }

    // POST /auth/login
    public function login(): void
    {
        $body     = $this->getBody();
        $login    = trim($body['email_or_username'] ?? '');
        $password = $body['password'] ?? '';

        if (!$login || !$password) {
            Response::error('Email/username dan password wajib diisi');
        }

        $stmt = $this->db->prepare("
    SELECT u.* FROM users u
    LEFT JOIN profiles p ON p.user_id = u.id
    WHERE u.email = :email OR p.username = :username
    LIMIT 1
");
        $stmt->execute([
            ':email'    => $login,
            ':username' => $login,
        ]);
        $user = $stmt->fetch();

        if ($user && $user['password'] === null) {
            Response::error('Akun ini terdaftar via Google. Silakan login dengan Google.', 401);
        }

        if (!$user || !password_verify($password, $user['password'])) {
            Response::error('Email/username atau password salah', 401);
        }

        $token   = $this->generateToken();
        $expired = date('Y-m-d H:i:s', strtotime('+30 days'));

        $stmt = $this->db->prepare("UPDATE users SET token = ?, token_expired_at = ? WHERE id = ?");
        $stmt->execute([$token, $expired, $user['id']]);

        $stmt = $this->db->prepare("SELECT username FROM profiles WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $profile = $stmt->fetch();

        Response::success([
            'user_id'          => (int)$user['id'],
            'email'            => $user['email'],
            'username'         => $profile['username'] ?? null,
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

    // POST /auth/forgot-password
    // Body: { "email": "user@mail.com" }
    public function forgotPassword(): void
    {
        $body  = $this->getBody();
        $email = trim($body['email'] ?? '');

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('Email tidak valid');
        }

        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Tetap return success meski email tidak ditemukan (security best practice)
        if (!$user) {
            Response::success(null, 'Jika email terdaftar, kode OTP akan dikirim');
        }

        $otp        = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otpExpired = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        $stmt = $this->db->prepare("UPDATE users SET otp_code = ?, otp_expired_at = ? WHERE id = ?");
        $stmt->execute([$otp, $otpExpired, $user['id']]);

        // TODO: Kirim OTP via email (SMTP/Mailgun/dll)
        try {
            sendOtpEmail($email, $otp);
        } catch (Exception $e) {
            // Tetap lanjut meski email gagal — jangan expose error ke user
            error_log('Gagal kirim OTP: ' . $e->getMessage());
        }

        Response::success(null, 'Jika email terdaftar, kode OTP akan dikirim');
    }

    // POST /auth/verify-otp
    // Body: { "email": "user@mail.com", "otp": "123456" }
    public function verifyOtp(): void
    {
        $body  = $this->getBody();
        $email = trim($body['email'] ?? '');
        $otp   = trim($body['otp'] ?? '');

        if (!$email || !$otp) {
            Response::error('Email dan OTP wajib diisi');
        }

        // Serahkan pengecekan expired ke MySQL NOW() — konsisten UTC
        $stmt = $this->db->prepare("
            SELECT id, otp_code
            FROM users
            WHERE email = ?
              AND (otp_expired_at IS NULL OR otp_expired_at > NOW())
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            Response::error('Email tidak ditemukan atau OTP sudah kadaluarsa', 400);
        }

        if (!$user['otp_code']) {
            Response::error('OTP tidak ditemukan, silakan kirim ulang', 400);
        }

        if ($user['otp_code'] !== $otp) {
            Response::error('OTP tidak valid', 400);
        }

        // Generate reset token + clear OTP sekaligus
        $resetToken = $this->generateToken();
        $stmt = $this->db->prepare("
            UPDATE users
            SET token            = ?,
                token_expired_at = ?,
                otp_code         = NULL,
                otp_expired_at   = NULL
            WHERE id = ?
        ");
        $stmt->execute([
            $resetToken,
            date('Y-m-d H:i:s', strtotime('+15 minutes')),
            $user['id'],
        ]);

        Response::success([
            'reset_token' => $resetToken,
        ], 'OTP valid');
    }

    // POST /auth/reset-password
    // Header: Authorization: Bearer {reset_token}
    // Body: { "password": "newpass", "password_confirmation": "newpass" }
    public function resetPassword(): void
    {
        $body     = $this->getBody();
        $password = $body['password'] ?? '';
        $confirm  = $body['password_confirmation'] ?? '';

        if (!$password || !$confirm) {
            Response::error('Password dan konfirmasi wajib diisi');
        }

        if (strlen($password) < 6) {
            Response::error('Password minimal 6 karakter');
        }

        if ($password !== $confirm) {
            Response::error('Password tidak cocok');
        }

        $resetToken = $this->getBearerToken();

        if (!$resetToken) {
            Response::error('Reset token wajib diisi', 401);
        }

        $stmt = $this->db->prepare("
            SELECT id FROM users
            WHERE token = ? AND token_expired_at > NOW()
        ");
        $stmt->execute([$resetToken]);
        $user = $stmt->fetch();

        if (!$user) {
            Response::error('Reset token tidak valid atau sudah kadaluarsa', 401);
        }

        $hashed     = password_hash($password, PASSWORD_BCRYPT);
        $newToken   = $this->generateToken();
        $newExpired = date('Y-m-d H:i:s', strtotime('+30 days'));

        $stmt = $this->db->prepare("
            UPDATE users
            SET password         = ?,
                otp_code         = NULL,
                otp_expired_at   = NULL,
                token            = ?,
                token_expired_at = ?
            WHERE id = ?
        ");
        $stmt->execute([$hashed, $newToken, $newExpired, $user['id']]);

        Response::success([
            'token'            => $newToken,
            'token_expired_at' => $newExpired,
        ], 'Password berhasil direset');
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

    // Robust bearer token reader — fallback untuk semua server config
    private function getBearerToken(): string
    {
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            $auth    = $headers['Authorization'] ?? $headers['authorization'] ?? '';
            if ($auth) {
                return str_replace('Bearer ', '', $auth);
            }
        }

        $auth = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? '';

        return str_replace('Bearer ', '', $auth);
    }
}
