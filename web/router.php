<?php

declare(strict_types=1);

/**
 * Router voor de ingebouwde PHP-server: php -S localhost:8080 -t web web/router.php
 * Apache gebruikt web/data/.htaccess; de ingebouwde server leest die niet.
 */

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (!is_string($path)) {
    $path = '/';
}

if (preg_match('#^/(data|lib)(/|$)#', $path) === 1 || $path === '/auth.php' || $path === '/auth_TEMPLATE.php') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Geen toegang';

    return true;
}

return false;
