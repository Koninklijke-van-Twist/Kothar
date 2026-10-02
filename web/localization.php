<?php

declare(strict_types=1);

/**
 * Talen zoals Seshat/Ponos: nl (standaard), en (UK), de, fr.
 * Geen aparte be-locale; Nederlands dekt het Belgische gebruik.
 */

const FLAG_SVGS = [
    'nl' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 900 600"><rect width="900" height="600" fill="#AE1C28"/><rect width="900" height="400" fill="#fff"/><rect width="900" height="200" fill="#fff"/><rect width="900" height="200" y="0" fill="#AE1C28"/><rect width="900" height="200" y="200" fill="#fff"/><rect width="900" height="200" y="400" fill="#21468B"/></svg>',
    'en' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 40"><clipPath id="a"><path d="M0 0v40h60V0z"/></clipPath><clipPath id="b"><path d="M30 20h30v20zv20H0zH0V0zV0h30z"/></clipPath><g clip-path="url(#a)"><path d="M0 0v40h60V0z" fill="#012169"/><path d="M0 0l60 40m0-40L0 40" stroke="#fff" stroke-width="8"/><path d="M0 0l60 40m0-40L0 40" clip-path="url(#b)" stroke="#C8102E" stroke-width="5"/><path d="M30 0v40M0 20h60" stroke="#fff" stroke-width="13"/><path d="M30 0v40M0 20h60" stroke="#C8102E" stroke-width="8"/></g></svg>',
    'de' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 5 3"><rect width="5" height="3" y="0" fill="#000"/><rect width="5" height="2" y="1" fill="#D00"/><rect width="5" height="1" y="2" fill="#FFCE00"/></svg>',
    'fr' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 900 600"><rect width="900" height="600" fill="#ED2939"/><rect width="600" height="600" fill="#fff"/><rect width="300" height="600" fill="#002395"/></svg>',
];

const SUPPORTED_LANGUAGES = [
    'nl' => ['flag' => '🇳🇱', 'label' => 'Nederlands'],
    'en' => ['flag' => '🇬🇧', 'label' => 'English'],
    'de' => ['flag' => '🇩🇪', 'label' => 'Deutsch'],
    'fr' => ['flag' => '🇫🇷', 'label' => 'Français'],
];

const LOCALE_BY_LANG = [
    'nl' => 'nl-NL',
    'en' => 'en-GB',
    'de' => 'de-DE',
    'fr' => 'fr-FR',
];

const TRANSLATIONS = [
    'nl' => [
        'lang.menu_aria' => 'Taal kiezen',
        'lang.switch_to' => 'Schakel naar %s',
        'kothar.app.title' => 'Kothar',
        'kothar.skip' => 'Naar de inhoud',
        'kothar.nav.main' => 'Hoofdmenu',
        'kothar.nav.start' => 'Start',
        'kothar.nav.compositions' => 'Samenstellingen',
        'kothar.nav.cart' => 'Winkelwagen',
        'kothar.nav.scan' => 'Scannen',
        'kothar.nav.info' => 'Informatie',
        'kothar.nav.admin' => 'Beheer',
        'kothar.footer' => 'Kothar · samenstellingen voor sleutels.kvt.nl',
        'kothar.user.unknown' => 'Onbekend',
        'kothar.error.invalid_session' => 'Ongeldige sessie. Laad de pagina opnieuw.',
        'kothar.error.unavailable' => 'Niet beschikbaar',
        'kothar.error.forbidden' => 'Geen toegang',
        'kothar.error.forbidden_admin' => 'Alleen e-mailadressen in %s (of %s) in auth.php mogen categorieën beheren.',
        'kothar.setup.title' => 'Kothar instellen',
        'kothar.setup.body' => 'Kopieer %s naar %s en zet de beheerders in %s.',
        'kothar.setup.git' => '%s hoort niet in git.',
        'kothar.back.start' => 'Terug naar start',
        'kothar.back' => 'Terug',
        'kothar.back.list' => 'Naar het overzicht',
        'kothar.index.categories_unavailable' => 'Categorieën niet beschikbaar',
        'kothar.index.lead' => 'Kies per kolom één optie. Het samenstellingsnummer is de gekozen codes, gescheiden door een punt.',
        'kothar.index.lookup' => 'Nummer opzoeken',
        'kothar.index.number_placeholder' => 'bijvoorbeeld I.2.20',
        'kothar.index.search' => 'Zoek',
        'kothar.index.lookup_hint' => 'Een bestaand nummer opent de samenstelling. Een nieuw nummer wordt opgebouwd als de codes bij een categorie passen.',
        'kothar.index.categories' => 'Categorieën',
        'kothar.index.empty' => 'Er zijn nog geen categorieën.',
        'kothar.index.columns_one' => '1 kolom',
        'kothar.index.columns_many' => '%d kolommen',
        'kothar.build.title' => 'Samenstellen',
        'kothar.build.not_found' => 'Categorie niet gevonden',
        'kothar.build.qty_range' => 'Aantal moet tussen 1 en 9999 liggen.',
        'kothar.build.already_exists' => 'Dit nummer bestond al.',
        'kothar.build.price_invalid' => 'Prijs is ongeldig.',
        'kothar.build.registered' => 'Samenstelling geregistreerd.',
        'kothar.build.added_cart' => 'In de winkelwagen gezet.',
        'kothar.build.fill_measures' => 'Vul de maten in',
        'kothar.build.choose_vector' => 'Kies ⌀×L of H×B×L',
        'kothar.build.hint' => 'Kies per kolom één kaart. Afmetingen en quantity vul je in. Het nummer bovenaan volgt meteen.',
        'kothar.build.progress' => '%d van %d',
        'kothar.build.choose' => 'Kies…',
        'kothar.build.mode_hwl' => 'Hoogte × breedte × lengte',
        'kothar.build.mode_diameter' => 'Diameter × lengte',
        'kothar.build.meters_hint' => 'Maten in meters.',
        'kothar.build.height' => 'Hoogte (m)',
        'kothar.build.width' => 'Breedte (m)',
        'kothar.build.length' => 'Lengte (m)',
        'kothar.build.diameter' => 'Diameter (m)',
        'kothar.build.codes_hint' => 'Codes in het blad:',
        'kothar.build.fill_code' => 'Code voor deze kolom',
        'kothar.build.no_dot' => 'De code mag geen punt bevatten.',
        'kothar.build.update' => 'Werk nummer bij',
        'kothar.build.number' => 'Samenstellingsnummer',
        'kothar.build.chosen' => 'Gekozen opties',
        'kothar.build.already_saved' => 'Dit nummer is al opgeslagen. Prijs:',
        'kothar.build.open_composition' => 'Open de samenstelling',
        'kothar.build.not_saved_yet' => 'Dit nummer staat nog niet bij de opgeslagen samenstellingen.',
        'kothar.build.qty' => 'Aantal',
        'kothar.build.add_cart' => 'Zet in winkelwagen',
        'kothar.build.price_on_register' => 'Prijs bij registratie',
        'kothar.build.register_and_cart' => 'Registreer en zet in winkelwagen',
        'kothar.build.quantity_prompt' => 'Vul quantity in',
        'kothar.build.quantity_per_piece' => 'Vul quantity per stuk in',
        'kothar.build.update_failed' => 'Het nummer kon niet worden bijgewerkt. Gebruik “Werk nummer bij”.',
        'kothar.column.fallback' => 'Kolom',
        'kothar.comp.no_columns' => 'Deze categorie heeft geen kolommen.',
        'kothar.comp.invalid_column' => 'Ongeldige kolom.',
        'kothar.comp.no_options' => 'Kolom %s heeft geen opties.',
        'kothar.comp.enter_quantity' => 'Vul quantity in.',
        'kothar.comp.choose_vector' => 'Kies ⌀×L of H×B×L voor %s.',
        'kothar.comp.fill_hwl' => 'Vul hoogte, breedte en lengte in meters in.',
        'kothar.comp.fill_diameter' => 'Vul diameter en lengte in meters in.',
        'kothar.comp.fill_measures' => 'Vul de maten in meters in.',
        'kothar.comp.choose_option' => 'Kies een optie voor %s.',
        'kothar.comp.enter_code' => 'Vul een code in voor %s.',
        'kothar.comp.no_dot' => 'De code voor %s mag geen punt bevatten.',
        'kothar.comp.no_code' => 'De samenstelling heeft geen code.',
        'kothar.comp.measures_note' => 'maten in meters',
        'kothar.admin.note.quantity' => 'Geen keuzelijst. De samensteller vult een getal in.',
        'kothar.admin.note.hwl' => 'Geen keuzelijst. De samensteller vult hoogte, breedte en lengte in meters in. De code wordt bijvoorbeeld H3xB2xL5.',
        'kothar.admin.note.diameter' => 'Geen keuzelijst. De samensteller vult diameter en lengte in meters in. De code wordt bijvoorbeeld ⌀4xL8.',
        'kothar.admin.note.dimensions' => 'Geen keuzelijst van codes. De samensteller kiest eerst ⌀×L of H×B×L en vult daarna de maten in meters in.',
        'kothar.admin.heading' => 'Categorieën',
        'kothar.admin.lead' => 'Alleen beheerders wijzigen categorieën, kolommen en opties. Samenstellen en opslaan kan iedere ingelogde gebruiker.',
        'kothar.admin.empty' => 'Nog geen categorieën.',
        'kothar.admin.confirm_delete' => 'Weet je zeker dat je deze categorie wilt verwijderen?',
        'kothar.admin.confirm_delete_named' => 'Weet je zeker dat je de categorie %s wilt verwijderen?',
        'kothar.admin.up' => 'Omhoog',
        'kothar.admin.down' => 'Omlaag',
        'kothar.admin.delete' => 'Verwijder',
        'kothar.admin.delete_note' => 'Opgeslagen samenstellingen blijven staan.',
        'kothar.admin.new_category' => 'Nieuwe categorie',
        'kothar.admin.name' => 'Naam',
        'kothar.admin.description' => 'Omschrijving',
        'kothar.admin.add' => 'Toevoegen',
        'kothar.admin.name_length' => 'Geef een naam van maximaal 120 tekens.',
        'kothar.admin.added' => 'Categorie toegevoegd.',
        'kothar.admin.not_found' => 'Categorie niet gevonden.',
        'kothar.admin.deleted' => 'Categorie verwijderd. Opgeslagen samenstellingen blijven staan.',
        'kothar.admin.save_failed' => 'Opslaan mislukt.',
        'kothar.admin.name_required' => 'De naam mag niet leeg zijn.',
        'kothar.admin.column_name_required' => 'Geef de kolom een naam.',
        'kothar.admin.saved_category' => 'Categorie opgeslagen.',
        'kothar.admin.column_added' => 'Kolom toegevoegd.',
        'kothar.admin.order_saved' => 'Volgorde opgeslagen.',
        'kothar.admin.column_not_found' => 'Kolom niet gevonden.',
        'kothar.admin.column_deleted' => 'Kolom verwijderd.',
        'kothar.admin.option_not_found' => 'Optie niet gevonden.',
        'kothar.admin.option_deleted' => 'Optie verwijderd.',
        'kothar.admin.option_added' => 'Optie toegevoegd.',
        'kothar.admin.option_needs_label' => 'Een optie heeft een label nodig.',
        'kothar.admin.column_saved' => 'Kolom opgeslagen.',
        'kothar.admin.unknown_action' => 'Onbekende actie.',
        'kothar.admin.category_fallback' => 'Categorie',
        'kothar.admin.edit_category' => 'Categorie bewerken',
        'kothar.admin.save_category' => 'Categorie opslaan',
        'kothar.admin.reorder' => 'Volgorde aanpassen',
        'kothar.admin.reorder_done' => 'Volgorde aanpassen gereed',
        'kothar.admin.reorder_hint' => 'Sleep de kolommen om de volgorde te wijzigen. Openen kan weer via “Volgorde aanpassen gereed”.',
        'kothar.admin.confirm_column' => 'Weet je zeker dat je deze kolom wilt verwijderen?',
        'kothar.admin.confirm_column_named' => 'Weet je zeker dat je de kolom %s wilt verwijderen?',
        'kothar.admin.drag_column' => 'Versleep kolom',
        'kothar.admin.hint' => 'Hint',
        'kothar.admin.save' => 'Opslaan',
        'kothar.admin.delete_column' => 'Verwijder kolom',
        'kothar.admin.no_options' => 'Nog geen opties.',
        'kothar.admin.confirm_option' => 'Weet je zeker dat je deze optie wilt verwijderen?',
        'kothar.admin.confirm_option_named' => 'Weet je zeker dat je de optie %s wilt verwijderen?',
        'kothar.admin.drag_option' => 'Versleep optie',
        'kothar.admin.label' => 'Label',
        'kothar.admin.code' => 'Code',
        'kothar.admin.placeholder_label' => 'Nieuw label',
        'kothar.admin.add_option' => 'Optie toevoegen',
        'kothar.admin.new_column' => 'Nieuwe kolom',
        'kothar.admin.add_column' => 'Kolom toevoegen',
        'kothar.admin.unsaved_confirm' => 'Er zijn niet-opgeslagen wijzigingen. OK verwerpt ze en past de volgorde aan.',
        'kothar.admin.unsaved_title' => 'Niet-opgeslagen wijzigingen',
        'kothar.admin.unsaved_body' => 'Er zijn niet-opgeslagen wijzigingen. Wil je die opslaan of verwerpen voordat je de volgorde aanpast?',
        'kothar.admin.discard' => 'Verwerpen',
        'kothar.confirm.title' => 'Verwijderen',
        'kothar.confirm.cancel' => 'Annuleren',
        'kothar.confirm.delete' => 'Verwijderen',
        'kothar.confirm.fallback' => 'Weet je zeker dat je dit wilt verwijderen?',
        'kothar.cart.removed' => 'Regel verwijderd.',
        'kothar.cart.qty_updated' => 'Aantal bijgewerkt.',
        'kothar.cart.summary' => '%d nieuw geregistreerd, %d bestonden al.',
        'kothar.cart.empty' => 'De winkelwagen is leeg.',
        'kothar.cart.empty_link' => 'Stel een samenstelling samen',
        'kothar.cart.col.number' => 'Nummer',
        'kothar.cart.col.category' => 'Categorie',
        'kothar.cart.col.qty' => 'Aantal',
        'kothar.cart.col.saved' => 'Opgeslagen',
        'kothar.cart.update' => 'Werk bij',
        'kothar.cart.register' => 'Registreer',
        'kothar.cart.delete' => 'Verwijder',
        'kothar.cart.register_all' => 'Registreer alle nieuwe nummers',
        'kothar.cart.hint' => 'Een nieuw nummer krijgt prijs € 0,00. De prijs wijzig je op de detailpagina. Bestaande nummers worden niet dubbel opgeslagen.',
        'kothar.list.heading' => 'Opgeslagen samenstellingen',
        'kothar.list.empty' => 'Er is nog niets opgeslagen.',
        'kothar.list.empty_link' => 'Stel er een samen',
        'kothar.list.col.price' => 'Prijs',
        'kothar.list.col.by' => 'Geregistreerd door',
        'kothar.detail.title' => 'Samenstelling',
        'kothar.detail.price_saved' => 'Prijs opgeslagen.',
        'kothar.detail.upload_failed' => 'Upload mislukt.',
        'kothar.detail.too_big' => 'Het bestand is groter dan 8 MB.',
        'kothar.detail.bad_type' => 'Dit bestandstype is niet toegestaan.',
        'kothar.detail.dir_unwritable' => 'De bijlagenmap is niet schrijfbaar.',
        'kothar.detail.save_failed' => 'Opslaan van de bijlage mislukt.',
        'kothar.detail.attached' => 'Bijlage toegevoegd.',
        'kothar.detail.attachment_deleted' => 'Bijlage verwijderd.',
        'kothar.detail.no_match' => 'Dit nummer staat niet opgeslagen en past bij geen enkele categorie.',
        'kothar.detail.matches_one' => 'Het nummer staat nog niet opgeslagen. Het past bij 1 categorie.',
        'kothar.detail.matches_many' => 'Het nummer staat nog niet opgeslagen. Het past bij %d categorieën.',
        'kothar.detail.open_builder' => 'Open in de samensteller',
        'kothar.detail.not_found' => 'Samenstelling niet gevonden',
        'kothar.detail.options' => 'Opties',
        'kothar.detail.col.column' => 'Kolom',
        'kothar.detail.col.option' => 'Optie',
        'kothar.detail.col.code' => 'Code',
        'kothar.detail.col.description' => 'Omschrijving',
        'kothar.detail.price' => 'Prijs',
        'kothar.detail.save_price' => 'Prijs opslaan',
        'kothar.detail.attachments' => 'Bijlagen',
        'kothar.detail.no_attachments' => 'Nog geen bijlagen.',
        'kothar.detail.attachment_fallback' => 'bijlage',
        'kothar.detail.file' => 'Bestand',
        'kothar.detail.upload' => 'Upload',
        'kothar.detail.file_hint' => 'pdf, afbeelding, tekst, Office, dwg of zip. Maximaal 8 MB.',
        'kothar.detail.rebuild' => 'Opnieuw samenstellen in deze categorie',
        'kothar.detail.not_found_file' => 'Bijlage niet gevonden',
        'kothar.scan.heading' => 'Barcode scannen',
        'kothar.scan.lead' => 'Scan een Code128 van een samenstellingsnummer. De camera werkt op localhost en via HTTPS.',
        'kothar.scan.start_hint' => 'Start de camera of vul het nummer in.',
        'kothar.scan.number' => 'Nummer',
        'kothar.scan.start_camera' => 'Camera starten',
        'kothar.scan.open' => 'Open',
        'kothar.scan.fallback_hint' => 'Zonder barcodedetector in de browser blijft dit invoerveld werken.',
        'kothar.scan.no_detector' => 'Deze browser heeft geen barcodedetector. Vul het nummer hieronder in.',
        'kothar.scan.aim' => 'Richt de camera op de barcode.',
        'kothar.scan.camera_unavailable' => 'Camera niet beschikbaar. Vul het nummer handmatig in.',
        'kothar.barcode.non_ascii' => 'Code128 kan dit nummer niet tekenen omdat er tekens buiten ASCII in staan.',
        'kothar.barcode.failed' => 'De barcode kon niet worden gemaakt.',
        'kothar.info.lead' => 'Kothar bouwt een samenstellingsnummer uit de opties van één categorie. Elke gekozen code wordt een segment, en de segmenten komen achter elkaar met een punt ertussen.',
        'kothar.info.example' => 'Voorbeeld: code %s, slang %s en %s wordt %s. Een vulpunt met indoor, twee punten, veren, 05 liter, alarm en zonder radar wordt %s. De spatie in %s staat vet in het bronblad.',
        'kothar.info.rules_heading' => 'Regels, later',
        'kothar.info.rules' => 'Compatibiliteit tussen opties, verplichte keuzes en extra notities op deze pagina zijn nog niet gebouwd. In de data staat daarvoor een leeg %s-veld per categorie, plus een placeholder %s in de seed.',
        'kothar.info.codes_heading' => 'Codes uit het blad',
        'kothar.info.codes' => 'De optiecode is het vette deel van de cel in het Deliverables-blad. Een paar cellen hebben geen vette code (zoals een aantal “00 tot 99”, Steel/Stainless bij dakkoeler-leidingen, en 1-phase/3-phase). Bij die opties vul je de code zelf in tijdens het samenstellen.',
        'kothar.info.excluded' => 'Room vent. en de notitie Start/Stop bij loadbanks staan wel in het blad, maar hebben geen optiekolom. Die zijn daarom geen categorie.',
    ],
    'en' => [
        'lang.menu_aria' => 'Choose language',
        'lang.switch_to' => 'Switch to %s',
        'kothar.app.title' => 'Kothar',
        'kothar.skip' => 'Skip to content',
        'kothar.nav.main' => 'Main menu',
        'kothar.nav.start' => 'Home',
        'kothar.nav.compositions' => 'Compositions',
        'kothar.nav.cart' => 'Cart',
        'kothar.nav.scan' => 'Scan',
        'kothar.nav.info' => 'Information',
        'kothar.nav.admin' => 'Admin',
        'kothar.footer' => 'Kothar · compositions for sleutels.kvt.nl',
        'kothar.user.unknown' => 'Unknown',
        'kothar.error.invalid_session' => 'Invalid session. Reload the page.',
        'kothar.error.unavailable' => 'Unavailable',
        'kothar.error.forbidden' => 'No access',
        'kothar.error.forbidden_admin' => 'Only email addresses in %s (or %s) in auth.php may manage categories.',
        'kothar.setup.title' => 'Set up Kothar',
        'kothar.setup.body' => 'Copy %s to %s and put the administrators in %s.',
        'kothar.setup.git' => '%s does not belong in git.',
        'kothar.back.start' => 'Back to home',
        'kothar.back' => 'Back',
        'kothar.back.list' => 'To the list',
        'kothar.index.categories_unavailable' => 'Categories unavailable',
        'kothar.index.lead' => 'Choose one option per column. The composition number is the chosen codes, separated by a dot.',
        'kothar.index.lookup' => 'Look up a number',
        'kothar.index.number_placeholder' => 'for example I.2.20',
        'kothar.index.search' => 'Search',
        'kothar.index.lookup_hint' => 'An existing number opens the composition. A new number is built when the codes match a category.',
        'kothar.index.categories' => 'Categories',
        'kothar.index.empty' => 'There are no categories yet.',
        'kothar.index.columns_one' => '1 column',
        'kothar.index.columns_many' => '%d columns',
        'kothar.build.title' => 'Build',
        'kothar.build.not_found' => 'Category not found',
        'kothar.build.qty_range' => 'Quantity must be between 1 and 9999.',
        'kothar.build.already_exists' => 'This number already existed.',
        'kothar.build.price_invalid' => 'Price is invalid.',
        'kothar.build.registered' => 'Composition registered.',
        'kothar.build.added_cart' => 'Added to the cart.',
        'kothar.build.fill_measures' => 'Enter the measurements',
        'kothar.build.choose_vector' => 'Choose ⌀×L or H×B×L',
        'kothar.build.hint' => 'Choose one card per column. Fill in dimensions and quantity. The number above updates straight away.',
        'kothar.build.progress' => '%d of %d',
        'kothar.build.choose' => 'Choose…',
        'kothar.build.mode_hwl' => 'Height × width × length',
        'kothar.build.mode_diameter' => 'Diameter × length',
        'kothar.build.meters_hint' => 'Measurements in metres.',
        'kothar.build.height' => 'Height (m)',
        'kothar.build.width' => 'Width (m)',
        'kothar.build.length' => 'Length (m)',
        'kothar.build.diameter' => 'Diameter (m)',
        'kothar.build.codes_hint' => 'Codes in the sheet:',
        'kothar.build.fill_code' => 'Code for this column',
        'kothar.build.no_dot' => 'The code must not contain a dot.',
        'kothar.build.update' => 'Update number',
        'kothar.build.number' => 'Composition number',
        'kothar.build.chosen' => 'Chosen options',
        'kothar.build.already_saved' => 'This number is already saved. Price:',
        'kothar.build.open_composition' => 'Open the composition',
        'kothar.build.not_saved_yet' => 'This number is not among the saved compositions yet.',
        'kothar.build.qty' => 'Quantity',
        'kothar.build.add_cart' => 'Add to cart',
        'kothar.build.price_on_register' => 'Price on registration',
        'kothar.build.register_and_cart' => 'Register and add to cart',
        'kothar.build.quantity_prompt' => 'Enter quantity',
        'kothar.build.quantity_per_piece' => 'Enter quantity per piece',
        'kothar.build.update_failed' => 'The number could not be updated. Use “Update number”.',
        'kothar.column.fallback' => 'Column',
        'kothar.comp.no_columns' => 'This category has no columns.',
        'kothar.comp.invalid_column' => 'Invalid column.',
        'kothar.comp.no_options' => 'Column %s has no options.',
        'kothar.comp.enter_quantity' => 'Enter quantity.',
        'kothar.comp.choose_vector' => 'Choose ⌀×L or H×B×L for %s.',
        'kothar.comp.fill_hwl' => 'Enter height, width and length in metres.',
        'kothar.comp.fill_diameter' => 'Enter diameter and length in metres.',
        'kothar.comp.fill_measures' => 'Enter the measurements in metres.',
        'kothar.comp.choose_option' => 'Choose an option for %s.',
        'kothar.comp.enter_code' => 'Enter a code for %s.',
        'kothar.comp.no_dot' => 'The code for %s must not contain a dot.',
        'kothar.comp.no_code' => 'The composition has no code.',
        'kothar.comp.measures_note' => 'measurements in metres',
        'kothar.admin.note.quantity' => 'No choice list. The builder asks for a number.',
        'kothar.admin.note.hwl' => 'No choice list. The builder asks for height, width and length in metres. The code looks like H3xB2xL5.',
        'kothar.admin.note.diameter' => 'No choice list. The builder asks for diameter and length in metres. The code looks like ⌀4xL8.',
        'kothar.admin.note.dimensions' => 'No list of codes. The builder first chooses ⌀×L or H×B×L, then fills in the measurements in metres.',
        'kothar.admin.heading' => 'Categories',
        'kothar.admin.lead' => 'Only administrators change categories, columns and options. Any signed-in user can build and save.',
        'kothar.admin.empty' => 'No categories yet.',
        'kothar.admin.confirm_delete' => 'Are you sure you want to delete this category?',
        'kothar.admin.confirm_delete_named' => 'Are you sure you want to delete the category %s?',
        'kothar.admin.up' => 'Up',
        'kothar.admin.down' => 'Down',
        'kothar.admin.delete' => 'Delete',
        'kothar.admin.delete_note' => 'Saved compositions stay in place.',
        'kothar.admin.new_category' => 'New category',
        'kothar.admin.name' => 'Name',
        'kothar.admin.description' => 'Description',
        'kothar.admin.add' => 'Add',
        'kothar.admin.name_length' => 'Enter a name of at most 120 characters.',
        'kothar.admin.added' => 'Category added.',
        'kothar.admin.not_found' => 'Category not found.',
        'kothar.admin.deleted' => 'Category deleted. Saved compositions stay in place.',
        'kothar.admin.save_failed' => 'Save failed.',
        'kothar.admin.name_required' => 'The name must not be empty.',
        'kothar.admin.column_name_required' => 'Give the column a name.',
        'kothar.admin.saved_category' => 'Category saved.',
        'kothar.admin.column_added' => 'Column added.',
        'kothar.admin.order_saved' => 'Order saved.',
        'kothar.admin.column_not_found' => 'Column not found.',
        'kothar.admin.column_deleted' => 'Column deleted.',
        'kothar.admin.option_not_found' => 'Option not found.',
        'kothar.admin.option_deleted' => 'Option deleted.',
        'kothar.admin.option_added' => 'Option added.',
        'kothar.admin.option_needs_label' => 'An option needs a label.',
        'kothar.admin.column_saved' => 'Column saved.',
        'kothar.admin.unknown_action' => 'Unknown action.',
        'kothar.admin.category_fallback' => 'Category',
        'kothar.admin.edit_category' => 'Edit category',
        'kothar.admin.save_category' => 'Save category',
        'kothar.admin.reorder' => 'Change order',
        'kothar.admin.reorder_done' => 'Finish changing order',
        'kothar.admin.reorder_hint' => 'Drag the columns to change the order. You can open them again via “Finish changing order”.',
        'kothar.admin.confirm_column' => 'Are you sure you want to delete this column?',
        'kothar.admin.confirm_column_named' => 'Are you sure you want to delete the column %s?',
        'kothar.admin.drag_column' => 'Drag column',
        'kothar.admin.hint' => 'Hint',
        'kothar.admin.save' => 'Save',
        'kothar.admin.delete_column' => 'Delete column',
        'kothar.admin.no_options' => 'No options yet.',
        'kothar.admin.confirm_option' => 'Are you sure you want to delete this option?',
        'kothar.admin.confirm_option_named' => 'Are you sure you want to delete the option %s?',
        'kothar.admin.drag_option' => 'Drag option',
        'kothar.admin.label' => 'Label',
        'kothar.admin.code' => 'Code',
        'kothar.admin.placeholder_label' => 'New label',
        'kothar.admin.add_option' => 'Add option',
        'kothar.admin.new_column' => 'New column',
        'kothar.admin.add_column' => 'Add column',
        'kothar.admin.unsaved_confirm' => 'There are unsaved changes. OK discards them and changes the order.',
        'kothar.admin.unsaved_title' => 'Unsaved changes',
        'kothar.admin.unsaved_body' => 'There are unsaved changes. Do you want to save or discard them before changing the order?',
        'kothar.admin.discard' => 'Discard',
        'kothar.confirm.title' => 'Delete',
        'kothar.confirm.cancel' => 'Cancel',
        'kothar.confirm.delete' => 'Delete',
        'kothar.confirm.fallback' => 'Are you sure you want to delete this?',
        'kothar.cart.removed' => 'Line removed.',
        'kothar.cart.qty_updated' => 'Quantity updated.',
        'kothar.cart.summary' => '%d newly registered, %d already existed.',
        'kothar.cart.empty' => 'The cart is empty.',
        'kothar.cart.empty_link' => 'Build a composition',
        'kothar.cart.col.number' => 'Number',
        'kothar.cart.col.category' => 'Category',
        'kothar.cart.col.qty' => 'Quantity',
        'kothar.cart.col.saved' => 'Saved',
        'kothar.cart.update' => 'Update',
        'kothar.cart.register' => 'Register',
        'kothar.cart.delete' => 'Remove',
        'kothar.cart.register_all' => 'Register all new numbers',
        'kothar.cart.hint' => 'A new number is saved at € 0.00. Change the price on the detail page. Existing numbers are not stored twice.',
        'kothar.list.heading' => 'Saved compositions',
        'kothar.list.empty' => 'Nothing has been saved yet.',
        'kothar.list.empty_link' => 'Build one',
        'kothar.list.col.price' => 'Price',
        'kothar.list.col.by' => 'Registered by',
        'kothar.detail.title' => 'Composition',
        'kothar.detail.price_saved' => 'Price saved.',
        'kothar.detail.upload_failed' => 'Upload failed.',
        'kothar.detail.too_big' => 'The file is larger than 8 MB.',
        'kothar.detail.bad_type' => 'This file type is not allowed.',
        'kothar.detail.dir_unwritable' => 'The attachments folder is not writable.',
        'kothar.detail.save_failed' => 'Saving the attachment failed.',
        'kothar.detail.attached' => 'Attachment added.',
        'kothar.detail.attachment_deleted' => 'Attachment removed.',
        'kothar.detail.no_match' => 'This number is not saved and does not match any category.',
        'kothar.detail.matches_one' => 'The number is not saved yet. It matches 1 category.',
        'kothar.detail.matches_many' => 'The number is not saved yet. It matches %d categories.',
        'kothar.detail.open_builder' => 'Open in the builder',
        'kothar.detail.not_found' => 'Composition not found',
        'kothar.detail.options' => 'Options',
        'kothar.detail.col.column' => 'Column',
        'kothar.detail.col.option' => 'Option',
        'kothar.detail.col.code' => 'Code',
        'kothar.detail.col.description' => 'Description',
        'kothar.detail.price' => 'Price',
        'kothar.detail.save_price' => 'Save price',
        'kothar.detail.attachments' => 'Attachments',
        'kothar.detail.no_attachments' => 'No attachments yet.',
        'kothar.detail.attachment_fallback' => 'attachment',
        'kothar.detail.file' => 'File',
        'kothar.detail.upload' => 'Upload',
        'kothar.detail.file_hint' => 'pdf, image, text, Office, dwg or zip. Maximum 8 MB.',
        'kothar.detail.rebuild' => 'Build again in this category',
        'kothar.detail.not_found_file' => 'Attachment not found',
        'kothar.scan.heading' => 'Scan a barcode',
        'kothar.scan.lead' => 'Scan a Code128 of a composition number. The camera works on localhost and over HTTPS.',
        'kothar.scan.start_hint' => 'Start the camera or enter the number.',
        'kothar.scan.number' => 'Number',
        'kothar.scan.start_camera' => 'Start camera',
        'kothar.scan.open' => 'Open',
        'kothar.scan.fallback_hint' => 'Without a barcode detector in the browser, this field still works.',
        'kothar.scan.no_detector' => 'This browser has no barcode detector. Enter the number below.',
        'kothar.scan.aim' => 'Point the camera at the barcode.',
        'kothar.scan.camera_unavailable' => 'Camera unavailable. Enter the number manually.',
        'kothar.barcode.non_ascii' => 'Code128 cannot draw this number because it contains characters outside ASCII.',
        'kothar.barcode.failed' => 'The barcode could not be created.',
        'kothar.info.lead' => 'Kothar builds a composition number from the options of one category. Each chosen code becomes a segment, and the segments are joined with a dot.',
        'kothar.info.example' => 'Example: code %s, hose %s and %s becomes %s. A filling point with indoor, two points, springs, 05 litres, alarm and without radar becomes %s. The space in %s is bold in the source sheet.',
        'kothar.info.rules_heading' => 'Rules, later',
        'kothar.info.rules' => 'Compatibility between options, required choices and extra notes on this page are not built yet. The data has an empty %s field per category for that, plus a placeholder %s in the seed.',
        'kothar.info.codes_heading' => 'Codes from the sheet',
        'kothar.info.codes' => 'The option code is the bold part of the cell in the Deliverables sheet. A few cells have no bold code (such as a quantity “00 tot 99”, Steel/Stainless on dry-cooler pipes, and 1-phase/3-phase). For those options you type the code yourself while building.',
        'kothar.info.excluded' => 'Room vent. and the Start/Stop note on loadbanks are in the sheet, but they have no option column. They are therefore not a category.',
    ],
    'de' => [
        'lang.menu_aria' => 'Sprache wählen',
        'lang.switch_to' => 'Wechseln zu %s',
        'kothar.app.title' => 'Kothar',
        'kothar.skip' => 'Zum Inhalt',
        'kothar.nav.main' => 'Hauptmenü',
        'kothar.nav.start' => 'Start',
        'kothar.nav.compositions' => 'Zusammensetzungen',
        'kothar.nav.cart' => 'Warenkorb',
        'kothar.nav.scan' => 'Scannen',
        'kothar.nav.info' => 'Information',
        'kothar.nav.admin' => 'Verwaltung',
        'kothar.footer' => 'Kothar · Zusammensetzungen für sleutels.kvt.nl',
        'kothar.user.unknown' => 'Unbekannt',
        'kothar.error.invalid_session' => 'Ungültige Sitzung. Laden Sie die Seite neu.',
        'kothar.error.unavailable' => 'Nicht verfügbar',
        'kothar.error.forbidden' => 'Kein Zugriff',
        'kothar.error.forbidden_admin' => 'Nur E-Mail-Adressen in %s (oder %s) in auth.php dürfen Kategorien verwalten.',
        'kothar.setup.title' => 'Kothar einrichten',
        'kothar.setup.body' => 'Kopieren Sie %s nach %s und tragen Sie die Administratoren in %s ein.',
        'kothar.setup.git' => '%s gehört nicht in Git.',
        'kothar.back.start' => 'Zurück zum Start',
        'kothar.back' => 'Zurück',
        'kothar.back.list' => 'Zur Übersicht',
        'kothar.index.categories_unavailable' => 'Kategorien nicht verfügbar',
        'kothar.index.lead' => 'Wählen Sie pro Spalte eine Option. Die Zusammensetzungsnummer sind die gewählten Codes, getrennt durch einen Punkt.',
        'kothar.index.lookup' => 'Nummer suchen',
        'kothar.index.number_placeholder' => 'zum Beispiel I.2.20',
        'kothar.index.search' => 'Suchen',
        'kothar.index.lookup_hint' => 'Eine vorhandene Nummer öffnet die Zusammensetzung. Eine neue Nummer wird aufgebaut, wenn die Codes zu einer Kategorie passen.',
        'kothar.index.categories' => 'Kategorien',
        'kothar.index.empty' => 'Es gibt noch keine Kategorien.',
        'kothar.index.columns_one' => '1 Spalte',
        'kothar.index.columns_many' => '%d Spalten',
        'kothar.build.title' => 'Zusammenstellen',
        'kothar.build.not_found' => 'Kategorie nicht gefunden',
        'kothar.build.qty_range' => 'Die Anzahl muss zwischen 1 und 9999 liegen.',
        'kothar.build.already_exists' => 'Diese Nummer gab es bereits.',
        'kothar.build.price_invalid' => 'Preis ist ungültig.',
        'kothar.build.registered' => 'Zusammensetzung registriert.',
        'kothar.build.added_cart' => 'In den Warenkorb gelegt.',
        'kothar.build.fill_measures' => 'Maße eintragen',
        'kothar.build.choose_vector' => '⌀×L oder H×B×L wählen',
        'kothar.build.hint' => 'Wählen Sie pro Spalte eine Karte. Maße und Quantity tragen Sie ein. Die Nummer oben folgt sofort.',
        'kothar.build.progress' => '%d von %d',
        'kothar.build.choose' => 'Wählen…',
        'kothar.build.mode_hwl' => 'Höhe × Breite × Länge',
        'kothar.build.mode_diameter' => 'Durchmesser × Länge',
        'kothar.build.meters_hint' => 'Maße in Metern.',
        'kothar.build.height' => 'Höhe (m)',
        'kothar.build.width' => 'Breite (m)',
        'kothar.build.length' => 'Länge (m)',
        'kothar.build.diameter' => 'Durchmesser (m)',
        'kothar.build.codes_hint' => 'Codes im Blatt:',
        'kothar.build.fill_code' => 'Code für diese Spalte',
        'kothar.build.no_dot' => 'Der Code darf keinen Punkt enthalten.',
        'kothar.build.update' => 'Nummer aktualisieren',
        'kothar.build.number' => 'Zusammensetzungsnummer',
        'kothar.build.chosen' => 'Gewählte Optionen',
        'kothar.build.already_saved' => 'Diese Nummer ist bereits gespeichert. Preis:',
        'kothar.build.open_composition' => 'Zusammensetzung öffnen',
        'kothar.build.not_saved_yet' => 'Diese Nummer steht noch nicht bei den gespeicherten Zusammensetzungen.',
        'kothar.build.qty' => 'Anzahl',
        'kothar.build.add_cart' => 'In den Warenkorb',
        'kothar.build.price_on_register' => 'Preis bei der Registrierung',
        'kothar.build.register_and_cart' => 'Registrieren und in den Warenkorb',
        'kothar.build.quantity_prompt' => 'Quantity eintragen',
        'kothar.build.quantity_per_piece' => 'Quantity pro Stück eintragen',
        'kothar.build.update_failed' => 'Die Nummer konnte nicht aktualisiert werden. Nutzen Sie „Nummer aktualisieren“.',
        'kothar.column.fallback' => 'Spalte',
        'kothar.comp.no_columns' => 'Diese Kategorie hat keine Spalten.',
        'kothar.comp.invalid_column' => 'Ungültige Spalte.',
        'kothar.comp.no_options' => 'Spalte %s hat keine Optionen.',
        'kothar.comp.enter_quantity' => 'Quantity eintragen.',
        'kothar.comp.choose_vector' => 'Wählen Sie ⌀×L oder H×B×L für %s.',
        'kothar.comp.fill_hwl' => 'Tragen Sie Höhe, Breite und Länge in Metern ein.',
        'kothar.comp.fill_diameter' => 'Tragen Sie Durchmesser und Länge in Metern ein.',
        'kothar.comp.fill_measures' => 'Tragen Sie die Maße in Metern ein.',
        'kothar.comp.choose_option' => 'Wählen Sie eine Option für %s.',
        'kothar.comp.enter_code' => 'Tragen Sie einen Code für %s ein.',
        'kothar.comp.no_dot' => 'Der Code für %s darf keinen Punkt enthalten.',
        'kothar.comp.no_code' => 'Die Zusammensetzung hat keinen Code.',
        'kothar.comp.measures_note' => 'Maße in Metern',
        'kothar.admin.note.quantity' => 'Keine Auswahlliste. In der Zusammenstellung wird eine Zahl eingetragen.',
        'kothar.admin.note.hwl' => 'Keine Auswahlliste. Höhe, Breite und Länge werden in Metern eingetragen. Der Code sieht zum Beispiel so aus: H3xB2xL5.',
        'kothar.admin.note.diameter' => 'Keine Auswahlliste. Durchmesser und Länge werden in Metern eingetragen. Der Code sieht zum Beispiel so aus: ⌀4xL8.',
        'kothar.admin.note.dimensions' => 'Keine Codeliste. Zuerst ⌀×L oder H×B×L wählen, danach die Maße in Metern eintragen.',
        'kothar.admin.heading' => 'Kategorien',
        'kothar.admin.lead' => 'Nur Administratoren ändern Kategorien, Spalten und Optionen. Zusammenstellen und Speichern kann jeder angemeldete Benutzer.',
        'kothar.admin.empty' => 'Noch keine Kategorien.',
        'kothar.admin.confirm_delete' => 'Möchten Sie diese Kategorie wirklich löschen?',
        'kothar.admin.confirm_delete_named' => 'Möchten Sie die Kategorie %s wirklich löschen?',
        'kothar.admin.up' => 'Nach oben',
        'kothar.admin.down' => 'Nach unten',
        'kothar.admin.delete' => 'Löschen',
        'kothar.admin.delete_note' => 'Gespeicherte Zusammensetzungen bleiben erhalten.',
        'kothar.admin.new_category' => 'Neue Kategorie',
        'kothar.admin.name' => 'Name',
        'kothar.admin.description' => 'Beschreibung',
        'kothar.admin.add' => 'Hinzufügen',
        'kothar.admin.name_length' => 'Geben Sie einen Namen mit höchstens 120 Zeichen ein.',
        'kothar.admin.added' => 'Kategorie hinzugefügt.',
        'kothar.admin.not_found' => 'Kategorie nicht gefunden.',
        'kothar.admin.deleted' => 'Kategorie gelöscht. Gespeicherte Zusammensetzungen bleiben erhalten.',
        'kothar.admin.save_failed' => 'Speichern fehlgeschlagen.',
        'kothar.admin.name_required' => 'Der Name darf nicht leer sein.',
        'kothar.admin.column_name_required' => 'Geben Sie der Spalte einen Namen.',
        'kothar.admin.saved_category' => 'Kategorie gespeichert.',
        'kothar.admin.column_added' => 'Spalte hinzugefügt.',
        'kothar.admin.order_saved' => 'Reihenfolge gespeichert.',
        'kothar.admin.column_not_found' => 'Spalte nicht gefunden.',
        'kothar.admin.column_deleted' => 'Spalte gelöscht.',
        'kothar.admin.option_not_found' => 'Option nicht gefunden.',
        'kothar.admin.option_deleted' => 'Option gelöscht.',
        'kothar.admin.option_added' => 'Option hinzugefügt.',
        'kothar.admin.option_needs_label' => 'Eine Option braucht ein Label.',
        'kothar.admin.column_saved' => 'Spalte gespeichert.',
        'kothar.admin.unknown_action' => 'Unbekannte Aktion.',
        'kothar.admin.category_fallback' => 'Kategorie',
        'kothar.admin.edit_category' => 'Kategorie bearbeiten',
        'kothar.admin.save_category' => 'Kategorie speichern',
        'kothar.admin.reorder' => 'Reihenfolge ändern',
        'kothar.admin.reorder_done' => 'Reihenfolge ändern fertig',
        'kothar.admin.reorder_hint' => 'Ziehen Sie die Spalten, um die Reihenfolge zu ändern. Öffnen geht wieder über „Reihenfolge ändern fertig“.',
        'kothar.admin.confirm_column' => 'Möchten Sie diese Spalte wirklich löschen?',
        'kothar.admin.confirm_column_named' => 'Möchten Sie die Spalte %s wirklich löschen?',
        'kothar.admin.drag_column' => 'Spalte ziehen',
        'kothar.admin.hint' => 'Hinweis',
        'kothar.admin.save' => 'Speichern',
        'kothar.admin.delete_column' => 'Spalte löschen',
        'kothar.admin.no_options' => 'Noch keine Optionen.',
        'kothar.admin.confirm_option' => 'Möchten Sie diese Option wirklich löschen?',
        'kothar.admin.confirm_option_named' => 'Möchten Sie die Option %s wirklich löschen?',
        'kothar.admin.drag_option' => 'Option ziehen',
        'kothar.admin.label' => 'Label',
        'kothar.admin.code' => 'Code',
        'kothar.admin.placeholder_label' => 'Neues Label',
        'kothar.admin.add_option' => 'Option hinzufügen',
        'kothar.admin.new_column' => 'Neue Spalte',
        'kothar.admin.add_column' => 'Spalte hinzufügen',
        'kothar.admin.unsaved_confirm' => 'Es gibt ungespeicherte Änderungen. OK verwirft sie und ändert die Reihenfolge.',
        'kothar.admin.unsaved_title' => 'Ungespeicherte Änderungen',
        'kothar.admin.unsaved_body' => 'Es gibt ungespeicherte Änderungen. Möchten Sie sie speichern oder verwerfen, bevor Sie die Reihenfolge ändern?',
        'kothar.admin.discard' => 'Verwerfen',
        'kothar.confirm.title' => 'Löschen',
        'kothar.confirm.cancel' => 'Abbrechen',
        'kothar.confirm.delete' => 'Löschen',
        'kothar.confirm.fallback' => 'Möchten Sie dies wirklich löschen?',
        'kothar.cart.removed' => 'Zeile entfernt.',
        'kothar.cart.qty_updated' => 'Anzahl aktualisiert.',
        'kothar.cart.summary' => '%d neu registriert, %d waren bereits vorhanden.',
        'kothar.cart.empty' => 'Der Warenkorb ist leer.',
        'kothar.cart.empty_link' => 'Zusammensetzung erstellen',
        'kothar.cart.col.number' => 'Nummer',
        'kothar.cart.col.category' => 'Kategorie',
        'kothar.cart.col.qty' => 'Anzahl',
        'kothar.cart.col.saved' => 'Gespeichert',
        'kothar.cart.update' => 'Aktualisieren',
        'kothar.cart.register' => 'Registrieren',
        'kothar.cart.delete' => 'Entfernen',
        'kothar.cart.register_all' => 'Alle neuen Nummern registrieren',
        'kothar.cart.hint' => 'Eine neue Nummer erhält den Preis 0,00 €. Den Preis ändern Sie auf der Detailseite. Bestehende Nummern werden nicht doppelt gespeichert.',
        'kothar.list.heading' => 'Gespeicherte Zusammensetzungen',
        'kothar.list.empty' => 'Es ist noch nichts gespeichert.',
        'kothar.list.empty_link' => 'Eine erstellen',
        'kothar.list.col.price' => 'Preis',
        'kothar.list.col.by' => 'Registriert von',
        'kothar.detail.title' => 'Zusammensetzung',
        'kothar.detail.price_saved' => 'Preis gespeichert.',
        'kothar.detail.upload_failed' => 'Upload fehlgeschlagen.',
        'kothar.detail.too_big' => 'Die Datei ist größer als 8 MB.',
        'kothar.detail.bad_type' => 'Dieser Dateityp ist nicht erlaubt.',
        'kothar.detail.dir_unwritable' => 'Der Anlagenordner ist nicht beschreibbar.',
        'kothar.detail.save_failed' => 'Speichern der Anlage fehlgeschlagen.',
        'kothar.detail.attached' => 'Anlage hinzugefügt.',
        'kothar.detail.attachment_deleted' => 'Anlage entfernt.',
        'kothar.detail.no_match' => 'Diese Nummer ist nicht gespeichert und passt zu keiner Kategorie.',
        'kothar.detail.matches_one' => 'Die Nummer ist noch nicht gespeichert. Sie passt zu 1 Kategorie.',
        'kothar.detail.matches_many' => 'Die Nummer ist noch nicht gespeichert. Sie passt zu %d Kategorien.',
        'kothar.detail.open_builder' => 'Im Zusammensteller öffnen',
        'kothar.detail.not_found' => 'Zusammensetzung nicht gefunden',
        'kothar.detail.options' => 'Optionen',
        'kothar.detail.col.column' => 'Spalte',
        'kothar.detail.col.option' => 'Option',
        'kothar.detail.col.code' => 'Code',
        'kothar.detail.col.description' => 'Beschreibung',
        'kothar.detail.price' => 'Preis',
        'kothar.detail.save_price' => 'Preis speichern',
        'kothar.detail.attachments' => 'Anlagen',
        'kothar.detail.no_attachments' => 'Noch keine Anlagen.',
        'kothar.detail.attachment_fallback' => 'Anlage',
        'kothar.detail.file' => 'Datei',
        'kothar.detail.upload' => 'Hochladen',
        'kothar.detail.file_hint' => 'pdf, Bild, Text, Office, dwg oder zip. Maximal 8 MB.',
        'kothar.detail.rebuild' => 'In dieser Kategorie erneut zusammenstellen',
        'kothar.detail.not_found_file' => 'Anlage nicht gefunden',
        'kothar.scan.heading' => 'Barcode scannen',
        'kothar.scan.lead' => 'Scannen Sie einen Code128 einer Zusammensetzungsnummer. Die Kamera funktioniert auf localhost und über HTTPS.',
        'kothar.scan.start_hint' => 'Starten Sie die Kamera oder geben Sie die Nummer ein.',
        'kothar.scan.number' => 'Nummer',
        'kothar.scan.start_camera' => 'Kamera starten',
        'kothar.scan.open' => 'Öffnen',
        'kothar.scan.fallback_hint' => 'Ohne Barcode-Erkennung im Browser funktioniert dieses Feld weiterhin.',
        'kothar.scan.no_detector' => 'Dieser Browser hat keine Barcode-Erkennung. Geben Sie die Nummer unten ein.',
        'kothar.scan.aim' => 'Richten Sie die Kamera auf den Barcode.',
        'kothar.scan.camera_unavailable' => 'Kamera nicht verfügbar. Geben Sie die Nummer manuell ein.',
        'kothar.barcode.non_ascii' => 'Code128 kann diese Nummer nicht zeichnen, weil sie Zeichen außerhalb von ASCII enthält.',
        'kothar.barcode.failed' => 'Der Barcode konnte nicht erstellt werden.',
        'kothar.info.lead' => 'Kothar baut eine Zusammensetzungsnummer aus den Optionen einer Kategorie. Jeder gewählte Code wird ein Segment, und die Segmente stehen hintereinander mit einem Punkt dazwischen.',
        'kothar.info.example' => 'Beispiel: Code %s, Schlauch %s und %s wird %s. Ein Befüllpunkt mit Indoor, zwei Punkten, Federn, 05 Liter, Alarm und ohne Radar wird %s. Das Leerzeichen in %s ist im Quellblatt fett.',
        'kothar.info.rules_heading' => 'Regeln, später',
        'kothar.info.rules' => 'Kompatibilität zwischen Optionen, Pflichtauswahlen und zusätzliche Notizen auf dieser Seite sind noch nicht gebaut. Dafür steht in den Daten ein leeres %s-Feld pro Kategorie, plus ein Platzhalter %s in der Seed.',
        'kothar.info.codes_heading' => 'Codes aus dem Blatt',
        'kothar.info.codes' => 'Der Optionscode ist der fette Teil der Zelle im Blatt Deliverables. Ein paar Zellen haben keinen fetten Code (etwa eine Anzahl „00 tot 99“, Steel/Stainless bei Dachkühler-Leitungen und 1-phase/3-phase). Bei diesen Optionen tragen Sie den Code beim Zusammenstellen selbst ein.',
        'kothar.info.excluded' => 'Room vent. und die Notiz Start/Stop bei Loadbanks stehen im Blatt, haben aber keine Optionsspalte. Deshalb sind sie keine Kategorie.',
    ],
    'fr' => [
        'lang.menu_aria' => 'Choisir la langue',
        'lang.switch_to' => 'Passer en %s',
        'kothar.app.title' => 'Kothar',
        'kothar.skip' => 'Aller au contenu',
        'kothar.nav.main' => 'Menu principal',
        'kothar.nav.start' => 'Accueil',
        'kothar.nav.compositions' => 'Compositions',
        'kothar.nav.cart' => 'Panier',
        'kothar.nav.scan' => 'Scanner',
        'kothar.nav.info' => 'Informations',
        'kothar.nav.admin' => 'Administration',
        'kothar.footer' => 'Kothar · compositions pour sleutels.kvt.nl',
        'kothar.user.unknown' => 'Inconnu',
        'kothar.error.invalid_session' => 'Session invalide. Rechargez la page.',
        'kothar.error.unavailable' => 'Indisponible',
        'kothar.error.forbidden' => 'Accès refusé',
        'kothar.error.forbidden_admin' => 'Seules les adresses e-mail dans %s (ou %s) dans auth.php peuvent gérer les catégories.',
        'kothar.setup.title' => 'Configurer Kothar',
        'kothar.setup.body' => 'Copiez %s vers %s et indiquez les administrateurs dans %s.',
        'kothar.setup.git' => '%s ne doit pas être dans git.',
        'kothar.back.start' => 'Retour à l’accueil',
        'kothar.back' => 'Retour',
        'kothar.back.list' => 'Vers la liste',
        'kothar.index.categories_unavailable' => 'Catégories indisponibles',
        'kothar.index.lead' => 'Choisissez une option par colonne. Le numéro de composition est l’ensemble des codes choisis, séparés par un point.',
        'kothar.index.lookup' => 'Rechercher un numéro',
        'kothar.index.number_placeholder' => 'par exemple I.2.20',
        'kothar.index.search' => 'Chercher',
        'kothar.index.lookup_hint' => 'Un numéro existant ouvre la composition. Un nouveau numéro est construit si les codes correspondent à une catégorie.',
        'kothar.index.categories' => 'Catégories',
        'kothar.index.empty' => 'Il n’y a pas encore de catégories.',
        'kothar.index.columns_one' => '1 colonne',
        'kothar.index.columns_many' => '%d colonnes',
        'kothar.build.title' => 'Composer',
        'kothar.build.not_found' => 'Catégorie introuvable',
        'kothar.build.qty_range' => 'La quantité doit être comprise entre 1 et 9999.',
        'kothar.build.already_exists' => 'Ce numéro existait déjà.',
        'kothar.build.price_invalid' => 'Le prix est invalide.',
        'kothar.build.registered' => 'Composition enregistrée.',
        'kothar.build.added_cart' => 'Ajouté au panier.',
        'kothar.build.fill_measures' => 'Saisissez les cotes',
        'kothar.build.choose_vector' => 'Choisir ⌀×L ou H×B×L',
        'kothar.build.hint' => 'Choisissez une carte par colonne. Saisissez les cotes et la quantity. Le numéro en haut suit tout de suite.',
        'kothar.build.progress' => '%d sur %d',
        'kothar.build.choose' => 'Choisir…',
        'kothar.build.mode_hwl' => 'Hauteur × largeur × longueur',
        'kothar.build.mode_diameter' => 'Diamètre × longueur',
        'kothar.build.meters_hint' => 'Cotes en mètres.',
        'kothar.build.height' => 'Hauteur (m)',
        'kothar.build.width' => 'Largeur (m)',
        'kothar.build.length' => 'Longueur (m)',
        'kothar.build.diameter' => 'Diamètre (m)',
        'kothar.build.codes_hint' => 'Codes dans la feuille :',
        'kothar.build.fill_code' => 'Code pour cette colonne',
        'kothar.build.no_dot' => 'Le code ne doit pas contenir de point.',
        'kothar.build.update' => 'Mettre à jour le numéro',
        'kothar.build.number' => 'Numéro de composition',
        'kothar.build.chosen' => 'Options choisies',
        'kothar.build.already_saved' => 'Ce numéro est déjà enregistré. Prix :',
        'kothar.build.open_composition' => 'Ouvrir la composition',
        'kothar.build.not_saved_yet' => 'Ce numéro ne figure pas encore parmi les compositions enregistrées.',
        'kothar.build.qty' => 'Quantité',
        'kothar.build.add_cart' => 'Mettre au panier',
        'kothar.build.price_on_register' => 'Prix à l’enregistrement',
        'kothar.build.register_and_cart' => 'Enregistrer et mettre au panier',
        'kothar.build.quantity_prompt' => 'Saisir la quantity',
        'kothar.build.quantity_per_piece' => 'Saisir la quantity par pièce',
        'kothar.build.update_failed' => 'Le numéro n’a pas pu être mis à jour. Utilisez « Mettre à jour le numéro ».',
        'kothar.column.fallback' => 'Colonne',
        'kothar.comp.no_columns' => 'Cette catégorie n’a pas de colonnes.',
        'kothar.comp.invalid_column' => 'Colonne invalide.',
        'kothar.comp.no_options' => 'La colonne %s n’a pas d’options.',
        'kothar.comp.enter_quantity' => 'Saisir la quantity.',
        'kothar.comp.choose_vector' => 'Choisissez ⌀×L ou H×B×L pour %s.',
        'kothar.comp.fill_hwl' => 'Saisissez la hauteur, la largeur et la longueur en mètres.',
        'kothar.comp.fill_diameter' => 'Saisissez le diamètre et la longueur en mètres.',
        'kothar.comp.fill_measures' => 'Saisissez les cotes en mètres.',
        'kothar.comp.choose_option' => 'Choisissez une option pour %s.',
        'kothar.comp.enter_code' => 'Saisissez un code pour %s.',
        'kothar.comp.no_dot' => 'Le code pour %s ne doit pas contenir de point.',
        'kothar.comp.no_code' => 'La composition n’a pas de code.',
        'kothar.comp.measures_note' => 'cotes en mètres',
        'kothar.admin.note.quantity' => 'Pas de liste de choix. L’assembleur demande un nombre.',
        'kothar.admin.note.hwl' => 'Pas de liste de choix. L’assembleur demande hauteur, largeur et longueur en mètres. Le code ressemble par exemple à H3xB2xL5.',
        'kothar.admin.note.diameter' => 'Pas de liste de choix. L’assembleur demande diamètre et longueur en mètres. Le code ressemble par exemple à ⌀4xL8.',
        'kothar.admin.note.dimensions' => 'Pas de liste de codes. L’assembleur choisit d’abord ⌀×L ou H×B×L, puis saisit les cotes en mètres.',
        'kothar.admin.heading' => 'Catégories',
        'kothar.admin.lead' => 'Seuls les administrateurs modifient les catégories, colonnes et options. Tout utilisateur connecté peut composer et enregistrer.',
        'kothar.admin.empty' => 'Pas encore de catégories.',
        'kothar.admin.confirm_delete' => 'Voulez-vous vraiment supprimer cette catégorie ?',
        'kothar.admin.confirm_delete_named' => 'Voulez-vous vraiment supprimer la catégorie %s ?',
        'kothar.admin.up' => 'Monter',
        'kothar.admin.down' => 'Descendre',
        'kothar.admin.delete' => 'Supprimer',
        'kothar.admin.delete_note' => 'Les compositions enregistrées restent en place.',
        'kothar.admin.new_category' => 'Nouvelle catégorie',
        'kothar.admin.name' => 'Nom',
        'kothar.admin.description' => 'Description',
        'kothar.admin.add' => 'Ajouter',
        'kothar.admin.name_length' => 'Indiquez un nom de 120 caractères au maximum.',
        'kothar.admin.added' => 'Catégorie ajoutée.',
        'kothar.admin.not_found' => 'Catégorie introuvable.',
        'kothar.admin.deleted' => 'Catégorie supprimée. Les compositions enregistrées restent en place.',
        'kothar.admin.save_failed' => 'Échec de l’enregistrement.',
        'kothar.admin.name_required' => 'Le nom ne peut pas être vide.',
        'kothar.admin.column_name_required' => 'Donnez un nom à la colonne.',
        'kothar.admin.saved_category' => 'Catégorie enregistrée.',
        'kothar.admin.column_added' => 'Colonne ajoutée.',
        'kothar.admin.order_saved' => 'Ordre enregistré.',
        'kothar.admin.column_not_found' => 'Colonne introuvable.',
        'kothar.admin.column_deleted' => 'Colonne supprimée.',
        'kothar.admin.option_not_found' => 'Option introuvable.',
        'kothar.admin.option_deleted' => 'Option supprimée.',
        'kothar.admin.option_added' => 'Option ajoutée.',
        'kothar.admin.option_needs_label' => 'Une option a besoin d’un libellé.',
        'kothar.admin.column_saved' => 'Colonne enregistrée.',
        'kothar.admin.unknown_action' => 'Action inconnue.',
        'kothar.admin.category_fallback' => 'Catégorie',
        'kothar.admin.edit_category' => 'Modifier la catégorie',
        'kothar.admin.save_category' => 'Enregistrer la catégorie',
        'kothar.admin.reorder' => 'Modifier l’ordre',
        'kothar.admin.reorder_done' => 'Ordre modifié',
        'kothar.admin.reorder_hint' => 'Faites glisser les colonnes pour changer l’ordre. Vous pouvez les rouvrir via « Ordre modifié ».',
        'kothar.admin.confirm_column' => 'Voulez-vous vraiment supprimer cette colonne ?',
        'kothar.admin.confirm_column_named' => 'Voulez-vous vraiment supprimer la colonne %s ?',
        'kothar.admin.drag_column' => 'Faire glisser la colonne',
        'kothar.admin.hint' => 'Indication',
        'kothar.admin.save' => 'Enregistrer',
        'kothar.admin.delete_column' => 'Supprimer la colonne',
        'kothar.admin.no_options' => 'Pas encore d’options.',
        'kothar.admin.confirm_option' => 'Voulez-vous vraiment supprimer cette option ?',
        'kothar.admin.confirm_option_named' => 'Voulez-vous vraiment supprimer l’option %s ?',
        'kothar.admin.drag_option' => 'Faire glisser l’option',
        'kothar.admin.label' => 'Libellé',
        'kothar.admin.code' => 'Code',
        'kothar.admin.placeholder_label' => 'Nouveau libellé',
        'kothar.admin.add_option' => 'Ajouter une option',
        'kothar.admin.new_column' => 'Nouvelle colonne',
        'kothar.admin.add_column' => 'Ajouter une colonne',
        'kothar.admin.unsaved_confirm' => 'Il y a des modifications non enregistrées. OK les abandonne et change l’ordre.',
        'kothar.admin.unsaved_title' => 'Modifications non enregistrées',
        'kothar.admin.unsaved_body' => 'Il y a des modifications non enregistrées. Voulez-vous les enregistrer ou les abandonner avant de changer l’ordre ?',
        'kothar.admin.discard' => 'Abandonner',
        'kothar.confirm.title' => 'Supprimer',
        'kothar.confirm.cancel' => 'Annuler',
        'kothar.confirm.delete' => 'Supprimer',
        'kothar.confirm.fallback' => 'Voulez-vous vraiment supprimer ceci ?',
        'kothar.cart.removed' => 'Ligne retirée.',
        'kothar.cart.qty_updated' => 'Quantité mise à jour.',
        'kothar.cart.summary' => '%d nouvellement enregistrées, %d existaient déjà.',
        'kothar.cart.empty' => 'Le panier est vide.',
        'kothar.cart.empty_link' => 'Créer une composition',
        'kothar.cart.col.number' => 'Numéro',
        'kothar.cart.col.category' => 'Catégorie',
        'kothar.cart.col.qty' => 'Quantité',
        'kothar.cart.col.saved' => 'Enregistré',
        'kothar.cart.update' => 'Mettre à jour',
        'kothar.cart.register' => 'Enregistrer',
        'kothar.cart.delete' => 'Retirer',
        'kothar.cart.register_all' => 'Enregistrer tous les nouveaux numéros',
        'kothar.cart.hint' => 'Un nouveau numéro reçoit le prix 0,00 €. Modifiez le prix sur la page de détail. Les numéros existants ne sont pas enregistrés deux fois.',
        'kothar.list.heading' => 'Compositions enregistrées',
        'kothar.list.empty' => 'Rien n’est encore enregistré.',
        'kothar.list.empty_link' => 'En créer une',
        'kothar.list.col.price' => 'Prix',
        'kothar.list.col.by' => 'Enregistré par',
        'kothar.detail.title' => 'Composition',
        'kothar.detail.price_saved' => 'Prix enregistré.',
        'kothar.detail.upload_failed' => 'Échec du téléversement.',
        'kothar.detail.too_big' => 'Le fichier dépasse 8 Mo.',
        'kothar.detail.bad_type' => 'Ce type de fichier n’est pas autorisé.',
        'kothar.detail.dir_unwritable' => 'Le dossier des pièces jointes n’est pas accessible en écriture.',
        'kothar.detail.save_failed' => 'L’enregistrement de la pièce jointe a échoué.',
        'kothar.detail.attached' => 'Pièce jointe ajoutée.',
        'kothar.detail.attachment_deleted' => 'Pièce jointe retirée.',
        'kothar.detail.no_match' => 'Ce numéro n’est pas enregistré et ne correspond à aucune catégorie.',
        'kothar.detail.matches_one' => 'Le numéro n’est pas encore enregistré. Il correspond à 1 catégorie.',
        'kothar.detail.matches_many' => 'Le numéro n’est pas encore enregistré. Il correspond à %d catégories.',
        'kothar.detail.open_builder' => 'Ouvrir dans l’assembleur',
        'kothar.detail.not_found' => 'Composition introuvable',
        'kothar.detail.options' => 'Options',
        'kothar.detail.col.column' => 'Colonne',
        'kothar.detail.col.option' => 'Option',
        'kothar.detail.col.code' => 'Code',
        'kothar.detail.col.description' => 'Description',
        'kothar.detail.price' => 'Prix',
        'kothar.detail.save_price' => 'Enregistrer le prix',
        'kothar.detail.attachments' => 'Pièces jointes',
        'kothar.detail.no_attachments' => 'Pas encore de pièces jointes.',
        'kothar.detail.attachment_fallback' => 'pièce jointe',
        'kothar.detail.file' => 'Fichier',
        'kothar.detail.upload' => 'Téléverser',
        'kothar.detail.file_hint' => 'pdf, image, texte, Office, dwg ou zip. 8 Mo maximum.',
        'kothar.detail.rebuild' => 'Recomposer dans cette catégorie',
        'kothar.detail.not_found_file' => 'Pièce jointe introuvable',
        'kothar.scan.heading' => 'Scanner un code-barres',
        'kothar.scan.lead' => 'Scannez un Code128 d’un numéro de composition. La caméra fonctionne en localhost et via HTTPS.',
        'kothar.scan.start_hint' => 'Démarrez la caméra ou saisissez le numéro.',
        'kothar.scan.number' => 'Numéro',
        'kothar.scan.start_camera' => 'Démarrer la caméra',
        'kothar.scan.open' => 'Ouvrir',
        'kothar.scan.fallback_hint' => 'Sans détecteur de code-barres dans le navigateur, ce champ reste utilisable.',
        'kothar.scan.no_detector' => 'Ce navigateur n’a pas de détecteur de code-barres. Saisissez le numéro ci-dessous.',
        'kothar.scan.aim' => 'Dirigez la caméra vers le code-barres.',
        'kothar.scan.camera_unavailable' => 'Caméra indisponible. Saisissez le numéro à la main.',
        'kothar.barcode.non_ascii' => 'Code128 ne peut pas dessiner ce numéro car il contient des caractères hors ASCII.',
        'kothar.barcode.failed' => 'Le code-barres n’a pas pu être créé.',
        'kothar.info.lead' => 'Kothar construit un numéro de composition à partir des options d’une seule catégorie. Chaque code choisi devient un segment, et les segments se suivent séparés par un point.',
        'kothar.info.example' => 'Exemple : code %s, tuyau %s et %s donne %s. Un point de remplissage avec indoor, deux points, ressorts, 05 litres, alarme et sans radar donne %s. L’espace dans %s est en gras dans la feuille source.',
        'kothar.info.rules_heading' => 'Règles, plus tard',
        'kothar.info.rules' => 'La compatibilité entre options, les choix obligatoires et les notes supplémentaires sur cette page ne sont pas encore construits. Les données prévoient pour cela un champ %s vide par catégorie, plus un placeholder %s dans le seed.',
        'kothar.info.codes_heading' => 'Codes de la feuille',
        'kothar.info.codes' => 'Le code d’option est la partie en gras de la cellule dans la feuille Deliverables. Quelques cellules n’ont pas de code en gras (comme une quantité « 00 tot 99 », Steel/Stainless sur les conduites de dry-cooler, et 1-phase/3-phase). Pour ces options, vous saisissez le code vous-même pendant la composition.',
        'kothar.info.excluded' => 'Room vent. et la note Start/Stop des loadbanks figurent dans la feuille, mais n’ont pas de colonne d’options. Ils ne forment donc pas une catégorie.',
    ],
];

/**
 * Map voor taalprefs per e-mail. Tests mogen $GLOBALS['kothar_user_prefs_dir'] zetten.
 */
function userPrefsDirectory(): string
{
    $override = $GLOBALS['kothar_user_prefs_dir'] ?? null;
    if (is_string($override) && $override !== '') {
        return $override;
    }

    return __DIR__ . '/data/user_prefs';
}

function getUserPrefsPath(string $email): ?string
{
    $email = strtolower(trim($email));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return null;
    }
    $dir = userPrefsDirectory();
    $filename = preg_replace('/[^a-z0-9._\-]/', '_', $email) . '.json';

    return $dir . '/' . $filename;
}

