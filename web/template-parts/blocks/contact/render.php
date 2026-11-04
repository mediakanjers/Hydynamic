<?php
/**
 * Blok: Contactgegevens (contactpagina)
 * Adres en telefoon komen uit Mediakanjers > Website instellingen.
 */
$title      = get_field('titel') ?: __('Contactgegevens', 'mk');
$hours      = get_field('openingstijden') ?: __('Maandag t/m vrijdag 8:00 - 17:00', 'mk');
$depts      = array_filter(get_field('afdelingen') ?: [], fn($d) => !empty($d['email']));
$phone      = hyd_company('telefoon');
$company    = hyd_company('bedrijfsnaam');
?>
<section <?php echo hyd_section_attrs($block, 'mk-contact', 'grijs'); ?>>
    <div class="mk-container mk-contact__layout">
        <div class="mk-card mk-contact__card">
            <h2 class="mk-contact__title"><?php echo esc_html($title); ?></h2>
            <div class="mk-contact__row">
                <span class="mk-icon-circle"><?php echo hyd_icon('map-pin', 18); ?></span>
                <address>
                    <strong><?php esc_html_e('Bezoek- en postadres', 'mk'); ?></strong><br>
                    <?php echo esc_html($company); ?><br>
                    <?php echo esc_html(hyd_company('adres')); ?><br>
                    <?php echo esc_html(trim(str_replace('(NL)', '', hyd_company('postcode')))); ?><br>
                    <?php esc_html_e('Nederland', 'mk'); ?>
                </address>
            </div>
            <div class="mk-contact__row">
                <span class="mk-icon-circle"><?php echo hyd_icon('clock', 18); ?></span>
                <p><strong><?php esc_html_e('Openingstijden', 'mk'); ?></strong><br><?php echo esc_html($hours); ?></p>
            </div>
            <div class="mk-contact__call">
                <a class="mk-button mk-button--primary mk-button--block" href="<?php echo esc_attr(hyd_tel_href($phone)); ?>"><?php echo hyd_icon('phone', 18); ?><span><?php printf(esc_html__('Bel %s', 'mk'), esc_html($phone)); ?></span></a>
            </div>
        </div>

        <?php if ($depts) : ?>
            <div class="mk-card mk-contact__card mk-contact__card--depts">
                <ul class="mk-contact__depts">
                    <?php foreach ($depts as $dept) : $mail = antispambot($dept['email']); ?>
                        <li class="mk-contact__dept">
                            <span class="mk-icon-circle"><?php echo hyd_icon($dept['icoon'] ?: 'info', 19); ?></span>
                            <div>
                                <h3 class="mk-contact__dept-title"><?php echo esc_html($dept['titel']); ?></h3>
                                <?php if ($dept['tekst']) : ?><p class="mk-contact__dept-text"><?php echo esc_html($dept['tekst']); ?></p><?php endif; ?>
                                <a class="mk-contact__mail" href="mailto:<?php echo esc_attr($mail); ?>"><?php echo esc_html($mail); ?></a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</section>
