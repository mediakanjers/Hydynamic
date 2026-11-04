<nav class="mk-nav" id="mk-nav" aria-label="<?php esc_attr_e('Hoofdmenu', 'mk'); ?>">
    <div class="mk-nav__panel">
        <?php
        wp_nav_menu([
            'theme_location' => 'main_menu',
            'container'      => false,
            'menu_class'     => 'mk-nav__list',
            'depth'          => 3,
            'walker'         => new Hyd_Nav_Walker(),
            'fallback_cb'    => false,
        ]);
        ?>
        <div class="mk-nav__mobile-extra">
            <?php echo hyd_button(__('Offerte aanvragen', 'mk'), hyd_offerte_url(), ['offerte' => true, 'class' => 'mk-nav__cta']); ?>
            <a class="mk-nav__phone" href="<?php echo esc_attr(hyd_tel_href(hyd_company('telefoon'))); ?>"><?php echo hyd_icon('phone', 18); ?><?php echo esc_html(hyd_company('telefoon')); ?></a>
        </div>
    </div>
</nav>
