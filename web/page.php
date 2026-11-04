<?php get_header(); ?>
	<main id="main-content" class="<?php echo is_front_page() ? 'voorpagina' : 'vervolgpagina'; ?>" tabindex="-1">
		<?php while (have_posts()) : the_post(); ?>
			<article id="post-<?php the_ID(); ?>" <?php post_class('mk-entry'); ?>>
				<div class="entry-content">
					<?php the_content(); ?>
				</div>
			</article>
		<?php endwhile; ?>
	</main>
<?php get_footer(); ?>
