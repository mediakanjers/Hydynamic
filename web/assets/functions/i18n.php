<?php
/**
 * Hydynamic – meertaligheid zonder plugin.
 *
 * Eén WordPress-installatie, twee domeinen:
 *   hydynamic.nl  → Nederlands (standaard)
 *   hydynamic.com → Engels
 *
 * - Elk bericht/pagina/product heeft een taal (meta _hyd_lang, leeg = nl) en een
 *   vertaalgroep (meta _hyd_group = ID van het Nederlandse origineel).
 * - Op elk domein worden alleen items in die taal getoond en gevonden.
 * - Post type-bases per taal: producten/products, oplossingen/solutions, diensten/services.
 * - Per taal: eigen voorpagina, kennisbankpagina, menu's, offerteformulier en locale.
 * - hreflang-tags en een taalkeuze die naar de vertaalde pagina linkt.
 *
 * Lokaal (bijv. hydynamic.test) schakel je met ?hyd_lang=en / ?hyd_lang=nl;
 * de keuze wordt in een cookie onthouden.
 */

if (!defined('ABSPATH')) exit;

/* =========================================================================
 * Configuratie
 * ====================================================================== */

function hyd_languages() {
    return apply_filters('hyd_languages', [
        'nl' => ['host' => 'www.hydynamic.nl',  'match' => 'hydynamic.nl',  'locale' => 'nl_NL', 'hreflang' => 'nl', 'label' => 'Nederlands', 'flag' => 'nl'],
        'en' => ['host' => 'www.hydynamic.com', 'match' => 'hydynamic.com', 'locale' => 'en_US', 'hreflang' => 'en', 'label' => 'English',    'flag' => 'en'],
    ]);
}

/** Post type-bases per taal (URL's). */
function hyd_cpt_bases() {
    return [
        'producten'   => ['nl' => 'producten',   'en' => 'products'],
        'oplossingen' => ['nl' => 'oplossingen', 'en' => 'solutions'],
        'diensten'    => ['nl' => 'diensten',    'en' => 'services'],
    ];
}

function hyd_translatable_types() {
    return ['page', 'post', 'producten', 'oplossingen', 'diensten'];
}

/* =========================================================================
 * Huidige taal
 * ====================================================================== */

function hyd_host() {
    return strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
}

/** Staat deze request op een van de productiedomeinen? */
function hyd_is_production_host() {
    foreach (hyd_languages() as $cfg) {
        if (strpos(hyd_host(), $cfg['match']) !== false) return true;
    }
    return false;
}

function hyd_lang() {
    static $lang = null;
    if ($lang !== null) return $lang;

    if (defined('HYD_FORCE_LANG')) return $lang = HYD_FORCE_LANG;

    foreach (hyd_languages() as $code => $cfg) {
        if (strpos(hyd_host(), $cfg['match']) !== false) return $lang = $code;
    }

    // Lokaal/staging: ?hyd_lang=en of cookie
    $lang = 'nl';
    $req  = isset($_GET['hyd_lang']) ? sanitize_key($_GET['hyd_lang']) : '';
    if ($req && isset(hyd_languages()[$req])) {
        $lang = $req;
        if (!headers_sent()) setcookie('hyd_lang', $req, 0, '/');
    } elseif (!empty($_COOKIE['hyd_lang']) && isset(hyd_languages()[$_COOKIE['hyd_lang']])) {
        $lang = sanitize_key($_COOKIE['hyd_lang']);
    }
    return $lang;
}

function hyd_is_en() {
    return hyd_lang() === 'en';
}

/** Taal van een bericht (leeg = nl). */
function hyd_post_lang($post) {
    $id = is_object($post) ? $post->ID : (int) $post;
    $l  = $id ? get_post_meta($id, '_hyd_lang', true) : '';
    return $l ?: 'nl';
}

/** Vertaling van een bericht in een andere taal (ID of 0). */
function hyd_translation($post, $lang) {
    $id = is_object($post) ? $post->ID : (int) $post;
    if (!$id) return 0;
    if (hyd_post_lang($id) === $lang) return $id;
    $group = (int) get_post_meta($id, '_hyd_group', true) ?: $id;

    hyd_i18n_suspend(true);
    $found = get_posts([
        'post_type'        => get_post_type($id),
        'post_status'      => 'publish',
        'numberposts'      => 1,
        'fields'           => 'ids',
        'suppress_filters' => true,
        'meta_query'       => $lang === 'nl'
            ? ['relation' => 'AND', ['relation' => 'OR', ['key' => '_hyd_lang', 'compare' => 'NOT EXISTS'], ['key' => '_hyd_lang', 'value' => 'nl']], ['relation' => 'OR', ['key' => '_hyd_group', 'value' => $group], ['key' => '_hyd_group', 'compare' => 'NOT EXISTS']]]
            : [['key' => '_hyd_lang', 'value' => $lang], ['key' => '_hyd_group', 'value' => $group]],
    ]);
    hyd_i18n_suspend(false);

    // Voor nl: het origineel is de groep zelf
    if ($lang === 'nl') return hyd_post_lang($group) === 'nl' && get_post_status($group) === 'publish' ? $group : 0;
    return $found ? (int) $found[0] : 0;
}

