<?php

/**
 * Kopieer naar web/auth.php op de server (niet committen).
 *
 * $allowedUsers
 *   weglaten of []  → elke geldige Entra-login heeft toegang
 *   lijst met e-mails → alleen die accounts
 *
 * $admins
 *   E-mailadressen die categorieën, kolommen en opties mogen beheren.
 *   Weglaten of [] → niemand is beheerder (fail-closed).
 *   $kotharAdmins is een alias voor dezelfde lijst.
 *
 * Lokaal (php -S) is de gebruiker lokaal@kvt.nl. Zet dat adres in $admins
 * om beheer op de ontwikkelserver te proberen.
 */

// $allowedUsers = [
//     'user@domain.nl',
// ];

$admins = [
    // 'beheerder@kvt.nl',
    // 'lokaal@kvt.nl',
];
