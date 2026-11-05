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
            // Nederlandse pagina: loopt de vertaling achter?
            if ($lang === 'nl' && hyd_translation_outdated($tid)) {
                echo '<br><span style="display:inline-block;margin-top:6px;padding:4px 8px;border-radius:3px;background:#fcf0e3;color:#8a4b00">⚠ Deze pagina is gewijzigd na de laatste update van de ' . esc_html(strtolower($cfg['label'])) . 'e vertaling.</span>';
            }
        } elseif ($post->post_status !== 'auto-draft') {
            $url = wp_nonce_url(admin_url('admin-post.php?action=hyd_translate&post=' . $post->ID . '&lang=' . $code), 'hyd_translate_' . $post->ID);
            echo '<a class="button" href="' . esc_url($url) . '">' . esc_html($cfg['label']) . 'e vertaling maken</a>';
            echo '<br><span class="description">Kopieert deze pagina met alle blokken als concept, zodat je alleen de teksten hoeft te vertalen.</span>';
        } else {
            echo '<span class="description">Sla de pagina eerst op.</span>';
        }
        echo '</p>';
    }

    // Vertaling: melding als het Nederlandse origineel nieuwer is
    if ($lang !== 'nl' && ($src = hyd_translation($post, 'nl')) && hyd_translation_outdated($post->ID)) {
        $done = wp_nonce_url(admin_url('admin-post.php?action=hyd_mark_synced&post=' . $post->ID), 'hyd_mark_synced_' . $post->ID);
        echo '<div style="margin-top:8px;padding:10px 12px;border-left:4px solid #dba617;background:#fcf9e8">';
        echo '<strong>Vertaling verouderd</strong><br>';
        echo 'Het Nederlandse origineel is gewijzigd op ' . esc_html(get_post_modified_time('j F Y \o\m H:i', false, $src, true)) . ', na de laatste update van deze vertaling.<br>';
        echo '<a href="' . esc_url(get_edit_post_link($src)) . '" target="_blank">Nederlandse versie bekijken</a>';
        $revisions = wp_get_post_revisions($src, ['numberposts' => 1]);
        if ($revisions) {
            echo ' · <a href="' . esc_url(admin_url('revision.php?revision=' . array_key_first($revisions))) . '" target="_blank">wijzigingen vergelijken</a>';
        }
        echo '<br><span class="description">Opslaan van deze vertaling haalt de melding weg.</span> ';
        echo '<a class="button button-small" style="margin-top:6px" href="' . esc_url($done) . '">Markeer als bijgewerkt</a>';
        echo '</div>';
    }
}

/* -------------------------------------------------------------------------
 * Vertaling verouderd?
 * Op een vertaling wordt bij elke opslag vastgelegd welke versie (wijzigdatum)
 * van het Nederlandse origineel is gevolgd: _hyd_src_modified.
 * ---------------------------------------------------------------------- */

function hyd_translation_outdated($translation_id) {
    if (hyd_post_lang($translation_id) === 'nl') return false;
    $src = hyd_translation($translation_id, 'nl');
    if (!$src) return false;
    $followed = get_post_meta($translation_id, '_hyd_src_modified', true) ?: get_post_field('post_modified_gmt', $translation_id);
    return strtotime(get_post_field('post_modified_gmt', $src)) > strtotime($followed) + 5;
}

function hyd_mark_synced($translation_id) {
    $src = hyd_translation($translation_id, 'nl');
    if ($src) update_post_meta($translation_id, '_hyd_src_modified', get_post_field('post_modified_gmt', $src));
}

// Opslaan van een vertaling (blokeditor of klassiek) = bijgewerkt
add_action('wp_after_insert_post', function ($post_id, $post) {
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) return;
    if (!in_array($post->post_type, hyd_translatable_types(), true)) return;
    if (hyd_post_lang($post_id) !== 'nl') hyd_mark_synced($post_id);
}, 10, 2);

add_action('admin_post_hyd_mark_synced', function () {
    $id = (int) ($_GET['post'] ?? 0);
    if (!$id || !current_user_can('edit_post', $id)) wp_die('Geen toegang.');
    check_admin_referer('hyd_mark_synced_' . $id);
    hyd_mark_synced($id);
    wp_safe_redirect(get_edit_post_link($id, 'url'));
    exit;
});

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
    hyd_mark_synced($new);
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
        $tid   = hyd_translation($post_id, $other);
        if (!$tid) return;
        $outdated = $lang === 'nl' ? hyd_translation_outdated($tid) : hyd_translation_outdated($post_id);
        if ($outdated) {
            echo ' <span title="Vertaling loopt achter op het Nederlandse origineel" style="color:#b26200">⚠ ' . esc_html(strtoupper($lang === 'nl' ? $other : $lang)) . ' verouderd</span>';
        } else {
            echo ' <span title="Vertaling aanwezig" style="color:#2E7A1F">✓ ' . esc_html(strtoupper($other)) . '</span>';
        }
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

/* -------------------------------------------------------------------------
 * Engelse alt-tekst in de mediabibliotheek
 * Afbeeldingen worden door beide talen gedeeld; op hydynamic.com wordt deze
 * alt-tekst gebruikt (leeg = de Nederlandse alt-tekst).
 * ---------------------------------------------------------------------- */

add_filter('attachment_fields_to_edit', function ($fields, $post) {
    if (!wp_attachment_is_image($post->ID)) return $fields;
    $fields['hyd_alt_en'] = [
        'label' => 'Alt-tekst (Engels)',
        'input' => 'text',
        'value' => get_post_meta($post->ID, '_hyd_alt_en', true),
        'helps' => 'Voor hydynamic.com. Leeg laten = de Nederlandse alt-tekst wordt gebruikt.',
    ];
    return $fields;
}, 10, 2);

add_filter('attachment_fields_to_save', function ($post, $attachment) {
    if (isset($attachment['hyd_alt_en'])) {
        $alt = sanitize_text_field($attachment['hyd_alt_en']);
        if ($alt === '') {
            delete_post_meta($post['ID'], '_hyd_alt_en');
        } else {
            update_post_meta($post['ID'], '_hyd_alt_en', $alt);
        }
    }
    return $post;
}, 10, 2);
