<?php
/**
 * Hydynamic – Engelse versie (hydynamic.com) opbouwen.
 *
 * Maakt van elke Nederlandse pagina een gekoppelde Engelse vertaling met dezelfde
 * blokken en foto's, plus Engelse kennisbankartikelen, offerteformulier, menu's en
 * instellingen. Draai eerst hydynamic-setup.php (Nederlands).
 *
 *   php setup/hydynamic-setup-en.php
 *
 * Opnieuw draaien is veilig (bestaande vertalingen worden bijgewerkt), maar
 * overschrijft wel de blokinhoud van de Engelse pagina's.
 */

if (php_sapi_name() !== 'cli') exit("Alleen via de command line.\n");

define('HYD_FORCE_LANG', 'en');
$_SERVER['HTTP_HOST']   = $_SERVER['HTTP_HOST'] ?? 'hydynamic.test';
$_SERVER['HTTPS']       = 'on';
$_SERVER['REQUEST_URI'] = '/';
define('WP_USE_THEMES', false);
require __DIR__ . '/../../../../wp-load.php';
require_once ABSPATH . 'wp-admin/includes/nav-menu.php';

if (!function_exists('hyd_find_by_path')) exit("Het Hydynamic-thema (met i18n) is niet actief.\n");
$admins = get_users(['role' => 'administrator', 'number' => 1, 'orderby' => 'ID']);
wp_set_current_user($admins ? $admins[0]->ID : 1);

function out($m) { echo $m . "\n"; }

/* =========================================================================
 * Helpers
 * ====================================================================== */

function media($key) {
    static $c = [];
    if (isset($c[$key])) return $c[$key];
    hyd_i18n_suspend(true);
    $ids = get_posts(['post_type' => 'attachment', 'post_status' => 'inherit', 'meta_key' => '_hyd_key', 'meta_value' => $key, 'numberposts' => 1, 'fields' => 'ids']);
    hyd_i18n_suspend(false);
    if (!$ids) out("  ! media ontbreekt: $key (draai eerst hydynamic-setup.php)");
    return $c[$key] = $ids ? (int) $ids[0] : 0;
}
function pdf($path) { return media('pdf:' . $path); }

function nl($type, $path) {
    $id = hyd_find_by_path($path, $type, 'nl');
    if (!$id) out("  ! Nederlands origineel niet gevonden: $type $path");
    return $id;
}

function blocks(array $list) {
    return implode("\n\n", array_map(fn($b) => hyd_block_markup($b[0], $b[1] ?? []), $list));
}
function usp(array $items) {
    return array_map(fn($t) => is_array($t) ? ['tekst' => $t[0], 'accent' => $t[1]] : ['tekst' => $t, 'accent' => ''], $items);
}
function link_to($id, $title = '') {
    return ['title' => $title ?: get_the_title($id), 'url' => wp_make_link_relative(get_permalink($id)), 'target' => '']; // relatief: werkt op .nl én .com
}
function pdf_link($id, $title) {
    return $id ? ['title' => $title, 'url' => wp_make_link_relative(wp_get_attachment_url($id)), 'target' => '_blank'] : '';
}

/** Engelse vertaling aanmaken/bijwerken, gekoppeld aan het Nederlandse origineel. */
function en($type, $nl_id, $slug, $title, $content, $args = []) {
    if (!$nl_id) return 0;
    $group = (int) get_post_meta($nl_id, '_hyd_group', true) ?: $nl_id;
    update_post_meta($nl_id, '_hyd_group', $group);

    hyd_i18n_suspend(true);
    $found = get_posts(['post_type' => $type, 'post_status' => ['publish', 'draft', 'pending', 'private'], 'numberposts' => 1, 'fields' => 'ids',
        'meta_query' => [['key' => '_hyd_lang', 'value' => 'en'], ['key' => '_hyd_group', 'value' => $group]]]);
    hyd_i18n_suspend(false);

    $data = [
        'post_type'    => $type,
        'post_name'    => $slug,
        'post_title'   => $title,
        'post_content' => wp_slash($content),
        'post_status'  => 'publish',
        'post_parent'  => (int) ($args['parent'] ?? 0),
        'menu_order'   => (int) ($args['order'] ?? get_post_field('menu_order', $nl_id)),
    ];
    foreach (['post_excerpt', 'post_date', 'post_author'] as $k) {
        if (isset($args[$k])) $data[$k] = $args[$k];
    }

    $GLOBALS['hyd_saving_lang'] = 'en';
    if ($found) {
        $data['ID'] = $found[0];
        $id = wp_update_post($data, true);
    } else {
        $id = wp_insert_post($data, true);
    }
    unset($GLOBALS['hyd_saving_lang']);
    if (is_wp_error($id)) { out("  ! $type/$slug: " . $id->get_error_message()); return 0; }

    update_post_meta($id, '_hyd_lang', 'en');
    update_post_meta($id, '_hyd_group', $group);
    $thumb = $args['thumb'] ?? get_post_thumbnail_id($nl_id);
    if ($thumb) set_post_thumbnail($id, $thumb);
    if (!empty($args['metadesc'])) update_post_meta($id, '_yoast_wpseo_metadesc', $args['metadesc']);
    if (!empty($args['focuskw'])) update_post_meta($id, '_yoast_wpseo_focuskw', $args['focuskw']);
    out("  ✓ $type: $title");
    return (int) $id;
}

/* =========================================================================
 * 1. Engelse alt-teksten
 * ====================================================================== */
out("\n[1/7] Engelse alt-teksten…");
$ALT = [
    'tile-hydrauliek-slangen' => 'Hydraulic hose with crimp fittings', 'tile-fittingen-adapters' => 'Hydraulic fittings and adapters',
    'tile-perskoppelingen' => 'Crimp couplings for hydraulic hoses', 'tile-flexmaster' => 'Danfoss Flexmaster couplings',
    'tile-polyamide-leidingen' => 'Coil of polyamide PA12 tube', 'tile-push-in' => 'Push-in connectors', 'tile-smeernippels' => 'Grease nipples and grease gun',
    'tile-nippelblokken' => 'Nipple block with accessories', 'tile-zuivelkoppelingen' => 'Dairy couplings to DIN 11851', 'tile-storz' => 'Storz couplings',
    'tile-camlock' => 'Camlock couplings', 'tile-tankwagen' => 'Tanker couplings',
    'foto-slangen-fittingen' => 'Hydraulic hoses with fittings and crimp couplings on a machine',
    'foto-assemblage-dnv' => 'Employee checking an engraved, DNV-certified hose assembly',
    'foto-ontwikkeling-productie' => 'Machined steel components with technical drawing and calliper',
    'foto-direct-startklaar' => 'Grease-filled lines connected to a distribution block',
    'foto-offshore-hero' => 'Black polyamide tubes on a coil', 'foto-werkdruk' => 'PA12 tube connected to a lubrication pump',
    'logo-danfoss' => 'Danfoss – Engineering Tomorrow', 'foto-bedrijfspand' => 'Hydynamic Holland BV building at Marssteden 28 in Enschede',
    'foto-team' => 'The Hydynamic team in front of the building in Enschede, holding hoses and couplings',
    'logo-dnv' => 'DNV management system certificate ISO 9001', 'logo-abs' => 'ABS (American Bureau of Shipping)',
    'logo-bureau-veritas' => 'Bureau Veritas', 'logo-lloyds-register' => 'Lloyd’s Register',
    'home-hero' => 'Hydraulic hoses and couplings on a machine', 'home-pand' => 'Aerial view of the Hydynamic building in Enschede',
];
for ($i = 1; $i <= 12; $i++) $ALT[sprintf('sfeer-%02d', $i)] = 'Impression of Hydynamic in Enschede ' . $i;
foreach ($ALT as $k => $alt) if ($id = media($k)) update_post_meta($id, '_hyd_alt_en', $alt);
out('  ✓ ' . count($ALT) . ' afbeeldingen');

/* =========================================================================
 * 2. Engels offerteformulier
 * ====================================================================== */
