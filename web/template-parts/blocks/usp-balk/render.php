<?php
/**
 * Blok: USP-balk
 */
$style = get_field('stijl') ?: 'donker';
$items = array_filter(get_field('items') ?: [], fn($i) => !empty($i['tekst']));
if (!$items) {
    if ($is_preview ?? false) echo '<p class="mk-block-empty">USP-balk: voeg USP\'s toe.</p>';
    return;
}
$anchor = !empty($block['anchor']) ? ' id="' . esc_attr($block['anchor']) . '"' : '';
?>
<section class="mk-usp mk-usp--<?php echo esc_attr($style); ?><?php echo !empty($block['className']) ? ' ' . esc_attr($block['className']) : ''; ?>" aria-label="<?php esc_attr_e('Kernpunten', 'mk'); ?>"<?php echo $anchor; ?>>
    <div class="mk-container">
        <ul class="mk-usp__list mk-usp__list--<?php echo count($items); ?>">
            <?php foreach ($items as $item) : ?>
                <li class="mk-usp__item">
                    <?php if ($style === 'donker') echo hyd_icon('check', 26, 'mk-usp__check'); ?>
                    <span><?php echo hyd_title_html($item['tekst'], $style === 'licht' ? ($item['accent'] ?? '') : ''); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
