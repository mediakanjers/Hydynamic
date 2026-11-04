<?php
/**
 * Blok: Klikbare tegels
 * Elke tegel heeft een eigen, handmatig ingevulde tekst en een instelbare doellink.
 * De hele tegel is klikbaar (stretched link), de titel is de linktekst.
 */
$title    = get_field('titel');
$tag      = hyd_heading_tag(get_field('kopniveau'), 'h2');
$tiles    = get_field('tegels') ?: [];
$cols     = get_field('kolommen') ?: '4';
$linktext = get_field('linktekst');
$intro    = get_field('intro');
$tile_tag = ($title && $tag === 'h2') ? 'h3' : ($title && $tag === 'h3' ? 'h4' : 'h3');

if (!$tiles) {
    if ($is_preview ?? false) echo '<p class="mk-block-empty">Klikbare tegels: voeg tegels toe.</p>';
    return;
}
?>
<section <?php echo hyd_section_attrs($block, 'mk-tegels', 'grijs'); ?>>
    <div class="mk-container">
        <?php if ($title || $intro) : ?>
            <div class="mk-section__head">
                <?php if ($title) : ?><<?php echo $tag; ?> class="mk-section__title"><?php echo hyd_title_html($title, get_field('accent')); ?></<?php echo $tag; ?>><?php endif; ?>
                <?php if ($intro) : ?><p class="mk-section__intro"><?php echo wp_kses($intro, ['br' => []]); ?></p><?php endif; ?>
            </div>
        <?php endif; ?>

        <ul class="mk-tegels__grid mk-tegels__grid--<?php echo esc_attr($cols); ?>">
            <?php foreach ($tiles as $tile) :
                $link   = is_array($tile['link'] ?? null) ? $tile['link'] : [];
                $url    = $link['url'] ?? '';
                $target = ($link['target'] ?? '') === '_blank' ? ' target="_blank" rel="noopener"' : '';
                ?>
                <li class="mk-tile<?php echo $url ? ' is-linked' : ''; ?>">
                    <?php if (!empty($tile['afbeelding'])) : ?>
                        <div class="mk-tile__image">
                            <?php echo hyd_image($tile['afbeelding'], 'hyd-tile', ['alt' => '', 'sizes' => '(min-width: 1200px) 25vw, (min-width: 600px) 50vw, 80vw']); ?>
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
    </div>
</section>
