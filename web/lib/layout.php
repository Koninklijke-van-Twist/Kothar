<?php

declare(strict_types=1);

require_once __DIR__ . '/user.php';

/**
 * @return array<int, string>
 */
function kothar_js_i18n_keys(): array
{
    return [
        'kothar.barcode.non_ascii',
        'kothar.barcode.failed',
        'kothar.scan.no_detector',
        'kothar.scan.aim',
        'kothar.scan.camera_unavailable',
        'kothar.build.quantity_prompt',
        'kothar.build.choose_vector',
        'kothar.build.fill_measures',
        'kothar.build.choose',
        'kothar.build.progress',
        'kothar.build.update_failed',
        'kothar.confirm.title',
        'kothar.confirm.cancel',
        'kothar.confirm.delete',
        'kothar.confirm.fallback',
        'kothar.admin.save_failed',
        'kothar.admin.reorder_done',
        'kothar.admin.reorder',
        'kothar.error.invalid_session',
        'kothar.column.fallback',
        'kothar.admin.unsaved_confirm',
        'kothar.admin.unsaved_title',
        'kothar.admin.unsaved_body',
        'kothar.admin.discard',
        'kothar.admin.save',
    ];
}

function kothar_column_count(int $count): string
{
    if ($count === 1) {
        return LOC('kothar.index.columns_one');
    }

    return LOC('kothar.index.columns_many', $count);
}

/**
 * URL onder web/ met ?v=filemtime, zodat browsers een nieuwe app.js ophalen.
 * Ontbreekt het bestand, dan valt de versie terug op time().
 */
function kothar_asset(string $relativePath): string
{
    $relativePath = ltrim($relativePath, '/');
    $absolute = __DIR__ . '/../' . $relativePath;
    $mtime = is_file($absolute) ? filemtime($absolute) : false;

    return $relativePath . '?v=' . ($mtime === false ? time() : $mtime);
}

function kothar_page_open(string $title): void
{
    $csrf = kothar_csrf_token();
    $app = LOC('kothar.app.title');
    $full = $title === $app ? $app : $title . ' · ' . $app;
    echo '<!DOCTYPE html><html lang="' . kothar_h(getHtmlLang()) . '"><head><meta charset="utf-8">';
    echo '<meta name="csrf-token" content="' . kothar_h($csrf) . '">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . kothar_h($full) . '</title>';
    echo '<link rel="icon" href="favicon.svg" type="image/svg+xml">';
    echo '<link rel="manifest" href="site.webmanifest">';
    echo '<link rel="stylesheet" href="' . kothar_h(kothar_asset('assets/app.css')) . '">';
    renderLanguageSwitcherStyles();
    echo '</head><body>';
    echo '<a class="skip" href="#inhoud">' . kothar_h(LOC('kothar.skip')) . '</a>';
    echo '<header class="site-header"><div class="wrap header-row">';
    echo '<a class="brand" href="index.php">Kothar</a>';
    echo '<nav class="nav" aria-label="' . kothar_h(LOC('kothar.nav.main')) . '">';
    echo '<a href="index.php">' . kothar_h(LOC('kothar.nav.start')) . '</a>';
    echo '<a href="samenstellingen.php">' . kothar_h(LOC('kothar.nav.compositions')) . '</a>';
    $count = count(kothar_cart()['items']);
    echo '<a href="winkelwagen.php">' . kothar_h(LOC('kothar.nav.cart'));
    if ($count > 0) {
        echo ' <span class="badge">' . $count . '</span>';
    }
    echo '</a>';
    echo '<a href="scannen.php">' . kothar_h(LOC('kothar.nav.scan')) . '</a>';
    echo '<a href="info.php">' . kothar_h(LOC('kothar.nav.info')) . '</a>';
    if (kothar_is_admin()) {
        echo '<a href="beheer.php">' . kothar_h(LOC('kothar.nav.admin')) . '</a>';
    }
    echo '</nav>';
    $user = kothar_current_user();
    echo '<div class="header-end">';
    echo '<p class="who">' . kothar_h($user['name']) . '</p>';
    renderLanguageSwitcher();
    echo '</div>';
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
    echo '<footer class="site-footer"><div class="wrap">' . kothar_h(LOC('kothar.footer')) . '</div></footer>';
    echo '<script>window.KOTHAR_I18N=' . localizationJsTranslations(kothar_js_i18n_keys()) . ';</script>';
    echo '<script src="' . kothar_h(kothar_asset('assets/vendor/JsBarcode.all.min.js')) . '"></script>';
    echo '<script src="' . kothar_h(kothar_asset('assets/app.js')) . '"></script>';
    renderLanguageSwitcherScript();
    echo '</body></html>';
}

function kothar_render_setup_page(): void
{
    echo '<!DOCTYPE html><html lang="' . kothar_h(getHtmlLang()) . '"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . kothar_h(LOC('kothar.setup.title')) . '</title>';
    echo '<link rel="stylesheet" href="' . kothar_h(kothar_asset('assets/app.css')) . '">';
    echo '</head><body><main class="wrap narrow">';
    echo '<h1>' . kothar_h(LOC('kothar.setup.title')) . '</h1>';
    echo '<p>' . LOC(
        'kothar.setup.body',
        '<code>web/auth_TEMPLATE.php</code>',
        '<code>web/auth.php</code>',
        '<code>$admins</code>'
    ) . '</p>';
    echo '<p>' . LOC('kothar.setup.git', '<code>auth.php</code>') . '</p>';
    echo '</main></body></html>';
}

function kothar_require_admin(): void
{
    if (kothar_is_admin()) {
        return;
    }
    http_response_code(403);
    kothar_page_open(LOC('kothar.error.forbidden'));
    echo '<h1>' . kothar_h(LOC('kothar.error.forbidden')) . '</h1>';
    echo '<p>' . LOC(
        'kothar.error.forbidden_admin',
        '<code>$admins</code>',
        '<code>$kotharAdmins</code>'
    ) . '</p>';
    kothar_page_close();
    exit;
}
