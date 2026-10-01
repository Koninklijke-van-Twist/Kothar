<?php

declare(strict_types=1);

require_once __DIR__ . '/user.php';

function kothar_page_open(string $title): void
{
    $full = $title === 'Kothar' ? 'Kothar' : $title . ' · Kothar';
    echo '<!DOCTYPE html><html lang="nl"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . kothar_h($full) . '</title>';
    echo '<link rel="icon" href="favicon.svg" type="image/svg+xml">';
    echo '<link rel="manifest" href="site.webmanifest">';
    echo '<link rel="stylesheet" href="assets/app.css">';
    echo '</head><body>';
    echo '<a class="skip" href="#inhoud">Naar de inhoud</a>';
    echo '<header class="site-header"><div class="wrap header-row">';
    echo '<a class="brand" href="index.php">Kothar</a>';
    echo '<nav class="nav" aria-label="Hoofdmenu">';
    echo '<a href="index.php">Start</a>';
    echo '<a href="samenstellingen.php">Samenstellingen</a>';
    $count = count(kothar_cart()['items']);
    echo '<a href="winkelwagen.php">Winkelwagen';
    if ($count > 0) {
        echo ' <span class="badge">' . $count . '</span>';
    }
    echo '</a>';
    echo '<a href="scannen.php">Scannen</a>';
    echo '<a href="info.php">Informatie</a>';
    if (kothar_is_admin()) {
        echo '<a href="beheer.php">Beheer</a>';
    }
    echo '</nav>';
    $user = kothar_current_user();
    echo '<p class="who">' . kothar_h($user['name']) . '</p>';
    echo '</div></header>';
    echo '<main id="inhoud" class="wrap">';
    foreach (kothar_take_flashes() as $flash) {
        if (!is_array($flash)) {
            continue;
        }
        $type = (string) ($flash['type'] ?? 'ok');
        if ($type !== 'ok' && $type !== 'warn') {
            $type = 'ok';
        }
        echo '<p class="flash flash-' . kothar_h($type) . '" role="status">' . kothar_h((string) ($flash['message'] ?? '')) . '</p>';
    }
}

function kothar_page_close(): void
{
    echo '</main>';
    echo '<footer class="site-footer"><div class="wrap">Kothar · samenstellingen voor sleutels.kvt.nl</div></footer>';
    echo '<script src="assets/vendor/JsBarcode.all.min.js"></script>';
    echo '<script src="assets/app.js"></script>';
    echo '</body></html>';
}

function kothar_render_setup_page(): void
{
    echo '<!DOCTYPE html><html lang="nl"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>Kothar instellen</title>';
    echo '<link rel="stylesheet" href="assets/app.css">';
    echo '</head><body><main class="wrap narrow">';
    echo '<h1>Kothar instellen</h1>';
    echo '<p>Kopieer <code>web/auth_TEMPLATE.php</code> naar <code>web/auth.php</code> en zet de beheerders in <code>$admins</code>.</p>';
    echo '<p><code>auth.php</code> hoort niet in git.</p>';
    echo '</main></body></html>';
}

function kothar_require_admin(): void
{
    if (kothar_is_admin()) {
        return;
    }
    http_response_code(403);
    kothar_page_open('Geen toegang');
    echo '<h1>Geen toegang</h1>';
    echo '<p>Alleen e-mailadressen in <code>$admins</code> (of <code>$kotharAdmins</code>) in auth.php mogen categorieën beheren.</p>';
    kothar_page_close();
    exit;
}
