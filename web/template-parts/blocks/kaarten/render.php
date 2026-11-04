<?php
/**
 * Blok: Tekstkaarten (2 of 3 per rij)
 */
$title = get_field('titel');
$tag   = hyd_heading_tag(get_field('kopniveau'), 'h2');
$intro = get_field('intro');
$cards = array_filter(get_field('kaarten') ?: [], fn($c) => !empty($c['titel']) || !empty($c['tekst']));
$cols  = get_field('kolommen') ?: '3';
$card_tag = $title && $tag === 'h2' ? 'h3' : 'h3';

if (!$cards) {
    if ($is_preview ?? false) echo '<p class="mk-block-empty">Tekstkaarten: voeg kaarten toe.</p>';
    return;
}
?>
<section <?php echo hyd_section_attrs($block, 'mk-kaarten', 'grijs'); ?>>
    <div class="mk-container">
        <?php if ($title || $intro) : ?>
            <div class="mk-section__head">
                <?php if ($title) : ?><<?php echo $tag; ?> class="mk-section__title"><?php echo hyd_title_html($title, get_field('accent')); ?></<?php echo $tag; ?>><?php endif; ?>
                <?php if ($intro) : ?><p class="mk-section__intro"><?php echo wp_kses($intro, ['br' => []]); ?></p><?php endif; ?>
            </div>
        <?php endif; ?>
        <ul class="mk-kaarten__grid mk-kaarten__grid--<?php echo esc_attr($cols); ?>">
            <?php foreach ($cards as $card) : ?>
                <li class="mk-card mk-kaart">
                    <?php if ($card['titel']) : ?><<?php echo $card_tag; ?> class="mk-kaart__title"><?php echo esc_html($card['titel']); ?></<?php echo $card_tag; ?>><?php endif; ?>
                    <?php if ($card['tekst']) : ?><p class="mk-kaart__text"><?php echo wp_kses($card['tekst'], ['br' => []]); ?></p><?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
