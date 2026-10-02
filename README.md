# Kothar

Samenstellingen bouwen voor sleutels.kvt.nl. Je kiest per kolom één optie; de codes vormen het samenstellingsnummer, gescheiden door een punt (`I.2.20`, of een volledig vulpunt `I.2.WS.05 L.Y.N`).

Pagina-root is **`web/`**. PHP 8.0. De FTP-deploy spiegelt `web/` naar de remote dir.

Deze versie koppelt niet aan Business Central of Mímir.

## Lokaal

```bash
cp web/auth_TEMPLATE.php web/auth.php
php -S localhost:8080 -t web web/router.php
```

Open <http://localhost:8080/>.

Op localhost slaat de login de gedeelde Entra-app over. De sessie is dan `lokaal@kvt.nl`. Zet dat adres in `$admins` om beheer te proberen.

`web/auth.php` staat in `.gitignore`. Commit die niet.

## Beheerders

`$admins` in `web/auth.php` is een lijst e-mailadressen. Alleen die accounts mogen categorieën, kolommen en opties aanmaken, hernoemen, ordenen en verwijderen. `$kotharAdmins` is een alias voor dezelfde lijst.

Ontbreekt de lijst of is hij leeg, dan is niemand beheerder.

`$allowedUsers` werkt zoals bij Ktesios: weglaten of `[]` laat elke geldige Entra-login toe. Buiten localhost laadt `web/logincheck.php` de gedeelde login via `../login/lib.php`.

Iedere ingelogde gebruiker mag samenstellen, de winkelwagen gebruiken, een samenstelling opslaan en bijlagen op bestaande nummers zetten.

## Seed

De categorieën komen uit het Excel-blad Deliverables. De optiecode is het **vette** deel van de cel (ook als dat de vette celopmaak erft, zoals `ST` in Standard).

- `web/data/categories.seed.json` is de bron in git (15 categorieën).
- Dezelfde inhoud staat in `web/fixtures/categories.seed.json`, omdat de FTP-deploy de map `data/` overslaat.
- Bij de eerste start, als `web/data/categories.json` ontbreekt, kopieert de app de seed. Een bestaand bestand wordt niet overschreven.

Een paar cellen in het blad hebben geen vette code (onder meer aantallen “00 to 99”, Steel/Stainless Steel bij de dakkoeler-leiding, 1-phase/3-phase). Daar vraagt de samensteller om een code. Room vent. en de notitie Start/Stop bij loadbanks hebben geen optiekolom en zitten daarom niet in de seed.

## Pagina’s

- `web/index.php` — categorieën en een nummer opzoeken
- `web/bouwen.php` — één optie per kolom, omschrijving, nummer, winkelwagen
- `web/winkelwagen.php` — aantal, registreren zonder dubbel nummer
- `web/samenstellingen.php` — lijst
- `web/samenstelling.php` — opties, registrator, prijs, bijlagen, Code128
- `web/scannen.php` — camera via BarcodeDetector, anders het nummer intypen
- `web/beheer.php` en `web/beheer_categorie.php` — CRUD voor beheerders
- `web/info.php` — uitleg, inclusief wat nog niet gebouwd is
- `web/bijlage.php` — download, alleen voor een ingelogde sessie

Bijlagen staan in `web/data/attachments/<samenstelling>/`. `web/data/.htaccess` blokkeert directe toegang; de ingebouwde server gebruikt `web/router.php` daarvoor.

## Later

Compatibiliteit tussen opties, verplichte keuzes en notities op de infopagina zijn niet gebouwd. Elke categorie heeft `"rules": []`. In de seed staat ook `rules.status` op `later`.

## Productie

Tim levert op de server (niet in git) `web/auth.php` vanuit `auth_TEMPLATE.php`.

Deploy: `.github/workflows/deploy-ftp.yml` op push naar `master`.

Secrets: `FTP_HOST`, `FTP_USERNAME`, `FTP_PASSWORD`, `FTP_REMOTE_DIR`.

Verwacht pad:

```text
FTP_REMOTE_DIR=/var/www/html/kothar
```

De job weigert te deployen als dat secret leeg is of geen pad onder `/var/www/html/…` is. `lftp mirror -R --delete` van `./web` slaat `auth.php`, `.htaccess`, `.htpasswd` en `data/` over, zodat prijzen, samenstellingen en bijlagen op de server blijven staan.

## Tests

```bash
php tests/composition_test.php
php tests/admin_test.php
php tests/localization_test.php
bash tests/guard_ftp_remote_dir_test.sh
```

De tests raken geen netwerk en lezen geen `web/auth.php`.