/** Taalfilter tijdelijk uitzetten (voor interne opzoekingen). */
function hyd_i18n_suspend($on = null) {
    static $suspended = false;
    if ($on !== null) $suspended = $on;
    return $suspended || defined('HYD_I18N_NO_FILTER');
}

/* =========================================================================
 * Domein, locale en theme-teksten
 * ====================================================================== */

// home/siteurl per productiedomeinen (lokaal blijft de ingestelde URL)
foreach (['option_home', 'option_siteurl'] as $hyd_opt) {
    add_filter($hyd_opt, function ($url) {
        if (!hyd_is_production_host()) return $url;
        $host = hyd_languages()[hyd_lang()]['host'];
        return preg_replace('#^(https?://)[^/]+#', '$1' . $host, $url);
    });
}

// Engelse locale op de voorkant (datums, lang-attribuut, WordPress-teksten)
add_action('after_setup_theme', function () {
    if (!is_admin() && hyd_lang() !== 'nl' && function_exists('switch_to_locale')) {
        switch_to_locale(hyd_languages()[hyd_lang()]['locale']);
    }
}, 1);

// Themateksten (tekstdomein 'mk') vertalen
add_filter('gettext', function ($translation, $text, $domain) {
    if ($domain !== 'mk' || !hyd_is_en() || is_admin()) return $translation;
    static $dict = null;
    if ($dict === null) $dict = require __DIR__ . '/i18n-strings.php';
    return $dict[$text] ?? $translation;
}, 10, 3);
add_filter('ngettext', function ($translation, $single, $plural, $number, $domain) {
    if ($domain !== 'mk' || !hyd_is_en() || is_admin()) return $translation;
    static $dict = null;
    if ($dict === null) $dict = require __DIR__ . '/i18n-strings.php';
    $key = $number == 1 ? $single : $plural;
    return $dict[$key] ?? $translation;
}, 10, 5);

/** Optie per taal: eerst "{naam}_en", anders de gewone optie. */
function hyd_opt($name) {
    if (!function_exists('get_field')) return null;
    if (!hyd_is_en()) return get_field($name, 'option');
    $v = get_option('options_' . $name . '_' . hyd_lang());
    return ($v !== false && $v !== '') ? $v : get_field($name, 'option');
}

// Voorpagina en kennisbankpagina per taal
add_filter('option_page_on_front', function ($v) {
    return hyd_is_en() && ($en = (int) get_option('hyd_front_page_en')) ? $en : $v;
});
add_filter('option_page_for_posts', function ($v) {
    return hyd_is_en() && ($en = (int) get_option('hyd_posts_page_en')) ? $en : $v;
});

// Menu's per taal
add_filter('theme_mod_nav_menu_locations', function ($locations) {
    if (!hyd_is_en() || is_admin()) return $locations;
    $en = get_option('hyd_menu_locations_en');
    return is_array($en) ? array_merge((array) $locations, $en) : $locations;
});

/* =========================================================================
 * Queries: alleen items in de huidige taal
 * ====================================================================== */

function hyd_lang_meta_query($lang) {
    return $lang === 'nl'
        ? ['relation' => 'OR', ['key' => '_hyd_lang', 'compare' => 'NOT EXISTS'], ['key' => '_hyd_lang', 'value' => 'nl']]
        : ['key' => '_hyd_lang', 'value' => $lang];
}

add_action('pre_get_posts', function ($q) {
    if (hyd_i18n_suspend() || (is_admin() && !wp_doing_ajax())) return;
    // REST: alleen ons eigen zoek-endpoint filteren (de blokeditor moet alles zien)
    if (defined('REST_REQUEST') && REST_REQUEST && strpos($_SERVER['REQUEST_URI'] ?? '', 'hyd/v1/') === false) return;

    $types = $q->get('post_type');
    if ($q->is_main_query() && empty($types)) {
        $types = $q->is_page() || $q->get('pagename') || $q->get('page_id') ? 'page' : 'post';
    }
    $types = (array) ($types ?: 'post');
    if (in_array('any', $types, true)) $types = hyd_translatable_types();
    if (!array_intersect($types, hyd_translatable_types())) return;
    // Als er expliciet op ID gevraagd wordt, niet filteren
    if ($q->get('p') || $q->get('page_id') || $q->get('post__in')) return;

    $mq   = (array) $q->get('meta_query');
    $mq[] = hyd_lang_meta_query(hyd_lang());
    $q->set('meta_query', $mq);
}, 5);

