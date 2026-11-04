<?php
/**
 * Blok: Fotoslider (sfeerimpressie, 3 foto's zichtbaar op desktop)
 */
$title = get_field('titel');
$tag   = hyd_heading_tag(get_field('kopniveau'), 'h2');
$intro = get_field('intro');
$ids   = get_field('fotos') ?: [];
if (!$ids) {
    if ($is_preview ?? false) echo '<p class="mk-block-empty">Fotoslider: voeg foto\'s toe.</p>';
    return;
}
?>
<section <?php echo hyd_section_attrs($block, 'mk-fotoslider', 'verloop'); ?>>
    <div class="mk-container">
        <?php if ($title || $intro) : ?>
            <div class="mk-section__head">
                <?php if ($title) : ?><<?php echo $tag; ?> class="mk-section__title"><?php echo hyd_title_html($title, get_field('accent')); ?></<?php echo $tag; ?>><?php endif; ?>
                <?php if ($intro) : ?><p class="mk-section__intro"><?php echo wp_kses($intro, ['br' => []]); ?></p><?php endif; ?>
            </div>
        <?php endif; ?>
        <?php get_template_part('template-parts/components/slider', null, [
            'ids'     => $ids,
            'variant' => 'gallery',
            'label'   => $title ? wp_strip_all_tags($title) : __('Sfeerimpressie', 'mk'),
            'size'    => 'hyd-tile',
            'sizes'   => '(min-width: 1200px) 30vw, (min-width: 600px) 45vw, 85vw',
        ]); ?>
    </div>
</section>
