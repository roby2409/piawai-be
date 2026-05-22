<?php

/**
 * UserController
 *
 * GET /user/me           → info akun (email, is_google_user)
 * PUT /user/username     → update profiles.username
 * PUT /user/password     → update users.password
 *                          - email user : wajib verifikasi password lama
 *                          - google user: langsung set (first time)
 */
class UserController
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // ─────────────────────────────────────────
    // GET /user/me
    // ─────────────────────────────────────────
    public function me(array $user): void
    {
        // Ambil username dari profiles
        $stmt = $this->db->prepare("SELECT username FROM profiles WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);

        Response::success([
            'user_id'        => (int)$user['id'],
            'email'          => $user['email'],
            'username'       => $profile['username'] ?? null,
            'is_google_user' => $user['google_id'] !== null,
            'has_password'   => $user['password'] !== null,
        ]);
    }

    // ─────────────────────────────────────────
    // PUT /user/username
    // Body: { "username": "baru123" }
    // ─────────────────────────────────────────
    public function updateUsername(array $user): void
    {
        $body     = $this->getBody();
        $username = trim($body['username'] ?? '');

        if (!$username) {
            Response::error('Username wajib diisi');
        }
        if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
            Response::error('Username hanya boleh huruf, angka, underscore, 3-30 karakter');
        }

        // Cek username sudah dipakai user lain
        $stmt = $this->db->prepare("
            SELECT id FROM profiles WHERE username = ? AND user_id != ?
        ");
        $stmt->execute([$username, $user['id']]);
        if ($stmt->fetch()) {
            Response::error('Username sudah digunakan', 409);
        }

        // Cek apakah sama dengan username sekarang
        $stmt = $this->db->prepare("SELECT username FROM profiles WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($current && $current['username'] === $username) {
            Response::error('Username sama dengan yang sekarang');
        }

        $stmt = $this->db->prepare("UPDATE profiles SET username = ? WHERE user_id = ?");
        $stmt->execute([$username, $user['id']]);

        Response::success(['username' => $username], 'Username berhasil diupdate');
    }

    // ─────────────────────────────────────────
    // PUT /user/password
    // Body (email user) : { "old_password": "xxx", "new_password": "yyy" }
    // Body (google user): { "new_password": "yyy" }  → first time set
    // ─────────────────────────────────────────
    public function updatePassword(array $user): void
    {
        $body        = $this->getBody();
        $oldPassword = $body['old_password'] ?? '';
        $newPassword = $body['new_password'] ?? '';

        if (!$newPassword) {
            Response::error('Password baru wajib diisi');
        }
        if (strlen($newPassword) < 8) {
            Response::error('Password baru minimal 8 karakter');
        }

        $isGoogleUser = $user['google_id'] !== null;
        $hasPassword  = $user['password'] !== null;

        if ($hasPassword) {
            // ── Email user: wajib verifikasi password lama ──
            if (!$oldPassword) {
                Response::error('Password lama wajib diisi');
            }
            if (!password_verify($oldPassword, $user['password'])) {
                Response::error('Password lama tidak sesuai', 401);
            }
            if (password_verify($newPassword, $user['password'])) {
                Response::error('Password baru tidak boleh sama dengan password lama');
            }
        }
        // Google user tanpa password → langsung set (tidak perlu old_password)

        $hashed = password_hash($newPassword, PASSWORD_BCRYPT);

        $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed, $user['id']]);

        $message = $hasPassword ? 'Password berhasil diupdate' : 'Password berhasil dibuat';
        Response::success(null, $message);
    }

    // ── Helpers ──
    private function getBody(): array
    {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?? $_POST;
    }
}
