<?php
/**
 * Hydynamic – opbouwscript
 *
 * Zet de complete site klaar in de admin: media, offerteformulier (Gravity Forms),
 * pagina's, producten, oplossingen, diensten, kennisbank, menu's en instellingen.
 * Alle pagina's zijn opgebouwd uit de Hydynamic-blokken en blijven daarna gewoon
 * in de editor aanpasbaar.
 *
 * Gebruik (vanuit de themamap):
 *   php setup/hydynamic-setup.php
 *
 * Opnieuw draaien is veilig: bestaande items (herkend op slug/sleutel) worden
 * bijgewerkt in plaats van dubbel aangemaakt.
 */

if (php_sapi_name() !== 'cli') {
    exit("Alleen via de command line.\n");
}

$_SERVER['HTTP_HOST']   = $_SERVER['HTTP_HOST'] ?? 'hydynamic.test';
$_SERVER['HTTPS']       = 'on';
$_SERVER['REQUEST_URI'] = '/';
define('WP_USE_THEMES', false);
require __DIR__ . '/../../../../wp-load.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/nav-menu.php';

if (!function_exists('hyd_block_markup')) {
    exit("Het Mediakanjers child theme (Hydynamic) is niet actief.\n");
}

$admins = get_users(['role' => 'administrator', 'number' => 1, 'orderby' => 'ID']);
wp_set_current_user($admins ? $admins[0]->ID : 1);

// SVG's toestaan tijdens het importeren
add_filter('upload_mimes', fn($m) => $m + ['svg' => 'image/svg+xml']);
add_filter('wp_check_filetype_and_ext', function ($data, $file, $filename) {
    if (substr(strtolower($filename), -4) === '.svg') {
        return ['ext' => 'svg', 'type' => 'image/svg+xml', 'proper_filename' => $filename];
    }
    return $data;
}, 10, 3);

function out($msg) { echo $msg . "\n"; }

/* =========================================================================
 * Media
 * ====================================================================== */

$MEDIA_DIR = __DIR__ . '/media';
$manifest  = json_decode(file_get_contents($MEDIA_DIR . '/manifest.json'), true);
$MEDIA     = [];
foreach ($manifest as $m) {
    $m['file'] = $MEDIA_DIR . '/' . basename(str_replace('\\', '/', $m['file']));
    $MEDIA[$m['key']] = $m;
}

function hyd_media($key) {
    global $MEDIA;
    static $cache = [];
    if (isset($cache[$key])) return $cache[$key];
    if (!isset($MEDIA[$key])) { out("  ! media ontbreekt: $key"); return 0; }

    $existing = get_posts(['post_type' => 'attachment', 'post_status' => 'inherit', 'meta_key' => '_hyd_key', 'meta_value' => $key, 'numberposts' => 1, 'fields' => 'ids']);
    if ($existing) return $cache[$key] = (int) $existing[0];

    $m   = $MEDIA[$key];
    $tmp = wp_tempnam(basename($m['file']));
    copy($m['file'], $tmp);
    $id = media_handle_sideload(['name' => basename($m['file']), 'tmp_name' => $tmp], 0, $m['title'] ?: null);
    if (is_wp_error($id)) { out("  ! upload mislukt $key: " . $id->get_error_message()); return 0; }
    update_post_meta($id, '_hyd_key', $key);
    update_post_meta($id, '_wp_attachment_image_alt', $m['alt']);
    if ($m['title']) wp_update_post(['ID' => $id, 'post_title' => $m['title']]);
    return $cache[$key] = (int) $id;
}

/* =========================================================================
 * Helpers voor content
 * ====================================================================== */

function blocks(array $list) {
    return implode("\n\n", array_map(fn($b) => hyd_block_markup($b[0], $b[1] ?? []), $list));
}

function upsert($type, $slug, $title, $content, $args = []) {
    $parent = (int) ($args['parent'] ?? 0);
    $found  = get_posts([
        'post_type'   => $type,
        'name'        => $slug,
        'post_parent' => $parent,
        'post_status' => ['publish', 'draft', 'private', 'pending'],
        'numberposts' => 1,
    ]);
    if (!$found && !empty($args['id'])) $found = [get_post($args['id'])];

    $data = array_merge([
        'post_type'    => $type,
        'post_name'    => $slug,
        'post_title'   => $title,
        'post_content' => wp_slash($content),
        'post_status'  => 'publish',
        'post_parent'  => $parent,
        'menu_order'   => (int) ($args['order'] ?? 0),
    ], array_intersect_key($args, array_flip(['post_excerpt', 'post_date', 'post_author'])));
    if (!empty($args['post_excerpt'])) $data['post_excerpt'] = $args['post_excerpt'];

    if ($found && $found[0]) {
        $data['ID'] = $found[0]->ID;
        $id = wp_update_post($data, true);
    } else {
        $id = wp_insert_post($data, true);
    }
    if (is_wp_error($id)) { out("  ! $type/$slug: " . $id->get_error_message()); return 0; }

    if (!empty($args['thumb'])) set_post_thumbnail($id, $args['thumb']);
    if (!empty($args['metadesc'])) update_post_meta($id, '_yoast_wpseo_metadesc', $args['metadesc']);
    out("  ✓ $type: $title");
    return (int) $id;
}

function link_to($id, $title = '') {
    return ['title' => $title ?: get_the_title($id), 'url' => wp_make_link_relative(get_permalink($id)), 'target' => '']; // relatief: werkt op .nl én .com
}

/** PDF van de live site importeren (eenmalig, herkend op pad). */
function hyd_pdf($path, $title) {
    static $cache = [];
    $key = 'pdf:' . $path;
    if (isset($cache[$key])) return $cache[$key];
    $existing = get_posts(['post_type' => 'attachment', 'post_status' => 'inherit', 'meta_key' => '_hyd_key', 'meta_value' => $key, 'numberposts' => 1, 'fields' => 'ids']);
    if ($existing) return $cache[$key] = (int) $existing[0];
    $tmp = download_url('https://www.hydynamic.nl/wp-content/uploads/' . $path, 600);
    if (is_wp_error($tmp)) { out("  ! PDF niet gedownload: $path"); return 0; }
    $id = media_handle_sideload(['name' => basename($path), 'tmp_name' => $tmp], 0, $title);
    if (is_wp_error($id)) { out("  ! PDF upload mislukt: $path"); return 0; }
    update_post_meta($id, '_hyd_key', $key);
    out("  ✓ PDF: $title");
    return $cache[$key] = (int) $id;
}

/** Bestaand item hernoemen naar de nieuwe slug (eerdere opbouw → structuur uit de export). */
function hyd_rename($type, $old, $new, $title = null) {
    $old_p = get_posts(['post_type' => $type, 'name' => $old, 'post_status' => ['publish', 'draft'], 'numberposts' => 1]);
    $new_p = get_posts(['post_type' => $type, 'name' => $new, 'post_status' => ['publish', 'draft'], 'numberposts' => 1]);
    if ($old_p && !$new_p) {
        wp_update_post(array_filter(['ID' => $old_p[0]->ID, 'post_name' => $new, 'post_title' => $title]));
        out("  ↻ $type: $old → $new");
    }
}

function pdf_link($id, $title) {
    return $id ? ['title' => $title, 'url' => wp_make_link_relative(wp_get_attachment_url($id)), 'target' => '_blank'] : '';
}

function usp(array $items) {
    return array_map(fn($t) => is_array($t) ? ['tekst' => $t[0], 'accent' => $t[1]] : ['tekst' => $t, 'accent' => ''], $items);
}

/* =========================================================================
 * 1. Media importeren
 * ====================================================================== */
out("\n[1/8] Media importeren…");
foreach (array_keys($MEDIA) as $key) {
    if (strpos($key, 'kbi-') === 0) continue; // inline kennisbank-afbeeldingen: bij de artikelen
    hyd_media($key);
}
out('  ✓ ' . count($MEDIA) . ' bestanden beschikbaar');

/* =========================================================================
 * 2. Gravity Forms – offerteformulier
 * ====================================================================== */
out("\n[2/8] Offerteformulier…");
$form_id = 0;
if (class_exists('GFAPI')) {
    foreach (GFAPI::get_forms(null) as $f) {
        if ($f['title'] === 'Offerte aanvragen') $form_id = (int) $f['id'];
    }
    $form = [
        'title'          => 'Offerte aanvragen',
        'description'    => '',
        'labelPlacement' => 'top_label',
        'button'         => ['type' => 'text', 'text' => 'Aanvraag versturen'],
        'enableHoneypot' => true,
        'markupVersion'  => 2,
        'fields'         => [
            ['id' => 1, 'type' => 'text', 'label' => 'Naam', 'placeholder' => 'Wat is uw naam?', 'isRequired' => true, 'autocompleteAttribute' => 'name', 'enableAutocomplete' => true],
            ['id' => 2, 'type' => 'text', 'label' => 'Bedrijfsnaam', 'placeholder' => 'Wat is uw bedrijfsnaam?', 'isRequired' => true, 'autocompleteAttribute' => 'organization', 'enableAutocomplete' => true],
            ['id' => 3, 'type' => 'phone', 'label' => 'Telefoon', 'placeholder' => 'Wat is uw telefoonnummer?', 'phoneFormat' => 'international', 'isRequired' => true, 'autocompleteAttribute' => 'tel', 'enableAutocomplete' => true],
            ['id' => 4, 'type' => 'email', 'label' => 'E-mailadres', 'placeholder' => 'Wat is uw e-mail?', 'isRequired' => true, 'autocompleteAttribute' => 'email', 'enableAutocomplete' => true],
            ['id' => 5, 'type' => 'textarea', 'label' => 'Bericht', 'placeholder' => 'Waarmee kunnen wij u helpen? Beschrijf hier uw vraag.', 'isRequired' => false],
            ['id' => 6, 'type' => 'hidden', 'label' => 'Verstuurd vanaf pagina', 'defaultValue' => '{embed_url}'],
        ],
        'confirmations' => [
            'hyd_bedankt' => ['id' => 'hyd_bedankt', 'name' => 'Standaard bevestiging', 'isDefault' => true, 'type' => 'message',
                'message' => '<strong>Bedankt voor uw aanvraag.</strong> Wij nemen zo snel mogelijk contact met u op.'],
        ],
        'notifications' => [
            'hyd_admin' => ['id' => 'hyd_admin', 'name' => 'Nieuwe offerteaanvraag', 'isActive' => true, 'event' => 'form_submission',
                'to' => '{admin_email}', 'toType' => 'email', 'subject' => 'Nieuwe offerteaanvraag via {embed_url}',
                'message' => '{all_fields}', 'replyTo' => '{E-mailadres:4}', 'from' => '{admin_email}', 'fromName' => 'Hydynamic website'],
        ],
    ];
    if ($form_id) {
        $form['id'] = $form_id;
        GFAPI::update_form($form);
    } else {
        $form_id = (int) GFAPI::add_form($form);
    }
    GFAPI::update_form_property($form_id, 'is_active', 1);
    out("  ✓ Formulier 'Offerte aanvragen' (#$form_id)");
} else {
    out('  ! Gravity Forms is niet actief');
}

