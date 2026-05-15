<?php
// Deteksi otomatis environment
$isLocal = in_array($_SERVER['HTTP_HOST'], ['localhost', '192.168.18.63']);

define(
    'APP_BASE_URL',
    $isLocal
        ? 'http://' . $_SERVER['HTTP_HOST'] . '/apigoogle'
        : 'https://namadomain.com'  // ← ganti dengan domain kamu
);

define('UPLOAD_DIR', __DIR__ . '/../uploads/avatars/');
define('UPLOAD_URL', APP_BASE_URL . '/uploads/avatars/');
