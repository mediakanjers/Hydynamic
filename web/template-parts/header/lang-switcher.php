<?php
/**
 * Taalkeuze: hydynamic.nl (Nederlands) ↔ hydynamic.com (English).
 * Linkt naar de vertaalde versie van de huidige pagina, of anders naar de
 * homepage van de andere taal.
 */
$links   = function_exists('hyd_language_links') ? hyd_language_links() : [];
$current = null;
foreach ($links as $code => $l) {
    if ($l['active']) $current = array_merge($l, ['code' => $code]);
}
if (!$current) return;
$others = array_filter($links, fn($l) => !$l['active']);

$flags = [
    'nl' => '<rect width="9" height="2" fill="#AE1C28"/><rect y="2" width="9" height="2" fill="#FFFFFF"/><rect y="4" width="9" height="2" fill="#21468B"/>',
    'en' => '<rect width="9" height="6" fill="#012169"/><path d="M0 0l9 6M9 0L0 6" stroke="#fff" stroke-width="1.2"/><path d="M0 0l9 6M9 0L0 6" stroke="#C8102E" stroke-width=".5"/><path d="M4.5 0v6M0 3h9" stroke="#fff" stroke-width="2"/><path d="M4.5 0v6M0 3h9" stroke="#C8102E" stroke-width="1.1"/>',
];
$flag = function ($c) use ($flags) {
    return '<svg class="mk-lang__flag" width="28" height="19" viewBox="0 0 9 6" aria-hidden="true" focusable="false">' . ($flags[$c] ?? $flags['nl']) . '</svg>';
};
?>
<div class="mk-lang">
    <button type="button" class="mk-lang__btn js-lang-toggle" aria-expanded="false" aria-controls="mk-lang-list"
            aria-label="<?php echo esc_attr(sprintf(__('Taal kiezen, huidige taal %s', 'mk'), $current['label'])); ?>">
        <?php echo $flag($current['flag']); ?>
        <?php echo hyd_icon('chevron-down', 12); ?>
    </button>
    <ul class="mk-lang__list" id="mk-lang-list" hidden>
        <?php foreach ($others as $code => $l) : ?>
            <li>
                <a href="<?php echo esc_url($l['url']); ?>" hreflang="<?php echo esc_attr($l['hreflang']); ?>" lang="<?php echo esc_attr($l['hreflang']); ?>">
                    <?php echo $flag($l['flag']); ?>
                    <span><?php echo esc_html($l['label']); ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