/* =========================================================================
 * 3. Kennisbank (berichten van de live site)
 * ====================================================================== */
out("\n[3/8] Kennisbank-artikelen…");
$author = get_user_by('login', 'laurenskooy');
if (!$author) {
    $uid = wp_insert_user(['user_login' => 'laurenskooy', 'user_pass' => wp_generate_password(24), 'display_name' => 'Laurens Kooy', 'first_name' => 'Laurens', 'last_name' => 'Kooy', 'role' => 'author', 'user_email' => 'laurens.kooy@hydynamic.invalid']);
    $author = get_user_by('id', $uid);
}
$hello = get_page_by_path('hello-world', OBJECT, 'post');
if ($hello) wp_trash_post($hello->ID);

$live_posts = json_decode(file_get_contents(__DIR__ . '/live-posts.json'), true);
foreach (array_reverse($live_posts) as $p) {
    $html = $p['content']['rendered'];
    // Inline afbeeldingen vervangen door de lokale kopie
    $i = 0;
    $html = preg_replace_callback('/<img[^>]+>/', function ($m) use ($p, &$i) {
        $key = 'kbi-' . $p['id'] . '-' . $i++;
        $id  = hyd_media($key);
        if (!$id) return '';
        preg_match('/alt="([^"]*)"/', $m[0], $alt);
        if (!empty($alt[1])) update_post_meta($id, '_wp_attachment_image_alt', html_entity_decode($alt[1]));
        $img = wp_get_attachment_image($id, 'large', false, ['alt' => html_entity_decode($alt[1] ?? ''), 'class' => 'wp-image-' . $id]);
        return $img;
    }, $html);
    $html = str_replace(['https://www.hydynamic.com/', 'https://www.hydynamic.nl/'], home_url('/'), $html);

    $title = html_entity_decode($p['title']['rendered'], ENT_QUOTES, 'UTF-8');
    upsert('post', $p['slug'], $title, $html, [
        'post_date'    => str_replace('T', ' ', $p['date']),
        'post_author'  => $author ? $author->ID : 1,
        'post_excerpt' => wp_strip_all_tags(html_entity_decode($p['excerpt']['rendered'] ?? '')),
        'thumb'        => hyd_media('kb-' . $p['id']),
    ]);
}

/* =========================================================================
 * 4. Gedeelde content
 * ====================================================================== */

$USP_HYDRAULIEK = usp(['Officieel Danfoss distributeur', 'DNV geautoriseerd assemblagecentrum', 'Gecertificeerde assemblage in 1 werkdag', 'ISO 9001 gecertificeerd']);

// Antwoorden zijn een concept op basis van de teksten uit het ontwerp — laten controleren door Hydynamic.
$FAQ = [
    ['vraag' => 'Welke hydrauliekslang of koppeling heb ik nodig voor mijn toepassing?',
     'antwoord' => '<p>Dat hangt af van de werkdruk, het medium, de temperatuur en de aansluiting op uw systeem. Stuur ons de specificaties, een tekening of een monster, dan adviseren wij de juiste combinatie van slang, fitting en koppeling uit het Danfoss-assortiment of onze eigen productlijn.</p>'],
    ['vraag' => 'Kunnen jullie hydrauliekslangen en leidingen op maat assembleren?',
     'antwoord' => '<p>Ja. Slangassemblages worden in eigen beheer geperst, gegraveerd voor traceerbaarheid, gereinigd en getest. Als DNV geautoriseerd assemblagecentrum keurt en certificeert Hydynamic een assemblage desgewenst zelfstandig.</p>'],
    ['vraag' => 'Kunnen jullie ook een compleet centraal smeersysteem samenstellen?',
     'antwoord' => '<p>Hydynamic ontwikkelt en produceert componenten voor centrale smeersystemen: polyamide leidingen, push in connectoren, smeernippels en onderdelen voor nippelblokken en verdeelblokken. Neem contact op om de samenstelling voor uw toepassing te bespreken.</p>'],
    ['vraag' => 'Wat is de levertijd van standaardproducten en maatwerk?',
     'antwoord' => '<p>Standaardproducten worden uit voorraad geleverd. Een DNV gecertificeerde slangassemblage is op verzoek binnen één werkdag leverbaar. Voor maatwerk naar tekening of monster ontvangt u de levertijd bij de offerte.</p>'],
];
$FAQ[1]['vraag'] = 'Kunnen jullie hydrauliekslangen en leidingen op maat assembleren?';

$KB      = ['kennisbank', ['titel' => 'Kennisbank', 'aantal' => '4', 'knoptekst' => 'Bekijk alle artikelen']];
$OFFERTE = ['offerte', []];

/* =========================================================================
 * 5. Pagina's (eerst de structuur, zodat links kloppen)
 * ====================================================================== */
out("\n[4/8] Basisstructuur…");
$home_id     = (int) get_option('page_on_front') ?: upsert('page', 'homepage', 'Homepage', '');
$page_prod   = upsert('page', 'producten', 'Producten', '');
$page_opl    = upsert('page', 'oplossingen', 'Oplossingen', '');
$page_dienst = upsert('page', 'diensten', 'Diensten', '');
$page_kb     = upsert('page', 'kennisbank', 'Kennisbank', '', ['post_excerpt' => 'Achtergrond en uitleg over hydrauliekslangen, koppelingen, centrale smering en certificering.']);
$page_over   = upsert('page', 'over-ons', 'Over ons', '');
$page_cert   = upsert('page', 'certificeringen', 'Certificeringen', '');
$contact_existing = get_page_by_path('contact');
$page_contact = upsert('page', 'contact', 'Contact', '', ['id' => $contact_existing ? $contact_existing->ID : 0]);

// Slugs gelijktrekken met de structuur uit de export van de stagingsite
foreach ([
    ['hydrauliek-slangen-en-koppelingen', 'hydrauliekslangen-koppelingen'], ['hydrauliek-slangen', 'hydrauliekslangen', 'Hydrauliekslangen'],
    ['fittingen-en-adapters', 'fittingen-adapters'], ['polyamide-leidingen-pa12', 'polyamide-buizen-pa12'],
    ['smeernippels-en-vetspuiten', 'smeernippels-vetpersen'], ['nippelblokken-en-accessoires', 'verdeelblokken-accessoires'],
] as $r) hyd_rename('producten', $r[0], $r[1], $r[2] ?? null);
hyd_rename('diensten', 'klantspecifiek', 'maatwerk-productie', 'Maatwerk productie');
hyd_rename('oplossingen', 'voedings-en-drankenindustrie', 'voedingsindustrie-en-drankenindustrie');
foreach (get_posts(['post_type' => 'producten', 'name' => 'flexmaster', 'post_status' => 'publish', 'numberposts' => 1]) as $p) {
    wp_trash_post($p->ID); // Flexmaster valt onder Hydrauliekslangen (menu + zoekwoorden uit de export)
}

// PDF's (uit de export / live site)
out("\n  PDF's…");
$PDF = [
    'catalogus'   => hyd_pdf('2026/01/Hydynamic_Catalogue_26.pdf', 'Hydynamic productcatalogus 2026'),
    'zoutspray'   => hyd_pdf('2024/04/Zoutspray-test.pdf', 'Zoutsproeitest zink nikkel (ZnNi)'),
    'pa-spec'     => hyd_pdf('2024/04/Specification-Hydynamic-PA6x15-emptyENG-2.pdf', 'Specificatie Hydynamic PA leiding 6x1,5'),
    'vet-ep00'    => hyd_pdf('2024/04/Product-specification-Grease-EP00-000-ENG.pdf', 'Productspecificatie vet EP00/000'),
    'danfoss-kat' => hyd_pdf('2025/06/DANFOSS-KAT.pdf', 'Danfoss Hydraulic Hoses'),
    'reusable'    => hyd_pdf('2025/06/Reusable_EA001J.pdf', 'Danfoss Reusable Fittings'),
    'danfoss-ac'  => hyd_pdf('2025/06/Danfoss-AC.pdf', 'Danfoss A/C and Refrigeration'),
    'flexmaster'  => hyd_pdf('2025/06/DANFOSS-FLEXMASTER.pdf', 'Danfoss Flexmaster'),
    'iso'         => hyd_pdf('2025/07/ISO-9001-277351-2018-AQ-NLD-RvA-2-20241003.pdf', 'ISO 9001:2015 certificaat Hydynamic Holland BV'),
    'dnv'         => hyd_pdf('2025/10/AMT0000099-revision4.pdf', 'DNV Approval of Manufacturer certificaat'),
    'privacy'     => hyd_pdf('2023/06/Privacy-statement-NL_2023.pdf', 'Privacy statement Hydynamic'),
    'voorwaarden' => hyd_pdf('2024/01/General-terms-and-conditions-NL-DE-EN.pdf', 'Algemene voorwaarden Hydynamic (NL-DE-EN)'),
];

$page_faq = upsert('page', 'veelgestelde-vragen', 'Veelgestelde vragen', '');

