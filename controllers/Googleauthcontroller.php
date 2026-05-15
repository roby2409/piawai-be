<?php

/**
 * GoogleAuthController
 *
 * Endpoint: POST /auth/google
 * Body    : { "id_token": "<token dari google_sign_in Flutter>" }
 *
 * Alur:
 * 1. Flutter login pakai google_sign_in → dapat idToken
 * 2. Kirim idToken ke endpoint ini
 * 3. PHP verifikasi ke Google tokeninfo API
 * 4. Simpan/update user di DB
 * 5. Kembalikan app token ke Flutter
 */
class GoogleAuthController
{
    private PDO $db;

    // Daftarkan Client ID dari Google Cloud Console
    // Bisa multiple client ID (Android + iOS + Web)
    private array $allowedClientIds = [
        '970494615255-5itcbkk4o6o68pleo5ur4u0j1us10oem.apps.googleusercontent.com',
    ];

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // POST /auth/google
    public function handle(): void
    {
        $body    = json_decode(file_get_contents('php://input'), true) ?? [];
        $idToken = trim($body['id_token'] ?? '');

        if (!$idToken) {
            Response::error('id_token wajib diisi');
        }

        // 1. Verifikasi token ke Google
        $googleUser = $this->verifyGoogleToken($idToken);

        if (!$googleUser) {
            Response::error('Token Google tidak valid', 401);
        }

        $googleId = $googleUser['sub'];       // unique Google user ID
        $email    = $googleUser['email'];
        $name     = $googleUser['name'] ?? '';
        $avatar   = $googleUser['picture'] ?? '';

        // 2. Cari user by google_id atau email
        $user = $this->findUser($googleId, $email);

        if ($user) {
            // Update google_id kalau belum ada (user daftar email dulu)
            if (!$user['google_id']) {
                $stmt = $this->db->prepare("UPDATE users SET google_id = ? WHERE id = ?");
                $stmt->execute([$googleId, $user['id']]);
            }
        } else {
            // 3. Register user baru via Google
            $token   = $this->generateToken();
            $expired = date('Y-m-d H:i:s', strtotime('+30 days'));

            $stmt = $this->db->prepare("
                INSERT INTO users (email, google_id, token, token_expired_at)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$email, $googleId, $token, $expired]);
            $userId = (int)$this->db->lastInsertId();

            // Buat profil awal dari data Google
            $this->createInitialProfile($userId, $name, $avatar);

            // Ambil user yang baru dibuat
            $user = $this->findUser($googleId, $email);
        }

        // 4. Refresh app token
        $token   = $this->generateToken();
        $expired = date('Y-m-d H:i:s', strtotime('+30 days'));

        $stmt = $this->db->prepare("UPDATE users SET token = ?, token_expired_at = ? WHERE id = ?");
        $stmt->execute([$token, $expired, $user['id']]);

        // 5. Ambil profil
        $stmt = $this->db->prepare("SELECT * FROM profiles WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $profile = $stmt->fetch() ?: null;

        $services = [];
        if ($profile) {
            $stmt = $this->db->prepare("
                SELECT id, nama, harga_jam, harga_hari, harga_proyek 
                FROM services 
                WHERE user_id = ? AND is_active = 1
            ");
            $stmt->execute([$user['id']]);
            $services = $stmt->fetchAll();
        }

        Response::success([
            'user_id'          => (int)$user['id'],
            'email'            => $user['email'],
            'token'            => $token,
            'token_expired_at' => $expired,
            'is_new_user'      => !isset($user['existing']),
            'profile'          => $profile,
            'services'         => $services,
        ], 'Login dengan Google berhasil');
    }

    // ---- Private helpers ----

    /**
     * Verifikasi idToken ke Google tokeninfo endpoint.
     * Shared hosting tidak perlu library — cukup HTTP request ke Google.
     */
    private function verifyGoogleToken(string $idToken): ?array
    {
        $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($idToken);

        $ctx = stream_context_create([
            'http' => [
                'timeout' => 5,
                'method'  => 'GET',
            ],
            'ssl' => [
                'verify_peer'      => true,
                'verify_peer_name' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $ctx);

        if (!$response) {
            return null;
        }

        $data = json_decode($response, true);

        // Cek error dari Google
        if (isset($data['error'])) {
            return null;
        }

        // Wajib: email terverifikasi
        if (($data['email_verified'] ?? 'false') !== 'true') {
            return null;
        }

        // Wajib: audience harus salah satu dari client ID kita
        $audience = $data['aud'] ?? '';
        if (!in_array($audience, $this->allowedClientIds, true)) {
            // Uncomment baris di bawah untuk strict check (recommended production)
            return null;
        }

        return $data;
    }

    private function findUser(string $googleId, string $email): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *, 1 as existing
            FROM users
            WHERE google_id = ? OR email = ?
            LIMIT 1
        ");
        $stmt->execute([$googleId, $email]);
        return $stmt->fetch() ?: null;
    }

    private function createInitialProfile(int $userId, string $fullName, string $avatarUrl): void
    {
        // Generate username unik dari nama
        $base     = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $fullName));
        $base     = $base ?: 'user';
        $username = $base;
        $counter  = 1;

        while (true) {
            $stmt = $this->db->prepare("SELECT id FROM profiles WHERE username = ?");
            $stmt->execute([$username]);
            if (!$stmt->fetch()) break;
            $username = $base . $counter++;
        }

        $stmt = $this->db->prepare("
            INSERT INTO profiles (user_id, full_name, username, avatar_url)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $fullName, $username, $avatarUrl]);
    }

    private function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}
