<?php

/**
 * ExploreController
 *
 * GET /explore          → search worker dengan filter
 * GET /explore/suggest  → suggestions dari services.nama
 *
 * Params /explore:
 *  - lat     : latitude user (required)
 *  - lng     : longitude user (required)
 *  - radius  : km, default 5, max 50 (optional)
 *  - q       : keyword — cocok ke services.nama, services.deskripsi, profiles.bio (optional)
 *  - gender  : 'Pria' | 'Wanita' (optional)
 *  - age_min : integer (optional)
 *  - age_max : integer (optional)
 *
 * Params /explore/suggest:
 *  - q       : keyword prefix (required, min 1 char)
 *  - limit   : default 8, max 15 (optional)
 */
class ExploreController
{
    private PDO $db;
    private int $currentUserId;

    private string $cacheDir = __DIR__ . '/../cache/';
    private string $cacheFile;
    private int $cacheTtl = 3600;

    public function __construct(PDO $db, int $currentUserId)
    {
        $this->db            = $db;
        $this->currentUserId = $currentUserId;
        $this->cacheFile     = $this->cacheDir . 'suggestions.json';
    }

    // ─────────────────────────────────────────
    // GET /explore
    // ─────────────────────────────────────────
    public function search(): void
    {
        // ── Params ──
        $lat    = $_GET['lat']    ?? null;
        $lng    = $_GET['lng']    ?? null;
        $radius = (float)($_GET['radius'] ?? 5);
        $q      = trim($_GET['q']      ?? '');
        $gender = trim($_GET['gender'] ?? '');
        $ageMin = isset($_GET['age_min']) ? (int)$_GET['age_min'] : null;
        $ageMax = isset($_GET['age_max']) ? (int)$_GET['age_max'] : null;

        if ($lat === null || $lng === null) {
            Response::error('Parameter lat dan lng wajib diisi', 422);
        }

        $lat    = (float)$lat;
        $lng    = (float)$lng;
        $radius = min(50, max(1, $radius)); // clamp 1–50 km

        // ── Haversine ──
        // haversineSelect: literal value → aman dipakai di SELECT (distance_km)
        // haversineWhere : named param  → dipakai di WHERE <= :radius
        // Dipisah karena PDO tidak bisa bind named param yang sama lebih dari sekali
        $haversineSelect = "
            (6371 * ACOS(
                GREATEST(-1, LEAST(1,
                    COS(RADIANS($lat)) * COS(RADIANS(p.lat)) *
                    COS(RADIANS(p.lng) - RADIANS($lng)) +
                    SIN(RADIANS($lat)) * SIN(RADIANS(p.lat))
                ))
            ))
        ";

        $haversineWhere = "
            (6371 * ACOS(
                GREATEST(-1, LEAST(1,
                    COS(RADIANS(:lat)) * COS(RADIANS(p.lat)) *
                    COS(RADIANS(p.lng) - RADIANS(:lng)) +
                    SIN(RADIANS(:lat)) * SIN(RADIANS(p.lat))
                ))
            ))
        ";

        // ── Base WHERE & params ──
        $where  = [
            "p.is_available = 1",
            "p.lat IS NOT NULL",
            "p.lng IS NOT NULL",
            "s.is_active = 1",
            "p.user_id != :current_user_id",
        ];
        $params = [
            ':lat'             => $lat,
            ':lng'             => $lng,
            ':current_user_id' => $this->currentUserId,
        ];

        // ── Filter keyword (JOIN ke services) ──
        // :q1, :q2, :q3 — PDO tidak boleh named param yang sama lebih dari sekali
        if ($q !== '') {
            $where[]        = "(s.nama LIKE :q1 OR s.deskripsi LIKE :q2 OR p.bio LIKE :q3)";
            $params[':q1']  = "%$q%";
            $params[':q2']  = "%$q%";
            $params[':q3']  = "%$q%";
        }

        // ── Filter gender ──
        if ($gender !== '' && in_array($gender, ['Pria', 'Wanita'])) {
            $where[]           = "p.gender = :gender";
            $params[':gender'] = $gender;
        }

        // ── Filter umur ──
        if ($ageMin !== null) {
            $where[]             = "p.age >= :age_min";
            $params[':age_min']  = $ageMin;
        }
        if ($ageMax !== null) {
            $where[]             = "p.age <= :age_max";
            $params[':age_max']  = $ageMax;
        }

        $whereClause = implode(' AND ', $where);

        // ── Main query ──
        // GROUP BY p.user_id → satu provider muncul sekali walau punya banyak service
        // GROUP_CONCAT → kumpulkan semua nama service jadi satu field
        $sql = "
            SELECT
                p.user_id,
                p.username,
                p.full_name,
                p.avatar_url,
                p.bio,
                p.age,
                p.gender,
                p.area_label,
                p.is_available,
                p.lat,
                p.lng,
                GROUP_CONCAT(DISTINCT s.nama ORDER BY s.nama SEPARATOR ', ') AS services,
                ROUND($haversineSelect, 2) AS distance_km
            FROM profiles p
            INNER JOIN services s ON s.user_id = p.user_id
            WHERE $whereClause
              AND $haversineWhere <= :radius
            GROUP BY
                p.user_id, p.username, p.full_name, p.avatar_url,
                p.bio, p.age, p.gender, p.area_label, p.is_available,
                p.lat, p.lng
            ORDER BY distance_km ASC
        ";

        // ── Fetch ──
        $allParams = array_merge($params, [':radius' => $radius]);

        $stmt = $this->db->prepare($sql);
        foreach ($allParams as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        $workers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ── Format response ──
        foreach ($workers as &$w) {
            $w['distance_km']   = (float)$w['distance_km'];
            $w['is_available']  = (bool)$w['is_available'];
            // services → array
            $w['services'] = $w['services'] ? explode(', ', $w['services']) : [];
        }

        Response::success([
            'total'   => count($workers),
            'workers' => $workers,
            'filter'  => [
                'lat'     => $lat,
                'lng'     => $lng,
                'radius'  => $radius,
                'q'       => $q,
                'gender'  => $gender ?: null,
                'age_min' => $ageMin,
                'age_max' => $ageMax,
            ],
        ]);
    }

    // ─────────────────────────────────────────
    // GET /explore/suggest
    // ─────────────────────────────────────────
    public function suggest(): void
    {
        $q     = trim($_GET['q'] ?? '');
        $limit = min(15, max(1, (int)($_GET['limit'] ?? 8)));

        if ($q === '') {
            // Kembalikan top popular dari cache
            Response::success(['suggestions' => $this->getPopularFromCache($limit)]);
            return;
        }

        // Search langsung ke DB (debounce di Flutter, jadi ini aman)
        $stmt = $this->db->prepare("
            SELECT DISTINCT s.nama, COUNT(*) AS total
            FROM services s
            INNER JOIN profiles p ON p.user_id = s.user_id
            WHERE s.is_active = 1
              AND p.is_available = 1
              AND s.nama LIKE :q
            GROUP BY s.nama
            ORDER BY total DESC, s.nama ASC
            LIMIT :limit
        ");
        $stmt->bindValue(':q',     "%$q%",  PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit,  PDO::PARAM_INT);
        $stmt->execute();

        $rows        = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $suggestions = array_column($rows, 'nama');

        Response::success(['suggestions' => $suggestions]);
    }

    // ─────────────────────────────────────────
    // Cache: popular suggestions (top services)
    // Dipakai saat q kosong — list awal suggestion
    // ─────────────────────────────────────────
    private function getPopularFromCache(int $limit): array
    {
        // Buat direktori cache kalau belum ada
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }

        // Pakai cache kalau masih fresh
        if (
            file_exists($this->cacheFile) &&
            (time() - filemtime($this->cacheFile)) < $this->cacheTtl
        ) {
            $cached = json_decode(file_get_contents($this->cacheFile), true);
            return array_slice($cached ?? [], 0, $limit);
        }

        // Rebuild cache dari DB
        $stmt = $this->db->prepare("
            SELECT s.nama, COUNT(*) AS total
            FROM services s
            INNER JOIN profiles p ON p.user_id = s.user_id
            WHERE s.is_active = 1
              AND p.is_available = 1
            GROUP BY s.nama
            ORDER BY total DESC, s.nama ASC
            LIMIT 50
        ");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $all  = array_column($rows, 'nama');

        file_put_contents($this->cacheFile, json_encode($all));

        return array_slice($all, 0, $limit);
    }
}