// Productcategorieën + producten
$CATS = [
    'hydrauliekslangen-koppelingen' => ['Hydrauliekslangen en koppelingen', [
        'hydrauliekslangen'   => ['Hydrauliekslangen', 'tile-hydrauliek-slangen', 'Danfoss slangseries, aangevuld met eigen DN4 en DN6 hogedrukslangen.'],
        'fittingen-adapters'=> ['Fittingen en adapters', 'tile-fittingen-adapters', 'Gangbare schroefdraadstandaarden, snijringkoppelingen volgens DIN 2353 en ISO B snelkoppelingen.'],
        'perskoppelingen'      => ['Perskoppelingen', 'tile-perskoppelingen', 'Crimpfittingen per slangtype, afgestemd op de Danfoss slangseries.'],
    ]],
    'centrale-smering' => ['Centrale smering', [
        'polyamide-buizen-pa12'   => ['Polyamide leidingen (PA12)', 'tile-polyamide-leidingen', 'Leidingen en meervoudige leidingen, inclusief fittingen zoals rechte en haakse koppelingen, T stukken en doorvoerkoppelingen.'],
        'push-in-connectoren'        => ['Push-in connectoren', 'tile-push-in', 'Snelsteekkoppelingen voor rechte, haakse en T verbindingen, met terugslagklep en stofkapuitvoeringen.'],
        'smeernippels-vetpersen' => ['Smeernippels en vetspuiten', 'tile-smeernippels', 'Smeernippels, vetpersen en vetvulnippels voor het aanbrengen van vet op smeerpunten.'],
        'verdeelblokken-accessoires' => ['Nippelblokken en accessoires', 'tile-nippelblokken', 'Onderdelen voor nippelblokken en verdeelblokken, zoals manometers, terugslagkleppen, schroefpluggen, beschermveren en slangklemmen.'],
    ]],
    'industrieel' => ['Industrieel', [
        'zuivelkoppelingen'      => ['Zuivelkoppelingen', 'tile-zuivelkoppelingen', 'Hygiënische koppelingen conform DIN 11851 en DIN 11853, voor de zuivel, voedings-middelen en farmaceutische industrie.'],
        'storz-koppelingen'      => ['Storz koppelingen', 'tile-storz', 'Snelkoppelingen met een symmetrische klauwverbinding die met een kwartslag wordt vastgezet, zonder onderscheid tussen een mannelijk en vrouwelijk deel. Gangbaar bij brand-bestrijding en watertransport.'],
        'camlock-koppelingen'    => ['Camlock koppelingen', 'tile-camlock', 'Snelkoppelingen die met twee hendels zonder gereedschap worden vast en losgezet, gangbaar waar leidingen regelmatig aan en afgekoppeld moeten worden, zoals bij tankwagenlossing.'],
        'tankwagenkoppelingen'   => ['Tankwagenkoppelingen', 'tile-tankwagen', 'Koppelingen voor het laden en lossen van tankwagens, met een vergrendeling tegen onbedoeld loskomen.'],
        'spiraalslangen'         => ['Spiraalslangen', '', 'PVC spiraalslangen voor het transporteren van vloeistoffen en andere media.'],
        'industriele-slangen'    => ['Industriële slangen', '', 'Industriële slangen, waaronder EPDM-slangen, reinigingsslangen en levensmiddelenslangen.'],
    ]],
];
$CAT_IDS = [];
$PROD_IDS = [];
$o = 0;
foreach ($CATS as $slug => [$title, $children]) {
    $CAT_IDS[$slug] = upsert('producten', $slug, $title, '', ['order' => $o++]);
    $c = 0;
    foreach ($children as $cslug => $child) {
        $PROD_IDS[$cslug] = upsert('producten', $cslug, $child[0], '', ['parent' => $CAT_IDS[$slug], 'order' => $c++, 'thumb' => $child[1] ? hyd_media($child[1]) : 0]);
    }
}

$OPLOSSINGEN = [
    'offshore-en-maritiem'          => 'Offshore en Maritiem',
    'luchtvaart-gse'                => 'Luchtvaart (GSE)',
    'machinebouw-oem'               => 'Machinebouw (OEM)',
    'agrarisch-en-bosbouw'          => 'Agrarisch en Bosbouw',
    'grondverzet-en-mijnbouw'       => 'Grondverzet en Mijnbouw',
    'transport-en-logistiek'        => 'Transport en Logistiek',
    'duurzame-energie'              => 'Duurzame Energie',
    'recycling'                     => 'Recycling',
    'voedingsindustrie-en-drankenindustrie'  => 'Voedings- en drankenindustrie',
];
$OPL_IDS = [];
$o = 0;
foreach ($OPLOSSINGEN as $slug => $title) {
    $OPL_IDS[$slug] = upsert('oplossingen', $slug, $title, '', ['order' => $o++]);
}
$DIENSTEN = [
    'slangassemblage-op-maat'     => 'Slangassemblage op maat',
    'dnv-certificering'           => 'DNV-certificering',
    'maatwerk-productie'          => 'Maatwerk productie',
    'voorvullen-smeerslangen'     => 'Voorvullen smeerslangen',
    'oppervlaktebehandeling-znni' => 'Oppervlaktebehandeling (ZnNi)',
];
$DIENST_IDS = [];
$o = 0;
foreach ($DIENSTEN as $slug => $title) $DIENST_IDS[$slug] = upsert('diensten', $slug, $title, '', ['order' => $o++]);

/* =========================================================================
 * 6. Pagina-inhoud
 * ====================================================================== */
out("\n[5/8] Homepage en vaste pagina's…");

// --- Homepage (overgenomen van de live site)
upsert('page', get_post_field('post_name', $home_id) ?: 'homepage', 'Homepage', blocks([
    ['home-hero', [
        'achtergrondfoto' => hyd_media('home-hero'),
        'titel'           => 'Your trusted supplier for lubrication & fluid technology',
        'tegels'          => [
            ['icoon' => hyd_media('icon-smering'), 'titel' => 'Centrale smering', 'link' => link_to($CAT_IDS['centrale-smering'], 'Centrale smering'), 'tekst' => 'Producent van snijring koppelingen, schroefhulsen, schroefpilaren terugslagkleppen, adapters en accessoires voor centrale smeersystemen in diverse materialen.'],
            ['icoon' => hyd_media('icon-klantspecifiek'), 'titel' => 'Klantspecifiek', 'link' => link_to($DIENST_IDS['maatwerk-productie'], 'Klantspecifiek'), 'tekst' => 'Klant specifieke productie van onderdelen in zowel grote als kleine series, in diverse materialen. Flexibiliteit, maatwerk en hoogwaardige kwaliteit staan hierbij centraal.'],
            ['icoon' => hyd_media('icon-hydrauliek'), 'titel' => 'Hydrauliek', 'link' => link_to($CAT_IDS['hydrauliekslangen-koppelingen'], 'Hydrauliek'), 'tekst' => 'Specialist in hydrauliek, slangen, perskoppelingen, flexmasters en accessoires. Als officieel distributeur van Danfoss bieden wij hoogwaardige producten en kunnen wij certificeringen volledig in eigen beheer uitvoeren.'],
            ['icoon' => hyd_media('icon-industrieel'), 'titel' => 'Industrieel', 'link' => link_to($CAT_IDS['industrieel'], 'Industrieel'), 'tekst' => 'Ontdek het brede assortiment van Hydynamic met PVC spiraalslangen, reinigingsslangen, storzen en camlock-koppelingen. Hoogwaardige oplossingen voor diverse industriële toepassingen.'],
        ],
    ]],
    ['usp-balk', ['stijl' => 'licht', 'items' => usp([['Kwaliteit maakt ons uniek', 'uniek'], ['Meer dan 25 jaar ervaring', 'ervaring'], ['Internationale leverancier', 'leverancier'], ['Ontwikkelen en produceren', 'produceren']])]],
    ['tekst-afbeelding', [
        'titel' => 'Sinds 1997', 'kopniveau' => 'h2',
        'tekst' => '<p>Hydynamic Holland BV werd in 1997 opgericht. Destijds was onze hoofdactiviteit de assemblage van hydraulische slangen. Kort na onze oprichting zijn wij officieel distributeur van Danfoss geworden. Inmiddels zijn wij al meer dan 24 jaar actief als Danfoss-distributeur (voorheen Eaton Aeroquip) en opereren wij nog altijd binnen de hydraulische sector. Onze focus ligt op kwaliteit en juist daarin maken wij het verschil. Niet voor niets luidt onze slogan dan ook: “kwaliteit maakt ons uniek”.</p>',
        'media' => 'afbeelding', 'positie' => 'rechts', 'afbeelding' => hyd_media('home-pand'), 'uitlopend' => 1,
        'knop_type' => 'link', 'knop_link' => link_to($page_over, 'Lees meer'),
        'achtergrond' => 'donker', 'ruimte' => 'normaal',
    ]],
    ['kennisbank', ['titel' => 'Kennisbank', 'subtitel' => 'Blijf op de hoogte', 'aantal' => '4', 'knoptekst' => 'Bekijk alle berichten']],
    $OFFERTE,
]), ['id' => $home_id, 'metadesc' => 'Hydynamic Holland BV in Enschede: officieel Danfoss distributeur, DNV geautoriseerd assemblagecentrum en producent van componenten voor centrale smering. Vraag vrijblijvend een offerte aan.']);

// --- Producten (overzicht)
$cat_tiles = [
    ['afbeelding' => hyd_media('tile-hydrauliek-slangen'), 'titel' => 'Hydrauliekslangen en koppelingen', 'tekst' => 'Slang, fitting en perskoppeling uit één hand: Danfoss merken en een eigen productlijn.', 'link' => link_to($CAT_IDS['hydrauliekslangen-koppelingen'])],
    ['afbeelding' => hyd_media('tile-polyamide-leidingen'), 'titel' => 'Centrale smering', 'tekst' => 'Componenten voor centrale smeersystemen, in eigen beheer ontwikkeld en geproduceerd.', 'link' => link_to($CAT_IDS['centrale-smering'])],
    ['afbeelding' => hyd_media('tile-camlock'), 'titel' => 'Industrieel', 'tekst' => 'Industriële koppelingen en slangen voor het verbinden en verplaatsen van vloeistoffen.', 'link' => link_to($CAT_IDS['industrieel'])],
];
upsert('page', 'producten', 'Producten', blocks([
    ['paginakop', ['variant' => 'tekst', 'titel' => 'Producten', 'intro' => '<p>Hydynamic levert hydrauliekslangen en koppelingen, componenten voor centrale smering en industriële koppelingen en slangen. Kies een productgroep voor het volledige assortiment.</p>']],
    ['tegels', ['titel' => 'Onze productgroepen', 'kopniveau' => 'h2', 'kolommen' => '3', 'linktekst' => 'Bekijk producten', 'tegels' => $cat_tiles, 'achtergrond' => 'grijs', 'ruimte' => 'aansluitend']],
    ['usp-balk', ['stijl' => 'donker', 'items' => $USP_HYDRAULIEK]],
    $KB, $OFFERTE,
]), ['metadesc' => 'Het assortiment van Hydynamic: hydrauliekslangen en koppelingen, componenten voor centrale smering en industriële koppelingen en slangen.']);

