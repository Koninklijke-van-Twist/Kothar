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

$built = kothar_build_from_choices($category, $choices, $fills);
$existing = $built['ok'] ? kothar_find_by_number($savedDoc['compositions'], $built['number']) : null;
$explicitUpdate = isset($_GET['bijwerken']);

$steps = [];
foreach ($category['columns'] as $column) {
    if (!is_array($column)) {
        continue;
    }
    $columnId = (string) ($column['id'] ?? '');
    $optionRows = [];
    $source = is_array($column['options'] ?? null) ? $column['options'] : [];
    foreach ($source as $option) {
        if (is_array($option)) {
            $optionRows[] = $option;
        }
    }
    $picked = trim((string) ($choices[$columnId] ?? ''));
    if ($picked === '' && count($optionRows) === 1) {
        $picked = (string) ($optionRows[0]['id'] ?? '');
    }
    $selected = null;
    foreach ($optionRows as $option) {
        if ((string) ($option['id'] ?? '') === $picked) {
            $selected = $option;
            break;
        }
    }
    $fill = (string) ($fills[$columnId] ?? '');
    $segment = '';
    $valid = false;
    $needsFill = false;
    if (is_array($selected)) {
        $optionCode = trim((string) ($selected['code'] ?? ''));
        if ($optionCode === '') {
            $needsFill = true;
            $segment = trim($fill);
            $valid = $segment !== '' && !str_contains($segment, '.');
        } else {
            $segment = $optionCode;
            $valid = true;
        }
    }
    $cards = [];
    foreach ($optionRows as $option) {
        $code = trim((string) ($option['code'] ?? ''));
        $label = (string) ($option['label'] ?? '');
        $description = (string) ($option['description'] ?? '');
        $cards[] = [
            'id' => (string) ($option['id'] ?? ''),
            'label' => $label,
            'description' => $description,
            'showDescription' => trim($description) !== '' && trim($description) !== trim($label),
            'code' => $code,
            'color' => kothar_segment_color($code),
            'needsFill' => $code === '',
            'selected' => (string) ($option['id'] ?? '') === $picked && $picked !== '',
        ];
    }
    $steps[] = [
        'id' => $columnId,
        'name' => (string) ($column['name'] ?? 'Kolom'),
        'hint' => trim((string) ($column['hint'] ?? '')),
        'fill' => $fill,
        'segment' => $segment,
        'color' => $segment !== '' ? kothar_segment_color($segment) : '',
        'valid' => $valid,
        'needsFill' => $needsFill,
        'current' => is_array($selected) ? (string) ($selected['label'] ?? '') : '',
        'fillError' => $needsFill && str_contains($fill, '.'),
        'cards' => $cards,
    ];
}

kothar_page_open((string) $category['name']);
echo '<p class="crumb"><a href="index.php">Start</a> · Samenstellen</p>';
echo '<h1>' . kothar_h((string) $category['name']) . '</h1>';
echo '<p class="lead">' . kothar_h((string) ($category['description'] ?? '')) . '</p>';
echo '<p class="hint">Kies per kolom één kaart. Het nummer bovenaan volgt meteen. De eerste keer klapt die kolom dicht en gaat de volgende open.</p>';

$doneCount = 0;
foreach ($steps as $step) {
    if ($step['valid']) {
        $doneCount++;
    }
}
$stepTotal = count($steps);
echo '<div class="builder-shell">';
echo '<div class="code-sticky">';
echo '<p class="code-meta"><span>Samenstellingsnummer</span><span data-progress>' . $doneCount . ' van ' . $stepTotal . '</span></p>';
echo '<p class="code-live number" data-code-live>';
foreach ($steps as $index => $step) {
    if ($index > 0) {
        echo '<span class="code-dot">.</span>';
    }
    if ($step['segment'] === '') {
        echo '<span class="code-seg is-empty">—</span>';
        continue;
    }
    $style = $step['color'] !== '' ? ' style="--seg: ' . kothar_h($step['color']) . '"' : '';
    echo '<span class="code-seg"' . $style . '>' . kothar_h($step['segment']) . '</span>';
}
echo '</p></div>';
echo '<form method="get" action="bouwen.php" class="builder" data-builder autocomplete="off">';
echo '<input type="hidden" name="categorie" value="' . kothar_h($categoryId) . '">';

