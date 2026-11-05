<?php
/**
 * Hydynamic – helperfuncties voor templates en blokken.
 * Alle functies hebben de prefix hyd_ om botsingen met mkbase te voorkomen.
 */

if (!defined('ABSPATH')) exit;

/**
 * Inline SVG-iconen (lijniconen, lijndikte 2,2 volgens de stijlgids).
 * Altijd aria-hidden: de betekenis staat in de tekst of het aria-label van de knop.
 */
function hyd_icon($name, $size = 20, $class = '') {
    $s = 'fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"';
    $paths = [
        'arrow-right'   => '<path d="M4 12h15M13 6l6 6-6 6"/>',
        'arrow-left'    => '<path d="M20 12H5M11 6l-6 6 6 6"/>',
        'arrow-down'    => '<path d="M12 4v15M6 13l6 6 6-6"/>',
        'chevron-down'  => '<path d="M6 9l6 6 6-6"/>',
        'chevron-up'    => '<path d="M6 15l6-6 6 6"/>',
        'chevron-right' => '<path d="M9 6l6 6-6 6"/>',
        'chevron-left'  => '<path d="M15 6l-6 6 6 6"/>',
        'check'         => '<path d="M5 12l5 5 9-10"/>',
        'check-box'     => '<rect x="3" y="3" width="18" height="18" rx="1"/><path d="M7 12l4 4 8-9"/>',
        'search'        => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/>',
        'close'         => '<path d="M6 6l12 12M18 6L6 18"/>',
        'menu'          => '<path d="M3 6h18M3 12h18M3 18h18"/>',
        'download'      => '<path d="M12 4v11M7 10l5 5 5-5M5 20h14"/>',
        'map-pin'       => '<path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
        'clock'         => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'info'          => '<circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7.5v.5"/>',
        'box'           => '<path d="M3 7.5 12 3l9 4.5v9L12 21l-9-4.5z"/><path d="M3 7.5 12 12l9-4.5M12 12v9"/>',
        'file'          => '<path d="M14 3H6a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V8z"/><path d="M14 3v5h5M9 13h6M9 17h6"/>',
        'plus'          => '<path d="M12 5v14M5 12h14"/>',
        'minus'         => '<path d="M5 12h14"/>',
        'globe'         => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>',
    ];
    $filled = [
        'phone'          => '<path d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1A17 17 0 0 1 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1z"/>',
        'mail'           => '<path d="M3 5h18a1 1 0 0 1 1 1v.4l-10 6.2L2 6.4V6a1 1 0 0 1 1-1zm-1 3.7 10 6.2 10-6.2V18a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1z"/>',
        'triangle-left'  => '<path d="M24 0v54L0 27z"/>',
        'triangle-right' => '<path d="M0 0v54l24-27z"/>',
    ];

    $cls = trim('mk-icon mk-icon--' . $name . ' ' . $class);
    if (isset($filled[$name])) {
        $vb = strpos($name, 'triangle') === 0 ? '0 0 24 54' : '0 0 24 24';
        $h  = strpos($name, 'triangle') === 0 ? round($size * 54 / 24) : $size;
        return '<svg class="' . esc_attr($cls) . '" width="' . (int) $size . '" height="' . (int) $h . '" viewBox="' . $vb . '" fill="currentColor" aria-hidden="true" focusable="false">' . $filled[$name] . '</svg>';
    }
    if (!isset($paths[$name])) return '';
    return '<svg class="' . esc_attr($cls) . '" width="' . (int) $size . '" height="' . (int) $size . '" viewBox="0 0 24 24" ' . $s . ' aria-hidden="true" focusable="false">' . $paths[$name] . '</svg>';
}

/** URL van het logo: ACF-optie "logo", anders het meegeleverde SVG-logo. */
function hyd_logo_url() {
    $logo = function_exists('get_field') ? get_field('logo', 'option') : null;
    if (!empty($logo['url'])) return $logo['url'];
    return get_stylesheet_directory_uri() . '/assets/images/brand/logo-hydynamic-wit.svg';
}

function hyd_brand_asset($file) {
    return get_stylesheet_directory_uri() . '/assets/images/brand/' . ltrim($file, '/');
}

/**
 * Kop met accentwoord: het opgegeven accentdeel krijgt <span class="accent">.
 * Escaped zowel titel als accent.
 */
function hyd_title_html($title, $accent = '') {
    $title  = (string) $title;
    $accent = trim((string) $accent);
    if ($accent === '' || ($pos = mb_strrpos($title, $accent)) === false) {
        return esc_html($title);
    }
    return esc_html(mb_substr($title, 0, $pos))
         . '<span class="accent">' . esc_html($accent) . '</span>'
         . esc_html(mb_substr($title, $pos + mb_strlen($accent)));
}

/** Toegestane kopniveaus voor blokvelden. */
function hyd_heading_tag($tag, $default = 'h2') {
    return in_array($tag, ['h1', 'h2', 'h3', 'h4', 'p'], true) ? $tag : $default;
}

