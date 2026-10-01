<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';

kothar_require_admin();

function kothar_clean_text(string $value, int $max): string
{
    $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    if (mb_strlen($value) > $max) {
        $value = mb_substr($value, 0, $max);
    }

    return $value;
}

function kothar_admin_finish(string $categoryId, bool $ok = true, string $error = ''): void
{
    $ajax = (string) ($_POST['ajax'] ?? '') === '1';
    if ($ajax) {
        if (!$ok) {
            http_response_code(400);
        }
        header('Content-Type: application/json; charset=utf-8');
        $payload = ['ok' => $ok];
        if (!$ok) {
            $payload['message'] = $error !== '' ? $error : 'Opslaan mislukt.';
        }
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
    $next = 'beheer_categorie.php?id=' . rawurlencode($categoryId);
    if ($ok && (string) ($_POST['daarna'] ?? '') === 'volgorde') {
        $next .= '&volgorde=1';
    }
    kothar_redirect($next);
}

/**
 * @return array<int, array{id: string, label: string, code: string, description: string}>
 */
function kothar_posted_column_options(): array
{
    $ids = $_POST['optie_id'] ?? [];
    $labels = $_POST['label'] ?? [];
    $codes = $_POST['code'] ?? [];
    $descriptions = $_POST['omschrijving'] ?? [];
    if (!is_array($ids)) {
        $ids = [];
    }
    if (!is_array($labels)) {
        $labels = [];
    }
    if (!is_array($codes)) {
        $codes = [];
    }
    if (!is_array($descriptions)) {
        $descriptions = [];
    }
    $count = max(count($ids), count($labels), count($codes), count($descriptions));
    $options = [];
    for ($i = 0; $i < $count; $i++) {
        $options[] = [
            'id' => kothar_clean_text((string) ($ids[$i] ?? ''), 80),
            'label' => kothar_clean_text((string) ($labels[$i] ?? ''), 160),
            'code' => kothar_clean_text((string) ($codes[$i] ?? ''), 40),
            'description' => kothar_clean_text((string) ($descriptions[$i] ?? ''), 800),
        ];
    }

    return $options;
}

try {
    $doc = kothar_load_categories();
} catch (Throwable $error) {
    http_response_code(500);
    kothar_page_open('Beheer');
    echo '<h1>Niet beschikbaar</h1><p>' . kothar_h($error->getMessage()) . '</p>';
    kothar_page_close();
    exit;
}

$id = (string) ($_REQUEST['id'] ?? '');
$index = null;
foreach ($doc['categories'] as $i => $category) {
    if (is_array($category) && (string) ($category['id'] ?? '') === $id) {
        $index = (int) $i;
        break;
    }
}
if ($index === null) {
    http_response_code(404);
    kothar_page_open('Beheer');
    echo '<h1>Categorie niet gevonden</h1><p><a href="beheer.php">Terug</a></p>';
    kothar_page_close();
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    kothar_csrf_check();
    $category = $doc['categories'][$index];
    $columns = is_array($category['columns'] ?? null) ? $category['columns'] : [];
    $action = (string) ($_POST['actie'] ?? '');
    $columnId = (string) ($_POST['kolom'] ?? '');
    $columnIndex = null;
    foreach ($columns as $i => $column) {
        if (is_array($column) && (string) ($column['id'] ?? '') === $columnId) {
            $columnIndex = (int) $i;
            break;
        }
    }
    $changed = false;
    $ok = true;
    $error = '';

    if ($action === 'meta') {
        $name = kothar_clean_text((string) ($_POST['naam'] ?? ''), 120);
        if ($name === '') {
            $error = 'De naam mag niet leeg zijn.';
            kothar_flash($error, 'warn');
            $ok = false;
        } else {
            $category['name'] = $name;
            $category['description'] = kothar_clean_text((string) ($_POST['omschrijving'] ?? ''), 800);
            if (!isset($category['rules']) || !is_array($category['rules'])) {
                $category['rules'] = [];
            }
            kothar_flash('Categorie opgeslagen.');
            $changed = true;
        }
    } elseif ($action === 'kolom-nieuw') {
        $name = kothar_clean_text((string) ($_POST['kolomnaam'] ?? ''), 120);
        if ($name === '') {
            $error = 'Geef de kolom een naam.';
            kothar_flash($error, 'warn');
            $ok = false;
        } else {
            $columns[] = [
                'id' => kothar_new_id('col-'),
                'name' => $name,
                'hint' => kothar_clean_text((string) ($_POST['hint'] ?? ''), 80),
                'options' => [],
            ];
            $category['columns'] = $columns;
            kothar_flash('Kolom toegevoegd.');
            $changed = true;
        }
    } elseif ($action === 'kolom-volgorde') {
        $ids = $_POST['kolom_id'] ?? [];
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $category['columns'] = kothar_order_by_id($columns, $ids);
        if ((string) ($_POST['ajax'] ?? '') !== '1') {
            kothar_flash('Volgorde opgeslagen.');
        }
        $changed = true;
    } elseif ($columnIndex === null) {
        $error = 'Kolom niet gevonden.';
        kothar_flash($error, 'warn');
        $ok = false;
    } elseif ($action === 'kolom-verwijder') {
        array_splice($columns, $columnIndex, 1);
        $category['columns'] = array_values($columns);
        kothar_flash('Kolom verwijderd.');
        $changed = true;
    } elseif ($action === 'kolom-bewaar' || $action === 'optie-nieuw' || $action === 'optie-verwijder') {
        $name = kothar_clean_text((string) ($_POST['kolomnaam'] ?? ''), 120);
        if ($name === '') {
            $error = 'Geef de kolom een naam.';
            kothar_flash($error, 'warn');
            $ok = false;
        } else {
            $posted = kothar_posted_column_options();
            $columns[$columnIndex] = kothar_apply_column_edit(
                $columns[$columnIndex],
                $name,
                kothar_clean_text((string) ($_POST['hint'] ?? ''), 80),
                $posted
            );
            if ($action === 'optie-verwijder') {
                $optionId = kothar_clean_text((string) ($_POST['optie'] ?? ''), 80);
                $existed = false;
                $currentOptions = $columns[$columnIndex]['options'] ?? [];
                if (is_array($currentOptions)) {
                    foreach ($currentOptions as $option) {
                        if (is_array($option) && (string) ($option['id'] ?? '') === $optionId && $optionId !== '') {
                            $existed = true;
                            break;
                        }
                    }
                }
                if (!$existed) {
                    $error = 'Optie niet gevonden.';
                    kothar_flash($error, 'warn');
                    $ok = false;
                } else {
                    $columns[$columnIndex] = kothar_remove_column_option($columns[$columnIndex], $optionId);
                    kothar_flash('Optie verwijderd.');
                }
            } elseif ($action === 'optie-nieuw') {
                $added = false;
                foreach ($posted as $option) {
                    if ($option['id'] === '' && $option['label'] !== '') {
                        $added = true;
                        break;
                    }
                }
                if ($added) {
                    kothar_flash('Optie toegevoegd.');
                } else {
                    $error = 'Een optie heeft een label nodig.';
                    kothar_flash($error, 'warn');
                    $ok = false;
                }
            } else {
                kothar_flash('Kolom opgeslagen.');
            }
            $category['columns'] = $columns;
            $changed = true;
        }
    } else {
        $error = 'Onbekende actie.';
        kothar_flash($error, 'warn');
        $ok = false;
    }

    if ($changed) {
        $doc['categories'][$index] = $category;
        kothar_save_categories($doc);
    }
    kothar_admin_finish($id, $ok, $error);
}

$category = $doc['categories'][$index];
$reorder = (string) ($_GET['volgorde'] ?? '') === '1';
kothar_page_open((string) ($category['name'] ?? 'Categorie'));
echo '<p class="crumb"><a href="beheer.php">Beheer</a></p>';
echo '<h1>' . kothar_h((string) ($category['name'] ?? '')) . '</h1>';
echo '<p class="reorder-bar"><button type="button" data-volgorde-knop aria-pressed="' . ($reorder ? 'true' : 'false') . '">';
echo $reorder ? 'Volgorde aanpassen gereed' : 'Volgorde aanpassen';
echo '</button></p>';
echo '<p class="hint" data-reorder-hint' . ($reorder ? '' : ' hidden') . '>Sleep de kolommen om de volgorde te wijzigen. Openen kan weer via “Volgorde aanpassen gereed”.</p>';
echo '<form method="post" class="stack" data-save-actie="meta">';
echo kothar_csrf_field();
echo '<input type="hidden" name="id" value="' . kothar_h($id) . '">';
echo '<label for="naam">Naam</label><input id="naam" name="naam" required maxlength="120" value="' . kothar_h((string) ($category['name'] ?? '')) . '">';
echo '<label for="omschrijving">Omschrijving</label><textarea id="omschrijving" name="omschrijving" maxlength="800" rows="3">' . kothar_h((string) ($category['description'] ?? '')) . '</textarea>';
echo '<button type="submit" name="actie" value="meta">Categorie opslaan</button>';
echo '</form>';

$columns = is_array($category['columns'] ?? null) ? $category['columns'] : [];
echo '<div data-column-list data-category-id="' . kothar_h($id) . '" data-reordering="' . ($reorder ? '1' : '0') . '">';
foreach ($columns as $column) {
    if (!is_array($column)) {
        continue;
    }
    $columnId = (string) ($column['id'] ?? '');
    $columnName = trim((string) ($column['name'] ?? ''));
    $columnConfirm = $columnName === ''
        ? 'Weet je zeker dat je deze kolom wilt verwijderen?'
        : 'Weet je zeker dat je de kolom ' . $columnName . ' wilt verwijderen?';
    echo '<details class="column-panel" data-column data-column-id="' . kothar_h($columnId) . '">';
    echo '<summary>';
    echo '<span class="drag-handle" data-column-handle aria-label="Versleep kolom"' . ($reorder ? '' : ' hidden') . '><span></span><span></span><span></span></span>';
    echo '<span class="column-name">' . kothar_h($columnName === '' ? 'Kolom' : $columnName) . '</span>';
    echo '</summary>';
    echo '<div class="column-body"><form method="post" class="column-form" data-column-form data-save-actie="kolom-bewaar">';
    echo kothar_csrf_field();
    echo '<input type="hidden" name="id" value="' . kothar_h($id) . '">';
    echo '<input type="hidden" name="kolom" value="' . kothar_h($columnId) . '">';
    echo '<div class="inline-form">';
    echo '<label>Naam <input name="kolomnaam" required maxlength="120" value="' . kothar_h((string) ($column['name'] ?? '')) . '"></label>';
    echo '<label>Hint <input name="hint" maxlength="80" value="' . kothar_h((string) ($column['hint'] ?? '')) . '"></label>';
    echo '</div>';

    $options = is_array($column['options'] ?? null) ? $column['options'] : [];
    $orderIds = [];
    foreach ($options as $option) {
        if (is_array($option)) {
            $orderIds[] = (string) ($option['id'] ?? '');
        }
    }
    echo '<div data-option-list data-original-order="' . kothar_h(implode(',', $orderIds)) . '">';
    if ($options === []) {
        echo '<p class="hint" data-empty-options>Nog geen opties.</p>';
    }
    foreach ($options as $option) {
        if (!is_array($option)) {
            continue;
        }
        $optionId = (string) ($option['id'] ?? '');
        $optionLabel = trim((string) ($option['label'] ?? ''));
        $optionConfirm = $optionLabel === ''
            ? 'Weet je zeker dat je deze optie wilt verwijderen?'
            : 'Weet je zeker dat je de optie ' . $optionLabel . ' wilt verwijderen?';
        echo '<div class="option-row" data-option-id="' . kothar_h($optionId) . '">';
        echo '<span class="drag-handle" data-option-handle role="button" tabindex="0" aria-label="Versleep optie"><span></span><span></span><span></span></span>';
        echo '<input type="hidden" name="optie_id[]" value="' . kothar_h($optionId) . '">';
        echo '<label>Label <input name="label[]" required maxlength="160" value="' . kothar_h((string) ($option['label'] ?? '')) . '"></label>';
        echo '<label>Code <input name="code[]" maxlength="40" value="' . kothar_h((string) ($option['code'] ?? '')) . '"></label>';
        echo '<label>Omschrijving <input name="omschrijving[]" maxlength="800" value="' . kothar_h((string) ($option['description'] ?? '')) . '"></label>';
        echo '<button type="button" data-confirm="' . kothar_h($optionConfirm) . '" data-actie="optie-verwijder" data-optie="' . kothar_h($optionId) . '">Verwijder</button>';
        echo '</div>';
    }
    echo '</div>';
    echo '<div class="option-row option-add">';
    echo '<input type="hidden" name="optie_id[]" value="">';
    echo '<label>Label <input name="label[]" data-new-label maxlength="160" placeholder="Nieuw label"></label>';
    echo '<label>Code <input name="code[]" maxlength="40" placeholder="Code"></label>';
    echo '<label>Omschrijving <input name="omschrijving[]" maxlength="800" placeholder="Omschrijving"></label>';
    echo '<button type="button" data-add-option>Optie toevoegen</button>';
    echo '</div>';
    echo '<div class="column-save">';
    echo '<button type="submit" name="actie" value="kolom-bewaar">Opslaan</button>';
    echo '<button type="button" data-confirm="' . kothar_h($columnConfirm) . '" data-actie="kolom-verwijder">Verwijder kolom</button>';
    echo '</div>';
    echo '</form></div></details>';
}
echo '</div>';

echo '<section class="panel"><h2>Nieuwe kolom</h2>';
echo '<form method="post" class="inline-form" data-save-actie="kolom-nieuw">';
echo kothar_csrf_field();
echo '<input type="hidden" name="id" value="' . kothar_h($id) . '">';
echo '<label>Naam <input name="kolomnaam" required maxlength="120"></label>';
echo '<label>Hint <input name="hint" maxlength="80"></label>';
echo '<button type="submit" name="actie" value="kolom-nieuw">Kolom toevoegen</button>';
echo '</form></section>';
kothar_page_close();
