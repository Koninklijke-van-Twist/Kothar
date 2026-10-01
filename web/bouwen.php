<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';

try {
    $doc = kothar_load_categories();
    $savedDoc = kothar_load_compositions();
} catch (Throwable $error) {
    http_response_code(500);
    kothar_page_open('Samenstellen');
    echo '<h1>Niet beschikbaar</h1><p>' . kothar_h($error->getMessage()) . '</p>';
    kothar_page_close();
    exit;
}

$categoryId = (string) ($_REQUEST['categorie'] ?? '');
$category = kothar_find_category($doc, $categoryId);
if ($category === null) {
    http_response_code(404);
    kothar_page_open('Samenstellen');
    echo '<h1>Categorie niet gevonden</h1><p><a href="index.php">Terug naar start</a></p>';
    kothar_page_close();
    exit;
}

$choices = $_REQUEST['keuze'] ?? [];
$fills = $_REQUEST['invul'] ?? [];
if (!is_array($choices)) {
    $choices = [];
}
if (!is_array($fills)) {
    $fills = [];
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    kothar_csrf_check();
    $built = kothar_build_from_choices($category, $choices, $fills);
    $action = (string) ($_POST['actie'] ?? '');
    if (!$built['ok']) {
        kothar_flash($built['error'], 'warn');
    } elseif ($action === 'winkelwagen' || $action === 'registreer') {
        $qty = kothar_parse_quantity((string) ($_POST['aantal'] ?? '1'));
        if ($qty === null) {
            kothar_flash('Aantal moet tussen 1 en 9999 liggen.', 'warn');
        } else {
            $existing = kothar_find_by_number($savedDoc['compositions'], $built['number']);
            if ($action === 'registreer' && $existing !== null) {
                kothar_flash('Dit nummer bestond al.');
            }
            if ($action === 'registreer' && $existing === null) {
                $price = kothar_parse_price((string) ($_POST['prijs'] ?? '0'));
                if ($price === null) {
                    kothar_flash('Prijs is ongeldig.', 'warn');
                    $price = null;
                } else {
                    $result = kothar_register_composition([
                        'number' => $built['number'],
                        'categoryId' => (string) $category['id'],
                        'categoryName' => (string) $category['name'],
                        'price' => $price,
                        'selections' => $built['selections'],
                        'registrant' => kothar_current_user(),
                    ]);
                    $existing = $result['composition'];
                    kothar_flash($result['created'] ? 'Samenstelling geregistreerd.' : 'Dit nummer bestond al.');
                }
            }
            if ($qty !== null && ($action === 'winkelwagen' || $existing !== null || $action !== 'registreer')) {
                kothar_cart_add([
                    'number' => $built['number'],
                    'quantity' => $qty,
                    'categoryId' => (string) $category['id'],
                    'categoryName' => (string) $category['name'],
                    'selections' => $built['selections'],
                ]);
                kothar_flash('In de winkelwagen gezet.');
                kothar_redirect('winkelwagen.php');
            }
        }
    }
    $query = ['categorie' => $categoryId, 'keuze' => $choices, 'invul' => $fills];
    kothar_redirect('bouwen.php?' . http_build_query($query));
}

$started = isset($_GET['keuze']) || isset($_GET['invul']);
$built = kothar_build_from_choices($category, $choices, $fills);
$existing = $built['ok'] ? kothar_find_by_number($savedDoc['compositions'], $built['number']) : null;

kothar_page_open((string) $category['name']);
echo '<p class="crumb"><a href="index.php">Start</a> · Samenstellen</p>';
echo '<h1>' . kothar_h((string) $category['name']) . '</h1>';
echo '<p class="lead">' . kothar_h((string) ($category['description'] ?? '')) . '</p>';

