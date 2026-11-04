<?php
/**
 * Lijst met PDF-downloads.
 * $args: rows (array met titel + bestand-ID), columns (1-3)
 */
$rows    = array_filter((array) ($args['rows'] ?? []), fn($r) => !empty($r['titel']) || !empty($r['bestand']));
$columns = (int) ($args['columns'] ?? 1);
if (!$rows) return;
?>
<ul class="mk-downloads__list mk-downloads__list--cols-<?php echo esc_attr($columns); ?>">
    <?php foreach ($rows as $row) :
        $file_id = (int) ($row['bestand'] ?? 0);
        $url     = $file_id ? wp_get_attachment_url($file_id) : '';
        $title   = $row['titel'] ?: ($file_id ? get_the_title($file_id) : '');
        $size    = $file_id && ($path = get_attached_file($file_id)) && file_exists($path) ? size_format(filesize($path), 1) : '';
        $tag     = $url ? 'a' : 'span';
        ?>
        <li class="mk-downloads__item">
            <<?php echo $tag; ?> class="mk-downloads__link"<?php if ($url) : ?> href="<?php echo esc_url($url); ?>" download target="_blank" rel="noopener"<?php endif; ?>>
                <span class="mk-downloads__icon" aria-hidden="true">
                    <svg width="28" height="32" viewBox="0 0 28 32" fill="none"><path d="M6 1h12l8 8v20a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2z" stroke="#1C1D2B" stroke-width="1.6"/><path d="M18 1v8h8M10 18h10M10 23h10" stroke="#1C1D2B" stroke-width="1.6"/><rect x="0" y="6" width="13" height="7" rx="1" fill="#C8102E"/><text x="6.5" y="11.4" text-anchor="middle" font-family="Arial, sans-serif" font-size="5.4" font-weight="700" fill="#fff">PDF</text></svg>
                </span>
                <span class="mk-downloads__title">
                    <?php echo esc_html($title); ?>
                    <?php if ($size) : ?><span class="sr-only">(PDF, <?php echo esc_html($size); ?>)</span><?php endif; ?>
                </span>
                <span class="mk-downloads__action"><?php echo hyd_icon('download', 20); ?></span>
            </<?php echo $tag; ?>>
        </li>
    <?php endforeach; ?>
</ul>
