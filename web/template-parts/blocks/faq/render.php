<?php
/**
 * Blok: Veelgestelde vragen
 * Uitklappen via <details>/<summary>: werkt zonder JS en de antwoorden staan
 * in de HTML (vindbaar). Optioneel FAQPage-schema voor rich results.
 */
$title  = get_field('titel') ?: __('Veelgestelde vragen', 'mk');
$items  = array_filter(get_field('vragen') ?: [], fn($q) => !empty($q['vraag']));
$link   = get_field('knop_link');
$schema = get_field('schema');
$schema = $schema === null ? true : (bool) $schema;

if (!$items) {
    if ($is_preview ?? false) echo '<p class="mk-block-empty">Veelgestelde vragen: voeg vragen toe.</p>';
    return;
}
$uid = 'faq-' . substr(md5(($block['id'] ?? '') . $title), 0, 6);
?>
<section <?php echo hyd_section_attrs($block, 'mk-faq', 'donker'); ?>>
    <img class="mk-faq__lines" src="<?php echo esc_url(hyd_brand_asset('achtergrond-h-lijnen.svg')); ?>" alt="" aria-hidden="true" width="1500" height="880">
    <div class="mk-container mk-container--faq">
        <h2 class="mk-label-head mk-faq__title" id="<?php echo esc_attr($uid); ?>"><?php echo esc_html($title); ?></h2>
        <div class="mk-faq__list" aria-labelledby="<?php echo esc_attr($uid); ?>">
            <?php foreach (array_values($items) as $i => $item) : ?>
                <details class="mk-faq__item">
                    <summary class="mk-faq__question">
                        <span class="mk-faq__nr" aria-hidden="true"><?php echo (int) $i + 1; ?></span>
                        <span class="mk-faq__q"><?php echo esc_html($item['vraag']); ?></span>
                        <span class="mk-faq__icon" aria-hidden="true"><?php echo hyd_icon('arrow-down', 24); ?></span>
                    </summary>
                    <div class="mk-faq__answer mk-richtext"><?php echo wp_kses_post($item['antwoord']); ?></div>
                </details>
            <?php endforeach; ?>
        </div>
        <?php if (!empty($link['url'])) : ?>
            <div class="mk-faq__actions"><?php echo hyd_button($link['title'] ?: __('Bekijk alle veelgestelde vragen', 'mk'), $link['url'], ['target' => $link['target'] ?? '']); ?></div>
        <?php endif; ?>
    </div>
</section>
<?php
if ($schema && !($is_preview ?? false)) {
    $entities = [];
    foreach ($items as $item) {
        if (empty($item['antwoord'])) continue;
        $entities[] = [
            '@type'          => 'Question',
            'name'           => wp_strip_all_tags($item['vraag']),
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => wp_kses($item['antwoord'], ['p' => [], 'br' => [], 'strong' => [], 'a' => ['href' => []], 'ul' => [], 'ol' => [], 'li' => []])],
        ];
    }
    if ($entities) {
        echo '<script type="application/ld+json">' . wp_json_encode(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $entities], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
    }
}
