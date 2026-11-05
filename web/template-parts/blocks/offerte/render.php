<?php
/**
 * Blok: Offerteformulier (Gravity Forms)
 * Heeft altijd id="offerte": alle "Offerte aanvragen"-knoppen scrollen hierheen.
 */
$title    = get_field('titel') ?: __('Start vandaag met een oplossing die werkt.', 'mk');
$accent   = get_field('titel') ? get_field('accent') : __('die werkt.', 'mk');
$subline  = get_field('subregel') ?: __('Vraag vrijblijvend een offerte op maat aan.', 'mk');
$side     = get_field('zijkant') ?: 'contact';
$route    = get_field('routefoto');
$form_id  = (int) (get_field('formulier') ?: hyd_opt('offerte_formulier'));
$phone    = hyd_company('telefoon');
$email    = hyd_company('email');
$card_t   = hyd_opt('contactkaart_titel') ?: __('Liever direct contact?', 'mk');
$card_a   = hyd_opt('contactkaart_accent') ?: __('contact?', 'mk');
$open     = hyd_is_open_now();

// "vrijblijvend" / "no-obligation" groen, zoals in het ontwerp
$subline_html = preg_replace('/\b(vrijblijvend|no-obligation)\b/u', '<span class="mk-offerte__green">$1</span>', esc_html($subline), 1);
?>
<section class="mk-section mk-bg--contact mk-space--normaal mk-offerte mk-offerte--<?php echo esc_attr($side); ?><?php echo !empty($block['className']) ? ' ' . esc_attr($block['className']) : ''; ?>" id="offerte" aria-labelledby="offerte-titel" tabindex="-1">
    <img class="mk-offerte__lines" src="<?php echo esc_url(hyd_brand_asset('logo-element-licht.svg')); ?>" alt="" aria-hidden="true" width="1837" height="856">
    <div class="mk-container mk-offerte__layout">
        <div class="mk-offerte__intro">
            <h2 class="mk-offerte__title" id="offerte-titel"><?php echo hyd_title_html($title, $accent); ?></h2>
            <p class="mk-offerte__subline"><?php echo hyd_icon('check-box', 28, 'mk-offerte__checkbox'); ?><span><?php echo $subline_html; ?></span></p>

            <?php if ($side === 'route' && $route) : ?>
                <div class="mk-offerte__route mk-photo">
                    <?php echo hyd_image($route, 'hyd-section', ['sizes' => '(min-width: 900px) 40vw, 100vw']); ?>
                    <?php echo hyd_button(__('Plan route in Google Maps', 'mk'), 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode(hyd_company('adres') . ', ' . hyd_company('postcode')), ['target' => '_blank', 'class' => 'mk-offerte__route-btn']); ?>
                </div>
            <?php else : ?>
                <div class="mk-offerte__card">
                    <p class="mk-offerte__card-title"><?php echo hyd_title_html($card_t, $card_a); ?></p>
                    <a class="mk-offerte__contact" href="<?php echo esc_attr(hyd_tel_href($phone)); ?>">
                        <span class="mk-offerte__contact-icon"><?php echo hyd_icon('phone', 15); ?></span>
                        <?php echo esc_html($phone); ?>
                        <span class="mk-status-dot<?php echo $open ? ' is-open' : ''; ?>" title="<?php echo esc_attr($open ? __('Nu bereikbaar', 'mk') : __('Nu gesloten', 'mk')); ?>"><span class="sr-only"><?php echo esc_html($open ? __('nu bereikbaar', 'mk') : __('nu gesloten', 'mk')); ?></span></span>
                    </a>
                    <a class="mk-offerte__contact" href="mailto:<?php echo esc_attr(antispambot($email)); ?>">
                        <span class="mk-offerte__contact-icon"><?php echo hyd_icon('mail', 15); ?></span>
                        <?php echo esc_html(antispambot($email)); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <div class="mk-offerte__form">
            <?php
            if ($form_id && function_exists('gravity_form')) {
                gravity_form($form_id, false, false, false, null, true, 0, true);
            } elseif ($is_preview ?? false) {
                echo '<p class="mk-block-empty">Kies een Gravity Forms-formulier in het blok of onder Mediakanjers &gt; Website instellingen.</p>';
            }
            ?>
        </div>
    </div>
</section>
