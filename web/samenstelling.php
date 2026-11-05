<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';

try {
    $categories = kothar_load_categories();
    $doc = kothar_load_compositions();
} catch (Throwable $error) {
    http_response_code(500);
    kothar_page_open(LOC('kothar.detail.title'));
    echo '<h1>' . kothar_h(LOC('kothar.error.unavailable')) . '</h1><p>' . kothar_h($error->getMessage()) . '</p>';
    kothar_page_close();
    exit;
}

$id = (string) ($_REQUEST['id'] ?? '');
$numberQuery = trim((string) ($_REQUEST['nummer'] ?? ''));
$row = $id !== '' ? kothar_find_composition_by_id($doc, $id) : null;
if ($row === null && $numberQuery !== '') {
    $row = kothar_find_by_number($doc['compositions'], $numberQuery);
    if ($row !== null) {
        kothar_redirect('samenstelling.php?id=' . rawurlencode((string) $row['id']));
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $row !== null) {
    kothar_csrf_check();
    $action = (string) ($_POST['actie'] ?? '');
    $compositionId = (string) $row['id'];
    if ($action === 'prijs') {
        $price = kothar_parse_price((string) ($_POST['prijs'] ?? ''));
        if ($price === null) {
            kothar_flash(LOC('kothar.build.price_invalid'), 'warn');
        } else {
            kothar_update_composition($compositionId, static function (array $current) use ($price): array {
                $current['price'] = $price;

                return $current;
            });
            kothar_flash(LOC('kothar.detail.price_saved'));
        }
    } elseif ($action === 'upload') {
        $file = $_FILES['bijlage'] ?? null;
        if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            kothar_flash(LOC('kothar.detail.upload_failed'), 'warn');
        } elseif ((int) ($file['size'] ?? 0) > 8 * 1024 * 1024) {
            kothar_flash(LOC('kothar.detail.too_big'), 'warn');
        } else {
            $stored = kothar_safe_attachment_name((string) ($file['name'] ?? ''));
            $tmp = (string) ($file['tmp_name'] ?? '');
            if ($stored === null || !is_uploaded_file($tmp)) {
                kothar_flash(LOC('kothar.detail.bad_type'), 'warn');
            } else {
                $dir = kothar_attachments_dir($compositionId);
                if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
                    kothar_flash(LOC('kothar.detail.dir_unwritable'), 'warn');
                } elseif (!move_uploaded_file($tmp, $dir . '/' . $stored)) {
                    kothar_flash(LOC('kothar.detail.save_failed'), 'warn');
                } else {
                    $original = basename(str_replace('\\', '/', (string) $file['name']));
                    $user = kothar_current_user();
                    kothar_update_composition($compositionId, static function (array $current) use ($stored, $original, $file, $user): array {
                        $list = is_array($current['attachments'] ?? null) ? $current['attachments'] : [];
                        $list[] = [
                            'id' => 'a_' . bin2hex(random_bytes(4)),
                            'name' => $original,
                            'stored' => $stored,
                            'size' => (int) ($file['size'] ?? 0),
                            'uploadedAt' => date('c'),
                            'uploadedBy' => $user['email'],
                        ];
                        $current['attachments'] = $list;

                        return $current;
                    });
                    kothar_flash(LOC('kothar.detail.attached'));
                }
            }
        }
    } elseif ($action === 'verwijder-bijlage') {
        $attachmentId = (string) ($_POST['bijlage'] ?? '');
        $removed = null;
        kothar_update_composition($compositionId, static function (array $current) use ($attachmentId, &$removed): array {
            $list = is_array($current['attachments'] ?? null) ? $current['attachments'] : [];
            $kept = [];
            foreach ($list as $attachment) {
                if (is_array($attachment) && (string) ($attachment['id'] ?? '') === $attachmentId) {
                    $removed = $attachment;
                    continue;
                }
                $kept[] = $attachment;
            }
            $current['attachments'] = $kept;

            return $current;
        });
        if (is_array($removed)) {
            $stored = (string) ($removed['stored'] ?? '');
            if (preg_match('/^[a-f0-9]{16}\.[a-z0-9]{1,5}$/', $stored) === 1) {
                $path = kothar_attachments_dir($compositionId) . '/' . $stored;
                if (is_file($path)) {
                    unlink($path);
                }
            }
            kothar_flash(LOC('kothar.detail.attachment_deleted'));
        }
    }
    kothar_redirect('samenstelling.php?id=' . rawurlencode($compositionId));
}

