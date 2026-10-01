<?php

declare(strict_types=1);

/**
 * Samenstellingsnummers: codes met een punt ertussen, opzoeken en ontdubbelen.
 * Dit bestand heeft geen sessie of schijf nodig, zodat de tests het los kunnen laden.
 */

/**
 * Optie- en kolomcodes horen zonder witruimte. Alle spaties, tabs en
 * regeleinden gaan eruit; daarna wordt op $max tekens afgekapt.
 */
function kothar_clean_code(string $value, int $max = 40): string
{
    $value = preg_replace('/\s+/u', '', $value) ?? '';
    if ($max >= 0 && mb_strlen($value) > $max) {
        $value = mb_substr($value, 0, $max);
    }

    return $value;
}

/**
 * @param array<string, mixed> $column
 * @return array<int, array<string, mixed>>
 */
function kothar_column_options(array $column): array
{
    $source = $column['options'] ?? null;
    if (!is_array($source)) {
        return [];
    }
    $options = [];
    foreach ($source as $option) {
        if (is_array($option)) {
            $options[] = $option;
        }
    }

    return $options;
}

function kothar_option_vector(array $option): string
{
    $label = strtolower((string) preg_replace('/[^a-z]/i', '', (string) ($option['label'] ?? '')));
    if ($label === 'heightxwidthxlength') {
        return 'hwl';
    }
    if ($label === 'diameterxlength') {
        return 'diameter';
    }

    return '';
}

/**
 * choices: gewone kaarten.
 * hwl: alleen hoogte × breedte × lengte.
 * diameter: alleen ⌀ × lengte.
 * dimensions: eerst een van die twee, daarna de bijbehorende maten.
 * quantity: een getal, geen keuzelijst.
 *
 * @param array<string, mixed> $column
 */
function kothar_column_kind(array $column): string
{
    $options = kothar_column_options($column);
    $hwl = 0;
    $diameter = 0;
    foreach ($options as $option) {
        $vector = kothar_option_vector($option);
        if ($vector === 'hwl') {
            $hwl++;
        } elseif ($vector === 'diameter') {
            $diameter++;
        }
    }
    if ($hwl > 0 && $diameter > 0) {
        return 'dimensions';
    }
    if ($hwl === 1 && count($options) === 1) {
        return 'hwl';
    }
    if ($diameter === 1 && count($options) === 1) {
        return 'diameter';
    }
    $name = trim((string) ($column['name'] ?? ''));
    if (preg_match('/^quantity\b/iu', $name) === 1 && count($options) === 1) {
        $code = trim((string) ($options[0]['code'] ?? ''));
        if ($code === '') {
            return 'quantity';
        }
    }

    return 'choices';
}

function kothar_quantity_prompt(array $column): string
{
    $name = (string) ($column['name'] ?? '');
    if (preg_match('/per\s*piece/iu', $name) === 1) {
        return 'Vul quantity per stuk in';
    }

    return 'Vul quantity in';
}

function kothar_column_admin_note(string $kind): string
{
    if ($kind === 'quantity') {
        return 'Geen keuzelijst. De samensteller vult een getal in.';
    }
    if ($kind === 'hwl') {
        return 'Geen keuzelijst. De samensteller vult hoogte, breedte en lengte in meters in. De code wordt bijvoorbeeld H3xB2xL5.';
    }
    if ($kind === 'diameter') {
        return 'Geen keuzelijst. De samensteller vult diameter en lengte in meters in. De code wordt bijvoorbeeld ⌀4xL8.';
    }
    if ($kind === 'dimensions') {
        return 'Geen keuzelijst van codes. De samensteller kiest eerst ⌀×L of H×B×L en vult daarna de maten in meters in.';
    }

    return '';
}

