<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../web/lib/store.php';
require_once __DIR__ . '/../web/lib/user.php';

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

$items = [
    ['id' => 'a', 'name' => 'A'],
    ['id' => 'b', 'name' => 'B'],
    ['id' => 'c', 'name' => 'C'],
];
$ordered = kothar_order_by_id($items, ['c', 'a', 'missing']);
check(array_column($ordered, 'id') === ['c', 'a', 'b'], 'order keeps omitted items at the end');
check(array_column(kothar_order_by_id($items, ['b', 'b', 'a']), 'id') === ['b', 'a', 'c'], 'duplicate ids are ignored');

$column = [
    'id' => 'col',
    'name' => 'Fluid',
    'hint' => 'oud',
    'options' => [
        ['id' => 'o1', 'label' => 'Fuel', 'code' => 'F', 'description' => 'Fuel'],
        ['id' => 'o2', 'label' => 'Ureal', 'code' => 'U', 'description' => 'Ureal'],
        ['id' => 'o3', 'label' => 'Glicol%', 'code' => 'G', 'description' => 'Glicol%'],
    ],
];
$partial = kothar_apply_column_edit($column, 'Fluid', 'F/U/G', [
    ['id' => 'o1', 'label' => 'Fuel edited', 'code' => 'F', 'description' => 'Brandstof'],
]);
check($partial['hint'] === 'F/U/G', 'column hint is saved with the option');
check($partial['options'][0]['label'] === 'Fuel edited', 'posted option is updated');
check($partial['options'][0]['description'] === 'Brandstof', 'posted description is updated');
check(array_column($partial['options'], 'id') === ['o1', 'o2', 'o3'], 'partial save keeps the other options');

$reordered = kothar_apply_column_edit($column, 'Fluid', 'F/U/G', [
    ['id' => 'o3', 'label' => 'Glicol%', 'code' => 'G', 'description' => 'Glicol%'],
    ['id' => 'o1', 'label' => 'Fuel', 'code' => 'F', 'description' => 'Fuel'],
    ['id' => 'o2', 'label' => '', 'code' => '', 'description' => ''],
]);
check(array_column($reordered['options'], 'id') === ['o3', 'o1', 'o2'], 'empty label does not wipe an existing option');
check($reordered['options'][2]['label'] === 'Ureal', 'kept option keeps its label');

$added = kothar_apply_column_edit($column, 'Fluid', 'hint', [
    ['id' => 'o2', 'label' => 'Ureal', 'code' => 'U', 'description' => 'Ureal'],
    ['id' => '', 'label' => 'Nieuw', 'code' => 'N', 'description' => ''],
    ['id' => '', 'label' => '', 'code' => 'X', 'description' => 'leeg'],
]);
check(count($added['options']) === 4, 'blank new row is not stored');
check($added['options'][1]['label'] === 'Nieuw', 'new option is appended in posted order');
check($added['options'][1]['description'] === 'Nieuw', 'empty description falls back to the label');
check(strpos($added['options'][1]['id'], 'opt-') === 0, 'new option gets an id');
check(array_column($added['options'], 'id')[0] === 'o2', 'posted order comes first');
check(in_array('o1', array_column($added['options'], 'id'), true), 'omitted option is kept');

$removed = kothar_remove_column_option($added, $added['options'][1]['id']);
check(count($removed['options']) === 3, 'remove drops one option');
check(array_column($removed['options'], 'label')[0] === 'Ureal', 'remaining options stay');

$_SESSION = [];
$_POST = [];
$_COOKIE = [];
$token = '0123456789abcdef0123456789abcdef';
$signature = hash_hmac('sha256', $token, session_id());
$_COOKIE['kothar_csrf'] = $token . '.' . $signature;
$_POST['csrf'] = $token;
check(kothar_csrf_matches(), 'cookie restores a missing session token');
check(($_SESSION['kothar_csrf'] ?? '') === $token, 'restored token is stored in the session');

$again = kothar_csrf_token();
check($again === $token, 'token stays stable across partial saves');

$_POST['csrf'] = '';
check(kothar_csrf_matches() === false, 'missing token is rejected');

$_SESSION['kothar_csrf'] = $token;
$_POST['csrf'] = $token;
check(kothar_csrf_matches(), 'session token matches a normal save');

$other = 'ffffffffffffffffffffffffffffffff';
$_POST['csrf'] = $other;
$_COOKIE['kothar_csrf'] = $other . '.' . hash_hmac('sha256', $other, session_id());
check(kothar_csrf_matches() === false, 'a different cookie does not replace the session token');
check(($_SESSION['kothar_csrf'] ?? '') === $token, 'session token stays after a rejected cookie');

$_SESSION['kothar_csrf'] = '';
$_COOKIE['kothar_csrf'] = $token . '.not-a-valid-signature';
$_POST['csrf'] = $token;
check(kothar_csrf_matches() === false, 'cookie without a valid seal is ignored');

if ($failures > 0) {
    echo "$failures failed\n";
    exit(1);
}

echo "all passed\n";