/**
 * Link naar het offerteformulier. Op pagina's met het offerteblok scrollt JS naar
 * #offerte; staat het blok niet op de pagina, dan valt de link terug op de contactpagina.
 */
function hyd_offerte_url() {
    return '#offerte';
}

function hyd_offerte_fallback_url() {
    $page = function_exists('hyd_opt') ? hyd_opt('offerte_fallback') : null;
    $url  = $page ? get_permalink($page) : home_url('/contact/');
    return trailingslashit($url) . '#offerte';
}

/**
 * Knop-HTML. $type: primary | outline | outline-white | white | link
 */
function hyd_button($label, $url, $args = []) {
    $args = wp_parse_args($args, [
        'type'    => 'primary',
        'size'    => '',
        'icon'    => 'arrow-right',
        'target'  => '',
        'class'   => '',
        'offerte' => false,
    ]);
    if ($label === '' || $url === '') return '';

    $cls = ['mk-button', 'mk-button--' . $args['type']];
    if ($args['size'])    $cls[] = 'mk-button--' . $args['size'];
    if ($args['offerte']) $cls[] = 'js-offerte';
    if ($args['class'])   $cls[] = $args['class'];

    $target = $args['target'] === '_blank' ? ' target="_blank" rel="noopener"' : '';
    $data   = $args['offerte'] ? ' data-fallback="' . esc_url(hyd_offerte_fallback_url()) . '"' : '';
    $icon   = $args['icon'] ? hyd_icon($args['icon'], 18) : '';

    return '<a class="' . esc_attr(implode(' ', $cls)) . '" href="' . esc_url($url) . '"' . $target . $data . '><span>' . esc_html($label) . '</span>' . $icon . '</a>';
}

/**
 * Knop op basis van de gedeelde ACF-knopvelden (knop_type / knop_link / knop_tekst).
 * knop_type: geen | offerte | link
 */
function hyd_block_button($prefix = 'knop', $args = []) {
    $type = get_field($prefix . '_type') ?: 'geen';
    if ($type === 'offerte') {
        $label = get_field($prefix . '_tekst') ?: __('Offerte aanvragen', 'mk');
        return hyd_button($label, hyd_offerte_url(), array_merge($args, ['offerte' => true]));
    }
    if ($type === 'link') {
        $link = get_field($prefix . '_link');
        if (!empty($link['url'])) {
            return hyd_button($link['title'] ?: __('Lees meer', 'mk'), $link['url'], array_merge($args, ['target' => $link['target'] ?? '']));
        }
    }
    return '';
}

/**
 * Responsieve afbeelding met srcset/sizes, lazy-loading en een alt-tekst die
 * terugvalt op de titel van de afbeelding.
 */
function hyd_image($image, $size = 'large', $attr = []) {
    $id = is_array($image) ? ($image['ID'] ?? $image['id'] ?? 0) : (int) $image;
    if (!$id) return '';
    $attr = wp_parse_args($attr, ['loading' => 'lazy', 'decoding' => 'async']);
    if (!empty($attr['loading']) && $attr['loading'] === 'eager') {
        $attr['fetchpriority'] = 'high';
    }
    if (!isset($attr['alt'])) {
        // Engelse alt-tekst op hydynamic.com (afbeeldingen worden door beide talen gedeeld)
        $alt = function_exists('hyd_is_en') && hyd_is_en() ? get_post_meta($id, '_hyd_alt_en', true) : '';
        $alt = $alt !== '' ? $alt : get_post_meta($id, '_wp_attachment_image_alt', true);
        $attr['alt'] = $alt !== '' ? $alt : get_the_title($id);
    }
    return wp_get_attachment_image($id, $size, false, $attr);
}

/**
 * Gedeelde sectie-wrapper voor alle blokken: achtergrond + ruimte.
 * achtergrond: wit | grijs | licht | verloop | contact | donker | blauw | navy
 */
function hyd_section_attrs($block, $base_class, $default_bg = 'wit', $extra = []) {
    $bg      = get_field('achtergrond') ?: $default_bg;
    $spacing = get_field('ruimte') ?: 'normaal';

    $classes = array_merge(['mk-section', $base_class, 'mk-bg--' . sanitize_html_class($bg), 'mk-space--' . sanitize_html_class($spacing)], $extra);
    if (in_array($bg, ['donker', 'blauw', 'navy'], true)) $classes[] = 'is-dark';
    if (!empty($block['className'])) $classes[] = $block['className'];

    $id = !empty($block['anchor']) ? ' id="' . esc_attr($block['anchor']) . '"' : '';
    return 'class="' . esc_attr(implode(' ', $classes)) . '"' . $id;
}

/**
 * Kruimelpad voor alle paginatypes. Hiërarchische post types (producten) tonen
 * de bovenliggende items; voor CPT's wordt de overzichtspagina met dezelfde slug
 * als tussenstap getoond. Yoast levert het BreadcrumbList-schema.
 */
