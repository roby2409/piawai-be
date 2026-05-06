<?php
class AppConfigController {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    // GET /app-config
    public function get(): void {
        $stmt = $this->db->query("SELECT * FROM app_config WHERE id = 1");
        $data = $stmt->fetch();

        if (!$data) {
            Response::notFound('Config tidak ditemukan');
        }

        // Cast types
        $data['force_update']     = (bool)$data['force_update'];
        $data['maintenance_mode'] = (bool)$data['maintenance_mode'];

        Response::success($data);
    }

    // PUT /app-config  → admin only (auth required)
    public function update(array $user): void {
        $body = $this->getBody();

        $allowed = ['min_version', 'latest_version', 'force_update', 'update_message', 'maintenance_mode', 'playstore_url'];
        $data    = [];

        foreach ($allowed as $f) {
            if (array_key_exists($f, $body)) {
                $data[$f] = $body[$f];
            }
        }

        if (empty($data)) {
            Response::error('Tidak ada field yang diupdate');
        }

        // Upsert: insert if not exists, update if exists
        $stmt = $this->db->query("SELECT id FROM app_config WHERE id = 1");
        $exists = $stmt->fetch();

        if ($exists) {
            $setClauses = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($data)));
            $stmt = $this->db->prepare("UPDATE app_config SET $setClauses WHERE id = 1");
            $stmt->execute($data);
        } else {
            $data['id'] = 1;
            $columns      = implode(', ', array_keys($data));
            $placeholders = implode(', ', array_map(fn($k) => ":$k", array_keys($data)));
            $stmt = $this->db->prepare("INSERT INTO app_config ($columns) VALUES ($placeholders)");
            $stmt->execute($data);
        }

        // Return updated
        $stmt = $this->db->query("SELECT * FROM app_config WHERE id = 1");
        $updated = $stmt->fetch();
        $updated['force_update']     = (bool)$updated['force_update'];
        $updated['maintenance_mode'] = (bool)$updated['maintenance_mode'];

        Response::success($updated, 'Config berhasil diupdate');
    }

    private function getBody(): array {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?? $_POST;
    }
}
