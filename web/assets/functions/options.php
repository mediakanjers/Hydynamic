<?php
/**
 * Extra velden op Mediakanjers > Website instellingen (naast de core-velden
 * voor logo, adres, telefoon, e-mail en socials).
 */

if (!defined('ABSPATH')) exit;

add_action('acf/include_fields', function () {
    if (!function_exists('acf_add_local_field_group')) return;

    acf_add_local_field_group([
        'key'    => 'group_hyd_opties',
        'title'  => 'Hydynamic – offerte en contact',
        'fields' => [
            [
                'key'          => 'field_hyd_opt_offerte_formulier',
                'label'        => 'Standaard offerteformulier',
                'name'         => 'offerte_formulier',
                'type'         => 'select',
                'choices'      => [],
                'allow_null'   => 1,
                'instructions' => 'Gravity Forms-formulier dat in het offerteblok onderaan de pagina\'s staat.',
                'wrapper'      => ['width' => 50],
            ],
            [
                'key'           => 'field_hyd_opt_offerte_fallback',
                'label'         => 'Pagina met formulier (terugval)',
                'name'          => 'offerte_fallback',
                'type'          => 'post_object',
                'post_type'     => ['page'],
                'return_format' => 'id',
                'allow_null'    => 1,
                'instructions'  => 'Staat er geen offerteformulier op de pagina, dan gaan "Offerte aanvragen"-knoppen naar het formulier op deze pagina.',
                'wrapper'       => ['width' => 50],
            ],
            [
                'key'           => 'field_hyd_opt_contact_titel',
                'label'         => 'Titel contactkaart',
                'name'          => 'contactkaart_titel',
                'type'          => 'text',
                'default_value' => 'Liever direct contact?',
                'wrapper'       => ['width' => 50],
            ],
            [
                'key'           => 'field_hyd_opt_contact_accent',
                'label'         => 'Accentwoord contactkaart',
                'name'          => 'contactkaart_accent',
                'type'          => 'text',
                'default_value' => 'contact?',
                'wrapper'       => ['width' => 50],
            ],
        ],
        'location' => [[[
            'param'    => 'options_page',
            'operator' => '==',
            'value'    => 'acf-options-website-instellingen',
        ]]],
        'menu_order' => 5,
        'active'     => true,
    ]);
});
