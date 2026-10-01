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

function kothar_csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return '';
    }
    $token = $_SESSION['kothar_csrf'] ?? '';
    if (!is_string($token) || $token === '') {
        $token = bin2hex(random_bytes(16));
        $_SESSION['kothar_csrf'] = $token;
    }

    return $token;
}

function kothar_csrf_check(): void
{
    $sent = (string) ($_POST['csrf'] ?? '');
    $have = (string) ($_SESSION['kothar_csrf'] ?? '');
    if ($have === '' || !hash_equals($have, $sent)) {
        http_response_code(400);
        echo 'Ongeldige sessie. Laad de pagina opnieuw.';
        exit;
    }
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
