<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';

kothar_page_open(LOC('kothar.nav.scan'));
echo '<h1>' . kothar_h(LOC('kothar.scan.heading')) . '</h1>';
echo '<p class="lead">' . kothar_h(LOC('kothar.scan.lead')) . '</p>';
echo '<div class="scanner" data-scanner'
    . ' data-msg-no-detector="' . kothar_h(LOC('kothar.scan.no_detector')) . '"'
    . ' data-msg-aim="' . kothar_h(LOC('kothar.scan.aim')) . '"'
    . ' data-msg-camera-unavailable="' . kothar_h(LOC('kothar.scan.camera_unavailable')) . '">';
echo '<p data-status>' . kothar_h(LOC('kothar.scan.start_hint')) . '</p>';
echo '<button type="button" data-start>' . kothar_h(LOC('kothar.scan.start_camera')) . '</button>';
echo '<video hidden playsinline muted></video>';
echo '</div>';
echo '<form method="get" action="samenstelling.php" class="inline-form">';
echo '<label for="nummer">' . kothar_h(LOC('kothar.scan.number')) . '</label>';
echo '<input id="nummer" name="nummer" required autocomplete="off" placeholder="I.2.20">';
echo '<button type="submit">' . kothar_h(LOC('kothar.scan.open')) . '</button>';
echo '</form>';
echo '<p class="hint">' . kothar_h(LOC('kothar.scan.fallback_hint')) . '</p>';
kothar_page_close();
