<?php

declare(strict_types=1);

$prefsDir = sys_get_temp_dir() . '/kothar-prefs-' . bin2hex(random_bytes(4));
$GLOBALS['kothar_user_prefs_dir'] = $prefsDir;
$GLOBALS['kothar_lang_redirect_capture'] = true;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$_SESSION = [
    'user' => [
        'email' => 'prefs@example.com',
        'name' => 'Prefs',
    ],
];
$_GET = [];
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/index.php';

require_once __DIR__ . '/../web/localization.php';

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

function placeholderCounts(string $value): array
{
    preg_match_all('/%(?:\d+\$)?[ds]/', $value, $matches);
    $counts = ['d' => 0, 's' => 0];
    foreach ($matches[0] as $token) {
        $type = substr($token, -1);
        $counts[$type]++;
    }

    return $counts;
}

$baseKeys = array_keys(TRANSLATIONS['nl']);
sort($baseKeys);
check($baseKeys !== [], 'Dutch dictionary is not empty');
foreach (['en', 'de', 'fr'] as $lang) {
    $keys = array_keys(TRANSLATIONS[$lang]);
    sort($keys);
    check($keys === $baseKeys, $lang . ' has the same keys as nl');
    foreach ($baseKeys as $key) {
        $nl = (string) TRANSLATIONS['nl'][$key];
        $other = (string) TRANSLATIONS[$lang][$key];
        check($other !== '', $lang . ' ' . $key . ' is not empty');
        check(placeholderCounts($nl) === placeholderCounts($other), $lang . ' ' . $key . ' placeholders match nl');
    }
}

check(getCurrentLanguage() === 'nl', 'default language is Dutch');
check(LOC('kothar.nav.start') === 'Start', 'Dutch nav start');
check(LOC('kothar.index.columns_many', 3) === '3 kolommen', 'Dutch sprintf');
$_SESSION['lang'] = 'en';
check(getHtmlLang() === 'en', 'html lang follows the session');
check(LOC('kothar.nav.cart') === 'Cart', 'English cart label');
check(LOC('kothar.index.columns_many', 3) === '3 columns', 'English sprintf');
check(LOC('kothar.app.title') === 'Kothar', 'app name stays Kothar');
$_SESSION['lang'] = 'de';
check(LOC('kothar.nav.admin') === 'Verwaltung', 'German admin label');
$_SESSION['lang'] = 'fr';
check(LOC('kothar.nav.info') === 'Informations', 'French info label');
$_SESSION['lang'] = 'nl';

$_SERVER['REQUEST_URI'] = '/index.php';
$_GET = [];
ob_start();
renderLanguageSwitcher();
$dutchSwitcher = (string) ob_get_clean();
check(str_contains($dutchSwitcher, '#21468B'), 'switcher renders the Dutch flag');
check(str_contains($dutchSwitcher, 'aria-label="Taal kiezen"'), 'switcher uses the Dutch menu label');
check(str_contains($dutchSwitcher, 'English'), 'menu lists English');
check(str_contains($dutchSwitcher, 'Deutsch'), 'menu lists German');
check(str_contains($dutchSwitcher, 'Français'), 'menu lists French');
check(!str_contains($dutchSwitcher, '>Nederlands<'), 'current language is left out of the menu');
check(str_contains($dutchSwitcher, 'lang=en'), 'menu link sets lang');

$_SESSION['lang'] = 'en';
$_GET = [];
$_SERVER['REQUEST_URI'] = '/scannen.php';
ob_start();
renderLanguageSwitcher();
$englishSwitcher = (string) ob_get_clean();
check(str_contains($englishSwitcher, '#012169'), 'switcher renders the UK flag');
check(str_contains($englishSwitcher, 'aria-label="Choose language"'), 'switcher uses the English menu label');
check(str_contains($englishSwitcher, '>Nederlands<'), 'Dutch is offered when English is active');
check(!str_contains($englishSwitcher, '>English<'), 'current English label is left out of the menu');

$_SESSION['lang'] = 'nl';
$_GET = ['lang' => 'fr', 'categorie' => 'abc'];
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/bouwen.php?categorie=abc&lang=fr';
$GLOBALS['kothar_lang_redirect_to'] = '';
kothar_bootstrap_language();
check(($GLOBALS['kothar_lang_redirect_to'] ?? '') === '/bouwen.php?categorie=abc', 'language redirect drops the lang parameter');
check($_SESSION['lang'] === 'fr', 'requested language is stored in the session');
$saved = loadUserPrefs('prefs@example.com');
check(($saved['lang'] ?? '') === 'fr', 'language is persisted for the email');
$path = getUserPrefsPath('prefs@example.com');
check(is_string($path) && str_starts_with($path, $prefsDir), 'prefs file stays in the temp directory');

saveUserPref('prefs@example.com', 'lang', 'de');
check((loadUserPrefs('prefs@example.com')['lang'] ?? '') === 'de', 'saveUserPref roundtrip overwrites lang');
$before = glob($prefsDir . '/*') ?: [];
saveUserPref('not-an-email', 'lang', 'en');
$after = glob($prefsDir . '/*') ?: [];
check(getUserPrefsPath('not-an-email') === null, 'invalid email has no prefs path');
check($before === $after, 'invalid email does not write a prefs file');

if (is_dir($prefsDir)) {
    foreach (glob($prefsDir . '/*') ?: [] as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    rmdir($prefsDir);
}

if ($failures > 0) {
    echo "$failures failed\n";
    exit(1);
}

echo "all passed\n";
