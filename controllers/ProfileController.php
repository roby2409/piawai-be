<?php
class ProfileController
{


    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // ─────────────────────────────────────────
    // GET /profile  → profil sendiri (auth required)
    // ─────────────────────────────────────────
    public function getMyProfile(array $user): void
    {
        $data = $this->fetchByUserId($user['id']);
        if (!$data) {
            Response::notFound('Profil belum dibuat');
        }
        Response::success($data);
    }

    // ─────────────────────────────────────────
    // GET /profile/{username}  → profil orang lain
    // ─────────────────────────────────────────
    public function getByUsername(string $username): void
    {
        $stmt = $this->db->prepare("SELECT * FROM profiles WHERE username = ?");
        $stmt->execute([$username]);
        $data = $stmt->fetch() ?: null;

        if (!$data) {
            Response::notFound('User tidak ditemukan');
            return;
        }

        $data['is_available'] = (bool) $data['is_available'];
        $data['services'] = $this->fetchServices($data['user_id']);
        unset($data['user_id'], $data['id'], $data['skills']); // ← field yang gak perlu ke publik

        Response::success($data);
    }

    // ─────────────────────────────────────────
    // POST/PUT /profile  → update profil
    // ─────────────────────────────────────────
    public function createOrUpdate(array $user): void
    {
        $body = $this->getBody();

        // Field yang boleh diupdate — skills dihapus
        $allowedFields = [
            'full_name',
            'phone_wa',
            'email_contact',
            'instagram',
            'bio',
            'lat',
            'lng',
            'radius_km',
            'area_label',
            'is_available',
            'age',
            'gender',
        ];

        $data = [];
        foreach ($allowedFields as $f) {
            if (array_key_exists($f, $body)) {
                $data[$f] = $body[$f];
            }
        }

        if (empty($data)) {
            Response::error('Tidak ada field yang diupdate');
        }

        // Profil sudah pasti ada (dibuat otomatis saat register)
        $stmt = $this->db->prepare("SELECT id FROM profiles WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $existing = $stmt->fetch();

        if ($existing) {
            $setClauses      = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($data)));
            $data['user_id'] = $user['id'];
            $stmt            = $this->db->prepare("UPDATE profiles SET $setClauses WHERE user_id = :user_id");
            $stmt->execute($data);
            $message = 'Profil berhasil diupdate';
        } else {
            $data['user_id'] = $user['id'];
            $columns         = implode(', ', array_keys($data));
            $placeholders    = implode(', ', array_map(fn($k) => ":$k", array_keys($data)));
            $stmt            = $this->db->prepare("INSERT INTO profiles ($columns) VALUES ($placeholders)");
            $stmt->execute($data);
            $message = 'Profil berhasil dibuat';
        }