out("\n[2/7] Offerteformulier (EN)…");
$form_id = 0;
if (class_exists('GFAPI')) {
    foreach (GFAPI::get_forms(null) as $f) if ($f['title'] === 'Request a quote (EN)') $form_id = (int) $f['id'];
    $form = [
        'title' => 'Request a quote (EN)', 'description' => '', 'labelPlacement' => 'top_label',
        'button' => ['type' => 'text', 'text' => 'Send request'], 'enableHoneypot' => true, 'markupVersion' => 2,
        'fields' => [
            ['id' => 1, 'type' => 'text', 'label' => 'Name', 'placeholder' => 'What is your name?', 'isRequired' => true, 'autocompleteAttribute' => 'name', 'enableAutocomplete' => true],
            ['id' => 2, 'type' => 'text', 'label' => 'Company', 'placeholder' => 'What is your company name?', 'isRequired' => true, 'autocompleteAttribute' => 'organization', 'enableAutocomplete' => true],
            ['id' => 3, 'type' => 'phone', 'label' => 'Phone', 'placeholder' => 'What is your phone number?', 'phoneFormat' => 'international', 'isRequired' => true, 'autocompleteAttribute' => 'tel', 'enableAutocomplete' => true],
            ['id' => 4, 'type' => 'email', 'label' => 'Email', 'placeholder' => 'What is your email address?', 'isRequired' => true, 'autocompleteAttribute' => 'email', 'enableAutocomplete' => true],
            ['id' => 5, 'type' => 'textarea', 'label' => 'Message', 'placeholder' => 'How can we help you? Please describe your question.', 'isRequired' => false],
            ['id' => 6, 'type' => 'hidden', 'label' => 'Sent from page', 'defaultValue' => '{embed_url}'],
        ],
        'confirmations' => ['hyd_thanks' => ['id' => 'hyd_thanks', 'name' => 'Default confirmation', 'isDefault' => true, 'type' => 'message',
            'message' => '<strong>Thank you for your request.</strong> We will contact you as soon as possible.']],
        'notifications' => ['hyd_admin' => ['id' => 'hyd_admin', 'name' => 'New quote request (EN)', 'isActive' => true, 'event' => 'form_submission',
            'to' => '{admin_email}', 'toType' => 'email', 'subject' => 'New quote request (EN) via {embed_url}', 'message' => '{all_fields}',
            'replyTo' => '{Email:4}', 'from' => '{admin_email}', 'fromName' => 'Hydynamic website']],
    ];
    if ($form_id) { $form['id'] = $form_id; GFAPI::update_form($form); } else { $form_id = (int) GFAPI::add_form($form); }
    GFAPI::update_form_property($form_id, 'is_active', 1);
    out("  ✓ Formulier 'Request a quote (EN)' (#$form_id)");
}

/* =========================================================================
 * 3. Gedeelde teksten
 * ====================================================================== */
$USP_HYD = usp(['Official Danfoss distributor', 'DNV authorised assembly centre', 'Certified assembly in 1 working day', 'ISO 9001 certified']);
$USP_PA  = usp(['Compliant with DIN 73378 and DIN 74324', 'Rigid and flexible versions', 'Coils of 50 or 100 metres', 'Available pre-filled with grease']);
$USP_CS  = usp(['Own development and production', 'Production to drawing or sample', 'Standard ZnNi surface treatment', 'Available pre-filled with grease']);
$USP_IND = usp(['Own product line under the Hydynamic brand', 'Dairy couplings compliant with DIN 11851 and DIN 11853', 'Active since 1997', 'ISO 9001 certified']);
$USP_OFF = usp(['DNV-authorised assembly centre', 'DNV-certified assembly in 1 working day', 'Type-approved fittings (DNV, ABS, BV)', 'ZnNi surface treatment']);
$USP_CERT = usp(['DNV-authorised assembly centre', 'ISO 9001:2015 certified', 'Inspection certificate with delivery', '3-year warranty on Danfoss assemblies']);

$FAQ = [
    ['vraag' => 'Which hydraulic hose or coupling do I need for my application?', 'antwoord' => '<p>That depends on the working pressure, the medium, the temperature and the connection to your system. Send us the specifications, a drawing or a sample and we will advise the right combination of hose, fitting and coupling from the Danfoss range or our own product line.</p>'],
    ['vraag' => 'Can you assemble hydraulic hoses and lines to size?', 'antwoord' => '<p>Yes. Hose assemblies are crimped, engraved for traceability, cleaned and tested in-house. As a DNV authorised assembly centre, Hydynamic can also inspect and certify an assembly independently if required.</p>'],
    ['vraag' => 'Can you also put together a complete central lubrication system?', 'antwoord' => '<p>Hydynamic develops and produces components for central lubrication systems: polyamide tubes, push-in connectors, grease nipples and parts for nipple blocks and distribution blocks. Contact us to discuss the configuration for your application.</p>'],
    ['vraag' => 'What is the lead time for standard products and custom work?', 'antwoord' => '<p>Standard products are delivered from stock. A DNV-certified hose assembly can be delivered within one working day on request. For custom work to drawing or sample, you will receive the lead time with the quotation.</p>'],
];
$FAQ_MORE = [
    ['vraag' => 'What are the operational risks of direct hose-to-hose contact in a hydraulic installation?', 'antwoord' => '<p>Constant contact between hoses causes abrasive friction during operation. This friction acts like sandpaper and damages the protective outer cover, exposing the reinforcement layer. Using clamps or protective spirals is essential to prevent this mechanical wear.</p>'],
    ['vraag' => 'What specific impact does prolonged exposure to UV radiation have on the outer cover of a hose?', 'antwoord' => '<p>UV radiation and ozone break down the polymers in the outer cover, leading to ozone cracking (fine hairline cracks). These cracks make the reinforcement layer vulnerable to moisture and corrosion, which affects the structural integrity of the hose and eventually leads to failure.</p>'],
    ['vraag' => 'How do I correctly interpret the technical data on the layline of a hydraulic hose?', 'antwoord' => '<p>The layline contains crucial information such as the manufacturer, the part number, the exact inner diameter and the maximum working pressure. It also lists the applicable international standards (such as ISO or SAE). Always verify this data before assembly to ensure the hose meets the system specifications.</p>'],
    ['vraag' => 'How does the total length of a hose affect the pressure drop in a lubrication system?', 'antwoord' => '<p>Longer lines increase the internal friction of the medium against the wall of the hose. In central lubrication systems this can lead to a significant pressure loss, so that the lubricant may not reach the furthest lubrication points in the required quantity.</p>'],
    ['vraag' => 'Why is it necessary to clean a hose internally immediately after it has been cut to length?', 'antwoord' => '<p>The cutting process releases rubber particles and metal shavings that remain inside the hose. If this contamination is not removed with compressed air or a cleaning projectile, these particles can damage sensitive valves or clog injectors in the system.</p>'],
    ['vraag' => 'What is the technical difference between the nominal working pressure and the theoretical burst pressure?', 'antwoord' => '<p>The working pressure is the maximum pressure at which the hose can be used safely and continuously. The burst pressure is the point at which the hose fails structurally. In accordance with safety standards, the burst pressure of hydraulic hoses is usually four times the working pressure (the 4:1 safety factor).</p>'],
    ['vraag' => 'Why are pre-filled hoses preferred when installing central lubrication systems?', 'antwoord' => '<p>Pre-filled hoses (filled with EP 00/000 grease) prevent air pockets from forming in the system. This ensures that bearings are lubricated from the very first activation of the system, preventing initial wear and damage to components.</p>'],
];
$KB      = ['kennisbank', ['titel' => 'Knowledge base', 'aantal' => '4', 'knoptekst' => 'View all articles']];
$OFFERTE = ['offerte', ['titel' => 'Start today with a solution that works.', 'accent' => 'that works.', 'subregel' => 'Request a no-obligation custom quote.']];
$STARTKLAAR = ['titel' => 'Delivered ready to use', 'accent' => 'ready to use', 'kopniveau' => 'h2',
    'tekst' => '<p>Tubes and hoses within this range are available on request in a version pre-filled with grease, so that lubrication points are supplied right from the first start-up.</p>',
    'media' => 'afbeelding', 'positie' => 'links', 'afbeelding' => media('foto-direct-startklaar'), 'knop_type' => 'offerte', 'knop_tekst' => 'Request a quote'];