echo '<form method="get" action="bouwen.php" class="builder">';
echo '<input type="hidden" name="categorie" value="' . kothar_h($categoryId) . '">';
foreach ($category['columns'] as $column) {
    if (!is_array($column)) {
        continue;
    }
    $columnId = (string) ($column['id'] ?? '');
    $options = is_array($column['options'] ?? null) ? $column['options'] : [];
    $picked = trim((string) ($choices[$columnId] ?? ''));
    if ($picked === '' && count($options) === 1 && is_array($options[0])) {
        $picked = (string) ($options[0]['id'] ?? '');
    }
    echo '<fieldset><legend>' . kothar_h((string) ($column['name'] ?? '')) . '</legend>';
    $hint = trim((string) ($column['hint'] ?? ''));
    if ($hint !== '') {
        echo '<p class="hint">Codes in het blad: ' . kothar_h($hint) . '</p>';
    }
    echo '<label class="sr" for="keuze-' . kothar_h($columnId) . '">Optie</label>';
    echo '<select id="keuze-' . kothar_h($columnId) . '" name="keuze[' . kothar_h($columnId) . ']" onchange="this.form.submit()">';
    echo '<option value="">Kies…</option>';
    $selected = null;
    foreach ($options as $option) {
        if (!is_array($option)) {
            continue;
        }
        $oid = (string) ($option['id'] ?? '');
        $isSel = $oid === $picked;
        if ($isSel) {
            $selected = $option;
        }
        $code = trim((string) ($option['code'] ?? ''));
        $text = (string) ($option['label'] ?? '');
        if ($code !== '') {
            $text .= ' (' . $code . ')';
        }
        echo '<option value="' . kothar_h($oid) . '"' . ($isSel ? ' selected' : '') . '>' . kothar_h($text) . '</option>';
    }
    echo '</select>';
    if (is_array($selected)) {
        echo '<p class="option-desc">' . kothar_h((string) ($selected['description'] ?? '')) . '</p>';
        if (trim((string) ($selected['code'] ?? '')) === '') {
            $fill = (string) ($fills[$columnId] ?? '');
            echo '<label for="invul-' . kothar_h($columnId) . '">Code voor deze kolom</label>';
            echo '<input id="invul-' . kothar_h($columnId) . '" name="invul[' . kothar_h($columnId) . ']" value="' . kothar_h($fill) . '" maxlength="40" autocomplete="off">';
        }
    }
    echo '</fieldset>';
}
echo '<p><button type="submit">Werk nummer bij</button></p>';
echo '</form>';

if (!$built['ok'] && $started) {
    echo '<p class="flash flash-warn">' . kothar_h($built['error']) . '</p>';
}

if ($built['ok']) {
    echo '<section class="panel confirm">';
    echo '<h2>Samenstellingsnummer</h2>';
    echo '<p class="number">' . kothar_h($built['number']) . '</p>';
    echo '<h3>Gekozen opties</h3><ul class="choice-list">';
    foreach ($built['selections'] as $selection) {
        echo '<li><strong>' . kothar_h($selection['columnName']) . '</strong> · ';
        echo kothar_h($selection['label']) . ' <code>' . kothar_h($selection['code']) . '</code>';
        echo '<br><span>' . kothar_h($selection['description']) . '</span></li>';
    }
    echo '</ul>';
    if ($existing !== null) {
        $href = 'samenstelling.php?id=' . rawurlencode((string) $existing['id']);
        echo '<p class="match">Dit nummer is al opgeslagen. Prijs: <strong>' . kothar_h(kothar_format_price((float) ($existing['price'] ?? 0))) . '</strong>. ';
        echo '<a href="' . kothar_h($href) . '">Open de samenstelling</a>.</p>';
    } else {
        echo '<p class="hint">Dit nummer staat nog niet bij de opgeslagen samenstellingen.</p>';
    }
    echo '<form method="post" action="bouwen.php" class="inline-form">';
    echo kothar_csrf_field();
    echo '<input type="hidden" name="categorie" value="' . kothar_h($categoryId) . '">';
    foreach ($built['selections'] as $selection) {
        echo '<input type="hidden" name="keuze[' . kothar_h($selection['columnId']) . ']" value="' . kothar_h($selection['optionId']) . '">';
        echo '<input type="hidden" name="invul[' . kothar_h($selection['columnId']) . ']" value="' . kothar_h($selection['code']) . '">';
    }
    echo '<label for="aantal">Aantal</label>';
    echo '<input id="aantal" name="aantal" type="number" min="1" max="9999" value="1" required>';
    echo '<button type="submit" name="actie" value="winkelwagen">Zet in winkelwagen</button>';
    if ($existing === null) {
        echo '<label for="prijs">Prijs bij registratie</label>';
        echo '<input id="prijs" name="prijs" inputmode="decimal" value="0,00">';
        echo '<button type="submit" name="actie" value="registreer">Registreer en zet in winkelwagen</button>';
    }
    echo '</form></section>';
}

kothar_page_close();
