<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';

kothar_page_open('Informatie');
echo '<h1>Informatie</h1>';
echo '<p class="lead">Kothar bouwt een samenstellingsnummer uit de opties van één categorie. Elke gekozen code wordt een segment, en de segmenten komen achter elkaar met een punt ertussen.</p>';
echo '<p>Voorbeeld: code <code>I</code>, slang <code>2</code> en <code>20</code> wordt <span class="number">I.2.20</span>. Een vulpunt met indoor, twee punten, veren, 05 liter, alarm en zonder radar wordt <span class="number">I.2.WS.05 L.Y.N</span>. De spatie in <code>05 L</code> staat vet in het bronblad.</p>';
echo '<h2>Regels, later</h2>';
echo '<p>Compatibiliteit tussen opties, verplichte keuzes en extra notities op deze pagina zijn nog niet gebouwd. In de data staat daarvoor een leeg <code>rules</code>-veld per categorie, plus een placeholder <code>rules.status = later</code> in de seed.</p>';
echo '<h2>Codes uit het blad</h2>';
echo '<p>De optiecode is het vette deel van de cel in het Deliverables-blad. Een paar cellen hebben geen vette code (zoals een aantal “00 tot 99”, Steel/Stainless bij dakkoeler-leidingen, en 1-phase/3-phase). Bij die opties vul je de code zelf in tijdens het samenstellen.</p>';
echo '<p>Room vent. en de notitie Start/Stop bij loadbanks staan wel in het blad, maar hebben geen optiekolom. Die zijn daarom geen categorie.</p>';
kothar_page_close();