$PDF = [
    'catalogus' => pdf('2026/01/Hydynamic_Catalogue_26.pdf'), 'zoutspray' => pdf('2024/04/Zoutspray-test.pdf'),
    'pa-spec' => pdf('2024/04/Specification-Hydynamic-PA6x15-emptyENG-2.pdf'), 'vet-ep00' => pdf('2024/04/Product-specification-Grease-EP00-000-ENG.pdf'),
    'danfoss-kat' => pdf('2025/06/DANFOSS-KAT.pdf'), 'reusable' => pdf('2025/06/Reusable_EA001J.pdf'), 'danfoss-ac' => pdf('2025/06/Danfoss-AC.pdf'),
    'flexmaster' => pdf('2025/06/DANFOSS-FLEXMASTER.pdf'), 'iso' => pdf('2025/07/ISO-9001-277351-2018-AQ-NLD-RvA-2-20241003.pdf'),
    'dnv' => pdf('2025/10/AMT0000099-revision4.pdf'), 'privacy' => pdf('2023/06/Privacy-statement-NL_2023.pdf'),
    'voorwaarden' => pdf('2024/01/General-terms-and-conditions-NL-DE-EN.pdf'),
];

/* =========================================================================
 * 4. Structuur (eerst aanmaken zodat links kloppen)
 * ====================================================================== */
out("\n[3/7] Structuur…");
$P = [];
$PG = [
    'home'      => [nl('page', 'homepage'), 'home', 'Home'],
    'products'  => [nl('page', 'producten'), 'products', 'Products'],
    'solutions' => [nl('page', 'oplossingen'), 'solutions', 'Solutions'],
    'services'  => [nl('page', 'diensten'), 'services', 'Services'],
    'kb'        => [nl('page', 'kennisbank'), 'knowledge-base', 'Knowledge base'],
    'about'     => [nl('page', 'over-ons'), 'about-us', 'About us'],
    'cert'      => [nl('page', 'certificeringen'), 'certifications', 'Certifications'],
    'contact'   => [nl('page', 'contact'), 'contact', 'Contact'],
    'faq'       => [nl('page', 'veelgestelde-vragen'), 'frequently-asked-questions', 'Frequently asked questions'],
    'quote'     => [nl('page', 'offerte-aanvragen'), 'request-a-quote', 'Request a quote'],
];
foreach ($PG as $k => [$nl_id, $slug, $title]) {
    $P[$k] = en('page', $nl_id, $slug, $title, '', $k === 'kb' ? ['post_excerpt' => 'Background and explanations on hydraulic hoses, couplings, central lubrication and certification.'] : []);
}

$CATS = [
    'hydrauliekslangen-koppelingen' => ['hydraulic-hoses-couplings', 'Hydraulic hoses and couplings', [
        'hydrauliekslangen'  => ['hydraulic-hoses', 'Hydraulic hoses', 'tile-hydrauliek-slangen', 'Danfoss hose series, supplemented with our own DN4 and DN6 high-pressure hoses.'],
        'fittingen-adapters' => ['fittings-adapters', 'Fittings and adapters', 'tile-fittingen-adapters', 'Common thread standards, compression fittings to DIN 2353 and ISO B quick couplings.'],
        'perskoppelingen'    => ['crimp-couplings', 'Crimp couplings', 'tile-perskoppelingen', 'Crimp fittings per hose type, matched to the Danfoss hose series.'],
    ]],
    'centrale-smering' => ['central-lubrication', 'Central lubrication', [
        'polyamide-buizen-pa12'      => ['polyamide-tubes-pa12', 'Polyamide tubes (PA12)', 'tile-polyamide-leidingen', 'Tubes and multi-core tubes, including fittings such as straight and elbow couplings, T-pieces and bulkhead couplings.'],
        'push-in-connectoren'        => ['push-in-connectors', 'Push-in connectors', 'tile-push-in', 'Push-in fittings for straight, elbow and T connections, with check valve and dust cap versions.'],
        'smeernippels-vetpersen'     => ['grease-nipples-grease-guns', 'Grease nipples and grease guns', 'tile-smeernippels', 'Grease nipples, grease guns and filler nipples for applying grease to lubrication points.'],
        'verdeelblokken-accessoires' => ['distribution-blocks-accessories', 'Nipple blocks and accessories', 'tile-nippelblokken', 'Parts for nipple blocks and distribution blocks, such as pressure gauges, check valves, screw plugs, protective springs and hose clamps.'],
    ]],
    'industrieel' => ['industrial', 'Industrial', [
        'zuivelkoppelingen'    => ['dairy-couplings', 'Dairy couplings', 'tile-zuivelkoppelingen', 'Hygienic couplings compliant with DIN 11851 and DIN 11853, for the dairy, food and pharmaceutical industries.'],
        'storz-koppelingen'    => ['storz-couplings', 'Storz couplings', 'tile-storz', 'Quick couplings with a symmetrical claw connection secured with a quarter turn, with no distinction between male and female parts. Common in firefighting and water transport.'],
        'camlock-koppelingen'  => ['camlock-couplings', 'Camlock couplings', 'tile-camlock', 'Quick couplings fastened and released with two levers without tools, common where lines are regularly connected and disconnected, such as when unloading tankers.'],
        'tankwagenkoppelingen' => ['tanker-couplings', 'Tanker couplings', 'tile-tankwagen', 'Couplings for loading and unloading tankers, with a lock against accidental release.'],
        'spiraalslangen'       => ['spiral-hoses', 'Spiral hoses', '', 'PVC spiral hoses for transporting liquids and other media.'],
        'industriele-slangen'  => ['industrial-hoses', 'Industrial hoses', '', 'Industrial hoses, including EPDM hoses, cleaning hoses and food-grade hoses.'],
    ]],
];
$CAT = []; $PROD = []; $NLPROD = [];
foreach ($CATS as $nl_slug => [$slug, $title, $children]) {
    $CAT[$slug] = en('producten', nl('producten', $nl_slug), $slug, $title, '');
    foreach ($children as $nl_child => [$cslug, $ctitle]) {
        $NLPROD[$cslug] = nl('producten', "$nl_slug/$nl_child");
        $PROD[$cslug]   = en('producten', $NLPROD[$cslug], $cslug, $ctitle, '', ['parent' => $CAT[$slug]]);
    }
}

$SOL = [
    'offshore-en-maritiem'                  => ['offshore-and-marine', 'Offshore and Marine'],
    'luchtvaart-gse'                        => ['aviation-gse', 'Aviation (GSE)'],
    'machinebouw-oem'                       => ['mechanical-engineering-oem', 'Mechanical engineering (OEM)'],
    'agrarisch-en-bosbouw'                  => ['agriculture-and-forestry', 'Agriculture and Forestry'],
    'grondverzet-en-mijnbouw'               => ['earthmoving-and-mining', 'Earthmoving and Mining'],
    'transport-en-logistiek'                => ['transport-and-logistics', 'Transport and Logistics'],
    'duurzame-energie'                      => ['renewable-energy', 'Renewable Energy'],
    'recycling'                             => ['recycling', 'Recycling'],
    'voedingsindustrie-en-drankenindustrie' => ['food-and-beverage-industry', 'Food and beverage industry'],
];
$S = [];
foreach ($SOL as $nl_slug => [$slug, $title]) $S[$slug] = en('oplossingen', nl('oplossingen', $nl_slug), $slug, $title, '');

$SRV = [
    'slangassemblage-op-maat'     => ['custom-hose-assembly', 'Custom hose assembly'],
    'dnv-certificering'           => ['dnv-certification', 'DNV certification'],
    'maatwerk-productie'          => ['custom-manufacturing', 'Custom manufacturing'],
    'voorvullen-smeerslangen'     => ['pre-filling-lubrication-hoses', 'Pre-filling lubrication hoses'],
    'oppervlaktebehandeling-znni' => ['surface-treatment-znni', 'Surface treatment (ZnNi)'],
];
$D = [];
foreach ($SRV as $nl_slug => [$slug, $title]) $D[$slug] = en('diensten', nl('diensten', $nl_slug), $slug, $title, '');

/* =========================================================================
 * 5. Inhoud
 * ====================================================================== */
out("\n[4/7] Pagina's…");

