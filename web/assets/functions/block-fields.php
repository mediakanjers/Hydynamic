<?php
/**
 * ACF-velden voor alle Hydynamic-blokken (mk/...).
 *
 * Veldsleutels zijn voorspelbaar: field_hyd_{blok}_{veld} en voor subvelden
 * field_hyd_{blok}_{repeater}_{subveld}. Daardoor kan hyd_block_markup() blokken
 * met inhoud genereren (pagina-templates en het opbouwscript in /web/setup).
 */

if (!defined('ABSPATH')) exit;

/* -------------------------------------------------------------------------
 * Gedeelde veldsets
 * ---------------------------------------------------------------------- */

function hyd_f_title($default_tag = 'h2', $with_tag = true) {
    $f = [
        ['name' => 'titel', 'label' => 'Titel', 'type' => 'text', 'wrapper' => ['width' => $with_tag ? 45 : 60]],
        ['name' => 'accent', 'label' => 'Accentwoord(en)', 'type' => 'text', 'instructions' => 'Deel van de titel dat in accentblauw wordt getoond, bijv. "koppelingen".', 'wrapper' => ['width' => $with_tag ? 35 : 40]],
    ];
    if ($with_tag) {
        $f[] = ['name' => 'kopniveau', 'label' => 'Kopniveau', 'type' => 'select', 'choices' => ['h2' => 'H2', 'h3' => 'H3', 'p' => 'Geen kop (tekst)'], 'default_value' => $default_tag, 'instructions' => 'Voor SEO: één H1 per pagina (paginakop), secties zijn H2.', 'wrapper' => ['width' => 20]];
    }
    return $f;
}

function hyd_f_button($default = 'geen', $label = 'Offerte aanvragen') {
    return [
        ['name' => 'knop_type', 'label' => 'Knop', 'type' => 'button_group', 'choices' => ['geen' => 'Geen', 'offerte' => 'Offerte aanvragen', 'link' => 'Eigen link'], 'default_value' => $default, 'instructions' => '"Offerte aanvragen" scrollt automatisch naar het formulier onderaan de pagina.', 'wrapper' => ['width' => 40]],
        ['name' => 'knop_tekst', 'label' => 'Knoptekst', 'type' => 'text', 'placeholder' => $label, 'wrapper' => ['width' => 30], 'cond' => ['knop_type', 'offerte']],
        ['name' => 'knop_link', 'label' => 'Link', 'type' => 'link', 'wrapper' => ['width' => 30], 'cond' => ['knop_type', 'link']],
    ];
}

function hyd_f_layout($default_bg = 'wit', $choices = null) {
    $choices = $choices ?: [
        'wit'     => 'Wit',
        'grijs'   => 'Lichtgrijs',
        'verloop' => 'Grijs → wit verloop',
        'contact' => 'Lichtblauw (contactvlak)',
        'donker'  => 'Donkerblauw',
        'blauw'   => 'Blauw verloop',
    ];
    return [
        ['name' => 'tab_opmaak', 'label' => 'Opmaak', 'type' => 'tab'],
        ['name' => 'achtergrond', 'label' => 'Achtergrond', 'type' => 'select', 'choices' => $choices, 'default_value' => $default_bg, 'wrapper' => ['width' => 50]],
        ['name' => 'ruimte', 'label' => 'Ruimte boven/onder', 'type' => 'select', 'choices' => ['normaal' => 'Normaal', 'klein' => 'Klein', 'groot' => 'Groot', 'aansluitend' => 'Aansluitend op vorige sectie (geen ruimte boven)'], 'default_value' => 'normaal', 'wrapper' => ['width' => 50]],
    ];
}

function hyd_f_tab($label) {
    return ['name' => 'tab_' . sanitize_title($label), 'label' => $label, 'type' => 'tab'];
}

/* -------------------------------------------------------------------------
 * Blokdefinities
 * ---------------------------------------------------------------------- */

