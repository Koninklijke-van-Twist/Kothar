<?php

declare(strict_types=1);

require_once __DIR__ . '/composition.php';

function kothar_default_data_dir(): string
{
    $fromEnv = getenv('KOTHAR_DATA_DIR');
    if (is_string($fromEnv) && $fromEnv !== '') {
        return $fromEnv;
    }

    return dirname(__DIR__) . '/data';
}

function kothar_seed_file(?string $dir = null): string
{
    $dir = $dir ?? kothar_default_data_dir();
    $beside = $dir . '/categories.seed.json';
    if (is_file($beside)) {
        return $beside;
    }
    $fixtures = dirname($dir) . '/fixtures/categories.seed.json';
    if (is_file($fixtures)) {
        return $fixtures;
    }

    throw new RuntimeException('Geen categories.seed.json gevonden.');
}

function kothar_read_json_file(string $path): ?array
{
    if (!is_file($path)) {
        return null;
    }
    $raw = file_get_contents($path);
    if ($raw === false) {
        throw new RuntimeException('Kan ' . basename($path) . ' niet lezen.');
    }
    if (trim($raw) === '') {
        return null;
    }
    try {
        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $error) {
        throw new RuntimeException('Ongeldige JSON in ' . basename($path) . '.', 0, $error);
    }
    if (!is_array($data)) {
        throw new RuntimeException('Ongeldige JSON in ' . basename($path) . '.');
    }

    return $data;
}

function kothar_write_json_file(string $path, array $data): void
{
    $dir = dirname($path);
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('Kan de datamap niet aanmaken.');
    }
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    $tmp = $path . '.tmp';
    if (file_put_contents($tmp, $json . "\n", LOCK_EX) === false) {
        throw new RuntimeException('Kan ' . basename($path) . ' niet schrijven. De map moet schrijfbaar zijn.');
    }
    if (!rename($tmp, $path)) {
        @unlink($tmp);
        throw new RuntimeException('Kan ' . basename($path) . ' niet vervangen.');
    }
}

/**
 * @template T
 * @param callable(): T $fn
 * @return T
 */
function kothar_with_file_lock(string $target, callable $fn)
{
    $dir = dirname($target);
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('Kan de datamap niet aanmaken.');
    }
    $handle = fopen($target . '.lock', 'c');
    if ($handle === false) {
        throw new RuntimeException('Kan geen slot openen voor ' . basename($target) . '.');
    }
    try {
        if (!flock($handle, LOCK_EX)) {
            throw new RuntimeException('Kan ' . basename($target) . ' niet vergrendelen.');
        }

        return $fn();
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}

function kothar_blank_category_document(): array
{
    return [
        'version' => 1,
        'source' => '',
        'rules' => [
            'status' => 'later',
            'note' => 'Compatibiliteit tussen opties, verplichte keuzes en notities op de infopagina zijn nog niet geïmplementeerd.',
        ],
        'categories' => [],
    ];
}

function kothar_normalize_category_document(?array $doc): array
{
    if ($doc === null) {
        return kothar_blank_category_document();
    }
    if (!isset($doc['categories']) || !is_array($doc['categories'])) {
        throw new RuntimeException('categories.json mist een categorieënlijst.');
    }
    if (!isset($doc['rules']) || !is_array($doc['rules'])) {
        $doc['rules'] = kothar_blank_category_document()['rules'];
    }
    if (!isset($doc['version'])) {
        $doc['version'] = 1;
    }

    return $doc;
}

function kothar_load_categories(?string $dir = null): array
{
    $dir = $dir ?? kothar_default_data_dir();
    $path = $dir . '/categories.json';

    return kothar_with_file_lock($path, static function () use ($path, $dir): array {
        $existing = kothar_read_json_file($path);
        if ($existing === null) {
            $seed = kothar_read_json_file(kothar_seed_file($dir));
            if ($seed === null) {
                throw new RuntimeException('De seed is leeg.');
            }
            $doc = kothar_normalize_category_document($seed);
            kothar_write_json_file($path, $doc);

            return $doc;
        }

        return kothar_normalize_category_document($existing);
    });
}

function kothar_save_categories(array $doc, ?string $dir = null): void
{
    $dir = $dir ?? kothar_default_data_dir();
    $path = $dir . '/categories.json';
    $doc = kothar_normalize_category_document($doc);
    kothar_with_file_lock($path, static function () use ($path, $doc): void {
        kothar_write_json_file($path, $doc);
    });
}

function kothar_load_compositions(?string $dir = null): array
{
    $dir = $dir ?? kothar_default_data_dir();
    $path = $dir . '/compositions.json';
    $doc = kothar_read_json_file($path);
    if ($doc === null) {
        return ['compositions' => []];
    }
    if (!isset($doc['compositions']) || !is_array($doc['compositions'])) {
        throw new RuntimeException('compositions.json mist een lijst.');
    }

    return $doc;
}

