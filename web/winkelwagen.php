<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';

try {
    $savedDoc = kothar_load_compositions();
} catch (Throwable $error) {
    http_response_code(500);
    kothar_page_open(LOC('kothar.nav.cart'));
    echo '<h1>' . kothar_h(LOC('kothar.error.unavailable')) . '</h1><p>' . kothar_h($error->getMessage()) . '</p>';
    kothar_page_close();
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    kothar_csrf_check();
    $cart = kothar_cart();
    $action = (string) ($_POST['actie'] ?? '');
    $index = (int) ($_POST['index'] ?? -1);
    if ($action === 'verwijder' && isset($cart['items'][$index])) {
        array_splice($cart['items'], $index, 1);
        kothar_save_cart($cart);
        kothar_flash(LOC('kothar.cart.removed'));
    } elseif ($action === 'aantal' && isset($cart['items'][$index]) && is_array($cart['items'][$index])) {
        $qty = kothar_parse_quantity((string) ($_POST['aantal'] ?? ''));
        if ($qty === null) {
            kothar_flash(LOC('kothar.build.qty_range'), 'warn');
        } else {
            $cart['items'][$index]['quantity'] = $qty;
            kothar_save_cart($cart);
            kothar_flash(LOC('kothar.cart.qty_updated'));
        }
    } elseif ($action === 'registreer' && isset($cart['items'][$index]) && is_array($cart['items'][$index])) {
        $item = $cart['items'][$index];
        try {
            $result = kothar_register_composition([
                'number' => (string) ($item['number'] ?? ''),
                'categoryId' => (string) ($item['categoryId'] ?? ''),
                'categoryName' => (string) ($item['categoryName'] ?? ''),
                'price' => 0,
                'selections' => is_array($item['selections'] ?? null) ? $item['selections'] : [],
                'registrant' => kothar_current_user(),
            ]);
            kothar_flash($result['created'] ? LOC('kothar.build.registered') : LOC('kothar.build.already_exists'));
        } catch (InvalidArgumentException $error) {
            kothar_flash($error->getMessage(), 'warn');
        }
    } elseif ($action === 'alles') {
        $created = 0;
        $known = 0;
        foreach ($cart['items'] as $item) {
            if (!is_array($item)) {
                continue;
            }
            try {
                $result = kothar_register_composition([
                    'number' => (string) ($item['number'] ?? ''),
                    'categoryId' => (string) ($item['categoryId'] ?? ''),
                    'categoryName' => (string) ($item['categoryName'] ?? ''),
                    'price' => 0,
                    'selections' => is_array($item['selections'] ?? null) ? $item['selections'] : [],
                    'registrant' => kothar_current_user(),
                ]);
            } catch (InvalidArgumentException $error) {
                kothar_flash($error->getMessage(), 'warn');
                continue;
            }
            if ($result['created']) {
                $created++;
            } else {
                $known++;
            }
        }
        kothar_flash(LOC('kothar.cart.summary', $created, $known));
    }
    kothar_redirect('winkelwagen.php');
}

$cart = kothar_cart();
kothar_page_open(LOC('kothar.nav.cart'));
echo '<h1>' . kothar_h(LOC('kothar.nav.cart')) . '</h1>';
if ($cart['items'] === []) {
    echo '<p>' . kothar_h(LOC('kothar.cart.empty')) . ' <a href="index.php">' . kothar_h(LOC('kothar.cart.empty_link')) . '</a>.</p>';
    kothar_page_close();
    exit;
}

echo '<table class="grid"><thead><tr><th>' . kothar_h(LOC('kothar.cart.col.number')) . '</th><th>' . kothar_h(LOC('kothar.cart.col.category')) . '</th><th>' . kothar_h(LOC('kothar.cart.col.qty')) . '</th><th>' . kothar_h(LOC('kothar.cart.col.saved')) . '</th><th></th></tr></thead><tbody>';
foreach ($cart['items'] as $index => $item) {
    if (!is_array($item)) {
        continue;
    }
    $number = (string) ($item['number'] ?? '');
    $existing = kothar_find_by_number($savedDoc['compositions'], $number);
    echo '<tr><td class="number">' . kothar_h($number) . '</td>';
    echo '<td>' . kothar_h((string) ($item['categoryName'] ?? '')) . '</td><td>';
    echo '<form method="post" class="inline-form">';
    echo kothar_csrf_field();
    echo '<input type="hidden" name="index" value="' . $index . '">';
    echo '<input name="aantal" type="number" min="1" max="9999" value="' . kothar_h((string) ($item['quantity'] ?? 1)) . '" required>';
    echo '<button type="submit" name="actie" value="aantal">' . kothar_h(LOC('kothar.cart.update')) . '</button>';
    echo '</form></td><td>';
    if ($existing !== null) {
        $href = 'samenstelling.php?id=' . rawurlencode((string) $existing['id']);
        echo '<a href="' . kothar_h($href) . '">' . kothar_h(kothar_format_price((float) ($existing['price'] ?? 0))) . '</a>';
    } else {
        echo '<form method="post" class="inline-form">';
        echo kothar_csrf_field();
        echo '<input type="hidden" name="index" value="' . $index . '">';
        echo '<button type="submit" name="actie" value="registreer">' . kothar_h(LOC('kothar.cart.register')) . '</button>';
        echo '</form>';
    }
    echo '</td><td><form method="post">';
    echo kothar_csrf_field();
    echo '<input type="hidden" name="index" value="' . $index . '">';
    echo '<button type="submit" name="actie" value="verwijder">' . kothar_h(LOC('kothar.cart.delete')) . '</button>';
    echo '</form></td></tr>';
}
echo '</tbody></table>';
echo '<form method="post"><p>';
echo kothar_csrf_field();
echo '<button type="submit" name="actie" value="alles">' . kothar_h(LOC('kothar.cart.register_all')) . '</button>';
echo '</p></form>';
echo '<p class="hint">' . kothar_h(LOC('kothar.cart.hint')) . '</p>';
kothar_page_close();