// Homepage
en('page', $PG['home'][0], 'home', 'Home', blocks([
    ['home-hero', ['achtergrondfoto' => media('home-hero'), 'titel' => 'Your trusted supplier for lubrication & fluid technology', 'tegels' => [
        ['icoon' => media('icon-smering'), 'titel' => 'Central lubrication', 'link' => link_to($CAT['central-lubrication'], 'Central lubrication'), 'tekst' => 'Manufacturer of compression fittings, screw sleeves, screw pillars, check valves, adapters and accessories for central lubrication systems in various materials.'],
        ['icoon' => media('icon-klantspecifiek'), 'titel' => 'Customer-specific', 'link' => link_to($D['custom-manufacturing'], 'Customer-specific'), 'tekst' => 'Customer-specific production of parts in both large and small series, in various materials. Flexibility, customisation and high quality are central.'],
        ['icoon' => media('icon-hydrauliek'), 'titel' => 'Hydraulics', 'link' => link_to($CAT['hydraulic-hoses-couplings'], 'Hydraulics'), 'tekst' => 'Specialist in hydraulics, hoses, crimp couplings, flexmasters and accessories. As an official Danfoss distributor we offer high-quality products and carry out certification entirely in-house.'],
        ['icoon' => media('icon-industrieel'), 'titel' => 'Industrial', 'link' => link_to($CAT['industrial'], 'Industrial'), 'tekst' => 'Discover the wide Hydynamic range of PVC spiral hoses, cleaning hoses, Storz and camlock couplings. High-quality solutions for various industrial applications.'],
    ]]],
    ['usp-balk', ['stijl' => 'licht', 'items' => usp([['Quality makes us unique', 'unique'], ['More than 25 years of experience', 'experience'], ['International supplier', 'supplier'], ['Develop and produce', 'produce']])]],
    ['tekst-afbeelding', ['titel' => 'Since 1997', 'kopniveau' => 'h2',
        'tekst' => '<p>Hydynamic Holland BV was founded in 1997. At the time, our main activity was the assembly of hydraulic hoses. Shortly after our founding we became an official Danfoss distributor. We have now been active as a Danfoss distributor (formerly Eaton Aeroquip) for more than 24 years and still operate in the hydraulics sector. Our focus is on quality, and that is exactly where we make the difference. It is no coincidence that our slogan is: “quality makes us unique”.</p>',
        'media' => 'afbeelding', 'positie' => 'rechts', 'afbeelding' => media('home-pand'), 'uitlopend' => 1,
        'knop_type' => 'link', 'knop_link' => link_to($P['about'], 'Read more'), 'achtergrond' => 'donker']],
    ['kennisbank', ['titel' => 'Knowledge base', 'subtitel' => 'Stay up to date', 'aantal' => '4', 'knoptekst' => 'View all posts']],
    $OFFERTE,
]), ['metadesc' => 'Hydynamic Holland BV in Enschede (NL): official Danfoss distributor, DNV authorised assembly centre and manufacturer of components for central lubrication. Request a no-obligation quote.']);

// Producten-overzicht
en('page', $PG['products'][0], 'products', 'Products', blocks([
    ['paginakop', ['variant' => 'tekst', 'titel' => 'Products', 'intro' => '<p>Hydynamic supplies hydraulic hoses and couplings, components for central lubrication and industrial couplings and hoses. Choose a product group for the full range.</p>']],
    ['tegels', ['titel' => 'Our product groups', 'kopniveau' => 'h2', 'kolommen' => '3', 'linktekst' => 'View products', 'achtergrond' => 'grijs', 'ruimte' => 'aansluitend', 'tegels' => [
        ['afbeelding' => media('tile-hydrauliek-slangen'), 'titel' => 'Hydraulic hoses and couplings', 'tekst' => 'Hose, fitting and crimp coupling from a single source: Danfoss brands and our own product line.', 'link' => link_to($CAT['hydraulic-hoses-couplings'])],
        ['afbeelding' => media('tile-polyamide-leidingen'), 'titel' => 'Central lubrication', 'tekst' => 'Components for central lubrication systems, developed and produced in-house.', 'link' => link_to($CAT['central-lubrication'])],
        ['afbeelding' => media('tile-camlock'), 'titel' => 'Industrial', 'tekst' => 'Industrial couplings and hoses for connecting and transferring liquids.', 'link' => link_to($CAT['industrial'])],
    ]]],
    ['usp-balk', ['stijl' => 'donker', 'items' => $USP_HYD]],
    $KB, $OFFERTE,
]), ['metadesc' => 'The Hydynamic range: hydraulic hoses and couplings, components for central lubrication and industrial couplings and hoses.']);

out("\n[5/7] Producten…");
function cat_tiles($slug) {
    global $CATS, $PROD;
    foreach ($CATS as $c) if ($c[0] === $slug) $children = $c[2];
    $tiles = [];
    foreach ($children as [$cslug, $title, $img, $text]) {
        if (!$img) continue;
        $tiles[] = ['afbeelding' => media($img), 'titel' => $title, 'tekst' => $text, 'link' => link_to($PROD[$cslug], $title)];
    }
    if ($slug === 'hydraulic-hoses-couplings') {
        $tiles[] = ['afbeelding' => media('tile-flexmaster'), 'titel' => 'Flexmaster', 'tekst' => 'Pipe and tube couplings for low pressure that absorb vibration and misalignment.', 'link' => link_to($PROD['hydraulic-hoses'], 'Flexmaster')];
    }
    return $tiles;
}
$FAQ_BLOCK = ['faq', ['titel' => 'Frequently asked questions', 'vragen' => $FAQ, 'schema' => 0, 'knop_link' => link_to($P['faq'], 'View all frequently asked questions'), 'achtergrond' => 'donker']];

$intro_hyd = 'Hose, fitting and crimp coupling from a single source: Danfoss brands and our own product line, supplied as a certified hose assembly if required.';
en('producten', nl('producten', 'hydrauliekslangen-koppelingen'), 'hydraulic-hoses-couplings', 'Hydraulic hoses and couplings', blocks([
    ['paginakop', ['variant' => 'tekst', 'titel' => 'Hydraulic hoses and couplings', 'accent' => 'couplings', 'intro' => "<p>$intro_hyd</p>"]],
    ['tegels', ['kolommen' => '4', 'linktekst' => 'View products', 'tegels' => cat_tiles('hydraulic-hoses-couplings'), 'achtergrond' => 'grijs', 'ruimte' => 'aansluitend']],
    ['downloads', ['label' => 'Downloads', 'achtergrond' => 'grijs', 'ruimte' => 'aansluitend', 'bestanden' => [
        ['titel' => 'Danfoss Hydraulic Hoses', 'bestand' => $PDF['danfoss-kat']], ['titel' => 'Danfoss Reusable Fittings', 'bestand' => $PDF['reusable']],
        ['titel' => 'Danfoss A/C and Refrigeration', 'bestand' => $PDF['danfoss-ac']], ['titel' => 'Danfoss flexmaster A/C', 'bestand' => $PDF['flexmaster']],
        ['titel' => 'Danfoss A/C and Refrigeration parts', 'bestand' => ''],
    ]]],
    ['usp-balk', ['stijl' => 'donker', 'items' => $USP_HYD]],
    ['tekst-afbeelding', ['titel' => 'Hoses, fittings and crimp couplings', 'kopniveau' => 'h2',
        'tekst' => '<p><strong>Hydraulic hoses</strong>:<br>Danfoss hose series, supplemented with our own DN4 and DN6 high-pressure hoses.</p><p><strong>Fittings and adapters</strong>:<br>connections in the common thread standards, compression fittings to DIN 2353 and ISO-B quick couplings, in several material versions.</p><p><strong>Crimp couplings</strong>:<br>crimp fittings per hose type, matched to the Danfoss hose series.</p><p><strong>Flexmaster</strong>:<br>Danfoss pipe and tube couplings for low-pressure applications, compensating for vibration and misalignment in piping systems.</p>',
        'media' => 'afbeelding', 'positie' => 'rechts', 'afbeelding' => media('foto-slangen-fittingen'), 'knop_type' => 'geen', 'achtergrond' => 'grijs']],
    ['tekst-afbeelding', ['titel' => 'Assembly and certification', 'accent' => 'certification', 'kopniveau' => 'h2',
        'tekst' => '<p>Hose assemblies are crimped, engraved for traceability, cleaned and tested in-house. As a DNV authorised assembly centre, Hydynamic inspects and certifies independently, with a lead time of one working day for a DNV-certified assembly.</p>',
        'media' => 'afbeelding', 'positie' => 'links', 'afbeelding' => media('foto-assemblage-dnv'), 'knop_type' => 'offerte', 'knop_tekst' => 'Request a quote', 'achtergrond' => 'wit']],
    ['tekst-afbeelding', ['titel' => 'Danfoss brands and our own line', 'accent' => 'our own line', 'kopniveau' => 'h2',
        'tekst' => '<p>Hydynamic is an official Danfoss distributor and supplies Aeroquip®, the Winner™ hose series and Weatherhead®, among others, supplemented with reusable fittings type-approved by DNV, ABS and Bureau Veritas. Hydynamic also develops and produces its own product line under the Hydynamic brand.</p>',
        'media' => 'logo', 'positie' => 'rechts', 'afbeelding' => media('logo-danfoss'), 'knop_type' => 'geen', 'achtergrond' => 'grijs']],
    $FAQ_BLOCK, $KB, $OFFERTE,
]), ['metadesc' => $intro_hyd, 'focuskw' => 'hydraulic hoses and couplings']);

