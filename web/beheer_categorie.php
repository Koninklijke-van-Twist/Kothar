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

    if ($action === 'meta') {
        $name = kothar_clean_text((string) ($_POST['naam'] ?? ''), 120);
        if ($name === '') {
            kothar_flash('De naam mag niet leeg zijn.', 'warn');
        } else {
            $category['name'] = $name;
            $category['description'] = kothar_clean_text((string) ($_POST['omschrijving'] ?? ''), 800);
            if (!isset($category['rules']) || !is_array($category['rules'])) {
                $category['rules'] = [];
            }
            kothar_flash('Categorie opgeslagen.');
        }
    } elseif ($action === 'kolom-nieuw') {
        $name = kothar_clean_text((string) ($_POST['kolomnaam'] ?? ''), 120);
        if ($name === '') {
            kothar_flash('Geef de kolom een naam.', 'warn');
        } else {
            $columns[] = [
                'id' => kothar_new_id('col-'),
                'name' => $name,
                'hint' => kothar_clean_text((string) ($_POST['hint'] ?? ''), 80),
                'options' => [],
            ];
            $category['columns'] = $columns;
            kothar_flash('Kolom toegevoegd.');
        }
    } elseif ($columnIndex === null) {
        kothar_flash('Kolom niet gevonden.', 'warn');
    } elseif ($action === 'kolom-naam') {
        $name = kothar_clean_text((string) ($_POST['kolomnaam'] ?? ''), 120);
        if ($name === '') {
            kothar_flash('Geef de kolom een naam.', 'warn');
        } else {
            $columns[$columnIndex]['name'] = $name;
            $columns[$columnIndex]['hint'] = kothar_clean_text((string) ($_POST['hint'] ?? ''), 80);
            $category['columns'] = $columns;
            kothar_flash('Kolom bijgewerkt.');
        }
    } elseif ($action === 'kolom-omhoog' || $action === 'kolom-omlaag') {
        $category['columns'] = kothar_move_item($columns, $columnIndex, $action === 'kolom-omhoog' ? -1 : 1);
    } elseif ($action === 'kolom-verwijder') {
        array_splice($columns, $columnIndex, 1);
        $category['columns'] = array_values($columns);
        kothar_flash('Kolom verwijderd.');
    } elseif ($action === 'optie-nieuw') {
        $label = kothar_clean_text((string) ($_POST['label'] ?? ''), 160);
        $code = kothar_clean_text((string) ($_POST['code'] ?? ''), 40);
        $description = kothar_clean_text((string) ($_POST['omschrijving'] ?? ''), 800);
        if ($label === '') {
            kothar_flash('Een optie heeft een label nodig.', 'warn');
        } else {
            if ($description === '') {
                $description = $label;
            }
            $options = is_array($columns[$columnIndex]['options'] ?? null) ? $columns[$columnIndex]['options'] : [];
            $options[] = [
                'id' => kothar_new_id('opt-'),
                'label' => $label,
                'code' => $code,
                'description' => $description,
            ];
            $columns[$columnIndex]['options'] = $options;
            $category['columns'] = $columns;
            kothar_flash('Optie toegevoegd.');
        }
    } else {
        $optionId = (string) ($_POST['optie'] ?? '');
        $options = is_array($columns[$columnIndex]['options'] ?? null) ? $columns[$columnIndex]['options'] : [];
        $optionIndex = null;
        foreach ($options as $i => $option) {
            if (is_array($option) && (string) ($option['id'] ?? '') === $optionId) {
                $optionIndex = (int) $i;
                break;
            }
        }
        if ($optionIndex === null) {
            kothar_flash('Optie niet gevonden.', 'warn');
        } elseif ($action === 'optie-bewaar') {
            $label = kothar_clean_text((string) ($_POST['label'] ?? ''), 160);
            if ($label === '') {
                kothar_flash('Een optie heeft een label nodig.', 'warn');
            } else {
                $description = kothar_clean_text((string) ($_POST['omschrijving'] ?? ''), 800);
                $options[$optionIndex]['label'] = $label;
                $options[$optionIndex]['code'] = kothar_clean_text((string) ($_POST['code'] ?? ''), 40);
                $options[$optionIndex]['description'] = $description === '' ? $label : $description;
                $columns[$columnIndex]['options'] = $options;
                $category['columns'] = $columns;
                kothar_flash('Optie opgeslagen.');
            }
        } elseif ($action === 'optie-omhoog' || $action === 'optie-omlaag') {
            $columns[$columnIndex]['options'] = kothar_move_item($options, $optionIndex, $action === 'optie-omhoog' ? -1 : 1);
            $category['columns'] = $columns;
        } elseif ($action === 'optie-verwijder') {
            array_splice($options, $optionIndex, 1);
            $columns[$columnIndex]['options'] = array_values($options);
            $category['columns'] = $columns;
            kothar_flash('Optie verwijderd.');
        }
    }

    $doc['categories'][$index] = $category;
    kothar_save_categories($doc);
    kothar_redirect('beheer_categorie.php?id=' . rawurlencode($id));
}

