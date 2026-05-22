<?php
function requireAuth(PDO $db): array
{
    $headers    = function_exists('getallheaders') ? getallheaders() : [];
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    // Fallback via $_SERVER untuk server yang tidak support getallheaders()
    if (!$authHeader) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? '';
    }

    if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
        Response::unauthorized('Token not provided');
    }

    $token = substr($authHeader, 7);

    // Serahkan pengecekan expired ke MySQL NOW() — konsisten UTC
    $stmt = $db->prepare("
        SELECT id, email, password, google_id, token, token_expired_at
        FROM users
        WHERE token = ?
          AND (token_expired_at IS NULL OR token_expired_at > NOW())
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        Response::unauthorized('Invalid or expired token');
    }

    return $user;
}
