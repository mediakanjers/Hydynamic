<?php
/**
 * Zoekresultaten. Yoast zet zoekpagina's standaard op noindex; de pagina is wel
 * volledig server-side gerenderd en bruikbaar zonder JavaScript.
 */
get_header();
global $wp_query;
$labels = [
	'page'        => __('Pagina', 'mk'),
	'post'        => __('Kennisbank', 'mk'),
	'producten'   => __('Product', 'mk'),
	'oplossingen' => __('Oplossing', 'mk'),
	'diensten'    => __('Dienst', 'mk'),
];
?>
<main id="main-content" class="vervolgpagina" tabindex="-1">
	<section class="mk-section mk-bg--pagina mk-paginakop mk-paginakop--compact">
		<img class="mk-paginakop__lines" src="<?php echo esc_url(hyd_brand_asset('logo-element-licht.svg')); ?>" alt="" aria-hidden="true" width="1837" height="856">
		<div class="mk-container">
			<?php echo hyd_breadcrumbs(); ?>
			<div class="mk-paginakop__text">
				<h1 class="mk-paginakop__title">
					<?php if (get_search_query()) : ?>
						<?php esc_html_e('Zoekresultaten voor', 'mk'); ?> <span class="accent">“<?php echo esc_html(get_search_query()); ?>”</span>
					<?php else : esc_html_e('Zoeken', 'mk'); endif; ?>
				</h1>
				<form class="mk-searchpage__form" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
					<label class="sr-only" for="mk-searchpage-input"><?php esc_html_e('Zoekterm', 'mk'); ?></label>
					<input id="mk-searchpage-input" class="mk-field" type="search" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="<?php esc_attr_e('Waar bent u naar op zoek?', 'mk'); ?>">
					<button class="mk-button mk-button--primary" type="submit"><span><?php esc_html_e('Zoeken', 'mk'); ?></span><?php echo hyd_icon('search', 18); ?></button>
				</form>
				<?php if (get_search_query()) : ?>
					<p class="mk-searchpage__count"><?php printf(esc_html(_n('%d resultaat gevonden', '%d resultaten gevonden', $wp_query->found_posts, 'mk')), (int) $wp_query->found_posts); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<section class="mk-section mk-bg--wit mk-space--normaal">
		<div class="mk-container mk-container--narrow">
			<?php if (have_posts()) : ?>
				<ol class="mk-searchpage__list">
					<?php while (have_posts()) : the_post(); $type = get_post_type(); ?>
						<li class="mk-searchpage__item">
							<span class="mk-searchpage__type"><?php echo esc_html($labels[$type] ?? ''); ?></span>
							<h2 class="mk-searchpage__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
							<p class="mk-searchpage__excerpt"><?php echo esc_html(wp_trim_words(hyd_search_excerpt(get_the_ID()), 28)); ?></p>
						</li>
					<?php endwhile; ?>
				</ol>
				<?php the_posts_pagination(['class' => 'mk-pagination', 'prev_text' => hyd_icon('arrow-left', 18), 'next_text' => hyd_icon('arrow-right', 18)]); ?>
			<?php else : ?>
				<p><?php esc_html_e('Geen resultaten gevonden. Probeer een andere zoekterm of neem contact met ons op.', 'mk'); ?></p>
			<?php endif; ?>
		</div>
	</section>

	<?php echo do_blocks('<!-- wp:mk/offerte {"name":"mk/offerte","data":{},"mode":"preview"} /-->'); ?>
</main>
<?php get_footer(); ?>