$intro_cs = 'Hydynamic develops and produces components for central lubrication systems. The range consists of polyamide tubes, push-in connectors, grease nipples and parts for nipple blocks and distribution blocks, supplemented with mounting and protection materials.';
en('producten', nl('producten', 'centrale-smering'), 'central-lubrication', 'Central lubrication', blocks([
    ['paginakop', ['variant' => 'tekst', 'titel' => 'Central lubrication', 'intro' => "<p>$intro_cs</p>"]],
    ['tegels', ['kolommen' => '4', 'linktekst' => 'View products', 'tegels' => cat_tiles('central-lubrication'), 'achtergrond' => 'grijs', 'ruimte' => 'aansluitend']],
    ['downloads', ['label' => 'Downloads', 'achtergrond' => 'grijs', 'ruimte' => 'aansluitend', 'bestanden' => [
        ['titel' => 'Hydynamic product catalogue 2026', 'bestand' => $PDF['catalogus']], ['titel' => 'Salt spray test zinc nickel (ZnNi)', 'bestand' => $PDF['zoutspray']]]]],
    ['usp-balk', ['stijl' => 'donker', 'items' => $USP_CS]],
    ['tekst-afbeelding', ['titel' => 'Development and production', 'accent' => 'production', 'kopniveau' => 'h2',
        'tekst' => '<p>Items are recorded in technical drawings. In addition to the standard range, production to specification, drawing or sample is possible.</p><p>Steel components are supplied as standard with a zinc nickel surface treatment (ZnNi), alongside versions in stainless steel and brass, among others. The corrosion resistance of zinc nickel has been successfully tested in a salt spray test.</p>',
        'media' => 'afbeelding', 'positie' => 'rechts', 'afbeelding' => media('foto-ontwikkeling-productie'), 'knop_type' => 'offerte', 'knop_tekst' => 'Request a quote', 'achtergrond' => 'grijs']],
    ['tekst-afbeelding', $STARTKLAAR + ['achtergrond' => 'wit']],
    $FAQ_BLOCK, $KB, $OFFERTE,
]), ['metadesc' => $intro_cs, 'focuskw' => 'central lubrication']);

$intro_ind = 'Hydynamic supplies industrial couplings and industrial hoses for connecting and transferring liquids and other media in various sectors: dairy couplings, Storz couplings, camlock couplings, tanker couplings, spiral hoses and industrial hoses.';
en('producten', nl('producten', 'industrieel'), 'industrial', 'Industrial', blocks([
    ['paginakop', ['variant' => 'tekst', 'titel' => 'Industrial', 'intro' => "<p>$intro_ind</p>"]],
    ['tegels', ['kolommen' => '4', 'linktekst' => 'View products', 'tegels' => cat_tiles('industrial'), 'achtergrond' => 'grijs', 'ruimte' => 'aansluitend']],
    ['downloads', ['label' => 'Downloads', 'achtergrond' => 'grijs', 'ruimte' => 'aansluitend', 'bestanden' => [['titel' => 'Hydynamic product catalogue 2026', 'bestand' => $PDF['catalogus']]]]],
    ['usp-balk', ['stijl' => 'donker', 'items' => $USP_IND]],
    ['tekst-afbeelding', ['titel' => 'Sectors and applications', 'accent' => 'applications', 'kopniveau' => 'h2',
        'tekst' => '<p>Dairy couplings are used in the food and beverage industry, as well as in the pharmaceutical and chemical industries, for the hygienic connection of lines.</p><p>Storz couplings, camlock couplings, tanker couplings, spiral hoses and industrial hoses are used in the wider sectors in which Hydynamic is active:</p><ul><li>Offshore and Marine</li><li>Aviation (GSE)</li><li>Mechanical engineering (OEM)</li><li>Agriculture and Forestry</li><li>Earthmoving and Mining</li><li>Transport and Logistics</li><li>Renewable Energy and Recycling</li></ul>',
        'media' => 'afbeelding', 'positie' => 'rechts', 'afbeelding' => media('foto-slangen-fittingen'), 'knop_type' => 'offerte', 'knop_tekst' => 'Request a quote', 'achtergrond' => 'grijs']],
    $FAQ_BLOCK,
    ['kennisbank', ['titel' => 'Knowledge base', 'aantal' => '4', 'knoptekst' => 'Go to knowledge base']],
    $OFFERTE,
]), ['metadesc' => $intro_ind, 'focuskw' => 'industrial couplings']);

// Polyamide tubes (PA12) – volledig
en('producten', $NLPROD['polyamide-tubes-pa12'], 'polyamide-tubes-pa12', 'Polyamide tubes (PA12)', blocks([
    ['paginakop', ['variant' => 'slider', 'titel' => 'Polyamide tubes (PA12)', 'accent' => '(PA12)', 'intro' => "<p>$intro_cs</p>",
        'slides' => [media('foto-offshore-hero'), media('foto-werkdruk'), media('foto-direct-startklaar'), media('tile-polyamide-leidingen'), media('foto-ontwikkeling-productie')],
        'downloads_label' => 'Downloads', 'downloads' => [
            ['titel' => 'Hydynamic product catalogue 2026', 'bestand' => $PDF['catalogus']],
            ['titel' => 'Specification Hydynamic PA tube 6x1.5', 'bestand' => $PDF['pa-spec']],
            ['titel' => 'Product specification grease EP00/000', 'bestand' => $PDF['vet-ep00']]],
        'knop_type' => 'offerte', 'knop_tekst' => 'Request a quote']],
    ['usp-balk', ['stijl' => 'donker', 'items' => $USP_PA]],
    ['kaarten', ['titel' => 'PA12 HL and PA12 PHLY', 'kopniveau' => 'h2', 'kolommen' => '2', 'achtergrond' => 'grijs', 'kaarten' => [
        ['titel' => 'Rigid and flexible', 'tekst' => 'Hydynamic supplies PA12 tubes in two versions: PA12 HL (PA 362), the more rigid version, and PA12 PHLY (PA 5224), the more flexible version. Both are available in size 6x1.5; PA12 HL also in 4x0.85 and 4x1.'],
        ['titel' => 'Multi-core version', 'tekst' => 'For several tubes in one sheath, a multi-core version with two or three inner tubes is also available.'],
    ]]],
    ['tekst-afbeelding', ['titel' => 'Working pressure and applications', 'accent' => 'applications', 'kopniveau' => 'h2',
        'tekst' => '<p>The working pressure, burst pressure and bend radius of a PA12 tube depend on diameter, wall thickness and construction, and differ per part number.</p><p>The exact values can be found in the product catalogue and technical specifications. PA12 has no built-in reinforcement layer and is therefore suitable for central lubrication and pneumatics, not for power hydraulics.</p>',
        'media' => 'afbeelding', 'positie' => 'rechts', 'afbeelding' => media('foto-werkdruk'), 'knop_type' => 'offerte', 'knop_tekst' => 'Request a quote', 'achtergrond' => 'blauw']],
    ['tekst-afbeelding', $STARTKLAAR + ['achtergrond' => 'grijs']],
    ['tekst-afbeelding', ['titel' => 'Connections and accessories', 'kopniveau' => 'h2',
        'tekst' => '<p>For the polyamide tubes, Hydynamic supplies matching accessories: reinforcing sleeves, union nuts to DIN 3871 and cutting rings to DIN 3862, for connection to fittings within the central lubrication range.</p>',
        'media' => 'afbeelding', 'positie' => 'rechts', 'afbeelding' => media('foto-ontwikkeling-productie'), 'knop_type' => 'offerte', 'knop_tekst' => 'Request a quote', 'achtergrond' => 'wit']],
    $KB, $OFFERTE,
]), ['parent' => $CAT['central-lubrication'], 'focuskw' => 'polyamide tubes',
     'metadesc' => 'Polyamide tubes (PA12) for central lubrication and pneumatics: PA12 HL and PA12 PHLY, compliant with DIN 73378 and DIN 74324, on coils of 50 or 100 metres and available pre-filled with grease.']);