function kothar_meter_token(string $raw): ?string
{
    $raw = str_replace(',', '.', trim($raw));
    $raw = preg_replace('/\s+/u', '', $raw) ?? '';
    if (preg_match('/^\d+(?:\.\d+)?$/', $raw) !== 1) {
        return null;
    }
    if (str_contains($raw, '.')) {
        $raw = rtrim(rtrim($raw, '0'), '.');
    }
    if ($raw === '') {
        return null;
    }

    return $raw;
}

function kothar_format_hwl(string $height, string $width, string $length): ?string
{
    $height = kothar_meter_token($height);
    $width = kothar_meter_token($width);
    $length = kothar_meter_token($length);
    if ($height === null || $width === null || $length === null) {
        return null;
    }

    return 'H' . $height . 'xB' . $width . 'xL' . $length;
}

function kothar_format_diameter(string $diameter, string $length): ?string
{
    $diameter = kothar_meter_token($diameter);
    $length = kothar_meter_token($length);
    if ($diameter === null || $length === null) {
        return null;
    }

    return '⌀' . $diameter . 'xL' . $length;
}

/**
 * @return array{h: string, b: string, l: string}|null
 */
function kothar_parse_hwl_code(string $code): ?array
{
    if (preg_match('/^H(\d+(?:\.\d+)?)xB(\d+(?:\.\d+)?)xL(\d+(?:\.\d+)?)$/u', $code, $match) !== 1) {
        return null;
    }

    return ['h' => $match[1], 'b' => $match[2], 'l' => $match[3]];
}

/**
 * @return array{d: string, l: string}|null
 */
function kothar_parse_diameter_code(string $code): ?array
{
    if (preg_match('/^⌀(\d+(?:\.\d+)?)xL(\d+(?:\.\d+)?)$/u', $code, $match) !== 1) {
        return null;
    }

    return ['d' => $match[1], 'l' => $match[2]];
}

/**
 * @param array<int, array<string, mixed>> $options
 * @return array<string, mixed>|null
 */
function kothar_option_for_vector(array $options, string $vector): ?array
{
    foreach ($options as $option) {
        if (kothar_option_vector($option) === $vector) {
            return $option;
        }
    }

    return null;
}

/**
 * @return array{code: string, rest: string}|null
 */
function kothar_take_pattern(string $remaining, string $body): ?array
{
    if (preg_match('/^(' . $body . ')(?=\.|$)/u', $remaining, $hit) !== 1) {
        return null;
    }
    $code = $hit[1];
    $rest = substr($remaining, strlen($code));
    if (strncmp($rest, '.', 1) === 0) {
        $rest = substr($rest, 1);
    }

    return ['code' => $code, 'rest' => $rest];
}

/**
 * @param array<string, mixed> $column
 * @param array<string, mixed> $choices
 * @param array<string, mixed> $fills
 * @param array<string, mixed> $measures
 * @return array<string, mixed>
 */
