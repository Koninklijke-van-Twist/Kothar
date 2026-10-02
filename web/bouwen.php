<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';

try {
    $doc = kothar_load_categories();
    $savedDoc = kothar_load_compositions();
} catch (Throwable $error) {
    http_response_code(500);
    kothar_page_open(LOC('kothar.build.title'));
    echo '<h1>' . kothar_h(LOC('kothar.error.unavailable')) . '</h1><p>' . kothar_h($error->getMessage()) . '</p>';
    kothar_page_close();
    exit;
}

$categoryId = (string) ($_REQUEST['categorie'] ?? '');
$category = kothar_find_category($doc, $categoryId);
if ($category === null) {
    http_response_code(404);
    kothar_page_open(LOC('kothar.build.title'));
    echo '<h1>' . kothar_h(LOC('kothar.build.not_found')) . '</h1><p><a href="index.php">' . kothar_h(LOC('kothar.back.start')) . '</a></p>';
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
$measures = $_REQUEST['maat'] ?? [];
if (!is_array($measures)) {
    $measures = [];
}
$fills = kothar_fills_with_measures($category, $choices, $fills, $measures);

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    kothar_csrf_check();
    $built = kothar_build_from_choices($category, $choices, $fills);
    $action = (string) ($_POST['actie'] ?? '');
    if (!$built['ok']) {
        kothar_flash($built['error'], 'warn');
    } elseif ($action === 'winkelwagen' || $action === 'registreer') {
        $qty = kothar_parse_quantity((string) ($_POST['aantal'] ?? '1'));
        if ($qty === null) {
            kothar_flash(LOC('kothar.build.qty_range'), 'warn');
        } else {
            $existing = kothar_find_by_number($savedDoc['compositions'], $built['number']);
            if ($action === 'registreer' && $existing !== null) {
                kothar_flash(LOC('kothar.build.already_exists'));
            }
            if ($action === 'registreer' && $existing === null) {
                $price = kothar_parse_price((string) ($_POST['prijs'] ?? '0'));
                if ($price === null) {
                    kothar_flash(LOC('kothar.build.price_invalid'), 'warn');
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
                    kothar_flash($result['created'] ? LOC('kothar.build.registered') : LOC('kothar.build.already_exists'));
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
                kothar_flash(LOC('kothar.build.added_cart'));
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
    $kind = kothar_column_kind($column);
    $mode = is_array($selected) ? kothar_option_vector($selected) : '';
    if ($kind === 'hwl') {
        $mode = 'hwl';
    } elseif ($kind === 'diameter') {
        $mode = 'diameter';
    }
    $meters = ['h' => '', 'b' => '', 'l' => '', 'd' => ''];
    $hwlParts = kothar_parse_hwl_code($fill);
    if (is_array($hwlParts)) {
        $meters['h'] = $hwlParts['h'];
        $meters['b'] = $hwlParts['b'];
        $meters['l'] = $hwlParts['l'];
        if ($kind === 'dimensions') {
            $mode = 'hwl';
        }
    }
    $diameterParts = kothar_parse_diameter_code($fill);
    if (is_array($diameterParts)) {
        $meters['d'] = $diameterParts['d'];
        $meters['l'] = $diameterParts['l'];
        if ($kind === 'dimensions') {
            $mode = 'diameter';
        }
    }
    if (!is_array($hwlParts) && !is_array($diameterParts)) {
        $group = $measures[$columnId] ?? null;
        if (is_array($group)) {
            $meters = kothar_meters_from_request($group, $mode);
        }
    }
    $segment = '';
    $valid = false;
    $needsFill = false;
    $current = '';
    if ($kind === 'quantity') {
        $needsFill = true;
        $quantityCode = kothar_clean_code($fill);
        $valid = preg_match('/^\d+$/', $quantityCode) === 1;
        $segment = $valid ? $quantityCode : '';
        $current = $valid ? $quantityCode : kothar_quantity_prompt($column);
    } elseif ($kind === 'hwl' || $kind === 'diameter' || $kind === 'dimensions') {
        $needsFill = true;
        if ($mode === 'hwl' && is_array($hwlParts)) {
            $segment = $fill;
            $valid = true;
        } elseif ($mode === 'diameter' && is_array($diameterParts)) {
            $segment = $fill;
            $valid = true;
        }
        if ($segment !== '') {
            $current = $segment;
        } elseif ($mode !== '') {
            $current = LOC('kothar.build.fill_measures');
        } else {
            $current = LOC('kothar.build.choose_vector');
        }
        if (!$valid && ($mode === 'hwl' || $mode === 'diameter')) {
            $preview = kothar_preview_measures($mode, $meters, '');
            if ($preview !== '') {
                $segment = $preview;
                $current = $preview;
            }
        }
    } elseif (is_array($selected)) {
        $optionCode = trim((string) ($selected['code'] ?? ''));
        if ($optionCode === '') {
            $needsFill = true;
            $segment = trim($fill);
            $valid = $segment !== '' && !str_contains($segment, '.');
        } else {
            $segment = $optionCode;
            $valid = true;
        }
        $current = (string) ($selected['label'] ?? '');
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
            'vector' => kothar_option_vector($option),
        ];
    }
    $steps[] = [
        'id' => $columnId,
        'name' => (string) ($column['name'] ?? LOC('kothar.column.fallback')),
        'hint' => trim((string) ($column['hint'] ?? '')),
        'fill' => $fill,
        'segment' => $segment,
        'color' => $segment !== '' ? kothar_segment_color($segment) : '',
        'valid' => $valid,
        'needsFill' => $needsFill,
        'current' => $current,
        'fillError' => $kind === 'choices' && $needsFill && str_contains($fill, '.'),
        'cards' => $cards,
        'kind' => $kind,
        'mode' => $mode,
        'meters' => $meters,
        'prompt' => $kind === 'quantity' ? kothar_quantity_prompt($column) : '',
        'optionId' => is_array($selected) ? (string) ($selected['id'] ?? '') : (count($optionRows) === 1 ? (string) ($optionRows[0]['id'] ?? '') : ''),
    ];
}

kothar_page_open((string) $category['name']);
echo '<p class="crumb"><a href="index.php">' . kothar_h(LOC('kothar.nav.start')) . '</a> · ' . kothar_h(LOC('kothar.build.title')) . '</p>';
echo '<h1>' . kothar_h((string) $category['name']) . '</h1>';
echo '<p class="lead">' . kothar_h((string) ($category['description'] ?? '')) . '</p>';
echo '<p class="hint">' . kothar_h(LOC('kothar.build.hint')) . '</p>';

$doneCount = 0;
foreach ($steps as $step) {
    if ($step['valid']) {
        $doneCount++;
    }
}
$stepTotal = count($steps);
echo '<div class="builder-shell">';
echo '<div class="code-sticky">';
echo '<p class="code-meta"><span>' . kothar_h(LOC('kothar.build.number')) . '</span><span data-progress>' . kothar_h(LOC('kothar.build.progress', $doneCount, $stepTotal)) . '</span></p>';
echo '<p class="code-live number" data-code-live>';
foreach ($steps as $index => $step) {
    if ($index > 0) {
        echo '<span class="code-dot">.</span><wbr>';
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
    echo '<details class="step' . ($step['valid'] ? ' is-done' : '') . '" data-step data-kind="' . kothar_h($step['kind']) . '"';
    if ($step['prompt'] !== '') {
        echo ' data-prompt="' . kothar_h($step['prompt']) . '"';
    }
    echo ' data-complete="' . ($step['valid'] ? '1' : '0') . '"' . $open . '>';
    echo '<summary>';
    echo '<span class="step-no">' . ($index + 1) . '</span>';
    echo '<span class="step-title"><span class="step-name">' . kothar_h($step['name']) . '</span>';
    $current = $step['current'] !== '' ? $step['current'] : LOC('kothar.build.choose');
    if ($step['kind'] === 'choices' && $step['needsFill'] && $step['segment'] !== '') {
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
    if ($step['kind'] === 'quantity') {
        $fillId = 'invul-' . $step['id'];
        echo '<div class="quantity-fill">';
        echo '<input type="hidden" name="keuze[' . kothar_h($step['id']) . ']" value="' . kothar_h($step['optionId']) . '">';
        echo '<label for="' . kothar_h($fillId) . '">' . kothar_h($step['prompt']) . '</label>';
        echo '<input id="' . kothar_h($fillId) . '" name="invul[' . kothar_h($step['id']) . ']" data-quantity type="number" min="0" step="1" inputmode="numeric" enterkeyhint="next" value="' . kothar_h($step['segment']) . '" autocomplete="off">';
        echo '</div></div></details>';
        continue;
    }
    if ($step['kind'] === 'hwl' || $step['kind'] === 'diameter' || $step['kind'] === 'dimensions') {
        if ($step['kind'] === 'dimensions') {
            echo '<div class="mode-row">';
            foreach ($step['cards'] as $card) {
                $vector = (string) ($card['vector'] ?? '');
                if ($vector !== 'hwl' && $vector !== 'diameter') {
                    continue;
                }
                $modeLabel = $vector === 'hwl' ? 'H×B×L' : '⌀×L';
                $modeText = $vector === 'hwl' ? LOC('kothar.build.mode_hwl') : LOC('kothar.build.mode_diameter');
                echo '<label class="mode-option">';
                echo '<input type="radio" name="keuze[' . kothar_h($step['id']) . ']" value="' . kothar_h($card['id']) . '" data-vector="' . kothar_h($vector) . '"';
                if ($step['mode'] === $vector) {
                    echo ' checked';
                }
                echo '>';
                echo '<span><strong>' . kothar_h($modeLabel) . '</strong> ' . kothar_h($modeText) . '</span>';
                echo '</label>';
            }
            echo '</div>';
        } else {
            echo '<input type="hidden" name="keuze[' . kothar_h($step['id']) . ']" value="' . kothar_h($step['optionId']) . '">';
        }
        echo '<p class="hint">' . kothar_h(LOC('kothar.build.meters_hint')) . '</p>';
        $showHwl = $step['kind'] === 'hwl' || ($step['kind'] === 'dimensions' && $step['mode'] === 'hwl');
        $showDiameter = $step['kind'] === 'diameter' || ($step['kind'] === 'dimensions' && $step['mode'] === 'diameter');
        if ($step['kind'] !== 'diameter') {
            $disabled = $showHwl ? '' : ' disabled';
            echo '<div class="measure-grid" data-measures="hwl"' . ($showHwl ? '' : ' hidden') . '>';
            echo '<label>' . kothar_h(LOC('kothar.build.height')) . ' <input type="text" name="maat[' . kothar_h($step['id']) . '][h]" data-measure="h" inputmode="decimal" enterkeyhint="next" autocomplete="off" spellcheck="false" value="' . kothar_h($step['meters']['h']) . '"' . $disabled . '></label>';
            echo '<label>' . kothar_h(LOC('kothar.build.width')) . ' <input type="text" name="maat[' . kothar_h($step['id']) . '][b]" data-measure="b" inputmode="decimal" enterkeyhint="next" autocomplete="off" spellcheck="false" value="' . kothar_h($step['meters']['b']) . '"' . $disabled . '></label>';
            echo '<label>' . kothar_h(LOC('kothar.build.length')) . ' <input type="text" name="maat[' . kothar_h($step['id']) . '][l]" data-measure="l" inputmode="decimal" enterkeyhint="next" autocomplete="off" spellcheck="false" value="' . kothar_h($step['meters']['l']) . '"' . $disabled . '></label>';
            echo '</div>';
        }
        if ($step['kind'] !== 'hwl') {
            $disabled = $showDiameter ? '' : ' disabled';
            echo '<div class="measure-grid" data-measures="diameter"' . ($showDiameter ? '' : ' hidden') . '>';
            echo '<label>' . kothar_h(LOC('kothar.build.diameter')) . ' <input type="text" name="maat[' . kothar_h($step['id']) . '][d]" data-measure="d" inputmode="decimal" enterkeyhint="next" autocomplete="off" spellcheck="false" value="' . kothar_h($step['meters']['d']) . '"' . $disabled . '></label>';
            echo '<label>' . kothar_h(LOC('kothar.build.length')) . ' <input type="text" name="maat[' . kothar_h($step['id']) . '][dl]" data-measure="l" inputmode="decimal" enterkeyhint="next" autocomplete="off" spellcheck="false" value="' . kothar_h($step['meters']['l']) . '"' . $disabled . '></label>';
            echo '</div>';
        }
        echo '</div></details>';
        continue;
    }
    if ($step['hint'] !== '') {
        echo '<p class="hint">' . kothar_h(LOC('kothar.build.codes_hint')) . ' ' . kothar_h($step['hint']) . '</p>';
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
    echo '<label for="' . kothar_h($fillId) . '">' . kothar_h(LOC('kothar.build.fill_code')) . '</label>';
    echo '<input id="' . kothar_h($fillId) . '" name="invul[' . kothar_h($step['id']) . ']" data-fill value="' . kothar_h($step['fill']) . '" maxlength="40" autocomplete="off" spellcheck="false" enterkeyhint="next" aria-describedby="' . kothar_h($errorId) . '">';
    echo '<p class="hint fill-error" id="' . kothar_h($errorId) . '" data-fill-error' . ($step['fillError'] ? '' : ' hidden') . '>' . kothar_h(LOC('kothar.build.no_dot')) . '</p>';
    echo '</div></div></details>';
}
echo '<p class="builder-update"><button type="submit" name="bijwerken" value="1" data-update>' . kothar_h(LOC('kothar.build.update')) . '</button></p>';
echo '</form>';

echo '<div id="samenstelling-uitkomst" data-result>';
if (!$built['ok'] && $explicitUpdate) {
    echo '<p class="flash flash-warn">' . kothar_h($built['error']) . '</p>';
}

if ($built['ok']) {
    echo '<section class="panel confirm">';
    echo '<h2>' . kothar_h(LOC('kothar.build.number')) . '</h2>';
    echo '<p class="number">' . kothar_h($built['number']) . '</p>';
    echo '<h3>' . kothar_h(LOC('kothar.build.chosen')) . '</h3><ul class="choice-list">';
    foreach ($built['selections'] as $selection) {
        echo '<li><strong>' . kothar_h($selection['columnName']) . '</strong> · ';
        echo kothar_h($selection['label']) . ' <code>' . kothar_h($selection['code']) . '</code>';
        $selectionDescription = trim((string) ($selection['description'] ?? ''));
        if ($selectionDescription !== '') {
            echo '<br><span>' . kothar_h($selectionDescription) . '</span>';
        }
        echo '</li>';
    }
    echo '</ul>';
    if ($existing !== null) {
        $href = 'samenstelling.php?id=' . rawurlencode((string) $existing['id']);
        echo '<p class="match">' . kothar_h(LOC('kothar.build.already_saved')) . ' <strong>' . kothar_h(kothar_format_price((float) ($existing['price'] ?? 0))) . '</strong>. ';
        echo '<a href="' . kothar_h($href) . '">' . kothar_h(LOC('kothar.build.open_composition')) . '</a>.</p>';
    } else {
        echo '<p class="hint">' . kothar_h(LOC('kothar.build.not_saved_yet')) . '</p>';
    }
    echo '<form method="post" action="bouwen.php" class="inline-form">';
    echo kothar_csrf_field();
    echo '<input type="hidden" name="categorie" value="' . kothar_h($categoryId) . '">';
    foreach ($built['selections'] as $selection) {
        echo '<input type="hidden" name="keuze[' . kothar_h($selection['columnId']) . ']" value="' . kothar_h($selection['optionId']) . '">';
        echo '<input type="hidden" name="invul[' . kothar_h($selection['columnId']) . ']" value="' . kothar_h($selection['code']) . '">';
    }
    echo '<label for="aantal">' . kothar_h(LOC('kothar.build.qty')) . '</label>';
    echo '<input id="aantal" name="aantal" type="number" min="1" max="9999" value="1" required>';
    echo '<button type="submit" name="actie" value="winkelwagen">' . kothar_h(LOC('kothar.build.add_cart')) . '</button>';
    if ($existing === null) {
        echo '<label for="prijs">' . kothar_h(LOC('kothar.build.price_on_register')) . '</label>';
        echo '<input id="prijs" name="prijs" inputmode="decimal" value="0,00">';
        echo '<button type="submit" name="actie" value="registreer">' . kothar_h(LOC('kothar.build.register_and_cart')) . '</button>';
    }
    echo '</form></section>';
}
echo '</div></div>';

kothar_page_close();