/* --- Productcategorieën */
out("\n[6/8] Producten…");

function cat_tiles($slug) {
    global $CATS, $PROD_IDS;
    $tiles = [];
    foreach ($CATS[$slug][1] as $cslug => $c) {
        if (!$c[1]) continue; // tegels zoals in het ontwerp (alleen producten met ontwerpfoto)
        $tiles[] = ['afbeelding' => hyd_media($c[1]), 'titel' => $c[0], 'tekst' => $c[2], 'link' => link_to($PROD_IDS[$cslug], $c[0])];
    }
    if ($slug === 'hydrauliekslangen-koppelingen') {
        $tiles[] = ['afbeelding' => hyd_media('tile-flexmaster'), 'titel' => 'Flexmaster', 'tekst' => 'Pijp en buiskoppelingen voor lagerdruk, die trillingen en uitlijning opvangen.', 'link' => link_to($PROD_IDS['hydrauliekslangen'], 'Flexmaster')];
    }
    return $tiles;
}

$intro_hydr = 'Slang, fitting en perskoppeling uit één hand: Danfoss merken en een eigen productlijn, desgewenst geleverd als gecertificeerde slangassemblage.';
upsert('producten', 'hydrauliekslangen-koppelingen', 'Hydrauliekslangen en koppelingen', blocks([
    ['paginakop', ['variant' => 'tekst', 'titel' => 'Hydrauliek slangen en koppelingen', 'accent' => 'koppelingen', 'intro' => "<p>$intro_hydr</p>"]],
    ['tegels', ['kolommen' => '4', 'linktekst' => 'Bekijk producten', 'tegels' => cat_tiles('hydrauliekslangen-koppelingen'), 'achtergrond' => 'grijs', 'ruimte' => 'aansluitend']],
    ['downloads', ['label' => 'Downloads', 'bestanden' => [
        ['titel' => 'Danfoss Hydraulic Hoses', 'bestand' => $PDF['danfoss-kat']], ['titel' => 'Danfoss Reusable Fittings', 'bestand' => $PDF['reusable']], ['titel' => 'Danfoss A/C and Refrigeration', 'bestand' => $PDF['danfoss-ac']],
        ['titel' => 'Danfoss flexmaster A/C', 'bestand' => $PDF['flexmaster']], ['titel' => 'Danfoss A/C and Refrigeration parts', 'bestand' => ''],
    ], 'achtergrond' => 'grijs', 'ruimte' => 'aansluitend']],
    ['usp-balk', ['stijl' => 'donker', 'items' => $USP_HYDRAULIEK]],
    ['tekst-afbeelding', [
        'titel' => 'Slangen, fittingen en perskoppelingen', 'kopniveau' => 'h2',
        'tekst' => '<p><strong>Hydrauliekslangen</strong>:<br>Danfoss-slangseries, aangevuld met eigen DN4- en DN6-hogedrukslangen.</p><p><strong>Fittingen en adapters</strong>:<br>aansluitingen in de gangbare schroefdraadstandaarden, snijringkoppelingen volgens DIN 2353 en ISO-B snelkoppelingen, in meerdere materiaaluitvoeringen.</p><p><strong>Perskoppelingen</strong>:<br>crimpfittingen per slangtype, afgestemd op de Danfoss-slangseries.</p><p><strong>Flexmaster</strong>:<br>Danfoss-pijp- en buiskoppelingen voor lagedruktoepassingen, ter compensatie van trillingen en uitlijning in leidingsystemen.</p>',
        'media' => 'afbeelding', 'positie' => 'rechts', 'afbeelding' => hyd_media('foto-slangen-fittingen'),
        'knop_type' => 'geen', 'achtergrond' => 'grijs',
    ]],
    ['tekst-afbeelding', [
        'titel' => 'Assemblage en certificering', 'accent' => 'certificering', 'kopniveau' => 'h2',
        'tekst' => '<p>Slangassemblages worden in eigen beheer geperst, gegraveerd voor traceerbaarheid, gereinigd en getest. Als DNV geautoriseerd assemblagecentrum keurt en certificeert Hydynamic zelfstandig, met een levertijd van één werkdag voor een DNV gecertificeerde assemblage.</p>',
        'media' => 'afbeelding', 'positie' => 'links', 'afbeelding' => hyd_media('foto-assemblage-dnv'),
        'knop_type' => 'offerte', 'achtergrond' => 'wit',
    ]],
    ['tekst-afbeelding', [
        'titel' => 'Danfoss merken en eigen lijn', 'accent' => 'eigen lijn', 'kopniveau' => 'h2',
        'tekst' => '<p>Hydynamic is officieel distributeur van Danfoss en levert onder meer Aeroquip®, de Winner™ slangserie en Weatherhead®, aangevuld met herbruikbare fittingen met typecertificering van DNV, ABS en Bureau Veritas. Daarnaast ontwikkelt en produceert Hydynamic een eigen productlijn onder de merknaam Hydynamic.</p>',
        'media' => 'logo', 'positie' => 'rechts', 'afbeelding' => hyd_media('logo-danfoss'),
        'knop_type' => 'geen', 'achtergrond' => 'grijs',
    ]],
    ['faq', ['titel' => 'Veelgestelde vragen', 'vragen' => $FAQ, 'schema' => 0, 'knop_link' => link_to($page_faq, 'Bekijk alle veelgestelde vragen'), 'achtergrond' => 'donker']],
    $KB, $OFFERTE,
]), ['metadesc' => $intro_hydr, 'thumb' => hyd_media('tile-hydrauliek-slangen'), 'order' => 0]);

$intro_cs = 'Hydynamic ontwikkelt en produceert componenten voor centrale smeersystemen. Het assortiment bestaat uit polyamide leidingen, push in connectoren, smeernippels en onderdelen voor nippelblokken en verdeelblokken, aangevuld met bevestigings en beschermingsmateriaal.';
$TXT_STARTKLAAR = ['titel' => 'Direct startklaar geleverd', 'accent' => 'geleverd', 'kopniveau' => 'h2',
    'tekst' => '<p>Leidingen en slangen binnen dit assortiment zijn op aanvraag leverbaar in een vooraf met vet gevulde uitvoering, zodat smeerpunten vanaf de eerste opstart direct worden voorzien.</p>',
    'media' => 'afbeelding', 'positie' => 'links', 'afbeelding' => hyd_media('foto-direct-startklaar'), 'knop_type' => 'offerte'];

upsert('producten', 'centrale-smering', 'Centrale smering', blocks([
    ['paginakop', ['variant' => 'tekst', 'titel' => 'Centrale smering', 'intro' => "<p>$intro_cs</p>"]],
    ['tegels', ['kolommen' => '4', 'linktekst' => 'Bekijk producten', 'tegels' => cat_tiles('centrale-smering'), 'achtergrond' => 'grijs', 'ruimte' => 'aansluitend']],
    ['downloads', ['label' => 'Downloads', 'bestanden' => [['titel' => 'Hydynamic productcatalogus 2026', 'bestand' => $PDF['catalogus']], ['titel' => 'Zoutsproeitest zink nikkel (ZnNi)', 'bestand' => $PDF['zoutspray']]], 'achtergrond' => 'grijs', 'ruimte' => 'aansluitend']],
    ['usp-balk', ['stijl' => 'donker', 'items' => usp(['Eigen ontwikkeling en productie', 'Productie naar tekening of monster', 'Standaard ZnNi oppervlaktebehandeling', 'Voorgevuld met vet leverbaar'])]],
    ['tekst-afbeelding', [
        'titel' => 'Ontwikkeling en productie', 'accent' => 'productie', 'kopniveau' => 'h2',
        'tekst' => '<p>Artikelen zijn vastgelegd in technische tekeningen. Naast het standaardassortiment is productie naar specificatie, tekening of monster mogelijk.</p><p>Staalcomponenten zijn standaard leverbaar met een zink nikkel oppervlaktebehandeling (ZnNi), naast uitvoeringen in onder meer roestvast staal en messing. De corrosiebestendigheid van zink nikkel is succesvol getest in een zoutsproeitest.</p>',
        'media' => 'afbeelding', 'positie' => 'rechts', 'afbeelding' => hyd_media('foto-ontwikkeling-productie'),
        'knop_type' => 'offerte', 'achtergrond' => 'grijs',
    ]],
    ['tekst-afbeelding', $TXT_STARTKLAAR + ['achtergrond' => 'wit']],
    ['faq', ['titel' => 'Veelgestelde vragen', 'vragen' => $FAQ, 'schema' => 0, 'knop_link' => link_to($page_faq, 'Bekijk alle veelgestelde vragen'), 'achtergrond' => 'donker']],
    $KB, $OFFERTE,
]), ['metadesc' => $intro_cs, 'thumb' => hyd_media('tile-polyamide-leidingen'), 'order' => 1]);