// Overige producten (zelfde opbouw als de Nederlandse versie)
$cat_usp = ['hydraulic-hoses-couplings' => $USP_HYD, 'central-lubrication' => $USP_PA, 'industrial' => $USP_IND];
foreach ($CATS as [$cat_slug, $cat_title, $children]) {
    foreach ($children as [$cslug, $title, $img, $text]) {
        if ($cslug === 'polyamide-tubes-pa12') continue;
        $list = [
            ['paginakop', ['variant' => $img ? 'afbeelding' : 'tekst', 'titel' => $title, 'intro' => "<p>$text</p>", 'afbeelding' => $img ? media($img) : '', 'knop_type' => 'offerte', 'knop_tekst' => 'Request a quote']],
            ['usp-balk', ['stijl' => 'donker', 'items' => $cat_usp[$cat_slug]]],
        ];
        if ($cat_slug === 'central-lubrication') $list[] = ['tekst-afbeelding', $STARTKLAAR + ['achtergrond' => 'grijs']];
        $list[] = $KB;
        $list[] = $OFFERTE;
        en('producten', $NLPROD[$cslug], $cslug, $title, blocks($list), ['parent' => $CAT[$cat_slug], 'metadesc' => $text]);
    }
}

out("\n[6/7] Oplossingen, diensten en vaste pagina's…");
$intro_off = 'Hydynamic develops and produces components for central lubrication systems. The range consists of polyamide tubes, push-in connectors, grease nipples and parts for nipple blocks and distribution blocks, supplemented with mounting and protection materials.';
en('oplossingen', nl('oplossingen', 'offshore-en-maritiem'), 'offshore-and-marine', 'Offshore and Marine', blocks([
    ['paginakop', ['variant' => 'afbeelding', 'titel' => 'Hydraulic solutions for offshore and marine', 'accent' => 'offshore and marine', 'intro' => "<p>$intro_off</p>", 'afbeelding' => media('foto-offshore-hero'), 'knop_type' => 'offerte', 'knop_tekst' => 'Request a quote']],
    ['usp-balk', ['stijl' => 'donker', 'items' => $USP_OFF]],
    ['kaarten', ['titel' => 'Technical requirements in offshore and marine', 'accent' => 'offshore and marine', 'kopniveau' => 'h2', 'kolommen' => '3', 'achtergrond' => 'grijs',
        'intro' => 'On board ships and on offshore installations, hydraulic systems are exposed to salt water, vibration and periodic inspections. A hose assembly must not only fit the system, but also be demonstrably certified. Three requirements determine the choice of hose, fitting and certification.',
        'kaarten' => [
            ['titel' => 'Corrosion from salt', 'tekst' => 'Steel components from the Hydynamic range are available with a zinc nickel surface treatment (ZnNi). Their corrosion resistance has been tested in a salt spray test.'],
            ['titel' => 'Classification and type approval', 'tekst' => 'Danfoss reusable Aeroquip fittings are type-approved by DNV, ABS and Bureau Veritas, among others, depending on type and size.'],
            ['titel' => 'Limiting downtime', 'tekst' => 'A replacement hose assembly must be available quickly and with a certificate. As a DNV authorised assembly centre, Hydynamic supplies a certified assembly within one working day.'],
        ]]],
    ['tegels', ['titel' => 'What Hydynamic supplies for offshore and marine', 'kopniveau' => 'h2', 'kolommen' => '3', 'linktekst' => 'View products', 'achtergrond' => 'blauw',
        'intro' => 'For offshore and marine, Hydynamic supplies both individual components and complete hose assemblies. Assembly, certification and surface treatment take place in-house, so that product and documentation come from a single source.',
        'tegels' => [
            ['afbeelding' => media('tile-hydrauliek-slangen'), 'titel' => 'Hydraulic hoses', 'tekst' => 'Danfoss FC and GH series, supplemented with our own DN4 and DN6 high-pressure hose.', 'link' => link_to($PROD['hydraulic-hoses'])],
            ['afbeelding' => media('tile-fittingen-adapters'), 'titel' => 'Fittings and adapters', 'tekst' => 'Including reusable fittings type-approved by DNV, ABS and Bureau Veritas.', 'link' => link_to($PROD['fittings-adapters'])],
            ['afbeelding' => media('tile-perskoppelingen'), 'titel' => 'Crimp couplings', 'tekst' => 'Crimp fittings per hose type, matched to the Danfoss hose series.', 'link' => link_to($PROD['crimp-couplings'])],
        ]]],
    $KB, $OFFERTE,
]), ['thumb' => media('foto-offshore-hero'), 'metadesc' => 'Hydraulic solutions for offshore and marine: DNV-certified hose assemblies within one working day, type-approved fittings and ZnNi surface treatment.']);

foreach ($SOL as $nl_slug => [$slug, $title]) {
    if ($slug === 'offshore-and-marine') continue;
    en('oplossingen', nl('oplossingen', $nl_slug), $slug, $title, blocks([
        ['paginakop', ['variant' => 'tekst', 'titel' => 'Hydraulic solutions for ' . strtolower($title), 'accent' => strtolower($title), 'knop_type' => 'offerte', 'knop_tekst' => 'Request a quote']],
        ['usp-balk', ['stijl' => 'donker', 'items' => $USP_HYD]],
        $KB, $OFFERTE,
    ]));
}

$sol_tiles = [];
foreach ($SOL as [$slug, $title]) $sol_tiles[] = ['afbeelding' => $slug === 'offshore-and-marine' ? media('foto-offshore-hero') : '', 'titel' => $title, 'tekst' => '', 'link' => link_to($S[$slug], $title)];
en('page', $PG['solutions'][0], 'solutions', 'Solutions', blocks([
    ['paginakop', ['variant' => 'tekst', 'titel' => 'Solutions', 'intro' => '<p>Hydynamic supplies hydraulic solutions, central lubrication and industrial couplings for a wide range of sectors.</p>']],
    ['tegels', ['titel' => 'Sectors', 'kopniveau' => 'h2', 'kolommen' => '3', 'linktekst' => 'View solution', 'tegels' => $sol_tiles, 'achtergrond' => 'grijs', 'ruimte' => 'aansluitend']],
    ['usp-balk', ['stijl' => 'donker', 'items' => $USP_HYD]],
    $KB, $OFFERTE,
]));