function loadUserPrefs(string $email): array
{
    $path = getUserPrefsPath($email);
    if ($path === null || !is_file($path)) {
        return [];
    }
    $data = json_decode((string) file_get_contents($path), true);

    return is_array($data) ? $data : [];
}

function saveUserPref(string $email, string $key, mixed $value): void
{
    $path = getUserPrefsPath($email);
    if ($path === null) {
        return;
    }
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }
    $prefs = loadUserPrefs($email);
    $prefs[$key] = $value;
    file_put_contents($path, json_encode($prefs, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function getCurrentLanguage(): string
{
    $lang = (string) ($_SESSION['lang'] ?? 'nl');

    return array_key_exists($lang, SUPPORTED_LANGUAGES) ? $lang : 'nl';
}

function getHtmlLang(): string
{
    return getCurrentLanguage();
}

function getDateLocale(): string
{
    $lang = getCurrentLanguage();

    return LOCALE_BY_LANG[$lang] ?? 'nl-NL';
}

/**
 * Geeft de vertaling voor $key in de actieve taal.
 * Extra $args worden via sprintf ingevoegd (voor %d, %s, etc.).
 */
function LOC(string $key, mixed ...$args): string
{
    $lang = getCurrentLanguage();
    $translations = TRANSLATIONS[$lang] ?? TRANSLATIONS['nl'];
    $string = $translations[$key] ?? (TRANSLATIONS['nl'][$key] ?? $key);

    return $args !== [] ? sprintf($string, ...$args) : $string;
}

function localizationFlagSvg(string $lang): string
{
    $svg = FLAG_SVGS[$lang] ?? '';
    if ($svg === '') {
        return '';
    }

    $safeLang = preg_replace('/[^a-z0-9]/', '', $lang) ?? $lang;

    return str_replace(
        ['id="a"', 'url(#a)', 'id="b"', 'url(#b)'],
        ['id="flag-' . $safeLang . '-a"', 'url(#flag-' . $safeLang . '-a)', 'id="flag-' . $safeLang . '-b"', 'url(#flag-' . $safeLang . '-b)'],
        $svg
    );
}

function localizationUrlWithLang(string $lang): string
{
    $params = $_GET;
    unset($params['lang']);
    $params['lang'] = $lang;
    $path = strtok((string) ($_SERVER['REQUEST_URI'] ?? ''), '?') ?: '';
    $query = http_build_query($params);

    return $path . ($query !== '' ? '?' . $query : '');
}

function localizationJsTranslations(array $keys): string
{
    $payload = [];
    foreach ($keys as $key) {
        $payload[$key] = LOC($key);
    }

    return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
}

function renderLanguageSwitcherStyles(): void
{
    echo <<<'CSS'
<style>
.lang-switcher {
    position: fixed;
    top: 12px;
    right: 12px;
    z-index: 5000;
    font-family: inherit;
}
.lang-switcher-toggle {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 30px;
    padding: 0;
    border: 1px solid rgba(0, 82, 155, 0.25);
    border-radius: 6px;
    background: #ffffff;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.12);
    cursor: pointer;
}
.lang-switcher-toggle:hover {
    background: #f2f9ff;
}
.lang-switcher-toggle svg {
    width: 28px;
    height: auto;
    display: block;
    border-radius: 2px;
    overflow: hidden;
}
.lang-switcher-menu {
    position: absolute;
    top: calc(100% + 6px);
    right: 0;
    min-width: 160px;
    margin: 0;
    padding: 6px;
    list-style: none;
    background: #ffffff;
    border: 1px solid #c9d7eb;
    border-radius: 10px;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.18);
    display: none;
}
.lang-switcher.is-open .lang-switcher-menu {
    display: block;
}
.lang-switcher-item a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 10px;
    border-radius: 8px;
    color: var(--kvt-text, #1f2937);
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
}
.lang-switcher-item a:hover {
    background: #edf7ff;
}
.lang-switcher-item.is-active a {
    background: #e6f4ff;
}
.lang-switcher-item svg {
    width: 24px;
    height: auto;
    flex-shrink: 0;
    border-radius: 2px;
    overflow: hidden;
}
@media print {
    .lang-switcher {
        display: none !important;
    }
}
</style>
CSS;
}

function renderLanguageSwitcher(): void
{
    $current = getCurrentLanguage();
    $menuAria = htmlspecialchars(LOC('lang.menu_aria'), ENT_QUOTES);

    echo '<div class="lang-switcher" data-lang-switcher>';
    echo '<button type="button" class="lang-switcher-toggle" aria-haspopup="true" aria-expanded="false" aria-label="' . $menuAria . '">';
    echo localizationFlagSvg($current);
    echo '</button>';
    echo '<ul class="lang-switcher-menu" role="menu">';

    foreach (SUPPORTED_LANGUAGES as $code => $meta) {
        if ($code === $current) {
            continue;
        }

        $label = (string) ($meta['label'] ?? $code);
        $href = htmlspecialchars(localizationUrlWithLang($code), ENT_QUOTES);
        $title = htmlspecialchars(LOC('lang.switch_to', $label), ENT_QUOTES);

        echo '<li class="lang-switcher-item" role="none">';
        echo '<a role="menuitem" href="' . $href . '" title="' . $title . '">';
        echo localizationFlagSvg($code);
        echo '<span>' . htmlspecialchars($label) . '</span>';
        echo '</a>';
        echo '</li>';
    }

    echo '</ul>';
    echo '</div>';
}

function renderLanguageSwitcherScript(): void
{
    echo <<<'JS'
<script>
(function () {
    document.querySelectorAll('[data-lang-switcher]').forEach(function (root) {
        var toggle = root.querySelector('.lang-switcher-toggle');
        if (!toggle) {
            return;
        }

        toggle.addEventListener('click', function (event) {
            event.stopPropagation();
            var isOpen = root.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        document.addEventListener('click', function () {
            root.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
        });

        root.addEventListener('click', function (event) {
            event.stopPropagation();
        });
    });
})();
</script>
JS;
}

/**
 * Past ?lang= toe, bewaart de keuze bij het e-mailadres en haalt de parameter
 * uit de URL. De sessie blijft open: Kothar schrijft daarna winkelwagen en flashes.
 * Tests zetten $GLOBALS['kothar_lang_redirect_capture'] om de redirect te vangen.
 */
function kothar_bootstrap_language(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }

    if (!isset($_SESSION['lang'])) {
        $prefEmail = strtolower(trim((string) ($_SESSION['user']['email'] ?? '')));
        if ($prefEmail !== '') {
            $savedPrefs = loadUserPrefs($prefEmail);
            if (isset($savedPrefs['lang']) && is_string($savedPrefs['lang']) && array_key_exists($savedPrefs['lang'], SUPPORTED_LANGUAGES)) {
                $_SESSION['lang'] = $savedPrefs['lang'];
            }
        }
    }

    if (!isset($_SESSION['lang']) || !array_key_exists((string) $_SESSION['lang'], SUPPORTED_LANGUAGES)) {
        $_SESSION['lang'] = 'nl';
    }

    $requested = $_GET['lang'] ?? null;
    if (!is_string($requested) || !array_key_exists($requested, SUPPORTED_LANGUAGES)) {
        return;
    }

    $langChanged = $requested !== getCurrentLanguage();
    $_SESSION['lang'] = $requested;
    $prefEmail = strtolower(trim((string) ($_SESSION['user']['email'] ?? '')));
    if ($prefEmail !== '' && $langChanged) {
        saveUserPref($prefEmail, 'lang', $requested);
    }

    $isApiAction = isset($_GET['action']) && trim((string) $_GET['action']) !== '';
    if ($isApiAction || strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'GET') {
        return;
    }

    $params = $_GET;
    unset($params['lang']);
    $path = strtok((string) ($_SERVER['REQUEST_URI'] ?? ''), '?') ?: '';
    $query = http_build_query($params);
    $target = $path . ($query !== '' ? '?' . $query : '');
    if (!empty($GLOBALS['kothar_lang_redirect_capture'])) {
        $GLOBALS['kothar_lang_redirect_to'] = $target;

        return;
    }
    header('Location: ' . $target);
    exit;
}

kothar_bootstrap_language();