$intro_ind = 'Hydynamic levert industriële koppelingen en industriële slangen voor het verbinden en verplaatsen van vloeistoffen en andere media, binnen diverse sectoren: zuivelkoppelingen, Storz koppelingen, camlockkoppelingen, tankwagenkoppelingen, spiraalslangen en industriële slangen.';
upsert('producten', 'industrieel', 'Industrieel', blocks([
    ['paginakop', ['variant' => 'tekst', 'titel' => 'Industrieel', 'intro' => "<p>$intro_ind</p>"]],
    ['tegels', ['kolommen' => '4', 'linktekst' => 'Bekijk producten', 'tegels' => cat_tiles('industrieel'), 'achtergrond' => 'grijs', 'ruimte' => 'aansluitend']],
    ['downloads', ['label' => 'Downloads', 'bestanden' => [['titel' => 'Hydynamic productcatalogus 2026', 'bestand' => $PDF['catalogus']]], 'achtergrond' => 'grijs', 'ruimte' => 'aansluitend']],
    ['usp-balk', ['stijl' => 'donker', 'items' => usp(['Eigen productlijn onder Hydynamic merknaam', 'Zuivelkoppelingen conform DIN 11851 en DIN 11853', 'Actief sinds 1997', 'ISO 9001 gecertificeerd'])]],
    ['tekst-afbeelding', [
        'titel' => 'Sectoren en toepassingsgebieden', 'accent' => 'toepassingsgebieden', 'kopniveau' => 'h2',
        'tekst' => '<p>Zuivelkoppelingen worden toegepast binnen de voedings en drankindustrie, en daarnaast in de farmaceutische en chemische industrie, voor het hygiënisch verbinden van leidingen.</p><p>Storz koppelingen, camlockkoppelingen, tankwagenkoppelingen, spiraalslangen en industriële slangen vinden hun toepassing binnen de bredere sectoren waarin Hydynamic actief is:</p><ul><li>Offshore en Maritiem</li><li>Luchtvaart (GSE)</li><li>Machinebouw (OEM)</li><li>Agrarisch en Bosbouw</li><li>Grondverzet en Mijnbouw</li><li>Transport en Logistiek</li><li>Duurzame Energie en Recycling</li></ul>',
        'media' => 'afbeelding', 'positie' => 'rechts', 'afbeelding' => hyd_media('foto-slangen-fittingen'),
        'knop_type' => 'offerte', 'achtergrond' => 'grijs',
    ]],
    ['faq', ['titel' => 'Veelgestelde vragen', 'vragen' => $FAQ, 'schema' => 0, 'knop_link' => link_to($page_faq, 'Bekijk alle veelgestelde vragen'), 'achtergrond' => 'donker']],
    ['kennisbank', ['titel' => 'Kennisbank', 'aantal' => '4', 'knoptekst' => 'Ga naar kennisbank']],
    $OFFERTE,
]), ['metadesc' => $intro_ind, 'thumb' => hyd_media('tile-camlock'), 'order' => 2]);

/* --- Producten (onderliggend) */
$USP_CS = usp(['Conform DIN 73378 en DIN 74324', 'Stijve en flexibele uitvoering', 'Rollen van 50 of 100 meter', 'Voorgevuld met vet leverbaar']);

// Polyamide leidingen (PA12) — volledig volgens ontwerp
upsert('producten', 'polyamide-buizen-pa12', 'Polyamide leidingen (PA12)', blocks([
    ['paginakop', [
        'variant' => 'slider', 'titel' => 'Polyamide buizen (PA12)', 'accent' => '(PA12)', 'intro' => "<p>$intro_cs</p>",
        'slides' => [hyd_media('foto-offshore-hero'), hyd_media('foto-werkdruk'), hyd_media('foto-direct-startklaar'), hyd_media('tile-polyamide-leidingen'), hyd_media('foto-ontwikkeling-productie')],
        'downloads_label' => 'Downloads',
        'downloads' => [['titel' => 'Hydynamic productcatalogus 2026', 'bestand' => $PDF['catalogus']], ['titel' => 'Specificatie Hydynamic PA leiding 6x1,5', 'bestand' => $PDF['pa-spec']], ['titel' => 'Productspecificatie vet EP00/000', 'bestand' => $PDF['vet-ep00']]],
        'knop_type' => 'offerte',
    ]],
    ['usp-balk', ['stijl' => 'donker', 'items' => $USP_CS]],
    ['kaarten', ['titel' => 'PA12 HL en PA12 PHLY', 'kopniveau' => 'h2', 'kolommen' => '2', 'kaarten' => [
        ['titel' => 'Stijf en flexibel', 'tekst' => 'Hydynamic levert PA12 buizen in twee uitvoeringen: PA12 HL (PA 362), de stijvere variant, en PA12 PHLY (PA 5224), de flexibelere variant. Beide zijn leverbaar in de maat 6x1,5; PA12 HL ook in 4x0,85 en 4x1.'],
        ['titel' => 'Meervoudige uitvoering', 'tekst' => 'Voor meerdere leidingen in één mantel is er ook een meervoudige uitvoering, met twee of drie binnenleidingen.'],
    ], 'achtergrond' => 'grijs']],
    ['tekst-afbeelding', [
        'titel' => 'Werkdruk en toepassingsgebied', 'accent' => 'toepassingsgebied', 'kopniveau' => 'h2',
        'tekst' => '<p>Werkdruk, barstdruk en buigradius van een PA12 leiding zijn afhankelijk van diameter, wanddikte en constructie, en verschillen per artikelnummer.</p><p>De exacte waarden staan in de productcatalogus en technische specificaties. PA12 heeft geen ingebouwde versterkingslaag en is daarmee geschikt voor centrale smering en pneumatiek, niet voor krachthydrauliek.</p>',
        'media' => 'afbeelding', 'positie' => 'rechts', 'afbeelding' => hyd_media('foto-werkdruk'),
        'knop_type' => 'offerte', 'achtergrond' => 'blauw',
    ]],
    ['tekst-afbeelding', $TXT_STARTKLAAR + ['achtergrond' => 'grijs']],
    ['tekst-afbeelding', [
        'titel' => 'Aansluiting en accessoires', 'kopniveau' => 'h2',
        'tekst' => '<p>Bij de polyamide buizen levert Hydynamic bijpassende accessoires: verstevigingshulzen, wartelmoeren conform DIN 3871 en snijringen conform DIN 3862, voor de aansluiting op fittingen binnen het centrale smeringsassortiment.</p>',
        'media' => 'afbeelding', 'positie' => 'rechts', 'afbeelding' => hyd_media('foto-ontwikkeling-productie'),
        'knop_type' => 'offerte', 'achtergrond' => 'wit',
    ]],
    $KB, $OFFERTE,
]), ['parent' => $CAT_IDS['centrale-smering'], 'order' => 0, 'thumb' => hyd_media('tile-polyamide-leidingen'),
     'metadesc' => 'Polyamide buizen (PA12) voor centrale smering en pneumatiek: PA12 HL en PA12 PHLY, conform DIN 73378 en DIN 74324, op rollen van 50 of 100 meter en voorgevuld met vet leverbaar.']);

// Overige producten: dezelfde opbouw, gevuld met de tegeltekst en foto (verder aanvullen in de editor)
$cat_usps = [
    'hydrauliekslangen-koppelingen' => $USP_HYDRAULIEK,
    'centrale-smering'                  => $USP_CS,
    'industrieel'                       => usp(['Eigen productlijn onder Hydynamic merknaam', 'Zuivelkoppelingen conform DIN 11851 en DIN 11853', 'Actief sinds 1997', 'ISO 9001 gecertificeerd']),
];
foreach ($CATS as $cat_slug => [$cat_title, $children]) {
    $o = 0;
    foreach ($children as $slug => [$title, $img, $text]) {
        if ($slug === 'polyamide-buizen-pa12') { $o++; continue; }
        upsert('producten', $slug, $title, blocks([
            ['paginakop', ['variant' => $img ? 'afbeelding' : 'tekst', 'titel' => $title, 'intro' => "<p>$text</p>", 'afbeelding' => $img ? hyd_media($img) : '', 'knop_type' => 'offerte']],
            ['usp-balk', ['stijl' => 'donker', 'items' => $cat_usps[$cat_slug]]],
            ['tekst-afbeelding', $TXT_STARTKLAAR + ['achtergrond' => 'grijs']],
            $KB, $OFFERTE,
        ]), ['parent' => $CAT_IDS[$cat_slug], 'order' => $o++, 'thumb' => $img ? hyd_media($img) : 0, 'metadesc' => $text]);
    }
}

