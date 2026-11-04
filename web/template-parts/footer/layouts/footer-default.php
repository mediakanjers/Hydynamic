<?php
/**
 * Footer volgens ontwerp: bedrijfsnaam + juridische links, DNV-keurmerk,
 * adresgegevens, logo; onderaan sitemap en realisatie.
 */
$company = hyd_company('bedrijfsnaam');
$phone   = hyd_company('telefoon');
?>
<div class="mk-footer__inner">
    <div class="mk-footer__grid">
        <div class="mk-footer__col mk-footer__col--legal">
            <p><?php echo esc_html($company); ?> &copy; <?php echo esc_html(date_i18n('Y')); ?></p>
            <?php
            wp_nav_menu([
                'theme_location' => 'footer_menu',
                'container'      => false,
                'menu_class'     => 'mk-footer__menu',
                'depth'          => 1,
                'fallback_cb'    => false,
            ]);
            ?>
        </div>
        <div class="mk-footer__col mk-footer__col--keurmerk">
            <img src="<?php echo esc_url(hyd_brand_asset('dnv-iso-9001.svg')); ?>" alt="<?php esc_attr_e('DNV managementsysteemcertificaat ISO 9001', 'mk'); ?>" width="124" height="118" loading="lazy">
        </div>
        <address class="mk-footer__col mk-footer__col--adres">
            <?php echo esc_html(hyd_company('adres')); ?><br>
            <?php echo esc_html(hyd_company('postcode')); ?><br>
            <a href="<?php echo esc_attr(hyd_tel_href($phone)); ?>"><?php echo esc_html($phone); ?></a>
        </address>
        <div class="mk-footer__col mk-footer__col--logo">
            <a href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php echo esc_attr(get_bloginfo('name') . ', ' . __('naar de homepage', 'mk')); ?>">
                <img src="<?php echo esc_url(hyd_logo_url()); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" width="222" height="68" loading="lazy">
            </a>
        </div>
    </div>
    <div class="mk-footer__bottom">
        <p>&copy; <?php echo esc_html(date_i18n('Y')); ?> <a href="<?php echo esc_url(home_url('/sitemap_index.xml')); ?>"><?php esc_html_e('Sitemap', 'mk'); ?></a></p>
        <p><?php esc_html_e('Gerealiseerd door', 'mk'); ?> <a href="https://www.mediakanjers.nl" target="_blank" rel="noopener">Mediakanjers</a></p>
    </div>
</div>
