<?php

declare(strict_types=1);

/**
 * analytics.php schrijft web/analytics/.htaccess als die ontbreekt, zonder
 * het endpoint zelf te sluiten. De ingebouwde server weigert *.sqlite daar.
 */

$root = dirname(__DIR__);
$htaccess = $root . '/web/analytics/.htaccess';
$sqlite = $root . '/web/analytics/analytics.sqlite';
$workflow = $root . '/.github/workflows/deploy-ftp.yml';
$failures = 0;

if (($argv[1] ?? '') === 'route') {
    session_start();
    $_SESSION['lang'] = 'nl';
    $_SERVER['REQUEST_URI'] = (string) ($argv[2] ?? '/');
    ob_start();
    $handled = require $root . '/web/router.php';
    $body = (string) ob_get_clean();
    echo json_encode([
        'handled' => $handled === true,
        'status' => http_response_code(),
        'body' => $body,
    ], JSON_UNESCAPED_UNICODE);
    exit(0);
}

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

function run_analytics(): string
{
    $script = dirname(__DIR__) . '/web/analytics/analytics.php';
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script);
    $output = shell_exec($command . ' 2>&1');

    return is_string($output) ? $output : '';
}

$existed = is_file($htaccess);
$previous = $existed ? (string) file_get_contents($htaccess) : null;

try {
    if ($existed) {
        unlink($htaccess);
    }

    $body = run_analytics();
    $rules = is_file($htaccess) ? (string) file_get_contents($htaccess) : '';
    check($rules !== '', 'ontbrekende .htaccess wordt aangemaakt');
    check(str_contains($body, '"ok":false'), 'endpoint antwoordt nog JSON');
    check(str_contains($rules, '<FilesMatch "\.sqlite">'), 'FilesMatch op .sqlite');
    check(str_contains($rules, 'Require all denied'), 'Require all denied');
    check(str_contains($rules, 'Deny from all'), 'Deny from all voor oudere Apache');
    check(str_contains($rules, 'analytics.php blijft het endpoint'), 'endpoint blijft uitgelegd');
    check(!is_file($sqlite), 'ongeautoriseerd verzoek maakt geen sqlite');

    $again = (string) file_get_contents($htaccess);
    run_analytics();
    check(is_file($htaccess) && file_get_contents($htaccess) === $again, 'bestaande sqlite-regels blijven staan');

    $custom = "Options -Indexes\n# bestaand\n";
    file_put_contents($htaccess, $custom);
    run_analytics();
    $appended = (string) file_get_contents($htaccess);
    check(str_starts_with($appended, rtrim($custom)), 'bestaande regels zonder sqlite blijven staan');
    check(str_contains($appended, '<FilesMatch "\.sqlite">'), 'sqlite-weigering wordt toegevoegd');

    $protected = $appended;
    run_analytics();
    check(file_get_contents($htaccess) === $protected, 'na toevoegen niet nog een keer schrijven');
} finally {
    if (is_file($htaccess)) {
        unlink($htaccess);
    }
    if ($previous !== null) {
        file_put_contents($htaccess, $previous);
    }
}

$workflowText = (string) file_get_contents($workflow);
check(str_contains($workflowText, "--exclude-glob analytics/*.sqlite \\"), 'exclude analytics/*.sqlite');
check(str_contains($workflowText, "--exclude-glob analytics/*.sqlite-* \\"), 'exclude analytics/*.sqlite-*');
$mirror = '';
if (preg_match('/mirror -R --delete \\\\.*?\\.\\/web \\./s', $workflowText, $match) === 1) {
    $mirror = $match[0];
}
check(substr_count($mirror, 'analytics/*.sqlite') === 2, 'alleen de twee sqlite-excludes in mirror');

function route(string $uri): array
{
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' route ' . escapeshellarg($uri);
    $output = shell_exec($command . ' 2>&1');
    $decoded = json_decode(is_string($output) ? $output : '', true);

    return is_array($decoded) ? $decoded : ['handled' => false, 'status' => 0, 'body' => (string) $output];
}

$blocked = route('/analytics/analytics.sqlite');
check($blocked['handled'] === true && $blocked['status'] === 403, 'router weigert analytics.sqlite');

$wal = route('/analytics/analytics.sqlite-wal');
check($wal['handled'] === true && $wal['status'] === 403, 'router weigert analytics.sqlite-wal');

$encoded = route('/analytics/analytics%2esqlite');
check($encoded['handled'] === true && $encoded['status'] === 403, 'router weigert gecodeerde .sqlite');

$endpoint = route('/analytics/analytics.php');
check($endpoint['handled'] === false, 'router laat analytics.php door');

$rulesPage = route('/analytics/rules.md');
check($rulesPage['handled'] === false, 'router laat rules.md door');

$data = route('/data/categories.seed.json');
check($data['handled'] === true && $data['status'] === 403, 'router weigert data/ nog steeds');

if ($failures > 0) {
    echo "$failures failed\n";
    exit(1);
}

echo "all passed\n";