/* --- Oplossingen */
out("\n[7/8] Oplossingen en diensten…");
$intro_off = 'Hydynamic ontwikkelt en produceert componenten voor centrale smeersystemen. Het assortiment bestaat uit polyamide leidingen, push in connectoren, smeernippels en onderdelen voor nippelblokken en verdeelblokken, aangevuld met bevestigings en beschermingsmateriaal.';
upsert('oplossingen', 'offshore-en-maritiem', 'Offshore en Maritiem', blocks([
    ['paginakop', ['variant' => 'afbeelding', 'titel' => 'Hydraulische oplossingen voor offshore en maritiem', 'accent' => 'offshore en maritiem', 'intro' => "<p>$intro_off</p>", 'afbeelding' => hyd_media('foto-offshore-hero'), 'knop_type' => 'offerte']],
    ['usp-balk', ['stijl' => 'donker', 'items' => usp(['DNV-geautoriseerd assemblagecentrum', 'DNV-gecertificeerde assemblage in 1 werkdag', 'Typegecertificeerde fittingen (DNV, ABS, BV)', 'ZnNi-oppervlaktebehandeling'])]],
    ['kaarten', ['titel' => 'Technische eisen in offshore en maritiem', 'accent' => 'offshore en maritiem', 'kopniveau' => 'h2',
        'intro' => 'Aan boord van schepen en op offshore installaties staan hydraulische systemen bloot aan zout water, trillingen en periodieke keuringen. Een slangassemblage moet daar niet alleen passen op het systeem, maar ook aantoonbaar gecertificeerd zijn. Drie eisen bepalen daarbij de keuze van slang, fitting en certificering.',
        'kolommen' => '3', 'kaarten' => [
            ['titel' => 'Corrosie door zout', 'tekst' => 'Staalcomponenten uit het eigen Hydynamic assortiment zijn leverbaar met een zink nikkel oppervlaktebehandeling (ZnNi). De corrosie-bestendigheid daarvan is getest in een zoutsproeitest.'],
            ['titel' => 'Classificatie en typecertificering', 'tekst' => 'Herbruikbare Aeroquip fittingen van Danfoss zijn typegecertificeerd door onder meer DNV, ABS en Bureau Veritas, afhankelijk van type en maat.'],
            ['titel' => 'Stilstand beperken', 'tekst' => 'Een vervangende slangassemblage moet snel en met certificaat beschikbaar zijn. Als DNV geautoriseerd assemblagecentrum levert Hydynamic een gecertificeerde assemblage binnen één werkdag.'],
        ], 'achtergrond' => 'grijs']],
    ['tegels', ['titel' => 'Wat Hydynamic levert voor offshore en maritiem', 'kopniveau' => 'h2',
        'intro' => 'Voor offshore en maritiem levert Hydynamic zowel losse componenten als complete slangassemblages. Assemblage, certificering en oppervlaktebehandeling vinden in eigen beheer plaats, zodat product en documentatie uit één hand komen.',
        'kolommen' => '3', 'linktekst' => 'Bekijk producten', 'tegels' => [
            ['afbeelding' => hyd_media('tile-hydrauliek-slangen'), 'titel' => 'Hydrauliek slangen', 'tekst' => 'Danfoss FC en GH serie, aangevuld met een eigen hogedrukslang DN4 en DN6.', 'link' => link_to($PROD_IDS['hydrauliekslangen'])],
            ['afbeelding' => hyd_media('tile-fittingen-adapters'), 'titel' => 'Fittingen en adapters', 'tekst' => 'Waaronder herbruikbare fittingen met typecertificering van DNV, ABS en Bureau Veritas.', 'link' => link_to($PROD_IDS['fittingen-adapters'])],
            ['afbeelding' => hyd_media('tile-perskoppelingen'), 'titel' => 'Perskoppelingen', 'tekst' => 'Crimpfittingen per slangtype, afgestemd op de Danfoss slangseries.', 'link' => link_to($PROD_IDS['perskoppelingen'])],
        ], 'achtergrond' => 'blauw']],
    ['tekst-afbeelding', $TXT_STARTKLAAR + ['achtergrond' => 'grijs']],
    $KB, $OFFERTE,
]), ['thumb' => hyd_media('foto-offshore-hero'), 'order' => 0,
     'metadesc' => 'Hydraulische oplossingen voor offshore en maritiem: DNV-gecertificeerde slangassemblages binnen één werkdag, typegecertificeerde fittingen en ZnNi-oppervlaktebehandeling.']);

$o = 1;
foreach ($OPLOSSINGEN as $slug => $title) {
    if ($slug === 'offshore-en-maritiem') continue;
    upsert('oplossingen', $slug, $title, blocks([
        ['paginakop', ['variant' => 'tekst', 'titel' => 'Hydraulische oplossingen voor ' . mb_strtolower($title), 'accent' => mb_strtolower($title), 'knop_type' => 'offerte']],
        ['usp-balk', ['stijl' => 'donker', 'items' => $USP_HYDRAULIEK]],
        ['tekst-afbeelding', $TXT_STARTKLAAR + ['achtergrond' => 'grijs']],
        $KB, $OFFERTE,
    ]), ['order' => $o++]);
}

// Oplossingen-overzicht
$opl_tiles = [];
foreach ($OPL_IDS as $slug => $id) {
    $opl_tiles[] = ['afbeelding' => $slug === 'offshore-en-maritiem' ? hyd_media('foto-offshore-hero') : '', 'titel' => $OPLOSSINGEN[$slug], 'tekst' => '', 'link' => link_to($id, $OPLOSSINGEN[$slug])];
}
upsert('page', 'oplossingen', 'Oplossingen', blocks([
    ['paginakop', ['variant' => 'tekst', 'titel' => 'Oplossingen', 'intro' => '<p>Hydynamic levert hydraulische oplossingen, centrale smering en industriële koppelingen voor uiteenlopende sectoren.</p>']],
    ['tegels', ['titel' => 'Sectoren', 'kopniveau' => 'h2', 'kolommen' => '3', 'linktekst' => 'Bekijk oplossing', 'tegels' => $opl_tiles, 'achtergrond' => 'grijs', 'ruimte' => 'aansluitend']],
    ['usp-balk', ['stijl' => 'donker', 'items' => $USP_HYDRAULIEK]],
    $KB, $OFFERTE,
]));

// Diensten (structuur uit de export; teksten uit de ontwerpen)
$DIENST_CONTENT = [
    'slangassemblage-op-maat' => [
        'titel' => 'Slangassemblage op maat', 'accent' => 'op maat',
        'intro' => '<p>Slangassemblages worden in eigen beheer geperst, gegraveerd voor traceerbaarheid, gereinigd en getest. Als DNV geautoriseerd assemblagecentrum keurt en certificeert Hydynamic zelfstandig, met een levertijd van één werkdag voor een DNV gecertificeerde assemblage.</p>',
        'img' => 'foto-assemblage-dnv',
        'usp' => $USP_HYDRAULIEK,
    ],
    'dnv-certificering' => [
        'titel' => 'DNV-certificering', 'accent' => 'certificering',
        'intro' => '<p>Hydynamic Holland BV is door DNV geautoriseerd om Danfoss-slangassemblages volledig zelfstandig te keuren en te certificeren, zonder tussenkomst van DNV. Een DNV-gekeurde slang is op verzoek binnen één werkdag leverbaar, met het keuringscertificaat direct bij de levering.</p>',
        'img' => 'foto-assemblage-dnv',
        'usp' => usp(['DNV-geautoriseerd assemblagecentrum', 'ISO 9001:2015-gecertificeerd', 'Keuringscertificaat bij de levering', '3 jaar garantie op Danfoss-assemblages']),
    ],
    'maatwerk-productie' => [
        'titel' => 'Maatwerk productie', 'accent' => 'productie',
        'intro' => '<p>Klant specifieke productie van onderdelen in zowel grote als kleine series, in diverse materialen. Flexibiliteit, maatwerk en hoogwaardige kwaliteit staan hierbij centraal.</p><p>Artikelen zijn vastgelegd in technische tekeningen. Naast het standaardassortiment is productie naar specificatie, tekening of monster mogelijk.</p>',
        'img' => 'foto-ontwikkeling-productie',
        'usp' => usp(['Eigen ontwikkeling en productie', 'Productie naar tekening of monster', 'Standaard ZnNi oppervlaktebehandeling', 'ISO 9001 gecertificeerd']),
    ],
    'voorvullen-smeerslangen' => [
        'titel' => 'Voorvullen smeerslangen', 'accent' => 'smeerslangen',
        'intro' => '<p>Leidingen en slangen binnen het centrale smeringsassortiment zijn op aanvraag leverbaar in een vooraf met vet gevulde uitvoering, zodat smeerpunten vanaf de eerste opstart direct worden voorzien.</p>',
        'img' => 'foto-direct-startklaar',
        'usp' => $USP_CS ?? usp(['Conform DIN 73378 en DIN 74324', 'Stijve en flexibele uitvoering', 'Rollen van 50 of 100 meter', 'Voorgevuld met vet leverbaar']),
        'downloads' => [['titel' => 'Productspecificatie vet EP00/000', 'bestand' => $PDF['vet-ep00']]],
    ],
    'oppervlaktebehandeling-znni' => [
        'titel' => 'Oppervlaktebehandeling (ZnNi)', 'accent' => '(ZnNi)',
        'intro' => '<p>Staalcomponenten zijn standaard leverbaar met een zink nikkel oppervlaktebehandeling (ZnNi), naast uitvoeringen in onder meer roestvast staal en messing. De corrosiebestendigheid van zink nikkel is succesvol getest in een zoutsproeitest.</p>',
        'img' => 'foto-ontwikkeling-productie',
        'usp' => usp(['Eigen ontwikkeling en productie', 'Standaard ZnNi oppervlaktebehandeling', 'Getest in een zoutsproeitest', 'ISO 9001 gecertificeerd']),
        'downloads' => [['titel' => 'Zoutsproeitest zink nikkel (ZnNi)', 'bestand' => $PDF['zoutspray']]],
    ],
];
$dienst_tiles = [];
$o = 0;
foreach ($DIENST_CONTENT as $slug => $d) {
    $list = [
        ['paginakop', ['variant' => 'afbeelding', 'titel' => $d['titel'], 'accent' => $d['accent'], 'intro' => $d['intro'], 'afbeelding' => hyd_media($d['img']), 'knop_type' => 'offerte']],
        ['usp-balk', ['stijl' => 'donker', 'items' => $d['usp']]],
    ];
    if (!empty($d['downloads'])) $list[] = ['downloads', ['label' => 'Downloads', 'bestanden' => $d['downloads'], 'achtergrond' => 'grijs']];
    $list[] = $KB;
    $list[] = $OFFERTE;
    upsert('diensten', $slug, $DIENSTEN[$slug], blocks($list), ['order' => $o++, 'thumb' => hyd_media($d['img']), 'metadesc' => wp_strip_all_tags($d['intro'])]);
    $dienst_tiles[] = ['afbeelding' => hyd_media($d['img']), 'titel' => $DIENSTEN[$slug], 'tekst' => wp_trim_words(wp_strip_all_tags($d['intro']), 16), 'link' => link_to($DIENST_IDS[$slug], $DIENSTEN[$slug])];
}

upsert('page', 'diensten', 'Diensten', blocks([
    ['paginakop', ['variant' => 'tekst', 'titel' => 'Diensten', 'intro' => '<p>Assemblage, certificering, maatwerk productie en oppervlaktebehandeling vinden bij Hydynamic in eigen beheer plaats, zodat product en documentatie uit één hand komen.</p>']],
    ['tegels', ['kolommen' => '3', 'linktekst' => 'Bekijk dienst', 'tegels' => $dienst_tiles, 'achtergrond' => 'grijs', 'ruimte' => 'aansluitend']],
    ['usp-balk', ['stijl' => 'donker', 'items' => $USP_HYDRAULIEK]],
    $KB, $OFFERTE,
]));