function hyd_block_definitions() {
    static $defs = null;
    if ($defs !== null) return $defs;

    $defs = [
        'paginakop' => array_merge(
            [hyd_f_tab('Inhoud')],
            [['name' => 'variant', 'label' => 'Variant', 'type' => 'select', 'choices' => [
                'tekst'       => 'Alleen tekst',
                'afbeelding'  => 'Tekst met afbeelding rechts',
                'slider'      => 'Downloads links, fotoslider rechts (productpagina)',
                'achtergrond' => 'Tekst op achtergrondfoto (over ons)',
            ], 'default_value' => 'tekst']],
            hyd_f_title('h1', false),
            [
                ['name' => 'intro', 'label' => 'Introtekst', 'type' => 'wysiwyg', 'toolbar' => 'basic', 'media_upload' => 0, 'delay' => 1],
                ['name' => 'afbeelding', 'label' => 'Afbeelding', 'type' => 'image', 'return_format' => 'id', 'cond' => [['variant', 'afbeelding'], ['variant', 'achtergrond']]],
                ['name' => 'slides', 'label' => 'Foto\'s slider', 'type' => 'gallery', 'return_format' => 'id', 'cond' => ['variant', 'slider']],
                ['name' => 'downloads_label', 'label' => 'Titel downloads', 'type' => 'text', 'default_value' => 'Downloads', 'cond' => ['variant', 'slider']],
                ['name' => 'downloads', 'label' => 'Downloads', 'type' => 'repeater', 'layout' => 'table', 'button_label' => 'Download toevoegen', 'cond' => ['variant', 'slider'], 'sub_fields' => [
                    ['name' => 'titel', 'label' => 'Titel', 'type' => 'text'],
                    ['name' => 'bestand', 'label' => 'Bestand (PDF)', 'type' => 'file', 'return_format' => 'id'],
                ]],
            ],
            hyd_f_button('geen'),
            [hyd_f_tab('Opmaak'), ['name' => 'kruimelpad', 'label' => 'Kruimelpad tonen', 'type' => 'true_false', 'ui' => 1, 'default_value' => 1, 'wrapper' => ['width' => 50]],
             ['name' => 'lijnen', 'label' => 'H-lijnen op de achtergrond', 'type' => 'true_false', 'ui' => 1, 'default_value' => 1, 'wrapper' => ['width' => 50]]]
        ),

        'home-hero' => [
            ['name' => 'achtergrondfoto', 'label' => 'Achtergrondfoto', 'type' => 'image', 'return_format' => 'id'],
            ['name' => 'titel', 'label' => 'Titel (H1)', 'type' => 'text'],
            ['name' => 'tegels', 'label' => 'Tegels', 'type' => 'repeater', 'layout' => 'block', 'max' => 4, 'button_label' => 'Tegel toevoegen', 'collapsed' => 'field_hyd_home-hero_tegels_titel', 'sub_fields' => [
                ['name' => 'icoon', 'label' => 'Icoon (SVG)', 'type' => 'image', 'return_format' => 'id', 'wrapper' => ['width' => 20]],
                ['name' => 'titel', 'label' => 'Titel', 'type' => 'text', 'wrapper' => ['width' => 40]],
                ['name' => 'link', 'label' => 'Link', 'type' => 'link', 'wrapper' => ['width' => 40]],
                ['name' => 'tekst', 'label' => 'Tekst', 'type' => 'textarea', 'rows' => 3],
            ]],
        ],

        'usp-balk' => [
            ['name' => 'stijl', 'label' => 'Stijl', 'type' => 'button_group', 'choices' => ['donker' => 'Donker met vinkjes', 'licht' => 'Wit met accentwoord'], 'default_value' => 'donker'],
            ['name' => 'items', 'label' => 'USP\'s', 'type' => 'repeater', 'layout' => 'table', 'min' => 1, 'max' => 6, 'button_label' => 'USP toevoegen', 'sub_fields' => [
                ['name' => 'tekst', 'label' => 'Tekst', 'type' => 'text'],
                ['name' => 'accent', 'label' => 'Accentwoord (alleen bij wit)', 'type' => 'text'],
            ]],
        ],

        'tegels' => array_merge(
            [hyd_f_tab('Inhoud')],
            hyd_f_title('h2'),
            [
                ['name' => 'intro', 'label' => 'Introtekst', 'type' => 'textarea', 'rows' => 3, 'new_lines' => 'br'],
                ['name' => 'bron', 'label' => 'Tegels', 'type' => 'button_group', 'choices' => ['handmatig' => 'Handmatig invullen', 'onderliggend' => 'Alle onderliggende pagina’s'], 'default_value' => 'handmatig',
                 'instructions' => '"Alle onderliggende pagina’s" toont automatisch alle producten/pagina’s onder deze pagina (of onder de gekozen pagina), met de tegeltekst en tegelfoto die je bij elk product invult. Nieuwe producten verschijnen vanzelf.', 'wrapper' => ['width' => 50]],
                ['name' => 'weergave', 'label' => 'Weergave', 'type' => 'button_group', 'choices' => ['raster' => 'Raster', 'slider' => 'Slider met pijlen'], 'default_value' => 'raster', 'wrapper' => ['width' => 50]],
                ['name' => 'bron_ouder', 'label' => 'Onderliggend aan', 'type' => 'post_object', 'post_type' => ['producten', 'oplossingen', 'diensten', 'page'], 'return_format' => 'id', 'allow_null' => 1, 'ui' => 1,
                 'instructions' => 'Leeg = de huidige pagina.', 'cond' => ['bron', 'onderliggend']],
                ['name' => 'kolommen', 'label' => 'Tegels per rij (desktop)', 'type' => 'button_group', 'choices' => ['3' => '3', '4' => '4'], 'default_value' => '4', 'wrapper' => ['width' => 50]],
                ['name' => 'linktekst', 'label' => 'Linktekst onder elke tegel', 'type' => 'text', 'default_value' => 'Bekijk producten', 'wrapper' => ['width' => 50]],
                ['name' => 'tegels', 'label' => 'Tegels (handmatig / extra)', 'instructions' => 'Bij "Alle onderliggende pagina’s" worden deze tegels achter de automatische tegels geplaatst.', 'type' => 'repeater', 'layout' => 'block', 'button_label' => 'Tegel toevoegen', 'collapsed' => 'field_hyd_tegels_tegels_titel', 'sub_fields' => [
                    ['name' => 'afbeelding', 'label' => 'Afbeelding', 'type' => 'image', 'return_format' => 'id', 'preview_size' => 'thumbnail', 'wrapper' => ['width' => 25]],
                    ['name' => 'titel', 'label' => 'Titel', 'type' => 'text', 'wrapper' => ['width' => 35]],
                    ['name' => 'link', 'label' => 'Doellink', 'type' => 'link', 'instructions' => 'Pagina waar de tegel naartoe linkt.', 'wrapper' => ['width' => 40]],
                    ['name' => 'tekst', 'label' => 'Tegeltekst (1 à 2 regels)', 'type' => 'textarea', 'rows' => 2, 'maxlength' => 160, 'new_lines' => '', 'instructions' => 'Korte, handmatige omschrijving. Wordt niet automatisch uit de pagina gehaald.'],
                ]],
            ],
            hyd_f_layout('grijs')
        ),

        'downloads' => array_merge(
            [hyd_f_tab('Inhoud')],
            [
                ['name' => 'label', 'label' => 'Titel', 'type' => 'text', 'default_value' => 'Downloads'],
                ['name' => 'bestanden', 'label' => 'Bestanden', 'type' => 'repeater', 'layout' => 'table', 'button_label' => 'Bestand toevoegen', 'sub_fields' => [
                    ['name' => 'titel', 'label' => 'Titel', 'type' => 'text'],
                    ['name' => 'bestand', 'label' => 'Bestand (PDF)', 'type' => 'file', 'return_format' => 'id'],
                ]],
            ],
            hyd_f_layout('grijs')
        ),

        'tekst-afbeelding' => array_merge(
            [hyd_f_tab('Inhoud')],
            hyd_f_title('h2'),
            [
                ['name' => 'tekst', 'label' => 'Tekst', 'type' => 'wysiwyg', 'toolbar' => 'full', 'media_upload' => 0, 'delay' => 1],
                ['name' => 'media', 'label' => 'Media', 'type' => 'button_group', 'choices' => ['afbeelding' => 'Afbeelding', 'slider' => 'Fotoslider', 'logo' => 'Logo (klein)'], 'default_value' => 'afbeelding', 'wrapper' => ['width' => 50]],
                ['name' => 'positie', 'label' => 'Positie media', 'type' => 'button_group', 'choices' => ['rechts' => 'Rechts', 'links' => 'Links'], 'default_value' => 'rechts', 'wrapper' => ['width' => 50]],
                ['name' => 'afbeelding', 'label' => 'Afbeelding', 'type' => 'image', 'return_format' => 'id', 'cond' => [['media', 'afbeelding'], ['media', 'logo']]],
                ['name' => 'slides', 'label' => 'Foto\'s', 'type' => 'gallery', 'return_format' => 'id', 'cond' => ['media', 'slider']],
                ['name' => 'uitlopend', 'label' => 'Afbeelding loopt door tot de rand van het scherm', 'type' => 'true_false', 'ui' => 1, 'cond' => ['media', 'afbeelding']],
            ],
            hyd_f_button('geen'),
            hyd_f_layout('wit')
        ),

        'kaarten' => array_merge(
            [hyd_f_tab('Inhoud')],
            hyd_f_title('h2'),
            [
                ['name' => 'intro', 'label' => 'Introtekst', 'type' => 'textarea', 'rows' => 3, 'new_lines' => 'br'],
                ['name' => 'kolommen', 'label' => 'Kaarten per rij', 'type' => 'button_group', 'choices' => ['2' => '2', '3' => '3'], 'default_value' => '3'],
                ['name' => 'kaarten', 'label' => 'Kaarten', 'type' => 'repeater', 'layout' => 'block', 'button_label' => 'Kaart toevoegen', 'collapsed' => 'field_hyd_kaarten_kaarten_titel', 'sub_fields' => [
                    ['name' => 'titel', 'label' => 'Titel', 'type' => 'text'],
                    ['name' => 'tekst', 'label' => 'Tekst', 'type' => 'textarea', 'rows' => 4, 'new_lines' => 'br'],
                ]],
            ],
            hyd_f_layout('grijs')
        ),

        'faq' => array_merge(
            [hyd_f_tab('Inhoud')],
            [
                ['name' => 'titel', 'label' => 'Titel', 'type' => 'text', 'default_value' => 'Veelgestelde vragen'],
                ['name' => 'vragen', 'label' => 'Vragen', 'type' => 'repeater', 'layout' => 'block', 'button_label' => 'Vraag toevoegen', 'collapsed' => 'field_hyd_faq_vragen_vraag', 'sub_fields' => [
                    ['name' => 'vraag', 'label' => 'Vraag', 'type' => 'text'],
                    ['name' => 'antwoord', 'label' => 'Antwoord', 'type' => 'wysiwyg', 'toolbar' => 'basic', 'media_upload' => 0, 'delay' => 1],
                ]],
                ['name' => 'schema', 'label' => 'FAQ-schema (rich results) toevoegen', 'type' => 'true_false', 'ui' => 1, 'default_value' => 1],
                ['name' => 'knop_link', 'label' => 'Knop onder de vragen', 'type' => 'link'],
            ],
            hyd_f_layout('donker', ['donker' => 'Donkerblauw', 'wit' => 'Wit', 'grijs' => 'Lichtgrijs'])
        ),

        'kennisbank' => array_merge(
            [hyd_f_tab('Inhoud')],
            [
                ['name' => 'titel', 'label' => 'Titel', 'type' => 'text', 'default_value' => 'Kennisbank', 'wrapper' => ['width' => 50]],
                ['name' => 'subtitel', 'label' => 'Subtitel', 'type' => 'text', 'wrapper' => ['width' => 50]],
                ['name' => 'aantal', 'label' => 'Aantal artikelen', 'type' => 'select', 'choices' => ['3' => '3', '4' => '4', '8' => '8'], 'default_value' => '4', 'wrapper' => ['width' => 33]],
                ['name' => 'categorie', 'label' => 'Alleen uit categorie', 'type' => 'taxonomy', 'taxonomy' => 'category', 'field_type' => 'select', 'allow_null' => 1, 'return_format' => 'id', 'add_term' => 0, 'wrapper' => ['width' => 33]],
                ['name' => 'knoptekst', 'label' => 'Knoptekst', 'type' => 'text', 'default_value' => 'Bekijk alle artikelen', 'wrapper' => ['width' => 34]],
                ['name' => 'artikelen', 'label' => 'Handmatige selectie (optioneel)', 'type' => 'relationship', 'post_type' => ['post'], 'filters' => ['search'], 'return_format' => 'id', 'max' => 8],
            ],
            hyd_f_layout('verloop')
        ),

        'offerte' => array_merge(
            [hyd_f_tab('Inhoud')],
            hyd_f_title('h2', false),
            [
                ['name' => 'subregel', 'label' => 'Subregel met vinkje', 'type' => 'text', 'placeholder' => 'Vraag vrijblijvend een offerte op maat aan.'],
                ['name' => 'zijkant', 'label' => 'Naast het formulier', 'type' => 'button_group', 'choices' => ['contact' => 'Kaart "Liever direct contact?"', 'route' => 'Foto met routeknop (contactpagina)'], 'default_value' => 'contact'],
                ['name' => 'routefoto', 'label' => 'Routefoto', 'type' => 'image', 'return_format' => 'id', 'cond' => ['zijkant', 'route']],
                ['name' => 'formulier', 'label' => 'Gravity Forms-formulier', 'type' => 'select', 'choices' => [], 'allow_null' => 1, 'instructions' => 'Leeg = het standaard offerteformulier uit Mediakanjers > Website instellingen.'],
            ]
        ),

        'fotoslider' => array_merge(
            [hyd_f_tab('Inhoud')],
            hyd_f_title('h2'),
            [
                ['name' => 'intro', 'label' => 'Introtekst', 'type' => 'textarea', 'rows' => 2, 'new_lines' => 'br'],
                ['name' => 'fotos', 'label' => 'Foto\'s', 'type' => 'gallery', 'return_format' => 'id', 'instructions' => 'Vul bij elke foto in de mediabibliotheek een alt-tekst in.'],
            ],
            hyd_f_layout('verloop')
        ),

        'logos' => array_merge(
            [hyd_f_tab('Inhoud')],
            hyd_f_title('h2'),
            [
                ['name' => 'tekst', 'label' => 'Tekst', 'type' => 'wysiwyg', 'toolbar' => 'basic', 'media_upload' => 0, 'delay' => 1],
                ['name' => 'indeling', 'label' => 'Indeling', 'type' => 'button_group', 'choices' => ['naast' => 'Logo\'s in raster naast de tekst', 'onder' => 'Logo\'s in een rij onder de tekst'], 'default_value' => 'naast'],
                ['name' => 'logos', 'label' => 'Logo\'s', 'type' => 'gallery', 'return_format' => 'id', 'instructions' => 'De alt-tekst van elk logo is de naam van de instantie.'],
            ],
            hyd_f_button('geen'),
            hyd_f_layout('blauw')
        ),

        'contact' => array_merge(
            [hyd_f_tab('Inhoud')],
            [
                ['name' => 'titel', 'label' => 'Titel kaart', 'type' => 'text', 'default_value' => 'Contactgegevens', 'wrapper' => ['width' => 50]],
                ['name' => 'openingstijden', 'label' => 'Openingstijden', 'type' => 'text', 'default_value' => 'Maandag t/m vrijdag 8:00 - 17:00', 'wrapper' => ['width' => 50]],
                ['name' => 'afdelingen', 'label' => 'E-mailadressen per afdeling', 'type' => 'repeater', 'layout' => 'block', 'button_label' => 'Afdeling toevoegen', 'collapsed' => 'field_hyd_contact_afdelingen_titel', 'sub_fields' => [
                    ['name' => 'icoon', 'label' => 'Icoon', 'type' => 'select', 'choices' => ['info' => 'Info', 'box' => 'Pakket', 'file' => 'Document', 'mail' => 'E-mail'], 'default_value' => 'info', 'wrapper' => ['width' => 20]],
                    ['name' => 'titel', 'label' => 'Titel', 'type' => 'text', 'wrapper' => ['width' => 40]],
                    ['name' => 'email', 'label' => 'E-mailadres', 'type' => 'email', 'wrapper' => ['width' => 40]],
                    ['name' => 'tekst', 'label' => 'Omschrijving', 'type' => 'text'],
                ]],
            ],
            hyd_f_layout('grijs')
        ),
    ];

    return $defs;
}

