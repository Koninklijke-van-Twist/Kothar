<?php

declare(strict_types=1);


require_once __DIR__ . '/lib/UserAccess.php';
require_once __DIR__ . '/lib/AllowedUsersStore.php';

function kvt_managed_allowed_emails(): array
{
    try {
        return (new AllowedUsersStore(__DIR__ . '/data/access.sqlite'))->listAllowedEmails();
    } catch (Throwable) {
        return [];
    }
}

function kvt_session_email(): string
{
    return strtolower(trim((string) ($_SESSION['user']['email'] ?? '')));
}

/**
 * true = toegang ok; false = weigeren. null = gebruik de oorspronkelijke auth.php-check.
 */
function kvt_access_decision(?array $authAllowedUsers = null): ?bool
{
    $email = kvt_session_email();
    if (kvt_email_is_admin($email)) {
        return true;
    }
    $managed = kvt_managed_allowed_emails();
    if ($managed !== []) {
        return in_array($email, $managed, true);
    }

    return null;
}
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

    $kvtDecision = kvt_access_decision(isset($allowedUsers) && is_array($allowedUsers) ? $allowedUsers : null);
    if ($kvtDecision === false) {
        require __DIR__ . '/../login/403.php';
        exit;
    }
    if ($kvtDecision === null) {
        $currentEmail = strtolower(trim((string) ($_SESSION['user']['email'] ?? '')));

        // Geen of lege $allowedUsers = alle geldige Entra-logins hebben toegang.
        $allowList = (isset($allowedUsers) && is_array($allowedUsers)) ? $allowedUsers : [];
        $restrictToAllowList = count($allowList) > 0;

        $isAllowedUser = false;
        if (!$restrictToAllowList) {
            $isAllowedUser = ($currentEmail !== '');
        } else {
            foreach ($allowList as $emailKey => $value) {
                $allowedEmail = is_int($emailKey) ? (string) $value : (string) $emailKey;
                if (strtolower(trim($allowedEmail)) === $currentEmail && $currentEmail !== '') {
                    $isAllowedUser = true;
                    break;
                }
            }
        }

        if (!$isAllowedUser) {
            require __DIR__ . '/../login/403.php';
            exit;
        }
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
