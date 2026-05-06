<?php

/**
 * ExploreController
 *
 * GET /explore?lat=-6.2&lng=106.8&radius=5&q=listrik
 *
 * Params:
 *  - lat     : latitude user (required)
 *  - lng     : longitude user (required)
 *  - radius  : km, default 5, max 10 (optional)
 *  - q       : keyword search nama/skills (optional)
 *  - page    : halaman, default 1 (optional)
 *  - limit   : jumlah per halaman, default 20 (optional)
 */
class ExploreController
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // GET /explore
    public function search(): void
    {
        $lat    = $_GET['lat']    ?? null;
        $lng    = $_GET['lng']    ?? null;
        $radius = (float)($_GET['radius'] ?? 5);
        $q      = trim($_GET['q'] ?? '');
        $page   = max(1, (int)($_GET['page']  ?? 1));
        $limit  = min(50, max(1, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        // lat & lng wajib
        if ($lat === null || $lng === null) {
            Response::error('Parameter lat dan lng wajib diisi');
        }

        $lat    = (float)$lat;
        $lng    = (float)$lng;
        $radius = min(10, max(1, $radius)); // clamp 1-10 km

        // Haversine formula — hitung jarak dalam km
        // 6371 = radius bumi dalam km
        $haversine = "
            (6371 * ACOS(
                COS(RADIANS(:lat)) * COS(RADIANS(p.lat)) *
                COS(RADIANS(p.lng) - RADIANS(:lng)) +
                SIN(RADIANS(:lat)) * SIN(RADIANS(p.lat))
            ))
        ";

        // Base query
        $where   = ["p.is_available = 1", "p.lat IS NOT NULL", "p.lng IS NOT NULL"];
        $params  = [':lat' => $lat, ':lng' => $lng];

        // Filter keyword — cari di skills (JSON) atau full_name
        if ($q !== '') {
            $where[]          = "(p.full_name LIKE :q OR JSON_SEARCH(LOWER(p.skills), 'one', LOWER(:q_exact)) IS NOT NULL)";
            $params[':q']     = "%$q%";
            $params[':q_exact'] = $q;
        }

        $whereClause = implode(' AND ', $where);

        // Query utama dengan jarak
        $sql = "
            SELECT
                p.user_id,
                p.username,
                p.full_name,
                p.avatar_url,
                p.bio,
                p.skills,
                p.is_available,
                p.age,
                p.lat,
                p.lng,
                ROUND($haversine, 2) AS distance_km
            FROM profiles p
            WHERE $whereClause
              AND $haversine <= :radius
            ORDER BY distance_km ASC
            LIMIT :limit OFFSET :offset
        ";

        // Count total untuk pagination
        $countSql = "
            SELECT COUNT(*) as total
            FROM profiles p
            WHERE $whereClause
              AND $haversine <= :radius
        ";

        $countParams = array_merge($params, [':radius' => $radius]);

        $stmtCount = $this->db->prepare($countSql);
        $stmtCount->execute($countParams);
        $total = (int)$stmtCount->fetch()['total'];

        // Fetch data
        $allParams = array_merge($params, [
            ':radius' => $radius,
            ':limit'  => $limit,
            ':offset' => $offset,
        ]);

        $stmt = $this->db->prepare($sql);

        // Bind integer params explicitly (PDO butuh ini untuk LIMIT/OFFSET)
        foreach ($allParams as $key => $val) {
            if (in_array($key, [':limit', ':offset'])) {
                $stmt->bindValue($key, $val, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $val);
            }
        }

        $stmt->execute();
        $workers = $stmt->fetchAll();

        // Decode skills JSON
        foreach ($workers as &$w) {
            $w['skills']       = $w['skills'] ? json_decode($w['skills'], true) : [];
            $w['distance_km']  = (float)$w['distance_km'];
            $w['is_available'] = (bool)$w['is_available'];
        }

        Response::success([
            'workers'     => $workers,
            'pagination'  => [
                'total'        => $total,
                'page'         => $page,
                'limit'        => $limit,
                'total_pages'  => (int)ceil($total / $limit),
            ],
            'filter' => [
                'lat'    => $lat,
                'lng'    => $lng,
                'radius' => $radius,
                'q'      => $q,
            ],
        ]);
    }
}
