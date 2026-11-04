<?php
/**
 * Post types met de URL-structuur uit de briefing:
 *   /producten/{categorie}/            -> product op het hoogste niveau (categoriepagina)
 *   /producten/{categorie}/{product}/  -> onderliggend product
 *   /oplossingen/{naam}/
 *   /diensten/{naam}/
 *
 * Producten zijn hiërarchisch: een categorie is een product zonder bovenliggend
 * item. Zo worden categorie- én productpagina's volledig met blokken opgebouwd
 * en klopt het kruimelpad automatisch. De overzichtspagina's (/producten/ etc.)
 * zijn gewone pagina's met dezelfde slug (has_archive = false).
 */

if (!defined('ABSPATH')) exit;

function hyd_default_block_template($type) {
    $usps = [
        'producten'   => ['Officieel Danfoss distributeur', 'DNV geautoriseerd assemblagecentrum', 'Gecertificeerde assemblage in 1 werkdag', 'ISO 9001 gecertificeerd'],
        'oplossingen' => ['DNV-geautoriseerd assemblagecentrum', 'DNV-gecertificeerde assemblage in 1 werkdag', 'Typegecertificeerde fittingen (DNV, ABS, BV)', 'ZnNi-oppervlaktebehandeling'],
        'diensten'    => ['Officieel Danfoss distributeur', 'DNV geautoriseerd assemblagecentrum', 'Gecertificeerde assemblage in 1 werkdag', 'ISO 9001 gecertificeerd'],
    ];
    $usp_rows = [];
    foreach ($usps[$type] as $u) $usp_rows[] = ['tekst' => $u];

    $blocks = [
        ['paginakop', ['variant' => $type === 'oplossingen' ? 'afbeelding' : 'tekst', 'knop_type' => $type === 'oplossingen' ? 'offerte' : 'geen']],
        ['usp-balk', ['stijl' => 'donker', 'items' => $usp_rows]],
        ['tekst-afbeelding', ['achtergrond' => 'grijs', 'positie' => 'rechts', 'knop_type' => 'offerte']],
        ['kennisbank', ['titel' => 'Kennisbank', 'aantal' => '4']],
        ['offerte', []],
    ];

    return array_map(fn($b) => ['mk/' . $b[0], [
        'name' => 'mk/' . $b[0],
        'data' => hyd_flatten_block_data($b[0], $b[1]),
        'mode' => 'preview',
    ]], $blocks);
}