foreach ($steps as $index => $step) {
    $open = $index === 0 ? ' open' : '';
    echo '<details class="step' . ($step['valid'] ? ' is-done' : '') . '" data-step data-complete="' . ($step['valid'] ? '1' : '0') . '"' . $open . '>';
    echo '<summary>';
    echo '<span class="step-no">' . ($index + 1) . '</span>';
    echo '<span class="step-title"><span class="step-name">' . kothar_h($step['name']) . '</span>';
    $current = $step['current'] !== '' ? $step['current'] : 'Kies…';
    if ($step['needsFill'] && $step['segment'] !== '') {
        $current .= ' · ' . $step['segment'];
    }
    echo '<span class="step-current" data-current>' . kothar_h($current) . '</span></span>';
    if ($step['color'] !== '') {
        echo '<span class="step-pip" data-pip style="--seg: ' . kothar_h($step['color']) . '"></span>';
    } else {
        echo '<span class="step-pip" data-pip hidden></span>';
    }
    echo '</summary>';
    echo '<div class="step-body">';
    if ($step['hint'] !== '') {
        echo '<p class="hint">Codes in het blad: ' . kothar_h($step['hint']) . '</p>';
    }
    echo '<div class="option-grid">';
    foreach ($step['cards'] as $card) {
        $selectedClass = $card['selected'] ? ' is-selected' : '';
        echo '<label class="option-card' . $selectedClass . '">';
        echo '<input type="radio" name="keuze[' . kothar_h($step['id']) . ']" value="' . kothar_h($card['id']) . '"';
        echo ' data-code="' . kothar_h($card['code']) . '" data-label="' . kothar_h($card['label']) . '"';
        echo ' data-needs-fill="' . ($card['needsFill'] ? '1' : '0') . '"';
        if ($card['color'] !== '') {
            echo ' data-color="' . kothar_h($card['color']) . '"';
        }
        if ($card['selected']) {
            echo ' checked';
        }
        echo '>';
        $topStyle = $card['color'] !== '' ? ' style="--seg: ' . kothar_h($card['color']) . '"' : '';
        echo '<span class="option-top"' . $topStyle . '></span>';
        echo '<span class="option-body"><span class="option-name"><span class="option-label">' . kothar_h($card['label']) . '</span>';
        if ($card['code'] !== '') {
            echo '<span class="option-code">' . kothar_h($card['code']) . '</span>';
        }
        echo '</span>';
        if ($card['showDescription']) {
            echo '<span class="option-desc">' . kothar_h($card['description']) . '</span>';
        }
        echo '</span>';
        echo '<span class="option-check" aria-hidden="true">✓</span>';
        echo '</label>';
    }
    echo '</div>';
    $fillId = 'invul-' . $step['id'];
    $errorId = 'invul-fout-' . $step['id'];
    echo '<div class="step-fill" data-step-fill' . ($step['needsFill'] ? '' : ' hidden') . '>';
    echo '<label for="' . kothar_h($fillId) . '">Code voor deze kolom</label>';
    echo '<input id="' . kothar_h($fillId) . '" name="invul[' . kothar_h($step['id']) . ']" data-fill value="' . kothar_h($step['fill']) . '" maxlength="40" autocomplete="off" spellcheck="false" enterkeyhint="next" aria-describedby="' . kothar_h($errorId) . '">';
    echo '<p class="hint fill-error" id="' . kothar_h($errorId) . '" data-fill-error' . ($step['fillError'] ? '' : ' hidden') . '>De code mag geen punt bevatten.</p>';
    echo '</div></div></details>';
}
echo '<p class="builder-update"><button type="submit" name="bijwerken" value="1" data-update>Werk nummer bij</button></p>';
echo '</form>';

echo '<div id="samenstelling-uitkomst" data-result>';
if (!$built['ok'] && $explicitUpdate) {
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
echo '</div></div>';

kothar_page_close();
