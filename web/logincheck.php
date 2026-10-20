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

    $analyticsEmail = strtolower(trim((string) ($_SESSION['user']['email'] ?? '')));
    $analyticsApiKey = trim((string) ($_SESSION['user']['api_key'] ?? ''));
    $analyticsOid = strtolower(trim((string) ($_SESSION['user']['oid'] ?? '')));
    if ($analyticsEmail !== '' && $analyticsApiKey !== '' && $analyticsOid !== '') {
        $analyticsScheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $analyticsHost = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $analyticsBase = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'))), '/');
        $analyticsUrl = $analyticsScheme . '://' . $analyticsHost . $analyticsBase . '/analytics/analytics.php?' . http_build_query([
            'user_email' => $analyticsEmail,
            'api_key' => $analyticsApiKey,
            'oid' => $analyticsOid,
        ], '', '&', PHP_QUERY_RFC3986);

        if (function_exists('curl_init')) {
            $analyticsCurl = curl_init($analyticsUrl);
            curl_setopt_array($analyticsCurl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 1,
                CURLOPT_TIMEOUT => 1,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_HTTPHEADER => ['X-API-Key: ' . $analyticsApiKey],
            ]);
            curl_exec($analyticsCurl);
            curl_close($analyticsCurl);
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
