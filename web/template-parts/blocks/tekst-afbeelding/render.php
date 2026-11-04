<?php
/**
 * Blok: Tekst met afbeelding / fotoslider / logo
 */
$title    = get_field('titel');
$tag      = hyd_heading_tag(get_field('kopniveau'), 'h2');
$text     = get_field('tekst');
$media    = get_field('media') ?: 'afbeelding';
$position = get_field('positie') ?: 'rechts';
$image    = get_field('afbeelding');
$slides   = get_field('slides') ?: [];
$bleed    = $media === 'afbeelding' && get_field('uitlopend');
$button   = hyd_block_button('knop');

$extra = ['mk-split--media-' . $position, 'mk-split--' . $media];
if ($bleed) $extra[] = 'mk-split--bleed';
?>
<section <?php echo hyd_section_attrs($block, 'mk-split', 'wit', $extra); ?>>
    <?php if ($bleed && $image) : ?>
        <div class="mk-split__bleed" aria-hidden="true"><?php echo hyd_image($image, 'hyd-hero', ['alt' => '', 'sizes' => '(min-width: 900px) 60vw, 100vw']); ?></div>
    <?php endif; ?>
    <div class="mk-container mk-split__layout">
        <div class="mk-split__text">
            <?php if ($title) : ?><<?php echo $tag; ?> class="mk-section__title"><?php echo hyd_title_html($title, get_field('accent')); ?></<?php echo $tag; ?>><?php endif; ?>
            <?php if ($text) : ?><div class="mk-split__body mk-richtext"><?php echo wp_kses_post($text); ?></div><?php endif; ?>
            <?php if ($button) : ?><div class="mk-split__actions"><?php echo $button; ?></div><?php endif; ?>
        </div>

        <?php if (!$bleed) : ?>
            <div class="mk-split__media">
                <?php if ($media === 'slider') :
                    get_template_part('template-parts/components/slider', null, ['ids' => $slides, 'label' => $title ? wp_strip_all_tags($title) : __('Foto\'s', 'mk')]);
                elseif ($image) : ?>
                    <div class="<?php echo $media === 'logo' ? 'mk-split__logo' : 'mk-photo'; ?>">
                        <?php echo hyd_image($image, $media === 'logo' ? 'medium' : 'hyd-section', ['sizes' => $media === 'logo' ? '420px' : '(min-width: 900px) 45vw, 100vw']); ?>
                    </div>
                <?php elseif ($is_preview ?? false) : ?>
                    <div class="mk-photo mk-photo--empty">Kies een afbeelding</div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
