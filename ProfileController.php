<?php
class ProfileController {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    // GET /profile  → profil sendiri (auth required)
    public function getMyProfile(array $user): void {
        $data = $this->fetchByUserId($user['id']);
        if (!$data) {
            Response::notFound('Profil belum dibuat');
        }
        Response::success($data);
    }

    // GET /profile/{username}  → profil publik
    public function getByUsername(string $username): void {
        $stmt = $this->db->prepare("
            SELECT p.username, p.full_name, p.avatar_url, p.bio,
                   p.skills, p.lat, p.lng, p.is_available, p.age
            FROM profiles p
            WHERE p.username = ?
        ");
        $stmt->execute([$username]);
        $data = $stmt->fetch();

        if (!$data) {
            Response::notFound('User tidak ditemukan');
        }

        if ($data['skills']) {
            $data['skills'] = json_decode($data['skills'], true);
        }

        Response::success($data);
    }

    // POST/PUT /profile  → create or update (auth required)
    public function createOrUpdate(array $user): void {
        $body = $this->getBody();

        $fields = ['username', 'full_name', 'avatar_url', 'phone_wa', 'bio', 'skills', 'lat', 'lng', 'is_available', 'age'];
        $data   = [];

        foreach ($fields as $f) {
            if (array_key_exists($f, $body)) {
                $data[$f] = $body[$f];
            }
        }

        if (empty($data)) {
            Response::error('Tidak ada field yang diupdate');
        }

        // Encode skills to JSON if array
        if (isset($data['skills']) && is_array($data['skills'])) {
            $data['skills'] = json_encode($data['skills']);
        }

        // Validate username uniqueness if changed
        if (isset($data['username'])) {
            $stmt = $this->db->prepare("SELECT id FROM profiles WHERE username = ? AND user_id != ?");
            $stmt->execute([$data['username'], $user['id']]);
            if ($stmt->fetch()) {
                Response::error('Username sudah digunakan', 409);
            }
        }

        // Check if profile exists
        $stmt = $this->db->prepare("SELECT id FROM profiles WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $existing = $stmt->fetch();

        if ($existing) {
            // UPDATE
            $setClauses = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($data)));
            $data['user_id'] = $user['id'];
            $stmt = $this->db->prepare("UPDATE profiles SET $setClauses WHERE user_id = :user_id");
            $stmt->execute($data);
            $message = 'Profil berhasil diupdate';
        } else {
            // INSERT
            $data['user_id'] = $user['id'];
            $columns      = implode(', ', array_keys($data));
            $placeholders = implode(', ', array_map(fn($k) => ":$k", array_keys($data)));
            $stmt = $this->db->prepare("INSERT INTO profiles ($columns) VALUES ($placeholders)");
            $stmt->execute($data);
            $message = 'Profil berhasil dibuat';
        }

        $profile = $this->fetchByUserId($user['id']);
        Response::success($profile, $message);
    }

    // ---- Helpers ----
    private function fetchByUserId(int $userId): ?array {
        $stmt = $this->db->prepare("SELECT * FROM profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $data = $stmt->fetch() ?: null;

        if ($data && $data['skills']) {
            $data['skills'] = json_decode($data['skills'], true);
        }

        return $data;
    }

    private function getBody(): array {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?? $_POST;
    }
}
