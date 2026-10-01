<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';

kothar_page_open('Scannen');
echo '<h1>Barcode scannen</h1>';
echo '<p class="lead">Scan een Code128 van een samenstellingsnummer. De camera werkt op localhost en via HTTPS.</p>';
echo '<div class="scanner" data-scanner>';
echo '<p data-status>Start de camera of vul het nummer in.</p>';
echo '<button type="button" data-start>Camera starten</button>';
echo '<video hidden playsinline muted></video>';
echo '</div>';
echo '<form method="get" action="samenstelling.php" class="inline-form">';
echo '<label for="nummer">Nummer</label>';
echo '<input id="nummer" name="nummer" required autocomplete="off" placeholder="I.2.20">';
echo '<button type="submit">Open</button>';
echo '</form>';
echo '<p class="hint">Zonder barcodedetector in de browser blijft dit invoerveld werken.</p>';
kothar_page_close();