function kothar_save_compositions(array $doc, ?string $dir = null): void
{
    $dir = $dir ?? kothar_default_data_dir();
    if (!isset($doc['compositions']) || !is_array($doc['compositions'])) {
        throw new RuntimeException('compositions.json mist een lijst.');
    }
    kothar_write_json_file($dir . '/compositions.json', $doc);
}

/**
 * @param array<string, mixed> $draft
 * @return array{created: bool, composition: array<string, mixed>}
 */
function kothar_register_composition(array $draft, ?string $dir = null): array
{
    $dir = $dir ?? kothar_default_data_dir();
    $path = $dir . '/compositions.json';
    $number = kothar_canonicalize_number((string) ($draft['number'] ?? ''));
    if ($number === '') {
        throw new InvalidArgumentException('Een samenstelling heeft een nummer nodig.');
    }

    return kothar_with_file_lock($path, static function () use ($path, $draft, $number): array {
        $doc = kothar_read_json_file($path);
        if ($doc === null) {
            $doc = ['compositions' => []];
        }
        if (!isset($doc['compositions']) || !is_array($doc['compositions'])) {
            throw new RuntimeException('compositions.json mist een lijst.');
        }
        $existing = kothar_find_by_number($doc['compositions'], $number);
        if ($existing !== null) {
            return ['created' => false, 'composition' => $existing];
        }
        $now = date('c');
        $composition = [
            'id' => 'c_' . bin2hex(random_bytes(8)),
            'number' => $number,
            'categoryId' => (string) ($draft['categoryId'] ?? ''),
            'categoryName' => (string) ($draft['categoryName'] ?? ''),
            'price' => (float) ($draft['price'] ?? 0),
            'selections' => is_array($draft['selections'] ?? null) ? array_values($draft['selections']) : [],
            'registrant' => is_array($draft['registrant'] ?? null) ? $draft['registrant'] : ['name' => '', 'email' => ''],
            'createdAt' => $now,
            'updatedAt' => $now,
            'attachments' => [],
        ];
        $doc['compositions'][] = $composition;
        kothar_write_json_file($path, $doc);

        return ['created' => true, 'composition' => $composition];
    });
}

function kothar_find_category(array $doc, string $id): ?array
{
    foreach ($doc['categories'] ?? [] as $category) {
        if (is_array($category) && (string) ($category['id'] ?? '') === $id) {
            return $category;
        }
    }

    return null;
}

function kothar_find_composition_by_id(array $doc, string $id): ?array
{
    foreach ($doc['compositions'] ?? [] as $row) {
        if (is_array($row) && (string) ($row['id'] ?? '') === $id) {
            return $row;
        }
    }

    return null;
}

function kothar_new_id(string $prefix): string
{
    return $prefix . bin2hex(random_bytes(4));
}

function kothar_move_item(array $items, int $index, int $direction): array
{
    $target = $index + $direction;
    if ($index < 0 || $index >= count($items) || $target < 0 || $target >= count($items)) {
        return $items;
    }
    $item = $items[$index];
    $items[$index] = $items[$target];
    $items[$target] = $item;

    return array_values($items);
}

function kothar_attachments_dir(string $compositionId, ?string $dir = null): string
{
    if (!preg_match('/^c_[a-f0-9]{16}$/', $compositionId)) {
        throw new RuntimeException('Ongeldig samenstellings-id.');
    }
    $dir = $dir ?? kothar_default_data_dir();

    return $dir . '/attachments/' . $compositionId;
}

function kothar_safe_attachment_name(string $original): ?string
{
    $base = basename(str_replace('\\', '/', $original));
    $ext = strtolower(pathinfo($base, PATHINFO_EXTENSION));
    $allowed = ['pdf', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'txt', 'csv', 'xls', 'xlsx', 'doc', 'docx', 'dwg', 'zip'];
    if (!in_array($ext, $allowed, true)) {
        return null;
    }
    if (str_contains(strtolower($base), '.php')) {
        return null;
    }

    return bin2hex(random_bytes(8)) . '.' . $ext;
}

/**
 * @return array<string, mixed>|null
 */
function kothar_update_composition(string $id, callable $mutator, ?string $dir = null): ?array
{
    $dir = $dir ?? kothar_default_data_dir();
    $path = $dir . '/compositions.json';

    return kothar_with_file_lock($path, static function () use ($path, $id, $mutator): ?array {
        $doc = kothar_read_json_file($path);
        if ($doc === null || !isset($doc['compositions']) || !is_array($doc['compositions'])) {
            return null;
        }
        foreach ($doc['compositions'] as $index => $row) {
            if (!is_array($row) || (string) ($row['id'] ?? '') !== $id) {
                continue;
            }
            $updated = $mutator($row);
            if (!is_array($updated)) {
                return null;
            }
            $updated['updatedAt'] = date('c');
            $doc['compositions'][$index] = $updated;
            kothar_write_json_file($path, $doc);

            return $updated;
        }

        return null;
    });
}