/* --- Veelgestelde vragen (FAQ's uit de export + vragen uit het ontwerp) */
$FAQ_EXPORT = [
    ['vraag' => 'Wat zijn de operationele risico\'s van direct slang-op-slang contact in een hydraulische installatie?', 'antwoord' => '<p>Constant contact tussen slangen veroorzaakt schurende wrijving tijdens bedrijf. Deze wrijving werkt als schuurpapier en beschadigt de beschermende buitenmantel, waardoor de versterkingslaag bloot komt te liggen. Het gebruik van klemmen of beschermspiralen is noodzakelijk om deze mechanische slijtage te voorkomen.</p>'],
    ['vraag' => 'Welke specifieke impact heeft langdurige blootstelling aan UV-straling op de buitenmantel van een slang?', 'antwoord' => '<p>UV-straling en ozon breken de polymeren in de buitenmantel af, wat leidt tot ozon-cracking (fijne haarscheurtjes). Deze scheurtjes maken de versterkingslaag kwetsbaar voor vocht en corrosie, wat de structurele integriteit van de slang aantast en uiteindelijk tot een breuk leidt.</p>'],
    ['vraag' => 'Hoe kan ik de technische gegevens op de lay-line van een hydrauliekslang correct interpreteren?', 'antwoord' => '<p>De lay-line bevat cruciale informatie zoals de fabrikant, het artikelnummer, de exacte binnendiameter en de maximale werkdruk. Ook worden hier de geldende internationale normeringen (zoals ISO of SAE) vermeld. Verifieer deze gegevens altijd voor de montage om te garanderen dat de slang voldoet aan de systeemspecificaties.</p>'],
    ['vraag' => 'Welke invloed heeft de totale lengte van een slang op de drukval binnen een smeersysteem?', 'antwoord' => '<p>Langere leidingen verhogen de interne wrijving van het medium tegen de wand van de slang. In centrale smeersystemen kan dit leiden tot een aanzienlijk drukverlies, waardoor het smeermiddel mogelijk niet in de vereiste hoeveelheid bij de verste smeerpunten aankomt.</p>'],
    ['vraag' => 'Waarom is het noodzakelijk om een slang inwendig te reinigen direct nadat deze op maat is gesneden?', 'antwoord' => '<p>Tijdens het snijproces komen rubberdeeltjes en metaalschilfers vrij die in de slang achterblijven. Indien deze vervuiling niet wordt verwijderd met perslucht of een reinigingsprop, kunnen deze deeltjes gevoelige kleppen beschadigen of injectoren in het systeem verstoppen.</p>'],
    ['vraag' => 'Wat is het technische verschil tussen de nominale werkdruk en de theoretische barstdruk?', 'antwoord' => '<p>De werkdruk is de maximale druk waarbij de slang veilig en continu kan worden ingezet. De barstdruk is het punt waarop de slang structureel bezwijkt. Conform de veiligheidsnormen ligt de barstdruk bij hydrauliekslangen doorgaans viermaal hoger dan de werkdruk (de 4:1 veiligheidsfactor).</p>'],
    ['vraag' => 'Waarom verdient het gebruik van voorgevulde slangen de voorkeur bij de installatie van centrale smeersystemen?', 'antwoord' => '<p>Voorgevulde slangen (voorzien van EP 00/000 vet) voorkomen de vorming van luchtpockets in het systeem. Dit garandeert dat de lagers direct vanaf de eerste activatie van het systeem van smering worden voorzien, wat initiële slijtage en schade aan componenten voorkomt.</p>'],
];
upsert('page', 'veelgestelde-vragen', 'Veelgestelde vragen', blocks([
    ['paginakop', ['variant' => 'tekst', 'titel' => 'Veelgestelde vragen', 'accent' => 'vragen', 'intro' => '<p>Antwoorden op veelgestelde vragen over hydrauliekslangen, koppelingen, centrale smering en certificering.</p>']],
    ['faq', ['titel' => 'Hydrauliekslangen en centrale smering', 'vragen' => array_merge($FAQ_EXPORT, $FAQ), 'schema' => 1, 'achtergrond' => 'donker']],
    $KB, $OFFERTE,
]), ['metadesc' => 'Veelgestelde vragen over hydrauliekslangen, koppelingen, centrale smering, levertijden en DNV-certificering bij Hydynamic Holland BV.']);

/* --- Over ons (Claude-ontwerp) */
$sfeer = [];
for ($i = 1; $i <= 12; $i++) $sfeer[] = hyd_media(sprintf('sfeer-%02d', $i));
$sfeer = array_values(array_filter($sfeer));

upsert('page', 'over-ons', 'Over ons', blocks([
    ['paginakop', ['variant' => 'achtergrond', 'titel' => 'Hydraulische slangen en smeersystemen sinds 1997', 'accent' => 'sinds 1997',
        'intro' => '<p>Hydynamic Holland BV in Enschede is in 1997 opgericht als assemblagebedrijf voor hydraulische slangen. Kort na de oprichting werd Hydynamic officieel distributeur van Danfoss, voorheen Eaton Aeroquip. Inmiddels omvat het assortiment ook componenten en accessoires voor centrale smeersystemen onder eigen merknaam.</p>',
        'afbeelding' => hyd_media('foto-bedrijfspand')]],
    ['usp-balk', ['stijl' => 'donker', 'items' => usp(['Opgericht in 1997', 'Danfoss-distributeur, voorheen Eaton Aeroquip', 'DNV-geautoriseerd assemblagecentrum', 'Eigen productlijn voor centrale smering'])]],
    ['tekst-afbeelding', [
        'titel' => 'Eén team, onder één dak', 'accent' => 'onder één dak', 'kopniveau' => 'h2',
        'tekst' => '<p>Binnendienst, magazijn en assemblage werken bij Hydynamic vanuit hetzelfde pand in Enschede. Een aanvraag voor een slangassemblage gaat daardoor niet langs externe partijen: wie de aanvraag behandelt, werkt naast de mensen die de assemblage maken, testen en certificeren.</p><p>Enkele jaren na de oprichting vroeg een OEM-klant of Hydynamic ook onderdelen voor centrale smeersystemen kon leveren. Die vraag groeide uit tot een tweede kernactiviteit: de productie van componenten en accessoires voor centrale smeersystemen.</p><p>Beide activiteiten volgen hetzelfde uitgangspunt, vastgelegd in de slogan van het bedrijf: <strong>“Kwaliteit maakt ons uniek.”</strong></p>',
        'media' => 'afbeelding', 'positie' => 'links', 'afbeelding' => hyd_media('foto-team'),
        'knop_type' => 'link', 'knop_link' => link_to($page_contact, 'Neem contact op'), 'achtergrond' => 'grijs', 'ruimte' => 'groot',
    ]],
    ['fotoslider', ['titel' => 'Een kijkje binnen', 'accent' => 'binnen', 'kopniveau' => 'h2', 'intro' => 'Magazijn, werkplaats en binnendienst van Hydynamic in Enschede.', 'fotos' => $sfeer, 'achtergrond' => 'verloop']],
    ['logos', ['titel' => 'Certificeringen en keuringen', 'kopniveau' => 'h2',
        'tekst' => '<p>Hydynamic Holland BV is gecertificeerd volgens ISO 9001:2015 en is door DNV geautoriseerd om Danfoss-slangassemblages volledig zelfstandig te keuren en te certificeren, zonder tussenkomst van DNV.</p><p>Een DNV-gekeurde slangassemblage is op verzoek binnen één werkdag leverbaar, met het keuringscertificaat bij de levering. Daarnaast kunnen slangassemblages worden voorzien van een certificaat van andere keuringsinstanties, waaronder ABS, Bureau Veritas en Lloyd’s Register.</p>',
        'indeling' => 'naast', 'logos' => [hyd_media('logo-dnv'), hyd_media('logo-abs'), hyd_media('logo-bureau-veritas'), hyd_media('logo-lloyds-register')],
        'knop_type' => 'link', 'knop_link' => link_to($page_cert, 'Bekijk alle certificeringen'), 'achtergrond' => 'blauw']],
    $KB, $OFFERTE,
]), ['thumb' => hyd_media('foto-bedrijfspand'), 'metadesc' => 'Hydynamic Holland BV in Enschede: sinds 1997 assemblagebedrijf voor hydraulische slangen, officieel Danfoss-distributeur en producent van componenten voor centrale smeersystemen.']);

/* --- Certificeringen (Claude-ontwerp) */
upsert('page', 'certificeringen', 'Certificeringen', blocks([
    ['paginakop', ['variant' => 'afbeelding', 'titel' => 'Certificeringen en keuringen', 'accent' => 'keuringen',
        'intro' => '<p><strong>Kwaliteit conform de ISO 9001:2015-norm</strong></p><p>Hydynamic Holland BV is gecertificeerd volgens ISO 9001:2015 en is een DNV-geautoriseerd assemblagecentrum. Slangassemblages worden in eigen beheer gekeurd en gecertificeerd, met het keuringscertificaat direct bij de levering.</p>',
        'afbeelding' => hyd_media('foto-assemblage-dnv'), 'knop_type' => 'link', 'knop_link' => pdf_link($PDF['iso'], 'Bekijk het certificaat')]],
    ['usp-balk', ['stijl' => 'donker', 'items' => usp(['DNV-geautoriseerd assemblagecentrum', 'ISO 9001:2015-gecertificeerd', 'Keuringscertificaat bij de levering', '3 jaar garantie op Danfoss-assemblages'])]],
    ['kaarten', ['titel' => 'Garantie en documentatie', 'accent' => 'documentatie', 'kopniveau' => 'h2',
        'intro' => 'Naast keuring en certificering zijn garantie en documentatie vastgelegd volgens erkende normen.', 'kolommen' => '3', 'kaarten' => [
            ['titel' => '3 jaar garantie', 'tekst' => 'Op Danfoss/Aeroquip-slangassemblages geldt 3 jaar garantie, mits gemonteerd volgens DIN 20066.'],
            ['titel' => 'Certificaten volgens NEN-EN 10204', 'tekst' => 'Bij de uitgifte van certificaten werkt Hydynamic Holland BV volgens NEN-EN 10204.'],
            ['titel' => 'DNV-keuring binnen één werkdag', 'tekst' => 'Een DNV-gekeurde slangassemblage is op verzoek binnen één werkdag leverbaar, met het keuringscertificaat bij de levering.'],
        ], 'achtergrond' => 'grijs']],
    ['logos', ['titel' => 'Overige keuringsinstanties', 'kopniveau' => 'h2',
        'tekst' => '<p>Slangassemblages kunnen ook worden voorzien van een certificaat van andere keuringsinstanties. Voor de slangen kan een Type Approval Certificate worden afgegeven, afhankelijk van slangtype en maat.</p>',
        'indeling' => 'onder', 'logos' => [hyd_media('logo-abs'), hyd_media('logo-bureau-veritas'), hyd_media('logo-lloyds-register')], 'achtergrond' => 'blauw']],
    ['tekst-afbeelding', [
        'titel' => 'DNV-geautoriseerd assemblagecentrum', 'accent' => 'assemblagecentrum', 'kopniveau' => 'h2',
        'tekst' => '<p>Hydynamic Holland BV is door DNV geautoriseerd om Danfoss-slangassemblages volledig zelfstandig te keuren en te certificeren, zonder tussenkomst van DNV.</p><p>Een DNV-gekeurde slang is op verzoek binnen één werkdag leverbaar. Het keuringscertificaat wordt direct met de levering meegestuurd, zodat er geen wachttijd ontstaat voor een externe keuring.</p>',
        'media' => 'afbeelding', 'positie' => 'links', 'afbeelding' => hyd_media('sfeer-01'), 'knop_type' => 'link', 'knop_link' => pdf_link($PDF['dnv'], 'Bekijk het certificaat'), 'achtergrond' => 'grijs', 'ruimte' => 'groot',
    ]],
    $KB, $OFFERTE,
]), ['metadesc' => 'Hydynamic Holland BV is ISO 9001:2015-gecertificeerd en DNV-geautoriseerd assemblagecentrum. DNV-gekeurde slangassemblages binnen één werkdag, met keuringscertificaat bij de levering.']);

