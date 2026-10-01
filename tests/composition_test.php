<?php

declare(strict_types=1);

require_once __DIR__ . '/../web/lib/store.php';

$failures = 0;

function check(bool $condition, string $name): void
{
    global $failures;
    if ($condition) {
        echo "OK  $name\n";
        return;
    }
    $failures++;
    echo "FAIL $name\n";
}

check(kothar_join_codes(['I', 'O', '2', '20']) === 'I.O.2.20', 'join all segments');
check(kothar_join_codes(['I', '2', '20']) === 'I.2.20', 'join Tim example');
check(kothar_join_codes(['I', '', '2', '  ', '20']) === 'I.2.20', 'skip empty codes');
check(kothar_join_codes(['05 L', 'Y']) === '05 L.Y', 'keep space inside a code');
check(kothar_canonicalize_number(' I. 2. 20 ') === 'I.2.20', 'canonicalize spaces around dots');
check(kothar_segment_color('') === '', 'empty segment has no color');
check(kothar_segment_color('   ') === '', 'blank segment has no color');
check(kothar_segment_color('F') === 'hsl(50 62% 36%)', 'Fuel code F keeps its hue');
check(kothar_segment_color('ST') === 'hsl(201 62% 36%)', 'Standard code ST keeps its hue');
check(kothar_segment_color(' F ') === kothar_segment_color('F'), 'segment color trims the ends');
check(kothar_segment_color('05 L') !== kothar_segment_color('05L'), 'space inside a code changes the color');
check(kothar_segment_color('Y') === kothar_segment_color('Y'), 'same code keeps the same color');

$stored = [
    ['id' => 'c_1', 'number' => 'I.2.20'],
    ['id' => 'c_2', 'number' => 'I.O.2.20'],
];
$found = kothar_find_by_number($stored, 'I. 2.20');
check(is_array($found) && ($found['id'] ?? '') === 'c_1', 'duplicate detection ignores spaces around dots');
check(kothar_find_by_number($stored, 'I.2.21') === null, 'unknown number is not a duplicate');
check(kothar_find_by_number($stored, 'I.O.2.20')['id'] === 'c_2', 'longer number is a different composition');

$mini = [
    'id' => 'mini',
    'name' => 'Mini',
    'columns' => [
        [
            'id' => 'loc',
            'name' => 'Locatie',
            'options' => [
                ['id' => 'i', 'label' => 'Indoor', 'code' => 'I', 'description' => 'Indoor'],
                ['id' => 'o', 'label' => 'Outdoor', 'code' => 'O', 'description' => 'Outdoor'],
            ],
        ],
        [
            'id' => 'n',
            'name' => 'Aantal',
            'options' => [
                ['id' => 'n2', 'label' => '2', 'code' => '2', 'description' => '2'],
                ['id' => 'n20', 'label' => '20', 'code' => '20', 'description' => '20'],
            ],
        ],
        [
            'id' => 'empty',
            'name' => 'Leeg',
            'options' => [
                ['id' => 'x', 'label' => 'geen code', 'code' => '', 'description' => 'geen code'],
            ],
        ],
        [
            'id' => 'valve',
            'name' => 'Afsluiter',
            'options' => [
                ['id' => 'n-only', 'label' => 'No', 'code' => 'N', 'description' => 'No'],
                ['id' => 'ns', 'label' => 'No Springs', 'code' => 'NS', 'description' => 'No Springs'],
            ],
        ],
    ],
];
$parsed = kothar_parse_category_number($mini, 'I.20.NS');
check($parsed !== null && $parsed['number'] === 'I.20.NS', 'parse skips a column without codes');
check($parsed !== null && $parsed['selections'][1]['code'] === '20', 'longest code 20 wins over 2');
check($parsed !== null && $parsed['selections'][2]['code'] === 'NS', 'longest code NS wins over N');
check(kothar_parse_category_number($mini, 'I.9.NS') === null, 'unknown segment does not parse');

$dotted = [
    'id' => 'dot',
    'name' => 'Punt',
    'columns' => [
        [
            'id' => 'a',
            'name' => 'A',
            'options' => [
                ['id' => '10', 'label' => '10', 'code' => '10', 'description' => '10'],
            ],
        ],
        [
            'id' => 'b',
            'name' => 'B',
            'options' => [
                ['id' => '52', 'label' => '5.2', 'code' => '5.2', 'description' => '5.2'],
            ],
        ],
    ],
];
$dotHit = kothar_parse_category_number($dotted, '10.5.2');
check($dotHit !== null && $dotHit['number'] === '10.5.2', 'code may contain a dot');

