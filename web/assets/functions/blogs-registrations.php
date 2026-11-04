<?php
/**
 * Blokken worden automatisch geregistreerd door mkbase vanuit
 * /template-parts/blocks/{blok}/block.json. De velden staan in block-fields.php.
 *
 * Hier: editor-instellingen voor de Hydynamic-blokken.
 */

if (!defined('ABSPATH')) exit;

// Paginakop/hero altijd bovenaan: in de editor zichtbaar als eerste blok
add_filter('block_editor_settings_all', function ($settings, $context) {
    if (!empty($context->post) && in_array($context->post->post_type, ['page', 'producten', 'oplossingen', 'diensten'], true)) {
        $settings['bodyPlaceholder'] = __('Kies een blok uit de categorie "Hydynamic"', 'mk');
    }
    return $settings;
}, 10, 2);

// Eigen blokcategorie, vóór de core-categorie "Mediakanjers"
add_filter('block_categories_all', function ($categories) {
    array_unshift($categories, ['slug' => 'hydynamic', 'title' => 'Hydynamic', 'icon' => 'star-filled']);
    return $categories;
}, 5);