$category = $doc['categories'][$index];
kothar_page_open((string) ($category['name'] ?? 'Categorie'));
echo '<p class="crumb"><a href="beheer.php">Beheer</a></p>';
echo '<h1>' . kothar_h((string) ($category['name'] ?? '')) . '</h1>';
echo '<form method="post" class="stack">';
echo kothar_csrf_field();
echo '<input type="hidden" name="id" value="' . kothar_h($id) . '">';
echo '<label for="naam">Naam</label><input id="naam" name="naam" required maxlength="120" value="' . kothar_h((string) ($category['name'] ?? '')) . '">';
echo '<label for="omschrijving">Omschrijving</label><textarea id="omschrijving" name="omschrijving" maxlength="800" rows="3">' . kothar_h((string) ($category['description'] ?? '')) . '</textarea>';
echo '<button type="submit" name="actie" value="meta">Categorie opslaan</button>';
echo '</form>';

$columns = is_array($category['columns'] ?? null) ? $category['columns'] : [];
foreach ($columns as $column) {
    if (!is_array($column)) {
        continue;
    }
    $columnId = (string) ($column['id'] ?? '');
    echo '<section class="panel"><h2>' . kothar_h((string) ($column['name'] ?? '')) . '</h2>';
    echo '<form method="post" class="inline-form">';
    echo kothar_csrf_field();
    echo '<input type="hidden" name="id" value="' . kothar_h($id) . '">';
    echo '<input type="hidden" name="kolom" value="' . kothar_h($columnId) . '">';
    echo '<label>Naam <input name="kolomnaam" required maxlength="120" value="' . kothar_h((string) ($column['name'] ?? '')) . '"></label>';
    echo '<label>Hint <input name="hint" maxlength="80" value="' . kothar_h((string) ($column['hint'] ?? '')) . '"></label>';
    $columnName = trim((string) ($column['name'] ?? ''));
    $columnConfirm = $columnName === ''
        ? 'Weet je zeker dat je deze kolom wilt verwijderen?'
        : 'Weet je zeker dat je de kolom ' . $columnName . ' wilt verwijderen?';
    echo '<button type="submit" name="actie" value="kolom-naam">Hernoem</button>';
    echo '<button type="submit" name="actie" value="kolom-omhoog">Omhoog</button>';
    echo '<button type="submit" name="actie" value="kolom-omlaag">Omlaag</button>';
    echo '<button type="button" data-confirm="' . kothar_h($columnConfirm) . '" data-actie="kolom-verwijder">Verwijder kolom</button>';
    echo '</form>';

    $options = is_array($column['options'] ?? null) ? $column['options'] : [];
    if ($options === []) {
        echo '<p class="hint">Nog geen opties.</p>';
    }
    foreach ($options as $option) {
        if (!is_array($option)) {
            continue;
        }
        echo '<form method="post" class="option-row">';
        echo kothar_csrf_field();
        echo '<input type="hidden" name="id" value="' . kothar_h($id) . '">';
        echo '<input type="hidden" name="kolom" value="' . kothar_h($columnId) . '">';
        echo '<input type="hidden" name="optie" value="' . kothar_h((string) ($option['id'] ?? '')) . '">';
        echo '<label>Label <input name="label" required maxlength="160" value="' . kothar_h((string) ($option['label'] ?? '')) . '"></label>';
        echo '<label>Code <input name="code" maxlength="40" value="' . kothar_h((string) ($option['code'] ?? '')) . '"></label>';
        echo '<label>Omschrijving <input name="omschrijving" maxlength="800" value="' . kothar_h((string) ($option['description'] ?? '')) . '"></label>';
        $optionLabel = trim((string) ($option['label'] ?? ''));
        $optionConfirm = $optionLabel === ''
            ? 'Weet je zeker dat je deze optie wilt verwijderen?'
            : 'Weet je zeker dat je de optie ' . $optionLabel . ' wilt verwijderen?';
        echo '<button type="submit" name="actie" value="optie-bewaar">Opslaan</button>';
        echo '<button type="submit" name="actie" value="optie-omhoog">Omhoog</button>';
        echo '<button type="submit" name="actie" value="optie-omlaag">Omlaag</button>';
        echo '<button type="button" data-confirm="' . kothar_h($optionConfirm) . '" data-actie="optie-verwijder">Verwijder</button>';
        echo '</form>';
    }

    echo '<form method="post" class="option-row">';
    echo kothar_csrf_field();
    echo '<input type="hidden" name="id" value="' . kothar_h($id) . '">';
    echo '<input type="hidden" name="kolom" value="' . kothar_h($columnId) . '">';
    echo '<label>Label <input name="label" maxlength="160" placeholder="Nieuw label"></label>';
    echo '<label>Code <input name="code" maxlength="40" placeholder="Code"></label>';
    echo '<label>Omschrijving <input name="omschrijving" maxlength="800" placeholder="Omschrijving"></label>';
    echo '<button type="submit" name="actie" value="optie-nieuw">Optie toevoegen</button>';
    echo '</form></section>';
}

echo '<section class="panel"><h2>Nieuwe kolom</h2>';
echo '<form method="post" class="inline-form">';
echo kothar_csrf_field();
echo '<input type="hidden" name="id" value="' . kothar_h($id) . '">';
echo '<label>Naam <input name="kolomnaam" required maxlength="120"></label>';
echo '<label>Hint <input name="hint" maxlength="80"></label>';
echo '<button type="submit" name="actie" value="kolom-nieuw">Kolom toevoegen</button>';
echo '</form></section>';
kothar_page_close();