$built = kothar_build_from_choices($mini, ['loc' => 'i', 'n' => 'n2', 'valve' => 'ns'], ['empty' => 'QQ']);
check($built['ok'] === true && $built['number'] === 'I.2.QQ.NS', 'typed code fills an empty option');
$missing = kothar_build_from_choices($mini, ['loc' => 'i', 'n' => 'n2', 'valve' => 'ns'], []);
check($missing['ok'] === false, 'empty option requires a typed code');

check(kothar_parse_price('12,50') === 12.5, 'Dutch decimal price');
check(kothar_parse_price('1.234,56') === 1234.56, 'Dutch thousands price');
check(kothar_parse_price('nee') === null, 'reject a word as price');

$seedPath = __DIR__ . '/../web/data/categories.seed.json';
$fixturePath = __DIR__ . '/../web/fixtures/categories.seed.json';
check(is_file($seedPath) && md5_file($seedPath) === md5_file($fixturePath), 'data seed matches fixtures seed');
$seed = json_decode((string) file_get_contents($seedPath), true, 512, JSON_THROW_ON_ERROR);
check(is_array($seed) && count($seed['categories']) === 15, 'seed has 15 categories');
check(($seed['rules']['status'] ?? '') === 'later', 'rules placeholder is later');

$fuel = null;
$standard = null;
$one = null;
$iv = null;
$filling = null;
$rulesOk = 0;
$optionCount = 0;
$optionGaps = 0;
foreach ($seed['categories'] as $category) {
    if (array_key_exists('rules', $category) && $category['rules'] === []) {
        $rulesOk++;
    }
    if ($category['id'] === 'filling-point') {
        $filling = $category;
    }
    foreach ($category['columns'] as $column) {
        foreach ($column['options'] as $option) {
            $optionCount++;
            if (!isset($option['label'], $option['code'], $option['description'])) {
                $optionGaps++;
            }
            if ($option['id'] === 'universal-c1-o1') {
                $fuel = $option;
            }
            if ($option['id'] === 'universal-c2-o1') {
                $standard = $option;
            }
            if ($option['id'] === 'filling-point-c2-o1') {
                $one = $option;
            }
            if (($option['label'] ?? '') === 'IV5.2') {
                $iv = $option;
            }
        }
    }
}
check($rulesOk === 15, 'each category has an empty rules list');
check($optionCount === 284 && $optionGaps === 0, 'every option has label, code and description');
check(is_array($fuel) && $fuel['code'] === 'F' && $fuel['label'] === 'Fuel', 'Fuel code is F');
check(is_array($standard) && $standard['code'] === 'ST' && $standard['label'] === 'Standard', 'Standard code is ST');
check(is_array($one) && $one['code'] === '1', 'filling count 1 keeps its code');
check(is_array($iv) && $iv['code'] === '5.2', 'IV5.2 code keeps the dot');

$sheetExample = kothar_parse_category_number($filling, 'I.2.WS.05L.Y.N');
check(
    $sheetExample !== null && $sheetExample['number'] === 'I.2.WS.05 L.Y.N',
    'sheet example 05L matches bold code 05 L'
);

$dir = sys_get_temp_dir() . '/kothar-test-' . bin2hex(random_bytes(4));
mkdir($dir);
copy($seedPath, $dir . '/categories.seed.json');
$loaded = kothar_load_categories($dir);
check(count($loaded['categories']) === 15 && is_file($dir . '/categories.json'), 'first run copies seed to categories.json');
$again = kothar_load_categories($dir);
check($again['categories'][0]['id'] === $loaded['categories'][0]['id'], 'second run keeps categories.json');

$draft = [
    'number' => 'I.2.20',
    'categoryId' => 'mini',
    'categoryName' => 'Mini',
    'price' => 10,
    'selections' => [],
    'registrant' => ['name' => 'Tim', 'email' => 'tim@example.test'],
];
$first = kothar_register_composition($draft, $dir);
$second = kothar_register_composition(['number' => ' I.2.20 ', 'categoryId' => 'mini', 'categoryName' => 'Mini', 'selections' => [], 'registrant' => $draft['registrant']], $dir);
check($first['created'] === true, 'first save creates the composition');
check($second['created'] === false && $second['composition']['id'] === $first['composition']['id'], 'second save detects the duplicate');
$onDisk = kothar_load_compositions($dir);
check(count($onDisk['compositions']) === 1, 'duplicate is not written twice');

if ($failures > 0) {
    echo "$failures failed\n";
    exit(1);
}

echo "all passed\n";
