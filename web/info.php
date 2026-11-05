<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';

kothar_page_open(LOC('kothar.nav.info'));
echo '<h1>' . kothar_h(LOC('kothar.nav.info')) . '</h1>';
echo '<p class="lead">' . kothar_h(LOC('kothar.info.lead')) . '</p>';
echo '<p>' . LOC(
    'kothar.info.example',
    '<code>I</code>',
    '<code>2</code>',
    '<code>20</code>',
    '<span class="number">I.2.20</span>',
    '<span class="number">I.2.WS.05 L.Y.N</span>',
    '<code>05 L</code>'
) . '</p>';
echo '<h2>' . kothar_h(LOC('kothar.info.rules_heading')) . '</h2>';
echo '<p>' . LOC(
    'kothar.info.rules',
    '<code>rules</code>',
    '<code>rules.status = later</code>'
) . '</p>';
echo '<h2>' . kothar_h(LOC('kothar.info.codes_heading')) . '</h2>';
echo '<p>' . kothar_h(LOC('kothar.info.codes')) . '</p>';
echo '<p>' . kothar_h(LOC('kothar.info.excluded')) . '</p>';
kothar_page_close();