if ($row === null && $numberQuery !== '') {
    $parsed = kothar_parse_number($categories['categories'], $numberQuery);
    kothar_page_open(LOC('kothar.index.lookup'));
    echo '<h1>' . kothar_h(LOC('kothar.index.lookup')) . '</h1>';
    echo '<p class="number">' . kothar_h(kothar_canonicalize_number($numberQuery)) . '</p>';
    if ($parsed === []) {
        echo '<p>' . kothar_h(LOC('kothar.detail.no_match')) . '</p>';
        echo '<p><a href="index.php">' . kothar_h(LOC('kothar.back.start')) . '</a></p>';
        kothar_page_close();
        exit;
    }
    $matchCount = count($parsed);
    echo '<p>' . kothar_h($matchCount === 1 ? LOC('kothar.detail.matches_one') : LOC('kothar.detail.matches_many', $matchCount)) . '</p>';
    foreach ($parsed as $hit) {
        echo '<section class="panel"><h2>' . kothar_h($hit['categoryName']) . '</h2>';
        echo '<p class="number">' . kothar_h($hit['number']) . '</p><ul class="choice-list">';
        foreach ($hit['selections'] as $selection) {
            echo '<li><strong>' . kothar_h($selection['columnName']) . '</strong> · ';
            echo kothar_h($selection['label']) . ' <code>' . kothar_h($selection['code']) . '</code>';
            $selectionDescription = trim((string) ($selection['description'] ?? ''));
            if ($selectionDescription !== '') {
                echo '<br><span>' . kothar_h($selectionDescription) . '</span>';
            }
            echo '</li>';
        }
        echo '</ul>';
        $query = ['categorie' => $hit['categoryId']];
        foreach ($hit['selections'] as $selection) {
            $query['keuze'][$selection['columnId']] = $selection['optionId'];
            $selectionCode = (string) ($selection['code'] ?? '');
            $isVector = kothar_parse_hwl_code($selectionCode) !== null || kothar_parse_diameter_code($selectionCode) !== null;
            $isQuantity = $selectionCode !== ''
                && preg_match('/^\d+$/', $selectionCode) === 1
                && trim((string) ($selection['description'] ?? '')) === '';
            if ($isVector || $isQuantity) {
                $query['invul'][$selection['columnId']] = $selectionCode;
            }
        }
        echo '<p><a class="button" href="bouwen.php?' . kothar_h(http_build_query($query)) . '">' . kothar_h(LOC('kothar.detail.open_builder')) . '</a></p>';
        echo '</section>';
    }
    kothar_page_close();
    exit;
}

if ($row === null) {
    http_response_code(404);
    kothar_page_open(LOC('kothar.detail.title'));
    echo '<h1>' . kothar_h(LOC('kothar.detail.not_found')) . '</h1><p><a href="samenstellingen.php">' . kothar_h(LOC('kothar.back.list')) . '</a></p>';
    kothar_page_close();
    exit;
}

$registrant = is_array($row['registrant'] ?? null) ? $row['registrant'] : [];
$selections = is_array($row['selections'] ?? null) ? $row['selections'] : [];
$attachments = is_array($row['attachments'] ?? null) ? $row['attachments'] : [];
$number = (string) ($row['number'] ?? '');

kothar_page_open($number);
echo '<p class="crumb"><a href="samenstellingen.php">' . kothar_h(LOC('kothar.nav.compositions')) . '</a></p>';
echo '<h1 class="number">' . kothar_h($number) . '</h1>';
echo '<p>' . kothar_h((string) ($row['categoryName'] ?? '')) . '</p>';
echo '<div data-barcode="' . kothar_h($number) . '" class="barcode"></div>';

