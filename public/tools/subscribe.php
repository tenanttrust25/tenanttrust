<?php
/**
 * Tenant Trust — landlord-tools lead capture endpoint.
 * Standalone; does NOT touch the Laravel app. Stores emails in a
 * non-web-exposed dir. Honeypot + simple per-IP rate limit.
 */
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function out(int $code, array $body): void { http_response_code($code); echo json_encode($body); exit; }

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    out(405, ['ok' => false, 'error' => 'method_not_allowed']);
}

$raw  = file_get_contents('php://input') ?: '';
$data = json_decode($raw, true);
if (!is_array($data)) { $data = $_POST; }

// Honeypot: bots fill hidden field -> pretend success, store nothing.
if (trim((string)($data['hp'] ?? '')) !== '') { out(200, ['ok' => true]); }

$email = trim((string)($data['email'] ?? ''));
$tool  = substr(preg_replace('/[^a-z0-9_-]/i', '', (string)($data['tool'] ?? '')) ?: 'unknown', 0, 60);

if ($email === '' || strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    out(422, ['ok' => false, 'error' => 'invalid_email']);
}

$dir = '/var/www/tenanttrust/storage/tools-leads';
if (!is_dir($dir)) { @mkdir($dir, 0770, true); }

// Per-IP throttle: max 10 submissions / hour.
$ip = (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
$now = time();
$rlFile = $dir . '/rl_' . sha1($ip) . '.txt';
$hits = [];
if (is_file($rlFile)) {
    $hits = array_filter(array_map('intval', explode(',', (string)@file_get_contents($rlFile))),
                         fn(int $t): bool => $t > $now - 3600);
}
if (count($hits) >= 10) { out(429, ['ok' => false, 'error' => 'rate_limited']); }
$hits[] = $now;
@file_put_contents($rlFile, implode(',', $hits), LOCK_EX);

$rec = [
    'ts'    => gmdate('c'),
    'email' => $email,
    'tool'  => $tool,
    'ip'    => substr($ip, 0, 45),
    'ua'    => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 200),
];
@file_put_contents($dir . '/leads.jsonl', json_encode($rec) . "\n", FILE_APPEND | LOCK_EX);

out(200, ['ok' => true]);
