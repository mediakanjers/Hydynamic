<?php
/**
 * Hoofdmenu-walker
 *
 * Voegt bij menu-items met een submenu een losse toggle-knop toe (aria-expanded),
 * zodat dropdowns ook met toetsenbord en touch werken. De link zelf blijft een
 * gewone, crawlbare link. Ondersteunt meerdere niveaus (submenu + sub-submenu).
 */

if (!defined('ABSPATH')) exit;

class Hyd_Nav_Walker extends Walker_Nav_Menu {

    public function start_lvl(&$output, $depth = 0, $args = null) {
        $output .= '<ul class="mk-nav__sub mk-nav__sub--level-' . ($depth + 1) . '">';
    }

    public function end_lvl(&$output, $depth = 0, $args = null) {
        $output .= '</ul>';
    }

    public function start_el(&$output, $data_object, $depth = 0, $args = null, $current_object_id = 0) {
        $item         = $data_object;
        $classes      = empty($item->classes) ? [] : (array) $item->classes;
        $has_children = in_array('menu-item-has-children', $classes, true);
        $is_current   = array_intersect($classes, ['current-menu-item', 'current_page_item']);
        $is_ancestor  = array_intersect($classes, ['current-menu-ancestor', 'current-menu-parent', 'current_page_ancestor']);

        $li_classes = ['mk-nav__item', 'mk-nav__item--level-' . $depth];
        if ($has_children) $li_classes[] = 'has-children';
        if ($is_current)   $li_classes[] = 'is-current';
        if ($is_ancestor)  $li_classes[] = 'is-ancestor';
        foreach ($classes as $c) {
            if (strpos($c, 'menu-') !== 0 && strpos($c, 'current') !== 0 && $c !== '') {
                $li_classes[] = sanitize_html_class($c); // eigen CSS-klassen uit het menu-scherm
            }
        }

        $output .= '<li class="' . esc_attr(implode(' ', $li_classes)) . '">';

        $atts = [
            'href'   => !empty($item->url) ? $item->url : '',
            'class'  => 'mk-nav__link',
            'target' => !empty($item->target) ? $item->target : '',
            'rel'    => !empty($item->xfn) ? $item->xfn : '',
        ];
        if ($is_current) $atts['aria-current'] = 'page';
        if ($atts['target'] === '_blank' && empty($atts['rel'])) $atts['rel'] = 'noopener';

        $attr_html = '';
        foreach ($atts as $name => $value) {
            if ($value === '') continue;
            $value      = $name === 'href' ? esc_url($value) : esc_attr($value);
            $attr_html .= ' ' . $name . '="' . $value . '"';
        }

        $title = apply_filters('the_title', $item->title, $item->ID);
        $title = apply_filters('nav_menu_item_title', $title, $item, $args, $depth);

        $output .= '<div class="mk-nav__row">';
        $output .= '<a' . $attr_html . '>' . esc_html($title) . '</a>';

        if ($has_children) {
            $output .= '<button type="button" class="mk-nav__toggle" aria-expanded="false"'
                     . ' aria-label="' . esc_attr(sprintf(__('Submenu %s openen', 'mk'), wp_strip_all_tags($title))) . '">'
                     . hyd_icon($depth === 0 ? 'chevron-down' : 'chevron-right', 16)
                     . '</button>';
        }
        $output .= '</div>';
    }

    public function end_el(&$output, $data_object, $depth = 0, $args = null) {
        $output .= '</li>';
    }
}
