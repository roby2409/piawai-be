<?php
class AppConfigController
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // GET /app-config
    public function get(): void
    {
        $stmt = $this->db->prepare("SELECT * FROM app_config WHERE id = 1");
        $stmt->execute();
        $config = $stmt->fetch();

        if (!$config) {
            Response::notFound('Config tidak ditemukan');
        }

        Response::success([
            // App version
            'min_version'      => $config['min_version'],
            'latest_version'   => $config['latest_version'],
            'force_update'     => (bool)$config['force_update'],
            'update_message'   => $config['update_message'],
            'maintenance_mode' => (bool)$config['maintenance_mode'],
            'playstore_url'    => $config['playstore_url'],

            // Feature flags
            'subscription_enabled'    => (bool)$config['subscription_enabled'],
            'ads_enabled'             => (bool)$config['ads_enabled'],

            // Limits
            'free_wa_click_limit'     => (int)$config['free_wa_click_limit'],
            'free_radius_km'          => (int)$config['free_radius_km'],
            'premium_radius_km'       => (int)$config['premium_radius_km'],

            // Ad Unit IDs — null kalau belum diset
            'banner_ad_unit_id'       => $config['banner_ad_unit_id'],
            'interstitial_ad_unit_id' => $config['interstitial_ad_unit_id'],

            'gemini_api_key' => $config['gemini_api_key'],
        ]);
    }

    // PUT /app-config  → admin only (auth required)
    public function update(array $user): void
    {
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

    private function getBody(): array
    {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?? $_POST;
    }

    public function getKeys(array $user): void
    {
        $stmt = $this->db->prepare("SELECT * FROM app_config WHERE id = 1");
        $stmt->execute();
        $config = $stmt->fetch();
        Response::success([
            'gemini_api_key' => $config['gemini_api_key'],
        ]);
    }
}
