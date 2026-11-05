<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$_SESSION = [
    'lang' => 'de',
    'user' => [
        'email' => 'cache@example.com',
        'name' => 'Cache',
    ],
];

require_once __DIR__ . '/../web/localization.php';
require_once __DIR__ . '/../web/lib/layout.php';

$failures = 0;

function check(bool $condition, string $name): void
{
    global $failures;
    if ($condition) {
        echo "OK  $name\n";
        return;
    }
    $failures++;
    echo "FAIL $name\n";
}

function expectVersioned(string $relative): void
{
    $url = kothar_asset($relative);
    $mtime = filemtime(__DIR__ . '/../web/' . $relative);
    check($url === $relative . '?v=' . $mtime, $relative . ' gets ?v=filemtime');
}

expectVersioned('assets/app.js');
expectVersioned('assets/app.css');
expectVersioned('assets/vendor/JsBarcode.all.min.js');

$missing = kothar_asset('assets/missing-for-cache-bust.js');
check(preg_match('#^assets/missing-for-cache-bust\.js\?v=\d+$#', $missing) === 1, 'missing file still returns ?v=');
check(abs((int) substr($missing, (int) strrpos($missing, '=') + 1) - time()) < 5, 'missing file falls back to time()');

ob_start();
kothar_page_open('Scan');
$head = (string) ob_get_clean();
$css = kothar_asset('assets/app.css');
check(str_contains($head, 'href="' . $css . '"'), 'page head loads versioned app.css');
check(str_contains($head, 'window.KOTHAR_I18N') === false, 'i18n payload stays in the page close');

ob_start();
kothar_page_close();
$close = (string) ob_get_clean();
$js = kothar_asset('assets/app.js');
$barcode = kothar_asset('assets/vendor/JsBarcode.all.min.js');
check(str_contains($close, 'src="' . $js . '"'), 'page close loads versioned app.js');
check(str_contains($close, 'src="' . $barcode . '"'), 'page close loads versioned JsBarcode');
check(str_contains($close, 'window.KOTHAR_I18N='), 'KOTHAR_I18N script stays on the page');

if ($failures > 0) {
    echo "$failures failed\n";
    exit(1);
}

echo "all passed\n";