/* -------------------------------------------------------------------------
 * Registratie bij ACF
 * ---------------------------------------------------------------------- */

function hyd_build_fields($block, array $fields, $parent_key = null) {
    $out    = [];
    $prefix = $parent_key ?: 'field_hyd_' . $block;
    foreach ($fields as $f) {
        $key = $prefix . '_' . $f['name'];
        $field = $f;
        $field['key'] = $key;
        if (($f['type'] ?? '') === 'tab') {
            $field['name'] = '';
            $field['placement'] = 'top';
        }
        if (!empty($f['cond'])) {
            $conds = is_array($f['cond'][0]) ? $f['cond'] : [$f['cond']];
            $field['conditional_logic'] = array_map(fn($c) => [[
                'field'    => $prefix . '_' . $c[0],
                'operator' => '==',
                'value'    => $c[1],
            ]], $conds);
            unset($field['cond']);
        }
        if (!empty($f['sub_fields'])) {
            $field['sub_fields'] = hyd_build_fields($block, $f['sub_fields'], $key);
        }
        $out[] = $field;
    }
    return $out;
}

add_action('acf/include_fields', function () {
    if (!function_exists('acf_add_local_field_group')) return;

    $titles = [
        'paginakop' => 'Paginakop', 'home-hero' => 'Homepage hero', 'usp-balk' => 'USP-balk',
        'tegels' => 'Klikbare tegels', 'downloads' => 'Downloads', 'tekst-afbeelding' => 'Tekst met afbeelding',
        'kaarten' => 'Tekstkaarten', 'faq' => 'Veelgestelde vragen', 'kennisbank' => 'Kennisbank',
        'offerte' => 'Offerteformulier', 'fotoslider' => 'Fotoslider', 'logos' => 'Logo\'s / certificeringen',
        'contact' => 'Contactgegevens',
    ];

    foreach (hyd_block_definitions() as $block => $fields) {
        acf_add_local_field_group([
            'key'      => 'group_hyd_' . $block,
            'title'    => 'Blok: ' . ($titles[$block] ?? $block),
            'fields'   => hyd_build_fields($block, $fields),
            'location' => [[['param' => 'block', 'operator' => '==', 'value' => 'mk/' . $block]]],
            'active'   => true,
        ]);
    }
});