add_action('init', function () {
    $common = [
        'public'              => true,
        'show_in_rest'        => true,
        'has_archive'         => false,
        'exclude_from_search' => false,
        'supports'            => ['title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'page-attributes'],
        'template_lock'       => false,
    ];

    register_post_type('producten', array_merge($common, [
        'labels' => [
            'name'               => 'Producten',
            'singular_name'      => 'Product',
            'add_new'            => 'Nieuw product',
            'add_new_item'       => 'Nieuw product of nieuwe categorie',
            'edit_item'          => 'Product bewerken',
            'all_items'          => 'Alle producten',
            'parent_item_colon'  => 'Categorie:',
            'menu_name'          => 'Producten',
        ],
        'hierarchical' => true,
        'menu_icon'    => 'dashicons-products',
        'menu_position'=> 21,
        'rewrite'      => ['slug' => 'producten', 'with_front' => false],
        'template'     => hyd_default_block_template('producten'),
    ]));

    register_post_type('oplossingen', array_merge($common, [
        'labels' => [
            'name'          => 'Oplossingen',
            'singular_name' => 'Oplossing',
            'add_new'       => 'Nieuwe oplossing',
            'add_new_item'  => 'Nieuwe oplossing',
            'edit_item'     => 'Oplossing bewerken',
            'all_items'     => 'Alle oplossingen',
            'menu_name'     => 'Oplossingen',
        ],
        'hierarchical' => false,
        'menu_icon'    => 'dashicons-lightbulb',
        'menu_position'=> 22,
        'rewrite'      => ['slug' => 'oplossingen', 'with_front' => false],
        'template'     => hyd_default_block_template('oplossingen'),
    ]));

    register_post_type('diensten', array_merge($common, [
        'labels' => [
            'name'          => 'Diensten',
            'singular_name' => 'Dienst',
            'add_new'       => 'Nieuwe dienst',
            'add_new_item'  => 'Nieuwe dienst',
            'edit_item'     => 'Dienst bewerken',
            'all_items'     => 'Alle diensten',
            'menu_name'     => 'Diensten',
        ],
        'hierarchical' => false,
        'menu_icon'    => 'dashicons-admin-tools',
        'menu_position'=> 23,
        'rewrite'      => ['slug' => 'diensten', 'with_front' => false],
        'template'     => hyd_default_block_template('diensten'),
    ]));
});

// Engelse URL-bases (hydynamic.com): /products/, /solutions/, /services/
add_action('init', function () {
    foreach (hyd_cpt_bases() as $type => $bases) {
        foreach ($bases as $lang => $base) {
            if ($base === $bases['nl']) continue;
            $hier = is_post_type_hierarchical($type);
            add_rewrite_rule('^' . $base . '/' . ($hier ? '(.+?)' : '([^/]+)') . '/?$', 'index.php?' . $type . '=$matches[1]', 'top');
        }
    }
}, 20);

// Rewrite-regels verversen na het wijzigen van dit bestand (eenmalig per versie)
add_action('init', function () {
    $version = '2026-11-04.2';
    if (get_option('hyd_cpt_version') !== $version) {
        flush_rewrite_rules(false);
        update_option('hyd_cpt_version', $version);
    }
}, 99);

// Nieuwe pagina's starten met paginakop, kennisbank en offerteformulier
add_filter('register_post_type_args', function ($args, $type) {
    if ($type === 'page' && function_exists('hyd_flatten_block_data')) {
        $args['template'] = [
            ['mk/paginakop', ['name' => 'mk/paginakop', 'data' => hyd_flatten_block_data('paginakop', ['variant' => 'tekst']), 'mode' => 'preview']],
            ['mk/kennisbank', ['name' => 'mk/kennisbank', 'data' => hyd_flatten_block_data('kennisbank', ['titel' => 'Kennisbank', 'aantal' => '4']), 'mode' => 'preview']],
            ['mk/offerte', ['name' => 'mk/offerte', 'data' => [], 'mode' => 'preview']],
        ];
    }
    return $args;
}, 10, 2);

/**
 * 301-redirects van de oude URL's (live site) naar de nieuwe structuur.
 * Alleen actief als de oude URL een 404 zou geven.
 */
add_action('template_redirect', function () {
    if (!is_404()) return;
    $path = trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
    $maps = [
        'nl' => [
            'producten/hydrauliek'                  => '/producten/hydrauliekslangen-koppelingen/',
            'producten/onderdelen-centrale-smering' => '/producten/centrale-smering/',
            'producten/klantspecifiek'              => '/diensten/maatwerk-productie/',
            'vacature'                              => '/over-ons/',
            'aanvraag-verstuurd'                    => '/offerte-aanvragen/',
            'bedankt'                               => '/offerte-aanvragen/',
        ],
        // Oude URL's van de huidige Engelse site op hydynamic.com
        'en' => [
            'home'               => '/',
            'company-profile'    => '/about-us/',
            'products-overview'  => '/products/',
            'resources'          => '/knowledge-base/',
            'vacancies'          => '/about-us/',
            'thank-you'          => '/request-a-quote/',
            'request-sent'       => '/request-a-quote/',
            'thank-you-application-form' => '/about-us/',
            'danfoss-fluid-conveyance'   => '/danfoss-fluid-conveyance-partner/',
        ],
    ];
    $map = $maps[function_exists('hyd_lang') ? hyd_lang() : 'nl'] ?? [];
    // Oude informatiepagina's onder /producten/onderdelen-centrale-smering/... (live site)
    if (strpos($path, 'producten/onderdelen-centrale-smering/') === 0) {
        $map[$path] = '/producten/centrale-smering/';
    }
    if (strpos($path, 'producten/central-lubrication') === 0) {
        $map[$path] = '/products/central-lubrication/';
    }
    if (isset($map[$path])) {
        wp_safe_redirect(home_url($map[$path]), 301);
        exit;
    }
}, 1); // vóór WordPress' eigen URL-gok (redirect_canonical)
