<?php
/**
 * Blok: Homepage hero – overgenomen van de live site.
 */
$bg     = get_field('achtergrondfoto');
$title  = get_field('titel') ?: get_bloginfo('description');
$tiles  = get_field('tegels') ?: [];
$anchor = !empty($block['anchor']) ? ' id="' . esc_attr($block['anchor']) . '"' : '';
?>
<section class="mk-home-hero<?php echo !empty($block['className']) ? ' ' . esc_attr($block['className']) : ''; ?>"<?php echo $anchor; ?>>
    <?php if ($bg) : ?>
        <div class="mk-home-hero__bg" aria-hidden="true"><?php echo hyd_image($bg, 'hyd-hero', ['loading' => 'eager', 'sizes' => '100vw', 'alt' => '']); ?></div>
    <?php endif; ?>
    <div class="mk-container">
        <h1 class="mk-home-hero__title"><?php echo esc_html($title); ?></h1>
        <?php if ($tiles) : ?>
            <ul class="mk-home-hero__tiles">
                <?php foreach ($tiles as $tile) :
                    $link = $tile['link'] ?: [];
                    $url  = $link['url'] ?? '';
                    ?>
                    <li class="mk-home-tile">
                        <?php if (!empty($tile['icoon'])) : ?>
                            <span class="mk-home-tile__icon" aria-hidden="true"><?php echo hyd_image($tile['icoon'], 'thumbnail', ['alt' => '', 'width' => 40, 'height' => 40]); ?></span>
                        <?php endif; ?>
                        <h2 class="mk-home-tile__title">
                            <?php if ($url) : ?>
                                <a class="mk-home-tile__link" href="<?php echo esc_url($url); ?>"<?php echo ($link['target'] ?? '') === '_blank' ? ' target="_blank" rel="noopener"' : ''; ?>><?php echo esc_html($tile['titel']); ?></a>
                            <?php else : echo esc_html($tile['titel']); endif; ?>
                            <?php echo hyd_icon('arrow-right', 30, 'mk-home-tile__arrow'); ?>
                        </h2>
                        <?php if ($tile['tekst']) : ?><p class="mk-home-tile__text"><?php echo esc_html($tile['tekst']); ?></p><?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</section>