function kothar_fills_with_measures(array $category, array $choices, array $fills, array $measures): array
{
    $columns = $category['columns'] ?? null;
    if (!is_array($columns)) {
        return $fills;
    }
    foreach ($columns as $column) {
        if (!is_array($column)) {
            continue;
        }
        $id = (string) ($column['id'] ?? '');
        $kind = kothar_column_kind($column);
        $group = $measures[$id] ?? null;
        if (!is_array($group)) {
            $group = [];
        }
        if ($kind === 'quantity') {
            $raw = (string) ($fills[$id] ?? '');
            $fills[$id] = kothar_clean_code($raw);
            continue;
        }
        $composed = null;
        if ($kind === 'hwl') {
            $composed = kothar_format_hwl(
                (string) ($group['h'] ?? ''),
                (string) ($group['b'] ?? ''),
                (string) ($group['l'] ?? '')
            );
        } else        if ($kind === 'diameter') {
            $composed = kothar_format_diameter(
                (string) ($group['d'] ?? ''),
                (string) (($group['dl'] ?? '') !== '' ? $group['dl'] : ($group['l'] ?? ''))
            );
        } elseif ($kind === 'dimensions') {
            $mode = '';
            $picked = trim((string) ($choices[$id] ?? ''));
            foreach (kothar_column_options($column) as $option) {
                if ((string) ($option['id'] ?? '') === $picked) {
                    $mode = kothar_option_vector($option);
                    break;
                }
            }
            if ($mode === '' && $picked === '') {
                $posted = (string) ($fills[$id] ?? '');
                if (kothar_parse_hwl_code($posted) !== null) {
                    $mode = 'hwl';
                } elseif (kothar_parse_diameter_code($posted) !== null) {
                    $mode = 'diameter';
                }
            }
            if ($mode === 'hwl') {
                $composed = kothar_format_hwl(
                    (string) ($group['h'] ?? ''),
                    (string) ($group['b'] ?? ''),
                    (string) ($group['l'] ?? '')
                );
            } elseif ($mode === 'diameter') {
                $composed = kothar_format_diameter(
                    (string) ($group['d'] ?? ''),
                    (string) (($group['dl'] ?? '') !== '' ? $group['dl'] : ($group['l'] ?? ''))
                );
            }
        }
        if ($composed !== null) {
            $fills[$id] = $composed;
        }
    }

    return $fills;
}

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

/**
 * Stabiele kleur bij een codesegment, voor de kaartbalk en de onderstreping
 * van dat segment. Zelfde formule in web/assets/app.js (kotharSegmentColor):
 *
 * trim de code, loop over de UTF-8-bytes:
 *   hash = (hash * 33 + byte) mod 9973
 *   hue  = (hash * 47) mod 360
 *
 * Resultaat is hsl(hue 62% 36%). Dezelfde code krijgt altijd dezelfde kleur,
 * ook in een andere kolom. Een spatie in de code telt mee (05 L ≠ 05L).
 * Een lege code heeft geen kleur.
 */
