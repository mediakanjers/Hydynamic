<?php
/**
 * Taalkeuze via WPML. Zonder WPML wordt alleen de huidige taal (NL) getoond,
 * zodat de header gelijk blijft aan het ontwerp.
 */
$languages = apply_filters('wpml_active_languages', null, ['skip_missing' => 0, 'orderby' => 'custom']);
$current   = null;
if (!empty($languages)) {
    foreach ($languages as $lang) {
        if (!empty($lang['active'])) $current = $lang;
    }
}
$code = $current ? $current['language_code'] : 'nl';

$flags = [
    'nl' => '<rect width="9" height="2" fill="#AE1C28"/><rect y="2" width="9" height="2" fill="#FFFFFF"/><rect y="4" width="9" height="2" fill="#21468B"/>',
    'de' => '<rect width="9" height="2" fill="#000"/><rect y="2" width="9" height="2" fill="#DD0000"/><rect y="4" width="9" height="2" fill="#FFCE00"/>',
    'en' => '<rect width="9" height="6" fill="#012169"/><path d="M0 0l9 6M9 0L0 6" stroke="#fff" stroke-width="1.2"/><path d="M0 0l9 6M9 0L0 6" stroke="#C8102E" stroke-width=".5"/><path d="M4.5 0v6M0 3h9" stroke="#fff" stroke-width="2"/><path d="M4.5 0v6M0 3h9" stroke="#C8102E" stroke-width="1.1"/>',
    'fr' => '<rect width="3" height="6" fill="#002395"/><rect x="3" width="3" height="6" fill="#fff"/><rect x="6" width="3" height="6" fill="#ED2939"/>',
];
$flag = function ($c) use ($flags) {
    $inner = $flags[$c] ?? $flags['nl'];
    return '<svg class="mk-lang__flag" width="28" height="19" viewBox="0 0 9 6" aria-hidden="true" focusable="false">' . $inner . '</svg>';
};
$names = ['nl' => 'Nederlands', 'en' => 'English', 'de' => 'Deutsch', 'fr' => 'Français'];
$label = $current['native_name'] ?? ($names[$code] ?? strtoupper($code));
$others = array_filter((array) $languages, fn($l) => empty($l['active']));
?>
<div class="mk-lang">
    <button type="button" class="mk-lang__btn js-lang-toggle" aria-expanded="false" aria-controls="mk-lang-list"
            aria-label="<?php echo esc_attr(sprintf(__('Taal kiezen, huidige taal %s', 'mk'), $label)); ?>"<?php echo empty($others) ? ' disabled' : ''; ?>>
        <?php echo $flag($code); ?>
        <?php echo hyd_icon('chevron-down', 12); ?>
    </button>
    <?php if (!empty($others)) : ?>
        <ul class="mk-lang__list" id="mk-lang-list" hidden>
            <?php foreach ($others as $lang) : ?>
                <li>
                    <a href="<?php echo esc_url($lang['url']); ?>" hreflang="<?php echo esc_attr($lang['language_code']); ?>" lang="<?php echo esc_attr($lang['language_code']); ?>">
                        <?php echo $flag($lang['language_code']); ?>
                        <span><?php echo esc_html($lang['native_name']); ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
