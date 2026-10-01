<?php

declare(strict_types=1);

/**
 * Samenstellingsnummers: codes met een punt ertussen, opzoeken en ontdubbelen.
 * Dit bestand heeft geen sessie of schijf nodig, zodat de tests het los kunnen laden.
 */

function kothar_join_codes(array $codes): string
{
    $parts = [];
    foreach ($codes as $code) {
        $code = trim((string) $code);
        if ($code === '') {
            continue;
        }
        $parts[] = $code;
    }

    return implode('.', $parts);
}

function kothar_canonicalize_number(string $number): string
{
    $number = trim($number);
    $replaced = preg_replace('/\s*\.\s*/u', '.', $number);
    if (!is_string($replaced)) {
        return $number;
    }

    return trim($replaced, '.');
}

/**
 * @param array<int, array<string, mixed>> $compositions
 * @return array<string, mixed>|null
 */
function kothar_find_by_number(array $compositions, string $number): ?array
{
    $needle = kothar_canonicalize_number($number);
    if ($needle === '') {
        return null;
    }
    foreach ($compositions as $row) {
        if (!is_array($row)) {
            continue;
        }
        $have = kothar_canonicalize_number((string) ($row['number'] ?? ''));
        if ($have !== '' && $have === $needle) {
            return $row;
        }
    }

    return null;
}

/**
 * @return array<int, string>
 */
function kothar_chars(string $value): array
{
    $chars = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY);
    if ($chars === false) {
        return [];
    }

    return $chars;
}

/**
 * Eet $code van het begin van $remaining. Spaties in code of nummer tellen niet mee,
 * zodat het blad-voorbeeld 05L dezelfde optie raakt als de vette code "05 L".
 * Een punt in de code zelf (zoals 5.2) hoort bij de code. Na de code moet een punt
 * of het einde van het nummer komen.
 *
 * @return string|null de rest, of null als de code hier niet past
 */
function kothar_consume_code(string $remaining, string $code): ?string
{
    $code = trim($code);
    if ($code === '') {
        return null;
    }
    $rchars = kothar_chars($remaining);
    $cchars = kothar_chars($code);
    if ($cchars === []) {
        return null;
    }
    $ri = 0;
    $rc = count($rchars);
    foreach ($cchars as $ch) {
        if ($ch === ' ') {
            continue;
        }
        while ($ri < $rc && $rchars[$ri] === ' ') {
            $ri++;
        }
        if ($ri >= $rc || $rchars[$ri] !== $ch) {
            return null;
        }
        $ri++;
    }
    while ($ri < $rc && $rchars[$ri] === ' ') {
        $ri++;
    }
    if ($ri >= $rc) {
        return '';
    }
    if ($rchars[$ri] === '.') {
        return implode('', array_slice($rchars, $ri + 1));
    }

    return null;
}

/**
 * @param array<string, mixed> $category
 * @return array{categoryId: string, categoryName: string, number: string, selections: array<int, array<string, string>>}|null
 */
function kothar_parse_category_number(array $category, string $number): ?array
{
    $number = kothar_canonicalize_number($number);
    if ($number === '') {
        return null;
    }
    $columns = $category['columns'] ?? null;
    if (!is_array($columns) || $columns === []) {
        return null;
    }

    $remaining = $number;
    $selections = [];
    foreach ($columns as $column) {
        if (!is_array($column)) {
            return null;
        }
        $options = $column['options'] ?? null;
        if (!is_array($options)) {
            return null;
        }
        $coded = [];
        foreach ($options as $index => $option) {
            if (!is_array($option)) {
                continue;
            }
            $code = trim((string) ($option['code'] ?? ''));
            if ($code === '') {
                continue;
            }
            $coded[] = ['index' => (int) $index, 'option' => $option, 'code' => $code, 'length' => mb_strlen($code)];
        }
        if ($coded === []) {
            continue;
        }
        usort($coded, static function (array $a, array $b): int {
            if ($a['length'] === $b['length']) {
                return $a['index'] <=> $b['index'];
            }

            return $b['length'] <=> $a['length'];
        });

        $matched = null;
        $next = null;
        foreach ($coded as $candidate) {
            $rest = kothar_consume_code($remaining, $candidate['code']);
            if ($rest === null) {
                continue;
            }
            $matched = $candidate;
            $next = $rest;
            break;
        }
        if ($matched === null || $next === null) {
            return null;
        }
        $option = $matched['option'];
        $selections[] = [
            'columnId' => (string) ($column['id'] ?? ''),
            'columnName' => (string) ($column['name'] ?? ''),
            'optionId' => (string) ($option['id'] ?? ''),
            'label' => (string) ($option['label'] ?? ''),
            'code' => $matched['code'],
            'description' => (string) ($option['description'] ?? ''),
        ];
        $remaining = $next;
    }

    if ($remaining !== '' || $selections === []) {
        return null;
    }

    $rebuilt = kothar_join_codes(array_map(static function (array $row): string {
        return $row['code'];
    }, $selections));

    return [
        'categoryId' => (string) ($category['id'] ?? ''),
        'categoryName' => (string) ($category['name'] ?? ''),
        'number' => $rebuilt,
        'selections' => $selections,
    ];
}

