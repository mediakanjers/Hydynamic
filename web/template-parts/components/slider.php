<?php
/**
 * Fotoslider (scroll-snap, zonder externe library).
 * Alle foto's staan in de HTML (crawlbaar), JS voegt alleen navigatie toe.
 * $args: ids (afbeelding-ID's), label, variant (photo | gallery), size, sizes, eager
 */
$ids     = array_values(array_filter(array_map('intval', (array) ($args['ids'] ?? []))));
$variant = $args['variant'] ?? 'photo';
$label   = $args['label'] ?? __('Foto\'s', 'mk');
$size    = $args['size'] ?? 'hyd-section';
$sizes   = $args['sizes'] ?? '(min-width: 900px) 50vw, 100vw';
$eager   = !empty($args['eager']);
if (!$ids) return;
$count = count($ids);
?>
<div class="mk-slider mk-slider--<?php echo esc_attr($variant); ?> js-slider" role="region" aria-roledescription="<?php esc_attr_e('carrousel', 'mk'); ?>" aria-label="<?php echo esc_attr($label); ?>">
    <div class="mk-slider__track js-slider-track" tabindex="0">
        <?php foreach ($ids as $i => $id) : ?>
            <figure class="mk-slider__slide" role="group" aria-roledescription="<?php esc_attr_e('dia', 'mk'); ?>" aria-label="<?php echo esc_attr(sprintf(__('%1$d van %2$d', 'mk'), $i + 1, $count)); ?>">
                <?php echo hyd_image($id, $size, ['sizes' => $sizes, 'loading' => ($eager && $i === 0) ? 'eager' : 'lazy']); ?>
            </figure>
        <?php endforeach; ?>
    </div>
    <?php if ($count > 1) : ?>
        <?php if ($variant === 'gallery') : ?>
            <button type="button" class="mk-slider__nav mk-slider__nav--prev js-slider-prev" aria-label="<?php esc_attr_e('Vorige foto\'s', 'mk'); ?>"><?php echo hyd_icon('triangle-left', 24); ?></button>
            <button type="button" class="mk-slider__nav mk-slider__nav--next js-slider-next" aria-label="<?php esc_attr_e('Volgende foto\'s', 'mk'); ?>"><?php echo hyd_icon('triangle-right', 24); ?></button>
        <?php else : ?>
            <button type="button" class="mk-slider__nav mk-slider__nav--prev js-slider-prev" aria-label="<?php esc_attr_e('Vorige foto', 'mk'); ?>"><?php echo hyd_icon('arrow-left', 20); ?></button>
            <button type="button" class="mk-slider__nav mk-slider__nav--next js-slider-next" aria-label="<?php esc_attr_e('Volgende foto', 'mk'); ?>"><?php echo hyd_icon('arrow-right', 20); ?></button>
            <span class="mk-slider__counter js-slider-counter" aria-live="polite">1 / <?php echo (int) $count; ?></span>
        <?php endif; ?>
    <?php endif; ?>
</div>
