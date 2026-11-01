<?php
/**
 * Tenant Trust — /book/ scheduler endpoint.
 * Standalone; does NOT touch the Laravel app.
 * Stores the booking request (non-web-exposed) AND appends to the lead stream
 * so the existing lead-alert job pings Steve. Honeypot + per-IP throttle.
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

function s($v, int $max = 500): string {
    return substr(trim((string)($v ?? '')), 0, $max);
}

// Honeypot
if (s($data['hp'] ?? '') !== '') { out(200, ['ok' => true]); }

$email = s($data['email'] ?? '', 254);
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    out(422, ['ok' => false, 'error' => 'invalid_email']);
}

$dir = '/var/www/tenanttrust/storage/tools-leads';
if (!is_dir($dir)) { @mkdir($dir, 0770, true); }

// Per-IP throttle: max 8 bookings / hour.
$ip = (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
$now = time();
$rlFile = $dir . '/rl_book_' . sha1($ip) . '.txt';
$hits = [];
if (is_file($rlFile)) {
    $hits = array_filter(array_map('intval', explode(',', (string)@file_get_contents($rlFile))),
                         fn(int $t): bool => $t > $now - 3600);
}
if (count($hits) >= 8) { out(429, ['ok' => false, 'error' => 'rate_limited']); }
$hits[] = $now;
@file_put_contents($rlFile, implode(',', $hits), LOCK_EX);

$rec = [
    'ts'        => gmdate('c'),
    'name'      => s($data['name'] ?? '', 120),
    'email'     => $email,
    'phone'     => s($data['phone'] ?? '', 40),
    'units'     => s($data['units'] ?? '', 12),
    'topic'     => s($data['topic'] ?? '', 80),
    'slot_date' => s($data['slot_date'] ?? '', 12),
    'slot_time' => s($data['slot_time'] ?? '', 12),
    'notes'     => s($data['notes'] ?? '', 1500),
    'ip'        => substr($ip, 0, 45),
    'ua'        => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 200),
];

// 1) Booking record
@file_put_contents($dir . '/bookings.jsonl', json_encode($rec) . "\n", FILE_APPEND | LOCK_EX);

// 2) Lead stream (so the alert job + nurture engine see it)
$lead = [
    'ts'    => $rec['ts'],
    'email' => $email,
    'tool'  => 'book-call',
    'ip'    => $rec['ip'],
    'ua'    => $rec['ua'],
];
@file_put_contents($dir . '/leads.jsonl', json_encode($lead) . "\n", FILE_APPEND | LOCK_EX);

out(200, ['ok' => true]);