/**
 * Paginapaden taalbewust oplossen: /contact/ op .nl en .com kunnen dezelfde slug
 * hebben. We zoeken de pagina in de huidige taal en geven WordPress het ID.
 */
add_filter('request', function ($vars) {
    if (is_admin() || hyd_i18n_suspend()) return $vars;

    $map = ['pagename' => 'page'];
    foreach (array_keys(hyd_cpt_bases()) as $type) $map[$type] = $type;

    foreach ($map as $var => $type) {
        if (empty($vars[$var])) continue;
        $id = hyd_find_by_path($vars[$var], $type, hyd_lang());
        // /%postname%/: WordPress probeert eerst een paginapad; valt terug op een bericht
        if (!$id && $type === 'page' && strpos(trim($vars[$var], '/'), '/') === false) {
            $post_id = hyd_find_by_path($vars[$var], 'post', hyd_lang());
            if ($post_id) {
                unset($vars['pagename'], $vars['name']);
                $vars['p'] = $post_id;
                return $vars;
            }
        }
        if ($id) {
            unset($vars[$var], $vars['name']);
            if ($type === 'page') {
                $vars['page_id'] = $id;
            } else {
                $vars['post_type'] = $type;
                $vars['p'] = $id;
            }
        }
        break;
    }
    // Berichten (kennisbank) op slug
    if (!empty($vars['name']) && empty($vars['post_type'])) {
        $id = hyd_find_by_path($vars['name'], 'post', hyd_lang());
        if ($id) { unset($vars['name']); $vars['p'] = $id; }
    }
    return $vars;
});

/** Zoek een (hiërarchisch) item op pad binnen een taal. */
function hyd_find_by_path($path, $type, $lang) {
    $parts = array_values(array_filter(explode('/', trim(urldecode($path), '/'))));
    if (!$parts) return 0;
    hyd_i18n_suspend(true);
    $candidates = get_posts([
        'post_type'        => $type,
        'name'             => sanitize_title(end($parts)),
        'post_status'      => ['publish', 'private'],
        'numberposts'      => 20,
        'suppress_filters' => true,
        'meta_query'       => [hyd_lang_meta_query($lang)],
    ]);
    hyd_i18n_suspend(false);
    foreach ($candidates as $c) {
        $ancestors = array_reverse(get_post_ancestors($c));
        $slugs = array_map(fn($a) => get_post_field('post_name', $a), $ancestors);
        $slugs[] = $c->post_name;
        if ($slugs === array_map('sanitize_title', $parts) || count($parts) === 1) return (int) $c->ID;
    }
    return 0;
}

// Dezelfde slug mag in twee talen bestaan (bijv. /contact/ op .nl en .com)
add_filter('wp_unique_post_slug', function ($slug, $post_id, $status, $type, $parent, $original) {
    if ($slug === $original || !in_array($type, hyd_translatable_types(), true)) return $slug;
    $lang = $GLOBALS['hyd_saving_lang'] ?? ($post_id ? hyd_post_lang($post_id) : hyd_lang());
    hyd_i18n_suspend(true);
    $clash = get_posts([
        'post_type'        => $type,
        'name'             => $original,
        'post_parent'      => $parent,
        'post__not_in'     => [$post_id ?: 0],
        'post_status'      => 'any',
        'numberposts'      => 1,
        'fields'           => 'ids',
        'suppress_filters' => true,
        'meta_query'       => [hyd_lang_meta_query($lang)],
    ]);
    hyd_i18n_suspend(false);
    return $clash ? $slug : $original;
}, 10, 6);

/* =========================================================================
 * Permalinks per taal (juiste domein + Engelse bases)
 * ====================================================================== */

function hyd_lang_home($lang) {
    if (hyd_is_production_host()) {
        return 'https://' . hyd_languages()[$lang]['host'] . '/';
    }
    return home_url('/');
}

function hyd_localize_url($url, $lang) {
    if (hyd_is_production_host()) {
        return preg_replace('#^https?://[^/]+#', rtrim(hyd_lang_home($lang), '/'), $url);
    }
    // Lokaal: zelfde domein, taal via ?hyd_lang
    return $lang === hyd_lang() ? $url : add_query_arg('hyd_lang', $lang, $url);
}

add_filter('post_type_link', function ($link, $post) {
    $bases = hyd_cpt_bases()[$post->post_type] ?? null;
    $lang  = hyd_post_lang($post);
    if ($bases && $lang !== 'nl') {
        $link = preg_replace('#/' . preg_quote($bases['nl'], '#') . '/#', '/' . $bases[$lang] . '/', $link, 1);
    }
    return $lang === hyd_lang() || !hyd_is_production_host() ? $link : hyd_localize_url($link, $lang);
}, 10, 2);

