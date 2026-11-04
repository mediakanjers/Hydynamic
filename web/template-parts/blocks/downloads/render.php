<?php
/**
 * Blok: Downloads (witte kaart met PDF-lijst, 3 kolommen op desktop)
 */
$rows = get_field('bestanden') ?: [];
if (!$rows) {
    if ($is_preview ?? false) echo '<p class="mk-block-empty">Downloads: voeg bestanden toe.</p>';
    return;
}
?>
<section <?php echo hyd_section_attrs($block, 'mk-downloads-block', 'grijs'); ?>>
    <div class="mk-container">
        <div class="mk-card mk-downloads">
            <p class="mk-label-head"><?php echo esc_html(get_field('label') ?: __('Downloads', 'mk')); ?></p>
            <?php get_template_part('template-parts/components/downloads-list', null, ['rows' => $rows, 'columns' => 3]); ?>
        </div>
    </div>
</section>