/* --- Contact (Claude-ontwerp) */
upsert('page', 'contact', 'Contact', blocks([
    ['paginakop', ['variant' => 'tekst', 'titel' => 'Contact']],
    ['contact', ['titel' => 'Contactgegevens', 'openingstijden' => 'Maandag t/m vrijdag 8:00 - 17:00', 'afdelingen' => [
        ['icoon' => 'info', 'titel' => 'Algemene vragen', 'tekst' => 'Voor algemene vragen, informatie en overige correspondentie.', 'email' => 'info@hydynamic.nl'],
        ['icoon' => 'box', 'titel' => 'Orders en leveringen', 'tekst' => 'Voor het plaatsen, opvolgen of wijzigen van lopende orders en leveringen.', 'email' => 'order@hydynamic.nl'],
        ['icoon' => 'file', 'titel' => 'Offertes en technisch advies', 'tekst' => 'Voor offerteaanvragen, maatwerkaanvragen en technisch advies.', 'email' => 'request@hydynamic.nl'],
    ], 'achtergrond' => 'grijs', 'ruimte' => 'aansluitend']],
    ['offerte', ['zijkant' => 'route', 'routefoto' => hyd_media('foto-bedrijfspand')]],
]), ['id' => $page_contact, 'metadesc' => 'Neem contact op met Hydynamic Holland BV, Marssteden 28 in Enschede. Bel +31(0)53-4282048 of mail naar info@hydynamic.nl.']);

/* --- Offerte aanvragen (losse pagina met het offerteblok; ook terugval voor de offerteknoppen) */
$page_offerte = upsert('page', 'offerte-aanvragen', 'Offerte aanvragen', blocks([
    ['paginakop', ['variant' => 'tekst', 'titel' => 'Offerte aanvragen', 'accent' => 'aanvragen',
        'intro' => '<p>Vraag vrijblijvend een offerte op maat aan voor hydrauliekslangen en koppelingen, centrale smering, industriële koppelingen of een gecertificeerde slangassemblage. Wij nemen zo snel mogelijk contact met u op.</p>']],
    ['usp-balk', ['stijl' => 'donker', 'items' => $USP_HYDRAULIEK]],
    $OFFERTE,
]), ['metadesc' => 'Vraag vrijblijvend een offerte op maat aan bij Hydynamic Holland BV voor hydrauliekslangen, koppelingen, centrale smering en DNV-gecertificeerde slangassemblages.']);

/* =========================================================================
 * 7. Menu's
 * ====================================================================== */
out("\n[8/8] Menu's en instellingen…");

function hyd_reset_menu($name, $location) {
    $menu = wp_get_nav_menu_object($name);
    $id   = $menu ? $menu->term_id : wp_create_nav_menu($name);
    foreach ((array) wp_get_nav_menu_items($id, ['post_status' => 'any']) as $item) {
        wp_delete_post($item->ID, true);
    }
    $locations = get_theme_mod('nav_menu_locations', []);
    $locations[$location] = $id;
    set_theme_mod('nav_menu_locations', $locations);
    return $id;
}
function hyd_menu_item($menu, $title, $object_id = 0, $object = 'page', $parent = 0, $url = '', $target = '') {
    $args = [
        'menu-item-title'     => $title,
        'menu-item-status'    => 'publish',
        'menu-item-parent-id' => $parent,
        'menu-item-target'    => $target,
    ];
    if ($url) {
        $args += ['menu-item-type' => 'custom', 'menu-item-url' => $url];
    } else {
        $args += ['menu-item-type' => 'post_type', 'menu-item-object' => $object, 'menu-item-object-id' => $object_id];
    }
    return wp_update_nav_menu_item($menu, 0, $args);
}

$main = hyd_reset_menu('Hoofdmenu', 'main_menu');
hyd_menu_item($main, 'Home', $home_id);
$m_prod = hyd_menu_item($main, 'Producten', $page_prod);
foreach ($CATS as $slug => [$title, $children]) {
    $m_cat = hyd_menu_item($main, $title, $CAT_IDS[$slug], 'producten', $m_prod);
    foreach ($children as $cslug => $child) {
        hyd_menu_item($main, $child[0], $PROD_IDS[$cslug], 'producten', $m_cat);
    }
}
$m_opl = hyd_menu_item($main, 'Oplossingen', $page_opl);
foreach ($OPL_IDS as $slug => $id) hyd_menu_item($main, $OPLOSSINGEN[$slug], $id, 'oplossingen', $m_opl);
$m_d = hyd_menu_item($main, 'Diensten', $page_dienst);
foreach ($DIENST_IDS as $id) hyd_menu_item($main, get_the_title($id), $id, 'diensten', $m_d);
hyd_menu_item($main, 'Kennisbank', $page_kb);
hyd_menu_item($main, 'Certificeringen', $page_cert);
hyd_menu_item($main, 'Over ons', $page_over);
hyd_menu_item($main, 'Contact', $page_contact);

$foot = hyd_reset_menu('Footermenu', 'footer_menu');
hyd_menu_item($foot, 'Privacy Policy', 0, '', 0, wp_make_link_relative(wp_get_attachment_url($PDF['privacy'])) ?: 'https://hydynamic.com/wp-content/uploads/2023/06/Privacy-statement-NL_2023.pdf', '_blank');
hyd_menu_item($foot, 'Algemene voorwaarden', 0, '', 0, wp_make_link_relative(wp_get_attachment_url($PDF['voorwaarden'])) ?: 'https://hydynamic.com/wp-content/uploads/2024/01/General-terms-and-conditions-NL-DE-EN.pdf', '_blank');

/* =========================================================================
 * 8. Instellingen
 * ====================================================================== */
update_option('show_on_front', 'page');
update_option('page_on_front', $home_id);
update_option('page_for_posts', $page_kb);
update_option('blogdescription', 'Your trusted supplier for lubrication & fluid technology');
update_option('timezone_string', 'Europe/Amsterdam');
update_option('WPLANG', 'nl_NL');
update_option('date_format', 'j F Y');

if (function_exists('update_field')) {
    update_field('logo', hyd_media('logo-hydynamic'), 'option');
    update_field('favicon', null, 'option'); // standaardicoon basisthema; favicon komt uit het thema (assets/functions/favicon.php)
    update_field('bedrijfsnaam', 'Hydynamic Holland BV', 'option');
    update_field('adres', 'Marssteden 28', 'option');
    update_field('postcode', '7547 TC Enschede (NL)', 'option');
    update_field('telefoon', '+31(0)53-4282048', 'option');
    update_field('email', 'info@hydynamic.nl', 'option');
    update_field('offerte_formulier', $form_id, 'option');
    update_field('offerte_fallback', $page_offerte, 'option');
    update_field('contactkaart_titel', 'Liever direct contact?', 'option');
    update_field('contactkaart_accent', 'contact?', 'option');
    update_field('alleen_thema_blokken', 1, 'option');
}

// Focus-zoekwoorden per pagina (zoekwoordenlijsten uit de export van de stagingsite)
$FOCUS = [
    ['producten', 'hydrauliekslangen-koppelingen', 'hydrauliekslangen en koppelingen'], ['producten', 'hydrauliekslangen', 'hydrauliekslangen'],
    ['producten', 'fittingen-adapters', 'fittingen'], ['producten', 'perskoppelingen', 'perskoppelingen'],
    ['producten', 'centrale-smering', 'centrale smering'], ['producten', 'polyamide-buizen-pa12', 'polyamide buizen'],
    ['producten', 'push-in-connectoren', 'push in connector'], ['producten', 'smeernippels-vetpersen', 'smeernippels'],
    ['producten', 'verdeelblokken-accessoires', 'nippelblokken smering'], ['producten', 'industrieel', 'industriële koppelingen'],
    ['producten', 'zuivelkoppelingen', 'zuivelkoppelingen'], ['producten', 'storz-koppelingen', 'storz koppeling'],
    ['producten', 'camlock-koppelingen', 'camlock koppelingen'], ['producten', 'tankwagenkoppelingen', 'tankwagenkoppelingen'],
    ['producten', 'spiraalslangen', 'spiraalslangen'], ['producten', 'industriele-slangen', 'industriële slangen'],
];
foreach ($FOCUS as [$type, $slug, $kw]) {
    $p = get_posts(['post_type' => $type, 'name' => $slug, 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids']);
    if ($p) update_post_meta($p[0], '_yoast_wpseo_focuskw', $kw);
}

flush_rewrite_rules(false);
out("\nKlaar. Bekijk de site op " . home_url('/') . "\n");