function kothar_segment_color(string $code): string
{
    $code = trim($code);
    if ($code === '') {
        return '';
    }
    $hash = 0;
    $length = strlen($code);
    for ($i = 0; $i < $length; $i++) {
        $hash = ($hash * 33 + ord($code[$i])) % 9973;
    }
    $hue = ($hash * 47) % 360;

    return 'hsl(' . $hue . ' 62% 36%)';
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
        $kind = kothar_column_kind($column);
        if ($kind === 'quantity') {
            $hit = kothar_take_pattern($remaining, '\d+');
            $only = kothar_column_options($column);
            if ($hit === null || $only === []) {
                return null;
            }
            $option = $only[0];
            $selections[] = [
                'columnId' => (string) ($column['id'] ?? ''),
                'columnName' => (string) ($column['name'] ?? ''),
                'optionId' => (string) ($option['id'] ?? ''),
                'label' => $hit['code'],
                'code' => $hit['code'],
                'description' => '',
            ];
            $remaining = $hit['rest'];
            continue;
        }
        if ($kind === 'hwl' || $kind === 'diameter' || $kind === 'dimensions') {
            $vectorHit = null;
            $vectorMode = '';
            if ($kind === 'hwl' || $kind === 'dimensions') {
                $vectorHit = kothar_take_pattern($remaining, 'H\d+(?:\.\d+)?xB\d+(?:\.\d+)?xL\d+(?:\.\d+)?');
                if ($vectorHit !== null) {
                    $vectorMode = 'hwl';
                }
            }
            if ($vectorHit === null && ($kind === 'diameter' || $kind === 'dimensions')) {
                $vectorHit = kothar_take_pattern($remaining, '⌀\d+(?:\.\d+)?xL\d+(?:\.\d+)?');
                if ($vectorHit !== null) {
                    $vectorMode = 'diameter';
                }
            }
            if ($vectorHit !== null) {
                $option = kothar_option_for_vector(kothar_column_options($column), $vectorMode);
                if ($option === null) {
                    return null;
                }
                $label = $vectorMode === 'hwl' ? 'Hoogte × breedte × lengte' : 'Diameter × lengte';
                $selections[] = [
                    'columnId' => (string) ($column['id'] ?? ''),
                    'columnName' => (string) ($column['name'] ?? ''),
                    'optionId' => (string) ($option['id'] ?? ''),
                    'label' => $label,
                    'code' => $vectorHit['code'],
                    'description' => 'maten in meters',
                ];
                $remaining = $vectorHit['rest'];
                continue;
            }
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
        $kind = kothar_column_kind($column);
        if ($kind === 'quantity') {
            if ($option === null && count($options) === 1 && is_array($options[0])) {
                $option = $options[0];
            }
            if (!is_array($option)) {
                return $fail('Vul quantity in.');
            }
            $code = kothar_clean_code((string) ($fills[$columnId] ?? ''));
            if (preg_match('/^\d+$/', $code) !== 1) {
                return $fail(kothar_quantity_prompt($column) . '.');
            }
            $codes[] = $code;
            $selections[] = [
                'columnId' => $columnId,
                'columnName' => $columnName,
                'optionId' => (string) ($option['id'] ?? ''),
                'label' => $code,
                'code' => $code,
                'description' => '',
            ];
            continue;
        }
        if ($kind === 'hwl' || $kind === 'diameter' || $kind === 'dimensions') {
            $mode = is_array($option) ? kothar_option_vector($option) : '';
            $posted = (string) ($fills[$columnId] ?? '');
            if ($mode === '' && $kind === 'hwl') {
                $mode = 'hwl';
                $option = kothar_option_for_vector(kothar_column_options($column), 'hwl');
            } elseif ($mode === '' && $kind === 'diameter') {
                $mode = 'diameter';
                $option = kothar_option_for_vector(kothar_column_options($column), 'diameter');
            } elseif ($mode === '' && $kind === 'dimensions') {
                if (kothar_parse_hwl_code($posted) !== null) {
                    $mode = 'hwl';
                } elseif (kothar_parse_diameter_code($posted) !== null) {
                    $mode = 'diameter';
                }
                if ($mode !== '') {
                    $option = kothar_option_for_vector(kothar_column_options($column), $mode);
                }
            }
            if ($mode !== 'hwl' && $mode !== 'diameter') {
                return $fail('Kies ⌀×L of H×B×L voor ' . $columnName . '.');
            }
            if (!is_array($option)) {
                return $fail('Kies ⌀×L of H×B×L voor ' . $columnName . '.');
            }
            $parsedVector = $mode === 'hwl' ? kothar_parse_hwl_code($posted) : kothar_parse_diameter_code($posted);
            if ($parsedVector === null) {
                $message = $mode === 'hwl'
                    ? 'Vul hoogte, breedte en lengte in meters in.'
                    : 'Vul diameter en lengte in meters in.';
                return $fail($message);
            }
            $code = $mode === 'hwl'
                ? kothar_format_hwl($parsedVector['h'], $parsedVector['b'], $parsedVector['l'])
                : kothar_format_diameter($parsedVector['d'], $parsedVector['l']);
            if ($code === null) {
                return $fail('Vul de maten in meters in.');
            }
            $codes[] = $code;
            $selections[] = [
                'columnId' => $columnId,
                'columnName' => $columnName,
                'optionId' => (string) ($option['id'] ?? ''),
                'label' => $mode === 'hwl' ? 'Hoogte × breedte × lengte' : 'Diameter × lengte',
                'code' => $code,
                'description' => 'maten in meters',
            ];
            continue;
        }
        if ($option === null) {
            return $fail('Kies een optie voor ' . $columnName . '.');
        }
        $code = trim((string) ($option['code'] ?? ''));
        if ($code === '') {
            $code = kothar_clean_code((string) ($fills[$columnId] ?? ''));
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