// Tegelgegevens per product/oplossing/dienst (gebruikt door "Alle onderliggende pagina's")
add_action('acf/include_fields', function () {
    if (!function_exists('acf_add_local_field_group')) return;
    acf_add_local_field_group([
        'key'      => 'group_hyd_tegel',
        'title'    => 'Tegel (overzichten)',
        'fields'   => [
            ['key' => 'field_hyd_tegeltekst', 'label' => 'Tegeltekst (1 à 2 regels)', 'name' => 'hyd_tegeltekst', 'type' => 'textarea', 'rows' => 3, 'maxlength' => 160, 'new_lines' => '',
             'instructions' => 'Korte omschrijving op de tegel in het overzicht van de bovenliggende pagina.'],
            ['key' => 'field_hyd_tegelafbeelding', 'label' => 'Tegelfoto', 'name' => 'hyd_tegelafbeelding', 'type' => 'image', 'return_format' => 'id', 'preview_size' => 'thumbnail',
             'instructions' => 'Leeg = de uitgelichte afbeelding.'],
        ],
        'location' => [
            [['param' => 'post_type', 'operator' => '==', 'value' => 'producten']],
            [['param' => 'post_type', 'operator' => '==', 'value' => 'oplossingen']],
            [['param' => 'post_type', 'operator' => '==', 'value' => 'diensten']],
        ],
        'position' => 'side',
        'active'   => true,
    ]);
});