/**
 * @param array<int, array<string, mixed>> $categories
 * @return array<int, array{categoryId: string, categoryName: string, number: string, selections: array<int, array<string, string>>}>
 */
function kothar_parse_number(array $categories, string $number): array
{
    $hits = [];
    foreach ($categories as $category) {
        if (!is_array($category)) {
            continue;
        }
        $parsed = kothar_parse_category_number($category, $number);
        if ($parsed !== null) {
            $hits[] = $parsed;
        }
    }

    return $hits;
}

/**
 * @param array<string, mixed> $category
 * @param array<string, mixed> $choices columnId => optionId
 * @param array<string, mixed> $fills columnId => ingevulde code als de optie geen code heeft
 * @return array{ok: bool, error: string, number: string, selections: array<int, array<string, string>>}
 */
function kothar_build_from_choices(array $category, array $choices, array $fills): array
{
    $fail = static function (string $error): array {
        return ['ok' => false, 'error' => $error, 'number' => '', 'selections' => []];
    };
    $columns = $category['columns'] ?? null;
    if (!is_array($columns) || $columns === []) {
        return $fail('Deze categorie heeft geen kolommen.');
    }

    $codes = [];
    $selections = [];
    foreach ($columns as $column) {
        if (!is_array($column)) {
            return $fail('Ongeldige kolom.');
        }
        $columnId = (string) ($column['id'] ?? '');
        $columnName = (string) ($column['name'] ?? 'Kolom');
        $options = $column['options'] ?? null;
        if (!is_array($options) || $options === []) {
            return $fail('Kolom ' . $columnName . ' heeft geen opties.');
        }
        $picked = trim((string) ($choices[$columnId] ?? ''));
        if ($picked === '' && count($options) === 1 && is_array($options[0])) {
            $picked = (string) ($options[0]['id'] ?? '');
        }
        $option = null;
        foreach ($options as $candidate) {
            if (is_array($candidate) && (string) ($candidate['id'] ?? '') === $picked) {
                $option = $candidate;
                break;
            }
        }
        if ($option === null) {
            return $fail('Kies een optie voor ' . $columnName . '.');
        }
        $code = trim((string) ($option['code'] ?? ''));
        if ($code === '') {
            $code = trim((string) ($fills[$columnId] ?? ''));
            if ($code === '') {
                return $fail('Vul een code in voor ' . $columnName . '.');
            }
            if (str_contains($code, '.')) {
                return $fail('De code voor ' . $columnName . ' mag geen punt bevatten.');
            }
        }
        $codes[] = $code;
        $selections[] = [
            'columnId' => $columnId,
            'columnName' => $columnName,
            'optionId' => (string) ($option['id'] ?? ''),
            'label' => (string) ($option['label'] ?? ''),
            'code' => $code,
            'description' => (string) ($option['description'] ?? ''),
        ];
    }

    $number = kothar_join_codes($codes);
    if ($number === '') {
        return $fail('De samenstelling heeft geen code.');
    }

    return ['ok' => true, 'error' => '', 'number' => $number, 'selections' => $selections];
}

function kothar_parse_price(string $raw): ?float
{
    $raw = trim(str_replace(['€', "\xc2\xa0"], '', $raw));
    if ($raw === '') {
        return 0.0;
    }
    $raw = str_replace(' ', '', $raw);
    if (str_contains($raw, ',') && str_contains($raw, '.')) {
        $raw = str_replace('.', '', $raw);
        $raw = str_replace(',', '.', $raw);
    } elseif (str_contains($raw, ',')) {
        $raw = str_replace(',', '.', $raw);
    }
    if (!is_numeric($raw)) {
        return null;
    }
    $value = round((float) $raw, 2);
    if ($value < 0 || $value > 100000000) {
        return null;
    }

    return $value;
}

function kothar_format_price(float $value): string
{
    return '€ ' . number_format($value, 2, ',', '.');
}

function kothar_parse_quantity(string $raw): ?int
{
    $raw = trim($raw);
    if ($raw === '' || !preg_match('/^\d+$/', $raw)) {
        return null;
    }
    $qty = (int) $raw;
    if ($qty < 1 || $qty > 9999) {
        return null;
    }

    return $qty;
}