echo '<section><h2>' . kothar_h(LOC('kothar.detail.options')) . '</h2>';
echo '<table class="grid"><thead><tr><th>' . kothar_h(LOC('kothar.detail.col.column')) . '</th><th>' . kothar_h(LOC('kothar.detail.col.option')) . '</th><th>' . kothar_h(LOC('kothar.detail.col.code')) . '</th><th>' . kothar_h(LOC('kothar.detail.col.description')) . '</th></tr></thead><tbody>';
foreach ($selections as $selection) {
    if (!is_array($selection)) {
        continue;
    }
    echo '<tr><td>' . kothar_h((string) ($selection['columnName'] ?? '')) . '</td>';
    echo '<td>' . kothar_h((string) ($selection['label'] ?? '')) . '</td>';
    echo '<td><code>' . kothar_h((string) ($selection['code'] ?? '')) . '</code></td>';
    echo '<td>' . kothar_h((string) ($selection['description'] ?? '')) . '</td></tr>';
}
echo '</tbody></table></section>';

echo '<section class="split">';
echo '<div><h2>' . kothar_h(LOC('kothar.list.col.by')) . '</h2>';
echo '<p>' . kothar_h((string) ($registrant['name'] ?? '')) . '<br>' . kothar_h((string) ($registrant['email'] ?? '')) . '</p>';
echo '<p class="hint">' . kothar_h((string) ($row['createdAt'] ?? '')) . '</p></div>';
echo '<div><h2>' . kothar_h(LOC('kothar.detail.price')) . '</h2>';
echo '<form method="post" class="inline-form">';
echo kothar_csrf_field();
echo '<input type="hidden" name="id" value="' . kothar_h((string) $row['id']) . '">';
echo '<label class="sr" for="prijs">' . kothar_h(LOC('kothar.detail.price')) . '</label>';
$priceRaw = number_format((float) ($row['price'] ?? 0), 2, ',', '');
echo '<input id="prijs" name="prijs" inputmode="decimal" value="' . kothar_h($priceRaw) . '" required>';
echo '<button type="submit" name="actie" value="prijs">' . kothar_h(LOC('kothar.detail.save_price')) . '</button>';
echo '</form></div></section>';

echo '<section><h2>' . kothar_h(LOC('kothar.detail.attachments')) . '</h2>';
if ($attachments === []) {
    echo '<p>' . kothar_h(LOC('kothar.detail.no_attachments')) . '</p>';
} else {
    echo '<ul class="files">';
    foreach ($attachments as $attachment) {
        if (!is_array($attachment)) {
            continue;
        }
        $href = 'bijlage.php?id=' . rawurlencode((string) $row['id']) . '&bestand=' . rawurlencode((string) ($attachment['id'] ?? ''));
        echo '<li><a href="' . kothar_h($href) . '">' . kothar_h((string) ($attachment['name'] ?? LOC('kothar.detail.attachment_fallback'))) . '</a>';
        echo '<form method="post" class="inline-form">';
        echo kothar_csrf_field();
        echo '<input type="hidden" name="id" value="' . kothar_h((string) $row['id']) . '">';
        echo '<input type="hidden" name="bijlage" value="' . kothar_h((string) ($attachment['id'] ?? '')) . '">';
        echo '<button type="submit" name="actie" value="verwijder-bijlage">' . kothar_h(LOC('kothar.admin.delete')) . '</button>';
        echo '</form></li>';
    }
    echo '</ul>';
}
echo '<form method="post" enctype="multipart/form-data" class="inline-form">';
echo kothar_csrf_field();
echo '<input type="hidden" name="id" value="' . kothar_h((string) $row['id']) . '">';
echo '<label for="bijlage">' . kothar_h(LOC('kothar.detail.file')) . '</label>';
echo '<input id="bijlage" type="file" name="bijlage" required>';
echo '<button type="submit" name="actie" value="upload">' . kothar_h(LOC('kothar.detail.upload')) . '</button>';
echo '</form>';
echo '<p class="hint">' . kothar_h(LOC('kothar.detail.file_hint')) . '</p>';
echo '</section>';

$query = ['categorie' => (string) ($row['categoryId'] ?? '')];
foreach ($selections as $selection) {
    if (!is_array($selection)) {
        continue;
    }
    $query['keuze'][(string) ($selection['columnId'] ?? '')] = (string) ($selection['optionId'] ?? '');
}
if (($query['categorie'] ?? '') !== '') {
    echo '<p><a href="bouwen.php?' . kothar_h(http_build_query($query)) . '">' . kothar_h(LOC('kothar.detail.rebuild')) . '</a></p>';
}
kothar_page_close();
