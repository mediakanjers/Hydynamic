					<?php
						$mkbase_footer = apply_filters('mkbase_footer_variant', '');
						if ($mkbase_footer) {
							get_template_part('template-parts/footer/layouts/footer', $mkbase_footer); return;
						}
					?>

					<footer class="mk-footer">
						<?php get_template_part('template-parts/footer/layouts/footer-default'); ?>
					</footer>
			</div> <!-- #page_container -->

			<!--  Notifications from core theme -->
			<?php get_template_part('template-parts/notifications/notifications'); ?>

		<?php wp_footer(); ?>
	</body>
</html>