// Diensten
$SRV_CONTENT = [
    'custom-hose-assembly' => ['Custom hose assembly', 'hose assembly', 'foto-assemblage-dnv', $USP_HYD,
        '<p>Hose assemblies are crimped, engraved for traceability, cleaned and tested in-house. As a DNV authorised assembly centre, Hydynamic inspects and certifies independently, with a lead time of one working day for a DNV-certified assembly.</p>', []],
    'dnv-certification' => ['DNV certification', 'certification', 'foto-assemblage-dnv', $USP_CERT,
        '<p>Hydynamic Holland BV is authorised by DNV to inspect and certify Danfoss hose assemblies completely independently, without the involvement of DNV. A DNV-inspected hose can be delivered within one working day on request, with the inspection certificate included with the delivery.</p>', []],
    'custom-manufacturing' => ['Custom manufacturing', 'manufacturing', 'foto-ontwikkeling-productie', $USP_CS,
        '<p>Customer-specific production of parts in both large and small series, in various materials. Flexibility, customisation and high quality are central.</p><p>Items are recorded in technical drawings. In addition to the standard range, production to specification, drawing or sample is possible.</p>', []],
    'pre-filling-lubrication-hoses' => ['Pre-filling lubrication hoses', 'lubrication hoses', 'foto-direct-startklaar', $USP_PA,
        '<p>Tubes and hoses within the central lubrication range are available on request in a version pre-filled with grease, so that lubrication points are supplied right from the first start-up.</p>',
        [['titel' => 'Product specification grease EP00/000', 'bestand' => $PDF['vet-ep00']]]],
    'surface-treatment-znni' => ['Surface treatment (ZnNi)', '(ZnNi)', 'foto-ontwikkeling-productie', usp(['Own development and production', 'Standard ZnNi surface treatment', 'Tested in a salt spray test', 'ISO 9001 certified']),
        '<p>Steel components are supplied as standard with a zinc nickel surface treatment (ZnNi), alongside versions in stainless steel and brass, among others. The corrosion resistance of zinc nickel has been successfully tested in a salt spray test.</p>',
        [['titel' => 'Salt spray test zinc nickel (ZnNi)', 'bestand' => $PDF['zoutspray']]]],
];
$srv_tiles = [];
foreach ($SRV as $nl_slug => [$slug, $title]) {
    [$h1, $accent, $img, $u, $intro, $dl] = $SRV_CONTENT[$slug];
    $list = [
        ['paginakop', ['variant' => 'afbeelding', 'titel' => $h1, 'accent' => $accent, 'intro' => $intro, 'afbeelding' => media($img), 'knop_type' => 'offerte', 'knop_tekst' => 'Request a quote']],
        ['usp-balk', ['stijl' => 'donker', 'items' => $u]],
    ];
    if ($dl) $list[] = ['downloads', ['label' => 'Downloads', 'bestanden' => $dl, 'achtergrond' => 'grijs']];
    $list[] = $KB;
    $list[] = $OFFERTE;
    en('diensten', nl('diensten', $nl_slug), $slug, $title, blocks($list), ['thumb' => media($img), 'metadesc' => wp_strip_all_tags($intro)]);
    $srv_tiles[] = ['afbeelding' => media($img), 'titel' => $title, 'tekst' => wp_trim_words(wp_strip_all_tags($intro), 16), 'link' => link_to($D[$slug], $title)];
}
en('page', $PG['services'][0], 'services', 'Services', blocks([
    ['paginakop', ['variant' => 'tekst', 'titel' => 'Services', 'intro' => '<p>Assembly, certification, custom manufacturing and surface treatment take place in-house at Hydynamic, so that product and documentation come from a single source.</p>']],
    ['tegels', ['kolommen' => '3', 'linktekst' => 'View service', 'tegels' => $srv_tiles, 'achtergrond' => 'grijs', 'ruimte' => 'aansluitend']],
    ['usp-balk', ['stijl' => 'donker', 'items' => $USP_HYD]],
    $KB, $OFFERTE,
]));

// Over ons
$sfeer = [];
for ($i = 1; $i <= 12; $i++) if ($m = media(sprintf('sfeer-%02d', $i))) $sfeer[] = $m;
en('page', $PG['about'][0], 'about-us', 'About us', blocks([
    ['paginakop', ['variant' => 'achtergrond', 'titel' => 'Hydraulic hoses and lubrication systems since 1997', 'accent' => 'since 1997', 'afbeelding' => media('foto-bedrijfspand'),
        'intro' => '<p>Hydynamic Holland BV in Enschede was founded in 1997 as an assembly company for hydraulic hoses. Shortly after its founding, Hydynamic became an official distributor of Danfoss, formerly Eaton Aeroquip. Today, the range also includes components and accessories for central lubrication systems under its own brand.</p>']],
    ['usp-balk', ['stijl' => 'donker', 'items' => usp(['Founded in 1997', 'Danfoss distributor, formerly Eaton Aeroquip', 'DNV-authorised assembly centre', 'Own product line for central lubrication'])]],
    ['tekst-afbeelding', ['titel' => 'One team, under one roof', 'accent' => 'under one roof', 'kopniveau' => 'h2',
        'tekst' => '<p>At Hydynamic, the sales office, warehouse and assembly work from the same building in Enschede. A request for a hose assembly therefore does not pass through external parties: whoever handles the request works alongside the people who make, test and certify the assembly.</p><p>A few years after its founding, an OEM customer asked whether Hydynamic could also supply parts for central lubrication systems. That question grew into a second core activity: the production of components and accessories for central lubrication systems.</p><p>Both activities follow the same principle, laid down in the company slogan: <strong>“Quality makes us unique.”</strong></p>',
        'media' => 'afbeelding', 'positie' => 'links', 'afbeelding' => media('foto-team'), 'knop_type' => 'link', 'knop_link' => link_to($P['contact'], 'Contact us'), 'achtergrond' => 'grijs', 'ruimte' => 'groot']],
    ['fotoslider', ['titel' => 'A look inside', 'accent' => 'inside', 'kopniveau' => 'h2', 'intro' => 'Warehouse, workshop and sales office of Hydynamic in Enschede.', 'fotos' => $sfeer, 'achtergrond' => 'verloop']],
    ['logos', ['titel' => 'Certifications and inspections', 'kopniveau' => 'h2', 'indeling' => 'naast', 'achtergrond' => 'blauw',
        'tekst' => '<p>Hydynamic Holland BV is certified to ISO 9001:2015 and is authorised by DNV to inspect and certify Danfoss hose assemblies completely independently, without the involvement of DNV.</p><p>A DNV-inspected hose assembly can be delivered within one working day on request, with the inspection certificate included with the delivery. Hose assemblies can also be provided with a certificate from other classification societies, including ABS, Bureau Veritas and Lloyd’s Register.</p>',
        'logos' => [media('logo-dnv'), media('logo-abs'), media('logo-bureau-veritas'), media('logo-lloyds-register')],
        'knop_type' => 'link', 'knop_link' => link_to($P['cert'], 'View all certifications')]],
    $KB, $OFFERTE,
]), ['metadesc' => 'Hydynamic Holland BV in Enschede: since 1997 an assembly company for hydraulic hoses, official Danfoss distributor and manufacturer of components for central lubrication systems.']);

// Certificeringen
en('page', $PG['cert'][0], 'certifications', 'Certifications', blocks([
    ['paginakop', ['variant' => 'afbeelding', 'titel' => 'Certifications and inspections', 'accent' => 'inspections', 'afbeelding' => media('foto-assemblage-dnv'),
        'intro' => '<p><strong>Quality in accordance with the ISO 9001:2015 standard</strong></p><p>Hydynamic Holland BV is certified to ISO 9001:2015 and is a DNV-authorised assembly centre. Hose assemblies are inspected and certified in-house, with the inspection certificate included directly with the delivery.</p>',
        'knop_type' => 'link', 'knop_link' => pdf_link($PDF['iso'], 'View the certificate')]],
    ['usp-balk', ['stijl' => 'donker', 'items' => $USP_CERT]],
    ['kaarten', ['titel' => 'Warranty and documentation', 'accent' => 'documentation', 'kopniveau' => 'h2', 'kolommen' => '3', 'achtergrond' => 'grijs',
        'intro' => 'In addition to inspection and certification, warranty and documentation are laid down in accordance with recognised standards.',
        'kaarten' => [
            ['titel' => '3-year warranty', 'tekst' => 'A 3-year warranty applies to Danfoss/Aeroquip hose assemblies, provided they are fitted in accordance with DIN 20066.'],
            ['titel' => 'Certificates to NEN-EN 10204', 'tekst' => 'When issuing certificates, Hydynamic Holland BV works in accordance with NEN-EN 10204.'],
            ['titel' => 'DNV inspection within one working day', 'tekst' => 'A DNV-inspected hose assembly can be delivered within one working day on request, with the inspection certificate included with the delivery.'],
        ]]],
    ['logos', ['titel' => 'Other classification societies', 'kopniveau' => 'h2', 'indeling' => 'onder', 'achtergrond' => 'blauw',
        'tekst' => '<p>Hose assemblies can also be provided with a certificate from other classification societies. A Type Approval Certificate can be issued for the hoses, depending on hose type and size.</p>',
        'logos' => [media('logo-abs'), media('logo-bureau-veritas'), media('logo-lloyds-register')]]],
    ['tekst-afbeelding', ['titel' => 'DNV-authorised assembly centre', 'accent' => 'assembly centre', 'kopniveau' => 'h2',
        'tekst' => '<p>Hydynamic Holland BV is authorised by DNV to inspect and certify Danfoss hose assemblies completely independently, without the involvement of DNV.</p><p>A DNV-inspected hose can be delivered within one working day on request. The inspection certificate is sent directly with the delivery, so there is no waiting time for an external inspection.</p>',
        'media' => 'afbeelding', 'positie' => 'links', 'afbeelding' => media('sfeer-01'), 'knop_type' => 'link', 'knop_link' => pdf_link($PDF['dnv'], 'View the certificate'), 'achtergrond' => 'grijs', 'ruimte' => 'groot']],
    $KB, $OFFERTE,
]), ['metadesc' => 'Hydynamic Holland BV is ISO 9001:2015 certified and a DNV-authorised assembly centre. DNV-inspected hose assemblies within one working day, with the inspection certificate included.']);

