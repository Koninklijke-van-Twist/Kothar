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
            $payload['message'] = $error !== '' ? $error : LOC('kothar.admin.save_failed');
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
            'code' => kothar_clean_code((string) ($codes[$i] ?? ''), 40),
            'description' => kothar_clean_text((string) ($descriptions[$i] ?? ''), 800),
        ];
    }

    return $options;
}

try {
    $doc = kothar_load_categories();
} catch (Throwable $error) {
    http_response_code(500);
    kothar_page_open(LOC('kothar.nav.admin'));
    echo '<h1>' . kothar_h(LOC('kothar.error.unavailable')) . '</h1><p>' . kothar_h($error->getMessage()) . '</p>';
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
    kothar_page_open(LOC('kothar.nav.admin'));
    echo '<h1>' . kothar_h(LOC('kothar.build.not_found')) . '</h1><p><a href="beheer.php">' . kothar_h(LOC('kothar.back')) . '</a></p>';
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
            $error = LOC('kothar.admin.name_required');
            kothar_flash($error, 'warn');
            $ok = false;
        } else {
            $category['name'] = $name;
            $category['description'] = kothar_clean_text((string) ($_POST['omschrijving'] ?? ''), 800);
            if (!isset($category['rules']) || !is_array($category['rules'])) {
                $category['rules'] = [];
            }
            kothar_flash(LOC('kothar.admin.saved_category'));
            $changed = true;
        }
    } elseif ($action === 'kolom-nieuw') {
        $name = kothar_clean_text((string) ($_POST['kolomnaam'] ?? ''), 120);
        if ($name === '') {
            $error = LOC('kothar.admin.column_name_required');
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
            kothar_flash(LOC('kothar.admin.column_added'));
            $changed = true;
        }
    } elseif ($action === 'kolom-volgorde') {
        $ids = $_POST['kolom_id'] ?? [];
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $category['columns'] = kothar_order_by_id($columns, $ids);
        if ((string) ($_POST['ajax'] ?? '') !== '1') {
            kothar_flash(LOC('kothar.admin.order_saved'));
        }
        $changed = true;
    } elseif ($columnIndex === null) {
        $error = LOC('kothar.admin.column_not_found');
        kothar_flash($error, 'warn');
        $ok = false;
    } elseif ($action === 'kolom-verwijder') {
        array_splice($columns, $columnIndex, 1);
        $category['columns'] = array_values($columns);
        kothar_flash(LOC('kothar.admin.column_deleted'));
        $changed = true;
    } elseif ($action === 'kolom-bewaar' || $action === 'optie-nieuw' || $action === 'optie-verwijder') {
        $name = kothar_clean_text((string) ($_POST['kolomnaam'] ?? ''), 120);
        if ($name === '') {
            $error = LOC('kothar.admin.column_name_required');
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
                    $error = LOC('kothar.admin.option_not_found');
                    kothar_flash($error, 'warn');
                    $ok = false;
                } else {
                    $columns[$columnIndex] = kothar_remove_column_option($columns[$columnIndex], $optionId);
                    kothar_flash(LOC('kothar.admin.option_deleted'));
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
                    kothar_flash(LOC('kothar.admin.option_added'));
                } else {
                    $error = LOC('kothar.admin.option_needs_label');
                    kothar_flash($error, 'warn');
                    $ok = false;
                }
            } else {
                kothar_flash(LOC('kothar.admin.column_saved'));
            }
            $category['columns'] = $columns;
            $changed = true;
        }
    } else {
        $error = LOC('kothar.admin.unknown_action');
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
$categoryName = trim((string) ($category['name'] ?? ''));
kothar_page_open($categoryName === '' ? LOC('kothar.admin.category_fallback') : $categoryName);
echo '<p class="crumb"><a href="beheer.php">' . kothar_h(LOC('kothar.nav.admin')) . '</a></p>';
echo '<h1><button type="button" class="category-edit-title" data-category-edit aria-haspopup="dialog" aria-controls="categorie-bewerken" aria-expanded="false">';
echo kothar_h($categoryName === '' ? LOC('kothar.admin.category_fallback') : $categoryName);
echo '</button></h1>';
echo '<dialog class="modal" id="categorie-bewerken" data-category-modal aria-labelledby="categorie-bewerken-titel">';
echo '<form method="post" class="modal-card category-meta" data-save-actie="meta">';
echo '<h2 id="categorie-bewerken-titel">' . kothar_h(LOC('kothar.admin.edit_category')) . '</h2>';
echo kothar_csrf_field();
echo '<input type="hidden" name="id" value="' . kothar_h($id) . '">';
echo '<label for="naam">' . kothar_h(LOC('kothar.admin.name')) . '</label><input id="naam" name="naam" required maxlength="120" value="' . kothar_h((string) ($category['name'] ?? '')) . '" autofocus>';
echo '<label for="omschrijving">' . kothar_h(LOC('kothar.admin.description')) . '</label><textarea id="omschrijving" name="omschrijving" maxlength="800" rows="3">' . kothar_h((string) ($category['description'] ?? '')) . '</textarea>';
echo '<div class="modal-actions">';
echo '<button type="button" class="quiet" data-category-cancel>' . kothar_h(LOC('kothar.confirm.cancel')) . '</button>';
echo '<button type="submit" name="actie" value="meta">' . kothar_h(LOC('kothar.admin.save_category')) . '</button>';
echo '</div>';
echo '</form></dialog>';
echo '<p class="reorder-bar"><button type="button" data-volgorde-knop aria-pressed="' . ($reorder ? 'true' : 'false') . '">';
echo kothar_h($reorder ? LOC('kothar.admin.reorder_done') : LOC('kothar.admin.reorder'));
echo '</button></p>';
echo '<p class="hint" data-reorder-hint' . ($reorder ? '' : ' hidden') . '>' . kothar_h(LOC('kothar.admin.reorder_hint')) . '</p>';

$columns = is_array($category['columns'] ?? null) ? $category['columns'] : [];
echo '<div data-column-list data-category-id="' . kothar_h($id) . '" data-reordering="' . ($reorder ? '1' : '0') . '">';
foreach ($columns as $column) {
    if (!is_array($column)) {
        continue;
    }
    $columnId = (string) ($column['id'] ?? '');
    $columnName = trim((string) ($column['name'] ?? ''));
    $columnConfirm = $columnName === ''
        ? LOC('kothar.admin.confirm_column')
        : LOC('kothar.admin.confirm_column_named', $columnName);
    echo '<details class="column-panel" data-column data-column-id="' . kothar_h($columnId) . '">';
    echo '<summary>';
    echo '<span class="drag-handle" data-column-handle role="button" tabindex="0" aria-label="' . kothar_h(LOC('kothar.admin.drag_column')) . '"' . ($reorder ? '' : ' hidden') . '><span></span><span></span><span></span></span>';
    echo '<span class="column-name">' . kothar_h($columnName === '' ? LOC('kothar.column.fallback') : $columnName) . '</span>';
    echo '</summary>';
    echo '<div class="column-body"><form method="post" class="column-form" data-column-form data-save-actie="kolom-bewaar">';
    echo kothar_csrf_field();
    echo '<input type="hidden" name="id" value="' . kothar_h($id) . '">';
    echo '<input type="hidden" name="kolom" value="' . kothar_h($columnId) . '">';
    echo '<div class="inline-form">';
    echo '<label>' . kothar_h(LOC('kothar.admin.name')) . ' <input name="kolomnaam" required maxlength="120" value="' . kothar_h((string) ($column['name'] ?? '')) . '"></label>';
    echo '<label>' . kothar_h(LOC('kothar.admin.hint')) . ' <input name="hint" maxlength="80" value="' . kothar_h((string) ($column['hint'] ?? '')) . '"></label>';
    echo '</div>';

    $options = is_array($column['options'] ?? null) ? $column['options'] : [];
    $columnKind = kothar_column_kind($column);
    if ($columnKind !== 'choices') {
        echo '<p class="hint">' . kothar_h(kothar_column_admin_note($columnKind)) . '</p>';
        foreach ($options as $option) {
            if (!is_array($option)) {
                continue;
            }
            echo '<input type="hidden" name="optie_id[]" value="' . kothar_h((string) ($option['id'] ?? '')) . '">';
            echo '<input type="hidden" name="label[]" value="' . kothar_h((string) ($option['label'] ?? '')) . '">';
            echo '<input type="hidden" name="code[]" value="' . kothar_h((string) ($option['code'] ?? '')) . '">';
            echo '<input type="hidden" name="omschrijving[]" value="' . kothar_h((string) ($option['description'] ?? '')) . '">';
        }
        echo '<div class="column-save">';
        echo '<button type="submit" name="actie" value="kolom-bewaar">' . kothar_h(LOC('kothar.admin.save')) . '</button>';
        echo '<button type="button" data-confirm="' . kothar_h($columnConfirm) . '" data-actie="kolom-verwijder">' . kothar_h(LOC('kothar.admin.delete_column')) . '</button>';
        echo '</div>';
        echo '</form></div></details>';
        continue;
    }
    $orderIds = [];
    foreach ($options as $option) {
        if (is_array($option)) {
            $orderIds[] = (string) ($option['id'] ?? '');
        }
    }
    echo '<div data-option-list data-original-order="' . kothar_h(implode(',', $orderIds)) . '">';
    if ($options === []) {
        echo '<p class="hint" data-empty-options>' . kothar_h(LOC('kothar.admin.no_options')) . '</p>';
    }
    foreach ($options as $option) {
        if (!is_array($option)) {
            continue;
        }
        $optionId = (string) ($option['id'] ?? '');
        $optionLabel = trim((string) ($option['label'] ?? ''));
        $optionConfirm = $optionLabel === ''
            ? LOC('kothar.admin.confirm_option')
            : LOC('kothar.admin.confirm_option_named', $optionLabel);
        echo '<div class="option-row" data-option-id="' . kothar_h($optionId) . '">';
        echo '<span class="drag-handle" data-option-handle role="button" tabindex="0" aria-label="' . kothar_h(LOC('kothar.admin.drag_option')) . '"><span></span><span></span><span></span></span>';
        echo '<input type="hidden" name="optie_id[]" value="' . kothar_h($optionId) . '">';
        echo '<label>' . kothar_h(LOC('kothar.admin.label')) . ' <input name="label[]" required maxlength="160" value="' . kothar_h((string) ($option['label'] ?? '')) . '"></label>';
        echo '<label>' . kothar_h(LOC('kothar.admin.code')) . ' <input name="code[]" maxlength="40" value="' . kothar_h((string) ($option['code'] ?? '')) . '"></label>';
        echo '<label>' . kothar_h(LOC('kothar.admin.description')) . ' <input name="omschrijving[]" maxlength="800" value="' . kothar_h((string) ($option['description'] ?? '')) . '"></label>';
        echo '<button type="button" data-confirm="' . kothar_h($optionConfirm) . '" data-actie="optie-verwijder" data-optie="' . kothar_h($optionId) . '">' . kothar_h(LOC('kothar.admin.delete')) . '</button>';
        echo '</div>';
    }
    echo '</div>';
    echo '<div class="option-row option-add">';
    echo '<input type="hidden" name="optie_id[]" value="">';
    echo '<label>' . kothar_h(LOC('kothar.admin.label')) . ' <input name="label[]" data-new-label maxlength="160" placeholder="' . kothar_h(LOC('kothar.admin.placeholder_label')) . '"></label>';
    echo '<label>' . kothar_h(LOC('kothar.admin.code')) . ' <input name="code[]" maxlength="40" placeholder="' . kothar_h(LOC('kothar.admin.code')) . '"></label>';
    echo '<label>' . kothar_h(LOC('kothar.admin.description')) . ' <input name="omschrijving[]" maxlength="800" placeholder="' . kothar_h(LOC('kothar.admin.description')) . '"></label>';
    echo '<button type="button" data-add-option>' . kothar_h(LOC('kothar.admin.add_option')) . '</button>';
    echo '</div>';
    echo '<div class="column-save">';
    echo '<button type="submit" name="actie" value="kolom-bewaar">' . kothar_h(LOC('kothar.admin.save')) . '</button>';
    echo '<button type="button" data-confirm="' . kothar_h($columnConfirm) . '" data-actie="kolom-verwijder">' . kothar_h(LOC('kothar.admin.delete_column')) . '</button>';
    echo '</div>';
    echo '</form></div></details>';
}
echo '</div>';

echo '<section class="panel"><h2>' . kothar_h(LOC('kothar.admin.new_column')) . '</h2>';
echo '<form method="post" class="inline-form" data-save-actie="kolom-nieuw">';
echo kothar_csrf_field();
echo '<input type="hidden" name="id" value="' . kothar_h($id) . '">';
echo '<label>' . kothar_h(LOC('kothar.admin.name')) . ' <input name="kolomnaam" required maxlength="120"></label>';
echo '<label>' . kothar_h(LOC('kothar.admin.hint')) . ' <input name="hint" maxlength="80"></label>';
echo '<button type="submit" name="actie" value="kolom-nieuw">' . kothar_h(LOC('kothar.admin.add_column')) . '</button>';
echo '</form></section>';
kothar_page_close();
