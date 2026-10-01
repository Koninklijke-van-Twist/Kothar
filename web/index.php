<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';

try {
    $doc = kothar_load_categories();
} catch (Throwable $error) {
    http_response_code(500);
    kothar_page_open('Kothar');
    echo '<h1>Categorieën niet beschikbaar</h1>';
    echo '<p>' . kothar_h($error->getMessage()) . '</p>';
    kothar_page_close();
    exit;
}

$categories = $doc['categories'];
kothar_page_open('Kothar');
echo '<h1>Samenstellen</h1>';
echo '<p class="lead">Kies per kolom één optie. Het samenstellingsnummer is de gekozen codes, gescheiden door een punt.</p>';

echo '<section class="panel"><h2>Nummer opzoeken</h2>';
echo '<form method="get" action="samenstelling.php" class="inline-form">';
echo '<label for="nummer">Samenstellingsnummer</label>';
echo '<input id="nummer" name="nummer" required placeholder="bijvoorbeeld I.2.20" autocomplete="off">';
echo '<button type="submit">Zoek</button>';
echo '</form>';
echo '<p class="hint">Een bestaand nummer opent de samenstelling. Een nieuw nummer wordt opgebouwd als de codes bij een categorie passen.</p>';
echo '</section>';

echo '<section><h2>Categorieën</h2>';
if ($categories === []) {
    echo '<p>Er zijn nog geen categorieën.</p>';
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
        echo '<em>' . $cols . ' kolommen</em>';
        echo '</a></li>';
    }
    echo '</ul>';
}
echo '</section>';
kothar_page_close();