// Contact
en('page', $PG['contact'][0], 'contact', 'Contact', blocks([
    ['paginakop', ['variant' => 'tekst', 'titel' => 'Contact']],
    ['contact', ['titel' => 'Contact details', 'openingstijden' => 'Monday to Friday 8:00 – 17:00', 'achtergrond' => 'grijs', 'ruimte' => 'aansluitend', 'afdelingen' => [
        ['icoon' => 'info', 'titel' => 'General questions', 'tekst' => 'For general questions, information and other correspondence.', 'email' => 'info@hydynamic.nl'],
        ['icoon' => 'box', 'titel' => 'Orders and deliveries', 'tekst' => 'For placing, following up or changing current orders and deliveries.', 'email' => 'order@hydynamic.nl'],
        ['icoon' => 'file', 'titel' => 'Quotations and technical advice', 'tekst' => 'For quotation requests, custom requests and technical advice.', 'email' => 'request@hydynamic.nl'],
    ]]],
    ['offerte', ['zijkant' => 'route', 'routefoto' => media('foto-bedrijfspand'), 'titel' => 'Start today with a solution that works.', 'accent' => 'that works.', 'subregel' => 'Request a no-obligation custom quote.']],
]), ['metadesc' => 'Contact Hydynamic Holland BV, Marssteden 28 in Enschede, the Netherlands. Call +31(0)53-4282048 or email info@hydynamic.nl.']);

// Veelgestelde vragen
en('page', $PG['faq'][0], 'frequently-asked-questions', 'Frequently asked questions', blocks([
    ['paginakop', ['variant' => 'tekst', 'titel' => 'Frequently asked questions', 'accent' => 'questions', 'intro' => '<p>Answers to frequently asked questions about hydraulic hoses, couplings, central lubrication and certification.</p>']],
    ['faq', ['titel' => 'Hydraulic hoses and central lubrication', 'vragen' => array_merge($FAQ_MORE, $FAQ), 'schema' => 1, 'achtergrond' => 'donker']],
    $KB, $OFFERTE,
]), ['metadesc' => 'Frequently asked questions about hydraulic hoses, couplings, central lubrication, lead times and DNV certification at Hydynamic Holland BV.']);

// Offerte aanvragen
en('page', $PG['quote'][0], 'request-a-quote', 'Request a quote', blocks([
    ['paginakop', ['variant' => 'tekst', 'titel' => 'Request a quote', 'accent' => 'quote',
        'intro' => '<p>Request a no-obligation custom quote for hydraulic hoses and couplings, central lubrication, industrial couplings or a certified hose assembly. We will contact you as soon as possible.</p>']],
    ['usp-balk', ['stijl' => 'donker', 'items' => $USP_HYD]],
    $OFFERTE,
]), ['metadesc' => 'Request a no-obligation custom quote from Hydynamic Holland BV for hydraulic hoses, couplings, central lubrication and DNV-certified hose assemblies.']);

/* =========================================================================
 * 6. Kennisbank (Engelse artikelen)
 * ====================================================================== */
out("\n[7/7] Kennisbank, menu's en instellingen…");
$live = json_decode(file_get_contents(__DIR__ . '/live-posts.json'), true);
$live_id = [];
foreach ($live as $lp) $live_id[$lp['slug']] = $lp['id'];
$author = get_user_by('login', 'laurenskooy');

function inline_images($html, $live_post_id) {
    $i = 0;
    return preg_replace_callback('/<img[^>]*>/', function () use (&$i, $live_post_id) {
        $id = media('kbi-' . $live_post_id . '-' . $i++);
        return $id ? wp_get_attachment_image($id, 'large', false, ['class' => 'wp-image-' . $id]) : '';
    }, $html);
}

$en_posts = json_decode(file_get_contents(__DIR__ . '/export-en-posts.json'), true);
foreach (require __DIR__ . '/content-en-posts.php' as $p) $en_posts[] = $p + ['own' => true];
foreach ($en_posts as $p) {
    $nl_id = nl('post', $p['nl_slug']);
    if (!$nl_id) continue;
    $lid  = $live_id[$p['nl_slug']] ?? 0;
    $html = $p['content'];
    if (!empty($p['own'])) {
        $html = preg_replace_callback('/\{\{img:([a-z0-9-]+)\}\}/', fn($m) => ($id = media($m[1])) ? wp_get_attachment_image($id, 'large', false, ['class' => 'wp-image-' . $id]) : '', $html);
    } else {
        $html = inline_images($html, $lid);
        $html = preg_replace('#https?://(www\.)?(hydynamic\.com|hydynamic\.nl|wordpress-1253021-6542008\.cloudwaysapps\.com)/#', '/', $html);
    }
    en('post', $nl_id, $p['slug'], html_entity_decode($p['title'], ENT_QUOTES, 'UTF-8'), $html, [
        'post_date'    => get_post_field('post_date', $nl_id),
        'post_author'  => $author ? $author->ID : get_post_field('post_author', $nl_id),
        'post_excerpt' => wp_strip_all_tags(html_entity_decode($p['excerpt'] ?? '', ENT_QUOTES, 'UTF-8')),
        'metadesc'     => $p['yoast_desc'] ?? '',
    ]);
}

/* =========================================================================
 * 7. Menu's en instellingen
 * ====================================================================== */
function menu_reset($name) {
    $m  = wp_get_nav_menu_object($name);
    $id = $m ? $m->term_id : wp_create_nav_menu($name);
    foreach ((array) wp_get_nav_menu_items($id, ['post_status' => 'any']) as $item) wp_delete_post($item->ID, true);
    return $id;
}
function menu_item($menu, $title, $oid = 0, $obj = 'page', $parent = 0, $url = '', $target = '') {
    $a = ['menu-item-title' => $title, 'menu-item-status' => 'publish', 'menu-item-parent-id' => $parent, 'menu-item-target' => $target];
    $a += $url ? ['menu-item-type' => 'custom', 'menu-item-url' => $url] : ['menu-item-type' => 'post_type', 'menu-item-object' => $obj, 'menu-item-object-id' => $oid];
    return wp_update_nav_menu_item($menu, 0, $a);
}

$main = menu_reset('Main menu (EN)');
menu_item($main, 'Home', $P['home']);
$mp = menu_item($main, 'Products', $P['products']);
foreach ($CATS as [$slug, $title, $children]) {
    $mc = menu_item($main, $title, $CAT[$slug], 'producten', $mp);
    foreach ($children as [$cslug, $ctitle]) menu_item($main, $ctitle, $PROD[$cslug], 'producten', $mc);
}
$ms = menu_item($main, 'Solutions', $P['solutions']);
foreach ($SOL as [$slug, $title]) menu_item($main, $title, $S[$slug], 'oplossingen', $ms);
$md = menu_item($main, 'Services', $P['services']);
foreach ($SRV as [$slug, $title]) menu_item($main, $title, $D[$slug], 'diensten', $md);
menu_item($main, 'Knowledge base', $P['kb']);
menu_item($main, 'Certifications', $P['cert']);
menu_item($main, 'About us', $P['about']);
menu_item($main, 'Contact', $P['contact']);

$foot = menu_reset('Footer menu (EN)');
menu_item($foot, 'Privacy Policy', 0, '', 0, wp_make_link_relative(wp_get_attachment_url($PDF['privacy'])), '_blank');
menu_item($foot, 'Terms and conditions', 0, '', 0, wp_make_link_relative(wp_get_attachment_url($PDF['voorwaarden'])), '_blank');

update_option('hyd_menu_locations_en', ['main_menu' => $main, 'footer_menu' => $foot]);
update_option('hyd_front_page_en', $P['home']);
update_option('hyd_posts_page_en', $P['kb']);
update_option('options_offerte_formulier_en', $form_id);
update_option('options_offerte_fallback_en', $P['quote']);
update_option('options_contactkaart_titel_en', 'Prefer to talk directly?');
update_option('options_contactkaart_accent_en', 'talk directly?');

flush_rewrite_rules(false);
out("\nKlaar. Engelse site lokaal: " . home_url('/?hyd_lang=en') . " — op productie: https://www.hydynamic.com/\n");
