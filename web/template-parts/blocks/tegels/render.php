<?php
/**
 * Blok: Klikbare tegels
 * - Handmatig: elke tegel heeft een eigen tekst en een instelbare doellink.
 * - Onderliggend: automatisch alle onderliggende pagina's/producten, met de
 *   tegeltekst en tegelfoto uit hun eigen velden (niet uit de paginatekst).
 * - Weergave als raster of als slider met pijlknoppen.
 * De hele tegel is klikbaar (stretched link), de titel is de linktekst.
 */
$title    = get_field('titel');
$tag      = hyd_heading_tag(get_field('kopniveau'), 'h2');
$cols     = get_field('kolommen') ?: '4';
$linktext = get_field('linktekst');
$intro    = get_field('intro');
// Standaard: slider met alle onderliggende pagina's (zonder onderliggende pagina's: de handmatige tegels)
$source   = get_field('bron') ?: 'onderliggend';
$slider   = (get_field('weergave') ?: 'slider') === 'slider';
$tile_tag = ($title && $tag === 'h3') ? 'h4' : 'h3';

$tiles = [];
if ($source === 'onderliggend') {
    $parent = (int) (get_field('bron_ouder') ?: ($post_id ?? get_the_ID()));
    if ($parent) {
        $children = get_posts([
            'post_type'      => get_post_type($parent),
            'post_parent'    => $parent,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => ['menu_order' => 'ASC', 'title' => 'ASC'],
            'suppress_filters' => false,
        ]);
        foreach ($children as $child) {
            $tiles[] = [
                'afbeelding' => get_field('hyd_tegelafbeelding', $child->ID) ?: get_post_thumbnail_id($child),
                'titel'      => get_the_title($child),
                'tekst'      => get_field('hyd_tegeltekst', $child->ID) ?: '',
                'link'       => ['url' => get_permalink($child), 'target' => ''],
                'auto'       => true,
            ];
        }
    }
}
// Handmatige tegels (bij "onderliggend" als extra tegels achteraan)
foreach (get_field('tegels') ?: [] as $t) $tiles[] = $t;

if (!$tiles) {
    if ($is_preview ?? false) echo '<p class="mk-block-empty">Klikbare tegels: voeg tegels toe of kies "Alle onderliggende pagina’s".</p>';
    return;
}

// Slider alleen als er meer tegels zijn dan er op één rij passen (bij 4 per rij: vanaf 5)
if ($slider && count($tiles) <= (int) $cols) $slider = false;

$uid    = 'tegels-' . substr(md5(($block['id'] ?? '') . serialize(array_column($tiles, 'titel'))), 0, 6);
$extra  = $slider ? ['mk-tegels--slider'] : [];
$label  = $title ? wp_strip_all_tags($title) : __('Overzicht', 'mk');
?>
<section <?php echo hyd_section_attrs($block, 'mk-tegels', 'grijs', $extra); ?>>
    <div class="mk-container">
        <?php if ($title || $intro) : ?>
            <div class="mk-section__head">
                <?php if ($title) : ?><<?php echo $tag; ?> class="mk-section__title"><?php echo hyd_title_html($title, get_field('accent')); ?></<?php echo $tag; ?>><?php endif; ?>
                <?php if ($intro) : ?><p class="mk-section__intro"><?php echo wp_kses($intro, ['br' => []]); ?></p><?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($slider) : ?><div class="mk-tegels__slider js-slider js-tile-slider" role="region" aria-roledescription="<?php esc_attr_e('carrousel', 'mk'); ?>" aria-label="<?php echo esc_attr($label); ?>"><?php endif; ?>

        <ul class="mk-tegels__grid mk-tegels__grid--<?php echo esc_attr($cols); ?><?php echo $slider ? ' js-slider-track' : ''; ?>"<?php echo $slider ? ' id="' . esc_attr($uid) . '" tabindex="0"' : ''; ?>>
            <?php foreach ($tiles as $tile) :
                $link   = is_array($tile['link'] ?? null) ? $tile['link'] : [];
                $url    = $link['url'] ?? '';
                $target = ($link['target'] ?? '') === '_blank' ? ' target="_blank" rel="noopener"' : '';
                $img    = $tile['afbeelding'] ?? 0;
                ?>
                <li class="mk-tile<?php echo $url ? ' is-linked' : ''; ?>">
                    <?php if ($img) : ?>
                        <div class="mk-tile__image">
                            <?php echo hyd_image($img, 'hyd-tile', ['alt' => '', 'sizes' => '(min-width: 1200px) 25vw, (min-width: 600px) 50vw, 80vw']); ?>
                        </div>
                    <?php elseif (!empty($tile['auto']) || $slider) : ?>
                        <div class="mk-tile__image mk-tile__image--placeholder" aria-hidden="true">
                            <img src="<?php echo esc_url(hyd_brand_asset('logo-element.svg')); ?>" alt="" width="1837" height="856" loading="lazy">
                        </div>
                    <?php endif; ?>
                    <div class="mk-tile__body">
                        <<?php echo $tile_tag; ?> class="mk-tile__title">
                            <?php if ($url) : ?>
                                <a class="mk-tile__link" href="<?php echo esc_url($url); ?>"<?php echo $target; ?>><?php echo esc_html($tile['titel']); ?></a>
                            <?php else : echo esc_html($tile['titel']); endif; ?>
                        </<?php echo $tile_tag; ?>>
                        <?php if (!empty($tile['tekst'])) : ?><p class="mk-tile__text"><?php echo esc_html($tile['tekst']); ?></p><?php endif; ?>
                        <?php if ($url && $linktext) : ?>
                            <span class="mk-tile__more mk-textlink" aria-hidden="true"><?php echo esc_html($linktext); ?><?php echo hyd_icon('arrow-right', 16); ?></span>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php if ($slider) : ?>
            <div class="mk-tegels__nav">
                <button type="button" class="mk-tegels__arrow js-slider-prev" aria-controls="<?php echo esc_attr($uid); ?>" aria-label="<?php esc_attr_e('Vorige', 'mk'); ?>"><?php echo hyd_icon('arrow-left', 20); ?></button>
                <button type="button" class="mk-tegels__arrow js-slider-next" aria-controls="<?php echo esc_attr($uid); ?>" aria-label="<?php esc_attr_e('Volgende', 'mk'); ?>"><?php echo hyd_icon('arrow-right', 20); ?></button>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>