        Response::success($this->fetchByUserId($user['id']), $message);
    }


    // ─────────────────────────────────────────
    // POST /profile/avatar
    // Request: multipart/form-data, field = "avatar"
    // ─────────────────────────────────────────
    public function uploadAvatar(array $user): void
    {
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            Response::error('File avatar tidak ditemukan atau gagal diupload');
        }

        $file    = $_FILES['avatar'];
        $tmpPath = $file['tmp_name'];
        $size    = $file['size'];

        if ($size > 2 * 1024 * 1024) {
            Response::error('Ukuran file maksimal 2MB');
        }

        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $tmpPath);
        finfo_close($finfo);

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mimeType, $allowedMimes, true)) {
            Response::error('Format file harus JPG, PNG, atau WebP');
        }

        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0755, true);
        }

        // Hapus avatar lama kalau file lokal
        $stmt = $this->db->prepare("SELECT avatar_url FROM profiles WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $existing = $stmt->fetch();

        if ($existing && $existing['avatar_url']) {
            $oldUrl = $existing['avatar_url'];
            // ← cek path relatif (bukan full URL)
            if (str_starts_with($oldUrl, 'uploads/avatars/')) {
                $oldFile = UPLOAD_DIR . basename($oldUrl);
                if (file_exists($oldFile)) {
                    @unlink($oldFile);
                }
            }
        }

        $ext = match ($mimeType) {
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => 'jpg',
        };

        $filename = 'avatar_' . $user['id'] . '_' . time() . '.' . $ext;
        $destPath = UPLOAD_DIR . $filename;

        if (!move_uploaded_file($tmpPath, $destPath)) {
            Response::error('Gagal menyimpan file');
        }

        // Simpan path relatif ke DB
        $avatarPath = 'uploads/avatars/' . $filename;

        $stmt = $this->db->prepare("UPDATE profiles SET avatar_url = ? WHERE user_id = ?");
        $stmt->execute([$avatarPath, $user['id']]);

        // Return full URL ke Flutter
        $avatarUrl = APP_BASE_URL . '/' . $avatarPath;
        Response::success(['avatar_url' => $avatarUrl], 'Foto profil berhasil diupdate');
    }


    // ─────────────────────────────────────────
    // GET /profile/services  → list services milik sendiri
    // ─────────────────────────────────────────
    public function getServices(array $user): void
    {
        $services = $this->fetchServices($user['id']);
        Response::success($services);
    }

    // ─────────────────────────────────────────
    // POST /profile/services  → tambah service baru
    // ─────────────────────────────────────────
    public function addService(array $user): void
    {
        $body = $this->getBody();

        $nama         = trim($body['nama'] ?? '');
        $deskripsi    = trim($body['deskripsi'] ?? '');

        if (!$nama) {
            Response::error('Nama layanan wajib diisi');
        }



        $stmt = $this->db->prepare("
            INSERT INTO services (user_id, nama, deskripsi)
            VALUES (:user_id, :nama, :deskripsi)
        ");
        $stmt->execute([
            ':user_id'      => $user['id'],
            ':nama'         => $nama,
            ':deskripsi'    => $deskripsi ?: null
        ]);

        $newId   = (int)$this->db->lastInsertId();
        $service = $this->fetchServiceById($newId, $user['id']);

        Response::success($service, 'Layanan berhasil ditambahkan', 201);
    }

    // ─────────────────────────────────────────
    // PUT /profile/services/{id}  → update service
    // ─────────────────────────────────────────
    public function updateService(array $user, int $serviceId): void
    {
        // Pastikan service milik user ini
        $service = $this->fetchServiceById($serviceId, $user['id']);
        if (!$service) {
            Response::notFound('Layanan tidak ditemukan');
        }

        $body = $this->getBody();

        $allowedFields = ['nama', 'deskripsi', 'is_active'];
        $data          = [];

        foreach ($allowedFields as $f) {
            if (array_key_exists($f, $body)) {
                $data[$f] = $body[$f] === '' ? null : $body[$f];
            }
        }

        if (empty($data)) {
            Response::error('Tidak ada field yang diupdate');
        }

        $setClauses    = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($data)));
        $data['id']    = $serviceId;
        $data['user_id'] = $user['id'];

        $stmt = $this->db->prepare("UPDATE services SET $setClauses WHERE id = :id AND user_id = :user_id");
        $stmt->execute($data);

        Response::success($this->fetchServiceById($serviceId, $user['id']), 'Layanan berhasil diupdate');
    }

    // ─────────────────────────────────────────
    // DELETE /profile/services/{id}  → hapus service
    // ─────────────────────────────────────────
    public function deleteService(array $user, int $serviceId): void
    {
        $service = $this->fetchServiceById($serviceId, $user['id']);
        if (!$service) {
            Response::notFound('Layanan tidak ditemukan');
        }

        $stmt = $this->db->prepare("DELETE FROM services WHERE id = ? AND user_id = ?");
        $stmt->execute([$serviceId, $user['id']]);

        Response::success(null, 'Layanan berhasil dihapus');
    }

    // ─────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────
    private function fetchByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $data = $stmt->fetch() ?: null;

        if ($data) {
            $data['services']    = $this->fetchServices($userId);
            $data['is_available'] = (bool)$data['is_available'];
        }

        return $data;
    }

    private function fetchServices(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT id, nama, deskripsi, is_active
            FROM services
            WHERE user_id = ?
            ORDER BY created_at ASC
        ");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();

        // Cast tipe data
        return array_map(function ($row) {
            return [
                'id'            => (int)$row['id'],
                'nama'          => $row['nama'],
                'deskripsi'     => $row['deskripsi'],
                'is_active'     => (bool)$row['is_active'],
            ];
        }, $rows);
    }

    private function fetchServiceById(int $serviceId, int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id, nama, deskripsi, is_active
            FROM services
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$serviceId, $userId]);
        $row = $stmt->fetch() ?: null;

        if (!$row) return null;

        return [
            'id'           => (int)$row['id'],
            'nama'         => $row['nama'],
            'deskripsi'    => $row['deskripsi'],
            'is_active'    => (bool)$row['is_active'],
        ];
    }

    private function getBody(): array
    {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?? $_POST;
    }
}
