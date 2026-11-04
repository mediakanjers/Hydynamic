<?php
/**
 * Hydynamic – thema-instellingen
 */

if (!defined('ABSPATH')) exit;

add_action('after_setup_theme', function () {
    add_theme_support('html5', ['search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets']);
    add_theme_support('responsive-embeds');
    remove_theme_support('core-block-patterns');

    // Afbeeldingsformaten voor tegels, secties en hero's
    add_image_size('hyd-tile', 760, 540, true);   // klikbare tegels (2x)
    add_image_size('hyd-card', 740, 600, true);   // kennisbank-artikeltegels (2x)
    add_image_size('hyd-section', 1600, 1040, false);
    add_image_size('hyd-hero', 2400, 1400, false);
});

add_filter('image_size_names_choose', function ($sizes) {
    return array_merge($sizes, [
        'hyd-tile'    => 'Tegel',
        'hyd-section' => 'Sectie',
        'hyd-hero'    => 'Hero',
    ]);
});

// Hogere JPEG/WebP-kwaliteit voor productfoto's
add_filter('wp_editor_set_quality', fn() => 82);

// Kennisbank-artikelen: "Kennisbank" als naam van Berichten in het menu
add_action('admin_menu', function () {
    global $menu, $submenu;
    foreach ($menu as $k => $item) {
        if (($item[2] ?? '') === 'edit.php') $menu[$k][0] = 'Kennisbank';
    }
    if (isset($submenu['edit.php'][5])) $submenu['edit.php'][5][0] = 'Alle artikelen';
});

// Vendor-CSS die de core altijd laadt maar die dit thema niet gebruikt
add_action('wp_enqueue_scripts', function () {
    foreach (['fancybox-css', 'owl-carousel-css', 'swiper-css'] as $handle) {
        wp_dequeue_style($handle);
    }
    // Lege child style.css (alleen de thema-header) hoeft niet geladen te worden
    wp_dequeue_style('mk-child-style');
    wp_dequeue_style('mk-core-style');
    // Standaard blokbibliotheek-CSS: we gebruiken alleen eigen blokken
    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('wp-block-library-theme');
    wp_dequeue_style('classic-theme-styles');
}, 100);

// Font vooraf laden (voorkomt verspringende tekst)
add_action('wp_head', function () {
    echo '<link rel="preload" href="' . esc_url(get_stylesheet_directory_uri() . '/assets/fonts/manrope-latin.woff2') . '" as="font" type="font/woff2" crossorigin>' . "\n";
}, 1);

// Emoji-scripts uit: scheelt een request op elke pagina
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');

// Body-klasse voor pagina's met overlay-header (homepage)
add_filter('body_class', function ($classes) {
    if (is_front_page()) $classes[] = 'has-overlay-header';
    return $classes;
});

/**
 * Zoeken: alleen relevante content doorzoeken.
 */
add_action('pre_get_posts', function ($q) {
    if (!is_admin() && $q->is_main_query() && $q->is_search()) {
        $q->set('post_type', ['page', 'post', 'producten', 'oplossingen', 'diensten']);
        $q->set('posts_per_page', 12);
    }
});

/**
 * Korte omschrijving voor zoekresultaten: Yoast-metabeschrijving, samenvatting,
 * of de intro-/teksttekst uit de eerste blokken.
 */
function hyd_search_excerpt($post_id) {
    $desc = get_post_meta($post_id, '_yoast_wpseo_metadesc', true);
    if ($desc) return $desc;
    $excerpt = get_post_field('post_excerpt', $post_id);
    if ($excerpt) return $excerpt;

    foreach (parse_blocks(get_post_field('post_content', $post_id)) as $block) {
        $data = $block['attrs']['data'] ?? [];
        foreach (['intro', 'tekst'] as $key) {
            if (!empty($data[$key]) && is_string($data[$key])) {
                return wp_strip_all_tags($data[$key]);
            }
        }
    }
    return wp_strip_all_tags(get_the_excerpt($post_id));
}

/**
 * Live-zoeken: REST-endpoint /wp-json/hyd/v1/search?q=...
 * Geeft maximaal 8 resultaten met type, titel, url en korte tekst.
 */
add_action('rest_api_init', function () {
    register_rest_route('hyd/v1', '/search', [
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'args'                => ['q' => ['required' => true, 'sanitize_callback' => 'sanitize_text_field']],
        'callback'            => function (WP_REST_Request $request) {
            $term = trim($request->get_param('q'));
            if (mb_strlen($term) < 2) return [];

            $labels = ['page' => 'Pagina', 'post' => 'Kennisbank', 'producten' => 'Product', 'oplossingen' => 'Oplossing', 'diensten' => 'Dienst'];
            $query  = new WP_Query([
                's'                   => $term,
                'post_type'           => array_keys($labels),
                'post_status'         => 'publish',
                'posts_per_page'      => 8,
                'no_found_rows'       => true,
                'ignore_sticky_posts' => true,
            ]);

            $results = [];
            foreach ($query->posts as $p) {
                $results[] = [
                    'title' => html_entity_decode(get_the_title($p), ENT_QUOTES, 'UTF-8'),
                    'url'   => get_permalink($p),
                    'type'  => $labels[$p->post_type] ?? '',
                    'text'  => wp_trim_words(hyd_search_excerpt($p->ID), 14),
                ];
            }
            return $results;
        },
    ]);
});

/**
 * Gravity Forms: knop als <button> met pijl-icoon, zodat hij gelijk is aan het ontwerp.
 */
add_filter('gform_submit_button', function ($button, $form) {
    $label = $form['button']['text'] ?? __('Aanvraag versturen', 'mk');
    return '<button type="submit" class="mk-button mk-button--primary gform_button" id="gform_submit_button_' . (int) $form['id'] . '"><span>' . esc_html($label) . '</span>' . hyd_icon('arrow-right', 20) . '</button>';
}, 10, 2);

// Gravity Forms scripts in de footer
add_filter('gform_init_scripts_footer', '__return_true');
