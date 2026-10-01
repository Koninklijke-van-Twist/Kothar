<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';

try {
    $doc = kothar_load_compositions();
} catch (Throwable $error) {
    http_response_code(500);
    kothar_page_open('Samenstellingen');
    echo '<h1>Niet beschikbaar</h1><p>' . kothar_h($error->getMessage()) . '</p>';
    kothar_page_close();
    exit;
}

$rows = $doc['compositions'];
usort($rows, static function ($a, $b): int {
    $left = is_array($a) ? (string) ($a['createdAt'] ?? '') : '';
    $right = is_array($b) ? (string) ($b['createdAt'] ?? '') : '';

    return $right <=> $left;
});

kothar_page_open('Samenstellingen');
echo '<h1>Opgeslagen samenstellingen</h1>';
if ($rows === []) {
    echo '<p>Er is nog niets opgeslagen. <a href="index.php">Stel er een samen</a>.</p>';
    kothar_page_close();
    exit;
}
echo '<table class="grid"><thead><tr><th>Nummer</th><th>Categorie</th><th>Prijs</th><th>Geregistreerd door</th></tr></thead><tbody>';
foreach ($rows as $row) {
    if (!is_array($row)) {
        continue;
    }
    $href = 'samenstelling.php?id=' . rawurlencode((string) ($row['id'] ?? ''));
    $registrant = is_array($row['registrant'] ?? null) ? $row['registrant'] : [];
    echo '<tr><td><a class="number" href="' . kothar_h($href) . '">' . kothar_h((string) ($row['number'] ?? '')) . '</a></td>';
    echo '<td>' . kothar_h((string) ($row['categoryName'] ?? '')) . '</td>';
    echo '<td>' . kothar_h(kothar_format_price((float) ($row['price'] ?? 0))) . '</td>';
    echo '<td>' . kothar_h((string) ($registrant['name'] ?? '')) . '</td></tr>';
}
echo '</tbody></table>';
kothar_page_close();
