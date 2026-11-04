<?php
/**
 * Beheer van vertalingen in de admin.
 * - Meta box "Taal en vertaling" (taal kiezen, vertaling openen of aanmaken)
 * - Kolom "Taal" in de overzichten + filter op taal
 */

if (!defined('ABSPATH')) exit;

add_action('add_meta_boxes', function () {
    foreach (hyd_translatable_types() as $type) {
        add_meta_box('hyd-lang', 'Taal en vertaling', 'hyd_lang_metabox', $type, 'side', 'high');
    }
});

function hyd_lang_metabox($post) {
    $lang = hyd_post_lang($post);
    wp_nonce_field('hyd_lang_save', 'hyd_lang_nonce');
    echo '<p><label for="hyd_lang"><strong>Taal van deze pagina</strong></label><br>';
    echo '<select name="hyd_lang" id="hyd_lang" style="width:100%">';
    foreach (hyd_languages() as $code => $cfg) {
        printf('<option value="%s"%s>%s (%s)</option>', esc_attr($code), selected($lang, $code, false), esc_html($cfg['label']), esc_html($cfg['host']));
    }
    echo '</select></p>';

    foreach (hyd_languages() as $code => $cfg) {
        if ($code === $lang) continue;
        $tid = hyd_translation($post, $code);
        if (!$tid) {
            // Ook concepten meenemen
            $group = (int) get_post_meta($post->ID, '_hyd_group', true) ?: $post->ID;
            hyd_i18n_suspend(true);
            $draft = get_posts(['post_type' => $post->post_type, 'post_status' => ['draft', 'pending', 'private'], 'numberposts' => 1, 'fields' => 'ids', 'suppress_filters' => true,
                'meta_query' => [['key' => '_hyd_lang', 'value' => $code], ['key' => '_hyd_group', 'value' => $group]]]);
            hyd_i18n_suspend(false);
            $tid = $draft ? (int) $draft[0] : 0;
        }
        echo '<p><strong>' . esc_html($cfg['label']) . '</strong><br>';
        if ($tid) {
            echo '<a href="' . esc_url(get_edit_post_link($tid)) . '">' . esc_html(get_the_title($tid)) . '</a> <span style="color:#646970">(' . esc_html(get_post_status($tid)) . ')</span>';
        } elseif ($post->post_status !== 'auto-draft') {
            $url = wp_nonce_url(admin_url('admin-post.php?action=hyd_translate&post=' . $post->ID . '&lang=' . $code), 'hyd_translate_' . $post->ID);
            echo '<a class="button" href="' . esc_url($url) . '">' . esc_html($cfg['label']) . 'e vertaling maken</a>';
            echo '<br><span class="description">Kopieert deze pagina met alle blokken als concept, zodat je alleen de teksten hoeft te vertalen.</span>';
        } else {
            echo '<span class="description">Sla de pagina eerst op.</span>';
        }
        echo '</p>';
    }
}

add_action('save_post', function ($post_id) {
    if (!isset($_POST['hyd_lang_nonce']) || !wp_verify_nonce($_POST['hyd_lang_nonce'], 'hyd_lang_save')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    $lang = sanitize_key($_POST['hyd_lang'] ?? 'nl');
    if (!isset(hyd_languages()[$lang])) return;
    if ($lang === 'nl') {
        delete_post_meta($post_id, '_hyd_lang');
    } else {
        update_post_meta($post_id, '_hyd_lang', $lang);
    }
});

// Vertaling aanmaken: kopie met alle blokken, metadata en uitgelichte afbeelding
add_action('admin_post_hyd_translate', function () {
    $id   = (int) ($_GET['post'] ?? 0);
    $lang = sanitize_key($_GET['lang'] ?? '');
    if (!$id || !isset(hyd_languages()[$lang]) || !current_user_can('edit_post', $id)) wp_die('Geen toegang.');
    check_admin_referer('hyd_translate_' . $id);

    $src   = get_post($id);
    $group = (int) get_post_meta($id, '_hyd_group', true) ?: $id;
    $parent = $src->post_parent ? (hyd_translation($src->post_parent, $lang) ?: 0) : 0;

    $GLOBALS['hyd_saving_lang'] = $lang;
    $new = wp_insert_post(wp_slash([
        'post_type'    => $src->post_type,
        'post_title'   => $src->post_title,
        'post_name'    => $src->post_name,
        'post_content' => $src->post_content,
        'post_excerpt' => $src->post_excerpt,
        'post_status'  => 'draft',
        'post_parent'  => $parent,
        'menu_order'   => $src->menu_order,
        'post_author'  => get_current_user_id(),
    ]), true);
    unset($GLOBALS['hyd_saving_lang']);
    if (is_wp_error($new)) wp_die($new->get_error_message());

    update_post_meta($new, '_hyd_lang', $lang);
    update_post_meta($new, '_hyd_group', $group);
    update_post_meta($id, '_hyd_group', $group);
    if ($thumb = get_post_thumbnail_id($id)) set_post_thumbnail($new, $thumb);

    wp_safe_redirect(get_edit_post_link($new, 'url'));
    exit;
});

// Kolom "Taal" + filter
foreach (hyd_translatable_types() as $hyd_type) {
    add_filter("manage_{$hyd_type}_posts_columns", function ($cols) {
        $out = [];
        foreach ($cols as $k => $v) {
            $out[$k] = $v;
            if ($k === 'title') $out['hyd_lang'] = 'Taal';
        }
        return $out;
    });
    add_action("manage_{$hyd_type}_posts_custom_column", function ($col, $post_id) {
        if ($col !== 'hyd_lang') return;
        $lang = hyd_post_lang($post_id);
        echo '<strong>' . esc_html(strtoupper($lang)) . '</strong>';
        $other = $lang === 'nl' ? 'en' : 'nl';
        if (hyd_translation($post_id, $other)) echo ' <span title="Vertaling aanwezig" style="color:#2E7A1F">✓ ' . esc_html(strtoupper($other)) . '</span>';
    }, 10, 2);
}

add_action('restrict_manage_posts', function ($type) {
    if (!in_array($type, hyd_translatable_types(), true)) return;
    $cur = sanitize_key($_GET['hyd_lang_filter'] ?? '');
    echo '<select name="hyd_lang_filter"><option value="">Alle talen</option>';
    foreach (hyd_languages() as $code => $cfg) {
        printf('<option value="%s"%s>%s</option>', esc_attr($code), selected($cur, $code, false), esc_html($cfg['label']));
    }
    echo '</select>';
});

add_action('pre_get_posts', function ($q) {
    if (!is_admin() || !$q->is_main_query() || empty($_GET['hyd_lang_filter'])) return;
    $lang = sanitize_key($_GET['hyd_lang_filter']);
    if (!isset(hyd_languages()[$lang])) return;
    $mq = (array) $q->get('meta_query');
    $mq[] = hyd_lang_meta_query($lang);
    $q->set('meta_query', $mq);
});
