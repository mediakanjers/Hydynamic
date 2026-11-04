<?php
/**
 * Enkelvoudige berichten.
 * - Producten, oplossingen en diensten worden volledig met blokken opgebouwd.
 * - Kennisbankartikelen (post) krijgen een vaste artikelopbouw.
 */
get_header();

if (get_post_type() !== 'post') : ?>
	<main id="main-content" class="vervolgpagina" tabindex="-1">
		<?php while (have_posts()) : the_post(); ?>
			<article id="post-<?php the_ID(); ?>" <?php post_class('mk-entry'); ?>>
				<div class="entry-content"><?php the_content(); ?></div>
			</article>
		<?php endwhile; ?>
	</main>
<?php else : ?>
	<main id="main-content" class="vervolgpagina" tabindex="-1">
		<?php while (have_posts()) : the_post(); ?>
			<article id="post-<?php the_ID(); ?>" <?php post_class('mk-article'); ?>>
				<header class="mk-section mk-bg--pagina mk-article__head">
					<div class="mk-container mk-container--narrow">
						<?php echo hyd_breadcrumbs(); ?>
						<h1 class="mk-article__title"><?php the_title(); ?></h1>
						<p class="mk-article__meta">
							<span><?php the_author(); ?></span>
							<span class="mk-dot" aria-hidden="true"></span>
							<time datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date()); ?></time>
						</p>
					</div>
				</header>
				<?php if (has_post_thumbnail()) : ?>
					<div class="mk-container mk-container--narrow mk-article__image">
						<?php the_post_thumbnail('large', ['loading' => 'eager', 'fetchpriority' => 'high']); ?>
					</div>
				<?php endif; ?>
				<div class="mk-container mk-container--narrow mk-article__body mk-richtext">
					<?php the_content(); ?>
				</div>
			</article>
		<?php endwhile; ?>

		<?php
		// Vaste afsluiting: gerelateerde kennisbankartikelen + offerteformulier
		echo do_blocks('<!-- wp:mk/kennisbank {"name":"mk/kennisbank","data":{"titel":"Meer uit de kennisbank","aantal":"4"},"mode":"preview"} /-->');
		echo do_blocks('<!-- wp:mk/offerte {"name":"mk/offerte","data":{},"mode":"preview"} /-->');
		?>
	</main>
<?php endif;

get_footer();
