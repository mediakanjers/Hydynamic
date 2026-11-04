<?php
/**
 * Header: logo, hoofdmenu (inline vanaf 1200px, daaronder uitklappaneel),
 * zoeken, offerteknop en taalkeuze (WPML).
 */
$header_classes = ['mk-header'];
if (is_front_page()) $header_classes[] = 'mk-header--overlay';
?>
<a class="skip-link" href="#main-content"><?php esc_html_e('Direct naar de inhoud', 'mk'); ?></a>

<header class="<?php echo esc_attr(implode(' ', $header_classes)); ?>" id="site-header">
    <div class="mk-header__inner">
        <a class="mk-header__logo" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php echo esc_attr(get_bloginfo('name') . ', ' . __('naar de homepage', 'mk')); ?>"<?php echo is_front_page() ? ' aria-current="page"' : ''; ?>>
            <img src="<?php echo esc_url(hyd_logo_url()); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" width="190" height="58">
        </a>

        <?php get_template_part('template-parts/header/nav-main'); ?>

        <div class="mk-header__actions">
            <button type="button" class="mk-header__icon-btn js-search-open" aria-controls="mk-search" aria-expanded="false" aria-label="<?php esc_attr_e('Zoeken', 'mk'); ?>">
                <?php echo hyd_icon('search', 26); ?>
            </button>

            <?php echo hyd_button(__('Offerte aanvragen', 'mk'), hyd_offerte_url(), ['size' => 'small', 'offerte' => true, 'class' => 'mk-header__cta']); ?>

            <?php get_template_part('template-parts/header/lang-switcher'); ?>

            <button type="button" class="mk-header__icon-btn mk-header__menu-btn js-menu-toggle" aria-controls="mk-nav" aria-expanded="false" aria-label="<?php esc_attr_e('Menu openen', 'mk'); ?>">
                <span class="mk-burger" aria-hidden="true"><span></span><span></span><span></span></span>
            </button>
        </div>
    </div>

    <?php get_template_part('template-parts/header/search'); ?>
</header>
