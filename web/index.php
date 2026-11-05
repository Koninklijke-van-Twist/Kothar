<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';

try {
    $doc = kothar_load_categories();
} catch (Throwable $error) {
    http_response_code(500);
    kothar_page_open(LOC('kothar.app.title'));
    echo '<h1>' . kothar_h(LOC('kothar.index.categories_unavailable')) . '</h1>';
    echo '<p>' . kothar_h($error->getMessage()) . '</p>';
    kothar_page_close();
    exit;
}

$categories = $doc['categories'];
kothar_page_open(LOC('kothar.app.title'));
echo '<h1>' . kothar_h(LOC('kothar.build.title')) . '</h1>';
echo '<p class="lead">' . kothar_h(LOC('kothar.index.lead')) . '</p>';

echo '<section class="panel"><h2>' . kothar_h(LOC('kothar.index.lookup')) . '</h2>';
echo '<form method="get" action="samenstelling.php" class="inline-form">';
echo '<label for="nummer">' . kothar_h(LOC('kothar.build.number')) . '</label>';
echo '<input id="nummer" name="nummer" required placeholder="' . kothar_h(LOC('kothar.index.number_placeholder')) . '" autocomplete="off">';
echo '<button type="submit">' . kothar_h(LOC('kothar.index.search')) . '</button>';
echo '</form>';
echo '<p class="hint">' . kothar_h(LOC('kothar.index.lookup_hint')) . '</p>';
echo '</section>';

echo '<section><h2>' . kothar_h(LOC('kothar.index.categories')) . '</h2>';
if ($categories === []) {
    echo '<p>' . kothar_h(LOC('kothar.index.empty')) . '</p>';
} else {
    echo '<ul class="cards">';
    foreach ($categories as $category) {
        if (!is_array($category)) {
            continue;
        }
        $id = (string) ($category['id'] ?? '');
        $href = 'bouwen.php?categorie=' . rawurlencode($id);
        echo '<li><a class="card" href="' . kothar_h($href) . '">';
        echo '<strong>' . kothar_h((string) ($category['name'] ?? '')) . '</strong>';
        echo '<span>' . kothar_h((string) ($category['description'] ?? '')) . '</span>';
        $cols = is_array($category['columns'] ?? null) ? count($category['columns']) : 0;
        echo '<em>' . kothar_h(kothar_column_count($cols)) . '</em>';
        echo '</a></li>';
    }
    echo '</ul>';
}
echo '</section>';
kothar_page_close();
