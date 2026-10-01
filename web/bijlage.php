<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';

$id = (string) ($_GET['id'] ?? '');
$attachmentId = (string) ($_GET['bestand'] ?? '');

try {
    $doc = kothar_load_compositions();
} catch (Throwable $error) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo $error->getMessage();
    exit;
}

$row = kothar_find_composition_by_id($doc, $id);
$found = null;
if ($row !== null) {
    foreach ($row['attachments'] ?? [] as $attachment) {
        if (is_array($attachment) && (string) ($attachment['id'] ?? '') === $attachmentId) {
            $found = $attachment;
            break;
        }
    }
}
if ($row === null || $found === null) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Bijlage niet gevonden';
    exit;
}

$stored = (string) ($found['stored'] ?? '');
if (preg_match('/^[a-f0-9]{16}\.[a-z0-9]{1,5}$/', $stored) !== 1) {
    http_response_code(404);
    echo 'Bijlage niet gevonden';
    exit;
}

$path = kothar_attachments_dir((string) $row['id']) . '/' . $stored;
$realDir = realpath(kothar_attachments_dir((string) $row['id']));
$real = realpath($path);
if ($realDir === false || $real === false || !is_file($real) || !str_starts_with($real, $realDir . DIRECTORY_SEPARATOR)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Bijlage niet gevonden';
    exit;
}

$ext = strtolower(pathinfo($real, PATHINFO_EXTENSION));
$types = [
    'pdf' => 'application/pdf',
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
    'txt' => 'text/plain; charset=utf-8',
    'csv' => 'text/csv; charset=utf-8',
    'zip' => 'application/zip',
];
header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
header('X-Content-Type-Options: nosniff');
$downloadName = str_replace(['"', "\r", "\n"], '', basename((string) ($found['name'] ?? $stored)));
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Length: ' . (string) filesize($real));
readfile($real);
exit;
