<?php

declare(strict_types=1);

/**
 * Zelfde poort als Ktesios/Forculus: auth.php op paginaniveau, en buiten
 * localhost de gedeelde Login-app (…/login/lib.php) voor de Entra-sessie.
 */

function kothar_is_trusted_requester(): bool
{
    $remote = $_SERVER['REMOTE_ADDR'] ?? '';
    $server = $_SERVER['SERVER_ADDR'] ?? '';
    if ($remote === $server && $remote !== '') {
        return true;
    }

    return $remote === '127.0.0.1' || $remote === '::1';
}

if (!kothar_is_trusted_requester()) {
    require __DIR__ . '/../login/lib.php';

    $currentEmail = strtolower(trim((string) ($_SESSION['user']['email'] ?? '')));
    $allowList = (isset($allowedUsers) && is_array($allowedUsers)) ? $allowedUsers : [];
    $restrict = count($allowList) > 0;
    $isAllowed = false;
    if (!$restrict) {
        $isAllowed = $currentEmail !== '';
    } else {
        foreach ($allowList as $emailKey => $value) {
            $allowedEmail = is_int($emailKey) ? (string) $value : (string) $emailKey;
            if (strtolower(trim($allowedEmail)) === $currentEmail && $currentEmail !== '') {
                $isAllowed = true;
                break;
            }
        }
    }
    if (!$isAllowed) {
        require __DIR__ . '/../login/403.php';
        exit;
    }
} else {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
    $existing = $_SESSION['user'] ?? null;
    $email = is_array($existing) ? trim((string) ($existing['email'] ?? '')) : '';
    if ($email === '') {
        $_SESSION['user'] = [
            'email' => 'lokaal@kvt.nl',
            'name' => 'Lokaal',
        ];
    }
}
