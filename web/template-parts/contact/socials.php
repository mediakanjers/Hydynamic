<div class="social-container">
    <?php global $stream_opts; ?>
    <h5>Socials</h5>
    <div class="socials">
        <?php if( have_rows('socials', 'options') ): while ( have_rows('socials', 'options') ) : the_row(); ?>
            <?php $social = get_sub_field('platform'); ?>
            <?php $icon = get_bloginfo('stylesheet_directory') . '/assets/images/socials/'.$social.'.svg'; ?>
            <?php if( file_exists(  ABSPATH . str_replace(get_bloginfo('url'). '/', '', $icon ) ) ) { ?>
                <a class="<?php echo esc_html($social);?>" target="_blank" href="<?php echo get_sub_field('url');?>">
                    <?php echo file_get_contents( $icon ,  false, stream_context_create($stream_opts)); ?>
                </a>
            <?php } ?>
        <?php endwhile; endif; ?>
    </div>
</div>