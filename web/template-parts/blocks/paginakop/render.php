<?php
/**
 * Blok: Paginakop – altijd de H1 van de pagina.
 */
$variant   = get_field('variant') ?: 'tekst';
$title     = get_field('titel') ?: get_the_title($post_id ?? 0);
$accent    = get_field('accent');
$intro     = get_field('intro');
$image     = get_field('afbeelding');
$slides    = get_field('slides') ?: [];
$downloads = get_field('downloads') ?: [];
$crumbs    = get_field('kruimelpad');
$crumbs    = $crumbs === null ? true : (bool) $crumbs;
$lines     = get_field('lijnen');
$lines     = $lines === null ? true : (bool) $lines;
$button    = hyd_block_button('knop');

$classes = ['mk-section', 'mk-bg--pagina', 'mk-paginakop', 'mk-paginakop--' . $variant];
if (!empty($block['className'])) $classes[] = $block['className'];
$anchor = !empty($block['anchor']) ? ' id="' . esc_attr($block['anchor']) . '"' : '';
?>
<section class="<?php echo esc_attr(implode(' ', $classes)); ?>"<?php echo $anchor; ?>>
    <?php if ($variant === 'achtergrond' && $image) : ?>
        <div class="mk-paginakop__bg" aria-hidden="true">
            <?php echo hyd_image($image, 'hyd-hero', ['loading' => 'eager', 'sizes' => '66vw', 'alt' => '']); ?>
        </div>
    <?php endif; ?>
    <?php if ($lines) : ?>
        <img class="mk-paginakop__lines" src="<?php echo esc_url(hyd_brand_asset('achtergrond-h-lijnen.svg')); ?>" alt="" aria-hidden="true" width="1500" height="880">
    <?php endif; ?>

    <div class="mk-container">
        <?php if ($crumbs) echo hyd_breadcrumbs($is_preview ?? false); ?>

        <div class="mk-paginakop__layout">
            <div class="mk-paginakop__text">
                <h1 class="mk-paginakop__title"><?php echo hyd_title_html($title, $accent); ?></h1>
                <?php if ($intro) : ?>
                    <div class="mk-paginakop__intro mk-richtext"><?php echo wp_kses_post($intro); ?></div>
                <?php endif; ?>
                <?php if ($button && $variant !== 'slider') : ?>
                    <div class="mk-paginakop__actions"><?php echo $button; ?></div>
                <?php endif; ?>
            </div>

            <?php if ($variant === 'afbeelding' && $image) : ?>
                <div class="mk-paginakop__media mk-photo">
                    <?php echo hyd_image($image, 'hyd-section', ['loading' => 'eager', 'sizes' => '(min-width: 900px) 45vw, 100vw']); ?>
                </div>
            <?php endif; ?>

            <?php if ($variant === 'slider') : ?>
                <div class="mk-paginakop__aside">
                    <?php if ($downloads) : ?>
                        <div class="mk-card mk-downloads mk-downloads--card">
                            <p class="mk-label-head"><?php echo esc_html(get_field('downloads_label') ?: __('Downloads', 'mk')); ?></p>
                            <?php get_template_part('template-parts/components/downloads-list', null, ['rows' => $downloads, 'columns' => 1]); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($button) : ?><div class="mk-paginakop__actions"><?php echo $button; ?></div><?php endif; ?>
                </div>
                <div class="mk-paginakop__media">
                    <?php get_template_part('template-parts/components/slider', null, ['ids' => $slides, 'label' => sprintf(__('Foto\'s van %s', 'mk'), wp_strip_all_tags($title)), 'eager' => true]); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
