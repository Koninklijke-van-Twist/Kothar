<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';

kothar_require_admin();

try {
    $doc = kothar_load_categories();
} catch (Throwable $error) {
    http_response_code(500);
    kothar_page_open(LOC('kothar.nav.admin'));
    echo '<h1>' . kothar_h(LOC('kothar.error.unavailable')) . '</h1><p>' . kothar_h($error->getMessage()) . '</p>';
    kothar_page_close();
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    kothar_csrf_check();
    $action = (string) ($_POST['actie'] ?? '');
    $id = (string) ($_POST['id'] ?? '');
    $categories = $doc['categories'];
    $index = null;
    foreach ($categories as $i => $category) {
        if (is_array($category) && (string) ($category['id'] ?? '') === $id) {
            $index = (int) $i;
            break;
        }
    }
    if ($action === 'nieuw') {
        $name = trim((string) ($_POST['naam'] ?? ''));
        $description = trim((string) ($_POST['omschrijving'] ?? ''));
        if ($name === '' || mb_strlen($name) > 120) {
            kothar_flash(LOC('kothar.admin.name_length'), 'warn');
        } else {
            $categories[] = [
                'id' => kothar_new_id('cat-'),
                'name' => $name,
                'description' => mb_substr($description, 0, 800),
                'rules' => [],
                'columns' => [],
            ];
            $doc['categories'] = $categories;
            kothar_save_categories($doc);
            kothar_flash(LOC('kothar.admin.added'));
        }
    } elseif ($index === null) {
        kothar_flash(LOC('kothar.admin.not_found'), 'warn');
    } elseif ($action === 'verwijder') {
        array_splice($categories, $index, 1);
        $doc['categories'] = array_values($categories);
        kothar_save_categories($doc);
        kothar_flash(LOC('kothar.admin.deleted'));
    } elseif ($action === 'omhoog' || $action === 'omlaag') {
        $doc['categories'] = kothar_move_item($categories, $index, $action === 'omhoog' ? -1 : 1);
        kothar_save_categories($doc);
    }
    kothar_redirect('beheer.php');
}

kothar_page_open(LOC('kothar.nav.admin'));
echo '<h1>' . kothar_h(LOC('kothar.admin.heading')) . '</h1>';
echo '<p class="lead">' . kothar_h(LOC('kothar.admin.lead')) . '</p>';

if ($doc['categories'] === []) {
    echo '<p>' . kothar_h(LOC('kothar.admin.empty')) . '</p>';
} else {
    echo '<ol class="admin-list">';
    foreach ($doc['categories'] as $category) {
        if (!is_array($category)) {
            continue;
        }
        $id = (string) ($category['id'] ?? '');
        $href = 'beheer_categorie.php?id=' . rawurlencode($id);
        echo '<li><a href="' . kothar_h($href) . '"><strong>' . kothar_h((string) ($category['name'] ?? '')) . '</strong></a>';
        $columnCount = count(is_array($category['columns'] ?? null) ? $category['columns'] : []);
        echo '<span class="hint">' . kothar_h(kothar_column_count($columnCount)) . '</span>';
        echo '<form method="post" class="inline-form">';
        echo kothar_csrf_field();
        echo '<input type="hidden" name="id" value="' . kothar_h($id) . '">';
        $name = trim((string) ($category['name'] ?? ''));
        $confirm = $name === ''
            ? LOC('kothar.admin.confirm_delete')
            : LOC('kothar.admin.confirm_delete_named', $name);
        echo '<button type="submit" name="actie" value="omhoog">' . kothar_h(LOC('kothar.admin.up')) . '</button>';
        echo '<button type="submit" name="actie" value="omlaag">' . kothar_h(LOC('kothar.admin.down')) . '</button>';
        echo '<button type="button" data-confirm="' . kothar_h($confirm) . '" data-actie="verwijder" data-confirm-note="' . kothar_h(LOC('kothar.admin.delete_note')) . '">' . kothar_h(LOC('kothar.admin.delete')) . '</button>';
        echo '</form></li>';
    }
    echo '</ol>';
}

echo '<section class="panel"><h2>' . kothar_h(LOC('kothar.admin.new_category')) . '</h2>';
echo '<form method="post" class="stack">';
echo kothar_csrf_field();
echo '<label for="naam">' . kothar_h(LOC('kothar.admin.name')) . '</label><input id="naam" name="naam" required maxlength="120">';
echo '<label for="omschrijving">' . kothar_h(LOC('kothar.admin.description')) . '</label><textarea id="omschrijving" name="omschrijving" maxlength="800" rows="3"></textarea>';
echo '<button type="submit" name="actie" value="nieuw">' . kothar_h(LOC('kothar.admin.add')) . '</button>';
echo '</form></section>';
kothar_page_close();
