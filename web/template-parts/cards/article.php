<?php
/**
 * Kennisbank-artikeltegel (zelfde opbouw als de live site):
 * foto, auteur + datum, titel (H3) en "Lees meer".
 */
$heading = $args['heading'] ?? 'h3';
?>
<article class="mk-article-card">
	<a class="mk-article-card__image" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
		<?php if (has_post_thumbnail()) {
			the_post_thumbnail('medium_large', ['loading' => 'lazy', 'decoding' => 'async', 'alt' => '', 'sizes' => '(min-width: 1200px) 25vw, (min-width: 600px) 50vw, 85vw']);
		} ?>
	</a>
	<p class="mk-article-card__meta">
		<span><?php the_author(); ?></span>
		<span class="mk-dot" aria-hidden="true"></span>
		<time datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date()); ?></time>
	</p>
	<<?php echo $heading; ?> class="mk-article-card__title"><?php the_title(); ?></<?php echo $heading; ?>>
	<a class="mk-textlink" href="<?php the_permalink(); ?>">
		<?php esc_html_e('Lees meer', 'mk'); ?><?php echo hyd_icon('arrow-right', 16); ?>
		<span class="sr-only"><?php printf(esc_html__('over %s', 'mk'), get_the_title()); ?></span>
	</a>
</article>
