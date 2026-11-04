<?php
/**
 * Blok: Logo's / certificeringen
 */
$title  = get_field('titel');
$tag    = hyd_heading_tag(get_field('kopniveau'), 'h2');
$text   = get_field('tekst');
$layout = get_field('indeling') ?: 'naast';
$logos  = get_field('logos') ?: [];
$button = hyd_block_button('knop', ['type' => 'white']);
?>
<section <?php echo hyd_section_attrs($block, 'mk-logos', 'blauw', ['mk-logos--' . $layout]); ?>>
    <div class="mk-container mk-logos__layout">
        <div class="mk-logos__text">
            <?php if ($title) : ?><<?php echo $tag; ?> class="mk-section__title"><?php echo hyd_title_html($title, get_field('accent')); ?></<?php echo $tag; ?>><?php endif; ?>
            <?php if ($text) : ?><div class="mk-richtext"><?php echo wp_kses_post($text); ?></div><?php endif; ?>
            <?php if ($button) : ?><div class="mk-split__actions"><?php echo $button; ?></div><?php endif; ?>
        </div>
        <?php if ($logos) : ?>
            <ul class="mk-logos__list" aria-label="<?php echo esc_attr($title ? wp_strip_all_tags($title) : __('Certificeringen', 'mk')); ?>">
                <?php foreach ($logos as $id) : ?>
                    <li class="mk-logos__item"><?php echo hyd_image($id, 'medium', ['sizes' => '200px']); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</section>
