<?php

declare(strict_types=1);

/**
 * Router voor de ingebouwde PHP-server: php -S localhost:8080 -t web web/router.php
 * Apache gebruikt .htaccess (analytics.php schrijft die voor sqlite).
 * De ingebouwde server leest die niet en weigert dezelfde paden hier.
 */

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (!is_string($path)) {
    $path = '/';
}

$decodedPath = rawurldecode($path);
if (
    preg_match('#^/(data|lib)(/|$)#', $path) === 1
    || $path === '/auth.php'
    || $path === '/auth_TEMPLATE.php'
    || preg_match('#^/analytics/[^/]*\.sqlite#i', $decodedPath) === 1
) {
    require_once __DIR__ . '/localization.php';
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo LOC('kothar.error.forbidden');

    return true;
}

return false;
