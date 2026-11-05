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

			<?php echo hyd_button(__('Offerte aanvragen', 'mk'), hyd_offerte_page_url(), ['icon' => 'file', 'class' => 'mk-float-offerte js-float-offerte']); ?>

			<button type="button" class="mk-to-top js-to-top" aria-label="<?php esc_attr_e('Terug naar boven', 'mk'); ?>"><?php echo hyd_icon('chevron-up', 22); ?></button>

			<!--  Notifications from core theme -->
			<?php get_template_part('template-parts/notifications/notifications'); ?>

		<?php wp_footer(); ?>
	</body>
</html>
