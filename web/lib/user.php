<?php

declare(strict_types=1);

function kothar_h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * @return array{name: string, email: string}
 */
function kothar_current_user(): array
{
    $user = $_SESSION['user'] ?? [];
    if (!is_array($user)) {
        $user = [];
    }
    $email = strtolower(trim((string) ($user['email'] ?? '')));
    $name = trim((string) ($user['name'] ?? ''));
    if ($name === '') {
        $name = $email !== '' ? $email : 'Onbekend';
    }

    return ['name' => $name, 'email' => $email];
}

/**
 * @return array<int, string>
 */
function kothar_admin_emails(): array
{
    $lists = [];
    if (isset($GLOBALS['admins']) && is_array($GLOBALS['admins'])) {
        $lists[] = $GLOBALS['admins'];
    }
    if (isset($GLOBALS['kotharAdmins']) && is_array($GLOBALS['kotharAdmins'])) {
        $lists[] = $GLOBALS['kotharAdmins'];
    }
    $emails = [];
    foreach ($lists as $list) {
        foreach ($list as $key => $value) {
            if (is_int($key)) {
                if (!is_string($value)) {
                    continue;
                }
                $email = $value;
            } else {
                $email = (string) $key;
            }
            $email = strtolower(trim($email));
            if ($email !== '') {
                $emails[] = $email;
            }
        }
    }

    return array_values(array_unique($emails));
}

function kothar_is_admin(): bool
{
    $email = kothar_current_user()['email'];
    if ($email === '') {
        return false;
    }

    return in_array($email, kothar_admin_emails(), true);
}

function kothar_ensure_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Token in de sessie, met een kopie in een cookie die aan het sessie-id hangt.
 * De loginlaag kan extra sessiesleutels wissen. Een gedeeltelijke opslag
 * (één kolom of één optie) stuurt het token uit de pagina mee; ontbreekt het
 * in de sessie, dan herstelt de cookie het. Een sessie die al een ander token
 * heeft, wordt niet overschreven.
 */
function kothar_csrf_token(): string
{
    kothar_ensure_session();
    $token = $_SESSION['kothar_csrf'] ?? '';
    if (!is_string($token) || !preg_match('/^[a-f0-9]{32}$/', $token)) {
        $token = kothar_csrf_from_cookie();
    }
    if ($token === '') {
        $token = bin2hex(random_bytes(16));
    }
    $_SESSION['kothar_csrf'] = $token;
    kothar_csrf_set_cookie($token);

    return $token;
}

function kothar_csrf_from_cookie(): string
{
    $raw = (string) ($_COOKIE['kothar_csrf'] ?? '');
    $parts = explode('.', $raw, 2);
    if (count($parts) !== 2) {
        return '';
    }
    $token = $parts[0];
    $signature = $parts[1];
    if (!preg_match('/^[a-f0-9]{32}$/', $token) || session_id() === '') {
        return '';
    }
    $expected = hash_hmac('sha256', $token, session_id());
    if (!hash_equals($expected, $signature)) {
        return '';
    }

    return $token;
}

function kothar_csrf_set_cookie(string $token): void
{
    if ($token === '' || session_id() === '') {
        return;
    }
    $value = $token . '.' . hash_hmac('sha256', $token, session_id());
    $_COOKIE['kothar_csrf'] = $value;
    if (headers_sent() || PHP_SAPI === 'cli') {
        return;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443');
    setcookie('kothar_csrf', $value, [
        'expires' => 0,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function kothar_csrf_matches(): bool
{
    kothar_ensure_session();
    $sent = (string) ($_POST['csrf'] ?? '');
    if (!preg_match('/^[a-f0-9]{32}$/', $sent)) {
        return false;
    }
    $have = $_SESSION['kothar_csrf'] ?? '';
    if (!is_string($have) || !preg_match('/^[a-f0-9]{32}$/', $have)) {
        $have = kothar_csrf_from_cookie();
        if ($have !== '') {
            $_SESSION['kothar_csrf'] = $have;
        }
    }
    if (!is_string($have) || $have === '') {
        return false;
    }

    return hash_equals($have, $sent);
}

function kothar_csrf_check(): void
{
    if (kothar_csrf_matches()) {
        return;
    }
    http_response_code(400);
    echo 'Ongeldige sessie. Laad de pagina opnieuw.';
    exit;
}

function kothar_csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . kothar_h(kothar_csrf_token()) . '">';
}

function kothar_flash(string $message, string $type = 'ok'): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }
    if (!isset($_SESSION['kothar_flash']) || !is_array($_SESSION['kothar_flash'])) {
        $_SESSION['kothar_flash'] = [];
    }
    $_SESSION['kothar_flash'][] = ['type' => $type, 'message' => $message];
}

function kothar_take_flashes(): array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return [];
    }
    $flashes = $_SESSION['kothar_flash'] ?? [];
    unset($_SESSION['kothar_flash']);

    return is_array($flashes) ? $flashes : [];
}

function kothar_redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function kothar_cart(): array
{
    $cart = $_SESSION['kothar_cart'] ?? [];
    if (!is_array($cart)) {
        $cart = [];
    }
    $items = $cart['items'] ?? [];
    if (!is_array($items)) {
        $items = [];
    }

    return ['items' => array_values($items)];
}

function kothar_save_cart(array $cart): void
{
    $_SESSION['kothar_cart'] = ['items' => array_values($cart['items'] ?? [])];
}

/**
 * @param array<string, mixed> $item
 */
function kothar_cart_add(array $item): void
{
    $cart = kothar_cart();
    $number = kothar_canonicalize_number((string) ($item['number'] ?? ''));
    $qty = (int) ($item['quantity'] ?? 1);
    if ($qty < 1) {
        $qty = 1;
    }
    foreach ($cart['items'] as $index => $existing) {
        if (!is_array($existing)) {
            continue;
        }
        if (kothar_canonicalize_number((string) ($existing['number'] ?? '')) === $number) {
            $cart['items'][$index]['quantity'] = min(9999, (int) ($existing['quantity'] ?? 0) + $qty);
            kothar_save_cart($cart);

            return;
        }
    }
    $item['number'] = $number;
    $item['quantity'] = $qty;
    $cart['items'][] = $item;
    kothar_save_cart($cart);
}
