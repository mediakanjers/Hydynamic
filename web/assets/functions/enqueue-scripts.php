<?php
/**
 * De core (mkbase) laadt dist/css/style-main.css en dist/scripts/scripts.min.js.
 * Hier alleen de extra configuratie voor het child thema.
 */

if (!defined('ABSPATH')) exit;

add_action('wp_enqueue_scripts', function () {
    wp_localize_script('mk-main-script', 'hydConfig', [
        'searchUrl' => esc_url_raw(rest_url('hyd/v1/search')),
        'homeUrl'   => esc_url_raw(home_url('/')),
        'i18n'      => [
            'noResults'  => __('Geen directe resultaten. Druk op Enter om alle resultaten te bekijken.', 'mk'),
            'allResults' => __('Bekijk alle resultaten', 'mk'),
            'results'    => __('%d suggesties beschikbaar', 'mk'),
            'menuOpen'   => __('Menu openen', 'mk'),
            'menuClose'  => __('Menu sluiten', 'mk'),
            'close'      => __('Sluiten', 'mk'),
            'prev'       => __('Vorige foto', 'mk'),
            'next'       => __('Volgende foto', 'mk'),
            'of'         => __('%1$d van %2$d', 'mk'),
        ],
    ]);
}, 20);

// Scripts uitstellen: ons eigen script heeft geen afhankelijkheden
add_filter('script_loader_tag', function ($tag, $handle) {
    if ($handle === 'mk-main-script' && strpos($tag, ' defer') === false) {
        $tag = str_replace(' src=', ' defer src=', $tag);
    }
    return $tag;
}, 10, 2);