foreach (['page_link', 'post_link'] as $hyd_hook) {
    add_filter($hyd_hook, function ($link, $post) {
        $lang = hyd_post_lang($post);
        return $lang === hyd_lang() || !hyd_is_production_host() ? $link : hyd_localize_url($link, $lang);
    }, 10, 2);
}

/* =========================================================================
 * Juiste taal afdwingen + oude Engelse URL's
 * ====================================================================== */

add_action('template_redirect', function () {
    // 404: bestaat dit pad in de andere taal? Dan naar het domein van die taal.
    if (is_404()) {
        $path  = trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
        $parts = explode('/', $path);
        foreach (array_keys(hyd_languages()) as $other) {
            if ($other === hyd_lang() || $path === '') continue;
            $id = 0;
            foreach (hyd_cpt_bases() as $type => $bases) {
                if (($parts[0] ?? '') === $bases[$other] && count($parts) > 1) {
                    $id = hyd_find_by_path(implode('/', array_slice($parts, 1)), $type, $other);
                    break;
                }
            }
            if (!$id) $id = hyd_find_by_path($path, 'page', $other);
            if (!$id && count($parts) === 1) $id = hyd_find_by_path($path, 'post', $other);
            if ($id) {
                wp_safe_redirect(hyd_localize_url(get_permalink($id), $other), 301);
                exit;
            }
        }
    }

    if (is_singular(hyd_translatable_types())) {
        $post = get_queried_object();
        if ($post && hyd_post_lang($post) !== hyd_lang()) {
            // Item hoort bij de andere taal: naar het eigen domein
            wp_safe_redirect(hyd_localize_url(get_permalink($post), hyd_post_lang($post)), 301);
            exit;
        }
    }
}, 2);

add_filter('allowed_redirect_hosts', function ($hosts) {
    foreach (hyd_languages() as $cfg) {
        $hosts[] = $cfg['host'];
        $hosts[] = $cfg['match'];
    }
    return $hosts;
});

/* =========================================================================
 * hreflang en taalkeuze
 * ====================================================================== */

/** Links naar dezelfde pagina in elke taal: [code => [url, label, active, flag]] */
function hyd_language_links() {
    $out = [];
    $obj = get_queried_object();
    foreach (hyd_languages() as $code => $cfg) {
        $url = '';
        if (is_front_page()) {
            $url = hyd_localize_url(hyd_lang_home($code), $code);
        } elseif ($obj instanceof WP_Post && in_array($obj->post_type, hyd_translatable_types(), true)) {
            $tid = hyd_translation($obj, $code);
            if ($tid) $url = hyd_localize_url(get_permalink($tid), $code);
        } elseif (is_home()) {
            $kb = $code === 'en' ? (int) get_option('hyd_posts_page_en') : (int) get_option('page_for_posts');
            if ($kb) $url = hyd_localize_url(get_permalink($kb), $code);
        }
        $out[$code] = [
            'url'       => $url ?: hyd_localize_url(hyd_lang_home($code), $code),
            'exact'     => (bool) $url,
            'label'     => $cfg['label'],
            'flag'      => $cfg['flag'],
            'hreflang'  => $cfg['hreflang'],
            'active'    => $code === hyd_lang(),
        ];
    }
    return $out;
}

add_action('wp_head', function () {
    if (is_404() || is_search()) return;
    $links = array_filter(hyd_language_links(), fn($l) => $l['exact']);
    if (count($links) < 2) return;
    foreach ($links as $l) {
        echo '<link rel="alternate" hreflang="' . esc_attr($l['hreflang']) . '" href="' . esc_url(remove_query_arg('hyd_lang', $l['url'])) . '">' . "\n";
    }
    if (isset($links['en'])) {
        echo '<link rel="alternate" hreflang="x-default" href="' . esc_url(remove_query_arg('hyd_lang', $links['en']['url'])) . '">' . "\n";
    }
}, 3);

// Yoast-sitemaps: alleen items in de taal van het domein
add_filter('wpseo_exclude_from_sitemap_by_post_ids', function ($ids) {
    hyd_i18n_suspend(true);
    $other = get_posts([
        'post_type'        => hyd_translatable_types(),
        'post_status'      => 'publish',
        'numberposts'      => -1,
        'fields'           => 'ids',
        'suppress_filters' => true,
        'meta_query'       => [hyd_lang_meta_query(hyd_lang() === 'nl' ? 'en' : 'nl')],
    ]);
    hyd_i18n_suspend(false);
    return array_merge((array) $ids, $other);
});

// Yoast og:locale
add_filter('wpseo_locale', fn($l) => hyd_languages()[hyd_lang()]['locale']);
