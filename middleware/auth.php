<?php
function requireAuth(PDO $db): array {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
        Response::unauthorized('Token not provided');
    }

    $token = substr($authHeader, 7);

    $stmt = $db->prepare("
        SELECT id, email, token, token_expired_at
        FROM users
        WHERE token = ?
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        Response::unauthorized('Invalid token');
    }

    if ($user['token_expired_at'] && strtotime($user['token_expired_at']) < time()) {
        Response::unauthorized('Token expired');
    }

    return $user;
}
