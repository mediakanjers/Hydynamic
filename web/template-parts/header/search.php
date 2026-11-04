<?php
/**
 * Zoekpaneel. Werkt zonder JavaScript als gewoon GET-formulier naar /?s=
 * (crawlbare, deelbare resultaatpagina). Met JS verschijnen live suggesties
 * via de WordPress REST API.
 */
$search_id = 'mk-search-input';
?>
<div class="mk-search" id="mk-search" hidden>
    <div class="mk-search__inner">
        <form class="mk-search__form" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
            <label class="sr-only" for="<?php echo esc_attr($search_id); ?>"><?php esc_html_e('Zoeken op de website', 'mk'); ?></label>
            <span class="mk-search__icon"><?php echo hyd_icon('search', 22); ?></span>
            <input class="mk-search__input js-search-input" id="<?php echo esc_attr($search_id); ?>" type="search" name="s"
                   value="<?php echo esc_attr(get_search_query()); ?>"
                   placeholder="<?php esc_attr_e('Zoek op product, oplossing of artikel…', 'mk'); ?>"
                   autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="mk-search-results">
            <button class="mk-button mk-button--primary mk-search__submit" type="submit"><span><?php esc_html_e('Zoeken', 'mk'); ?></span></button>
            <button class="mk-search__close js-search-close" type="button" aria-label="<?php esc_attr_e('Zoeken sluiten', 'mk'); ?>"><?php echo hyd_icon('close', 22); ?></button>
        </form>
        <div class="mk-search__results" id="mk-search-results" role="listbox" aria-label="<?php esc_attr_e('Zoeksuggesties', 'mk'); ?>"></div>
        <p class="mk-search__status sr-only" aria-live="polite"></p>
    </div>
</div>
