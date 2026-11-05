<?php

declare(strict_types=1);

/**
 * Include dit bestand op topniveau van de pagina, niet vanuit een functie.
 * auth.php zet $allowedUsers en $admins. Die moeten globaal blijven.
 */

require_once __DIR__ . '/layout.php';

if (!is_file(__DIR__ . '/../auth.php')) {
    require_once __DIR__ . '/../localization.php';
    http_response_code(503);
    kothar_render_setup_page();
    exit;
}

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../logincheck.php';
require_once __DIR__ . '/../localization.php';
require_once __DIR__ . '/store.php';
