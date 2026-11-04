<?php
/**
 * Overzichten: kennisbank (berichtenpagina), categorie- en archiefpagina's.
 */
get_header();

$kb_id = (int) get_option('page_for_posts');
if (is_home() && $kb_id) {
	$title = get_the_title($kb_id);
	$intro = get_post_field('post_excerpt', $kb_id);
} else {
	$title = wp_strip_all_tags(get_the_archive_title());
	$intro = get_the_archive_description();
}
?>
<main id="main-content" class="vervolgpagina" tabindex="-1">
	<section class="mk-section mk-bg--pagina mk-paginakop mk-paginakop--compact">
		<img class="mk-paginakop__lines" src="<?php echo esc_url(hyd_brand_asset('achtergrond-h-lijnen.svg')); ?>" alt="" aria-hidden="true" width="1500" height="880">
		<div class="mk-container">
			<?php echo hyd_breadcrumbs(); ?>
			<div class="mk-paginakop__text">
				<h1 class="mk-paginakop__title"><?php echo esc_html($title ?: __('Kennisbank', 'mk')); ?></h1>
				<?php if ($intro) : ?><div class="mk-paginakop__intro"><?php echo wp_kses_post(wpautop($intro)); ?></div><?php endif; ?>
			</div>
		</div>
	</section>

	<section class="mk-section mk-bg--verloop mk-space--normaal mk-kennisbank">
		<div class="mk-container">
			<?php if (have_posts()) : ?>
				<div class="mk-kennisbank__grid mk-kennisbank__grid--archive">
					<?php while (have_posts()) : the_post(); get_template_part('template-parts/cards/article'); endwhile; ?>
				</div>
				<?php
				the_posts_pagination([
					'mid_size'           => 1,
					'prev_text'          => hyd_icon('arrow-left', 18) . '<span class="sr-only">' . __('Vorige pagina', 'mk') . '</span>',
					'next_text'          => '<span class="sr-only">' . __('Volgende pagina', 'mk') . '</span>' . hyd_icon('arrow-right', 18),
					'class'              => 'mk-pagination',
					'screen_reader_text' => __('Paginering', 'mk'),
				]);
				?>
			<?php else : ?>
				<p><?php esc_html_e('Er zijn nog geen artikelen gepubliceerd.', 'mk'); ?></p>
			<?php endif; ?>
		</div>
	</section>

	<?php echo do_blocks('<!-- wp:mk/offerte {"name":"mk/offerte","data":{},"mode":"preview"} /-->'); ?>
</main>
<?php get_footer(); ?>
