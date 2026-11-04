<?php
/**
 * Blok: Kennisbank – nieuwste artikelen (berichten) als tegels, zoals op de live site.
 */
$title    = get_field('titel') ?: __('Kennisbank', 'mk');
$subtitle = get_field('subtitel');
$count    = (int) (get_field('aantal') ?: 4);
$cat      = get_field('categorie');
$manual   = get_field('artikelen') ?: [];
$btn_text = get_field('knoptekst') ?: __('Bekijk alle artikelen', 'mk');
$kb_page  = (int) get_option('page_for_posts');
$kb_url   = $kb_page ? get_permalink($kb_page) : home_url('/kennisbank/');

$args = [
    'post_type'           => 'post',
    'post_status'         => 'publish',
    'posts_per_page'      => $count,
    'ignore_sticky_posts' => true,
    'no_found_rows'       => true,
];
if ($manual) {
    $args['post__in'] = array_map('intval', $manual);
    $args['orderby']  = 'post__in';
} elseif ($cat) {
    $args['cat'] = (int) $cat;
}
if (is_singular('post')) $args['post__not_in'] = [get_the_ID()];

$query = new WP_Query($args);
if (!$query->have_posts()) {
    if ($is_preview ?? false) echo '<p class="mk-block-empty">Kennisbank: er zijn nog geen artikelen.</p>';
    return;
}
?>
<section <?php echo hyd_section_attrs($block, 'mk-kennisbank', 'verloop'); ?>>
    <div class="mk-container">
        <div class="mk-kennisbank__head">
            <div>
                <h2 class="mk-label-head mk-kennisbank__title"><?php echo esc_html($title); ?></h2>
                <?php if ($subtitle) : ?><p class="mk-kennisbank__subtitle"><?php echo esc_html($subtitle); ?></p><?php endif; ?>
            </div>
            <?php echo hyd_button($btn_text, $kb_url); ?>
        </div>
        <div class="mk-kennisbank__grid">
            <?php while ($query->have_posts()) : $query->the_post(); get_template_part('template-parts/cards/article'); endwhile; wp_reset_postdata(); ?>
        </div>
    </div>
</section>