// Gravity Forms-keuzelijst in het offerteblok en de instellingen vullen
function hyd_gf_choices($field) {
    $field['choices'] = [];
    if (class_exists('GFAPI')) {
        foreach (GFAPI::get_forms() as $form) {
            $field['choices'][$form['id']] = $form['title'] . ' (#' . $form['id'] . ')';
        }
    }
    return $field;
}
add_filter('acf/load_field/key=field_hyd_offerte_formulier', 'hyd_gf_choices');
add_filter('acf/load_field/key=field_hyd_opt_offerte_formulier', 'hyd_gf_choices');

/* -------------------------------------------------------------------------
 * Blokmarkup genereren (voor templates en het opbouwscript)
 * ---------------------------------------------------------------------- */

/**
 * Zet geneste data om naar ACF-blokdata (zoals ACF het zelf opslaat):
 * scalars -> naam + _naam (veldsleutel), repeaters -> aantal + naam_i_sub.
 */
function hyd_flatten_block_data($block, array $data, $fields = null, $name_prefix = '', $key_prefix = null) {
    $fields     = $fields ?? hyd_block_definitions()[$block] ?? [];
    $key_prefix = $key_prefix ?? 'field_hyd_' . $block;
    $index      = [];
    foreach ($fields as $f) {
        if (!empty($f['name'])) $index[$f['name']] = $f;
    }

    $out = [];
    foreach ($data as $name => $value) {
        $field = $index[$name] ?? null;
        $key   = $key_prefix . '_' . $name;
        $full  = $name_prefix . $name;

        if ($field && ($field['type'] ?? '') === 'repeater' && is_array($value)) {
            $out[$full]       = count($value);
            $out['_' . $full] = $key;
            foreach (array_values($value) as $i => $row) {
                $out += hyd_flatten_block_data($block, (array) $row, $field['sub_fields'], $full . '_' . $i . '_', $key);
            }
            continue;
        }
        $out[$full]       = $value;
        $out['_' . $full] = $key;
    }
    return $out;
}

function hyd_block_markup($block, array $data = []) {
    $attrs = [
        'name' => 'mk/' . $block,
        'data' => (object) hyd_flatten_block_data($block, $data),
        'mode' => 'preview',
    ];
    return '<!-- wp:mk/' . $block . ' ' . wp_json_encode($attrs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ' /-->';
}