function hyd_breadcrumbs($is_preview = false) {
    if (is_front_page() && !$is_preview) return '';

    $post  = get_post();
    $items = [['label' => __('Homepage', 'mk'), 'url' => home_url('/')]];

    if (is_singular() || ($is_preview && $post)) {
        $type = get_post_type($post);
        if ($type && !in_array($type, ['page', 'post'], true)) {
            $obj = get_post_type_object($type);
            $slug = $obj && !empty($obj->rewrite['slug']) ? $obj->rewrite['slug'] : $type;
            $bases    = function_exists('hyd_cpt_bases') ? (hyd_cpt_bases()[$type] ?? []) : [];
            $slug     = $bases[function_exists('hyd_lang') ? hyd_lang() : 'nl'] ?? $slug;
            $overview = function_exists('hyd_find_by_path') ? get_post(hyd_find_by_path($slug, 'page', hyd_lang())) : get_page_by_path($slug);
            if ($overview) {
                $items[] = ['label' => get_the_title($overview), 'url' => get_permalink($overview)];
            }
        }
        if ($type === 'post') {
            $kb = (int) get_option('page_for_posts');
            if ($kb) $items[] = ['label' => get_the_title($kb), 'url' => get_permalink($kb)];
        }
        foreach (array_reverse(get_post_ancestors($post)) as $ancestor) {
            $items[] = ['label' => get_the_title($ancestor), 'url' => get_permalink($ancestor)];
        }
        $items[] = ['label' => get_the_title($post), 'url' => ''];
    } elseif (is_home()) {
        $items[] = ['label' => get_the_title((int) get_option('page_for_posts')) ?: __('Kennisbank', 'mk'), 'url' => ''];
    } elseif (is_search()) {
        $items[] = ['label' => __('Zoeken', 'mk'), 'url' => ''];
    } elseif (is_archive()) {
        $items[] = ['label' => wp_strip_all_tags(get_the_archive_title()), 'url' => ''];
    } elseif (is_404()) {
        $items[] = ['label' => __('Pagina niet gevonden', 'mk'), 'url' => ''];
    }

    $html = '<nav class="mk-crumbs" aria-label="' . esc_attr__('Kruimelpad', 'mk') . '"><ol class="mk-crumbs__list">';
    $last = count($items) - 1;
    foreach ($items as $i => $item) {
        $html .= '<li class="mk-crumbs__item">';
        if ($i === $last || $item['url'] === '' || $is_preview) {
            $html .= $i === $last ? '<strong aria-current="page">' . esc_html($item['label']) . '</strong>' : '<span>' . esc_html($item['label']) . '</span>';
        } else {
            $html .= '<a href="' . esc_url($item['url']) . '">' . esc_html($item['label']) . '</a>';
        }
        $html .= '</li>';
    }
    return $html . '</ol></nav>';
}

/** Bedrijfsgegevens uit Mediakanjers > Website instellingen, met standaardwaarden. */
function hyd_company($key) {
    $defaults = [
        'bedrijfsnaam' => 'Hydynamic Holland BV',
        'adres'        => 'Marssteden 28',
        'postcode'     => '7547 TC Enschede (NL)',
        'telefoon'     => '+31(0)53-4282048',
        'email'        => 'info@hydynamic.nl',
    ];
    $value = function_exists('get_field') ? get_field($key, 'option') : '';
    return $value ?: ($defaults[$key] ?? '');
}

function hyd_tel_href($number) {
    $clean = preg_replace('/[^0-9+]/', '', str_replace('(0)', '', (string) $number));
    return 'tel:' . $clean;
}

/** Is Hydynamic nu geopend? (ma–vr 8:00–17:00, Europe/Amsterdam) */
function hyd_is_open_now() {
    $now  = new DateTime('now', new DateTimeZone('Europe/Amsterdam'));
    $day  = (int) $now->format('N');
    $mins = (int) $now->format('G') * 60 + (int) $now->format('i');
    return $day <= 5 && $mins >= 480 && $mins < 1020;
}

/**
 * Afbeelding in een lightbox-link (wordt door script.js als lightbox geopend).
 * $group: foto's met dezelfde groep zijn met de pijlen te doorlopen.
 */
function hyd_lightbox($image_html, $id, $group = '') {
    $id = (int) $id;
    if (!$id || !$image_html) return $image_html;
    $full = wp_get_attachment_image_src($id, 'hyd-hero') ?: wp_get_attachment_image_src($id, 'full');
    if (!$full) return $image_html;
    $alt = function_exists('hyd_is_en') && hyd_is_en() ? get_post_meta($id, '_hyd_alt_en', true) : '';
    $alt = $alt ?: get_post_meta($id, '_wp_attachment_image_alt', true);
    return '<a class="mk-lightbox-link" href="' . esc_url($full[0]) . '" data-lightbox="' . esc_attr($group ?: 'foto-' . $id) . '"'
         . ' data-caption="' . esc_attr($alt) . '" aria-label="' . esc_attr(sprintf(__('Foto vergroten: %s', 'mk'), $alt ?: get_the_title($id))) . '">'
         . $image_html . '</a>';
}
