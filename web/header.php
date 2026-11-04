<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<?php wp_body_open(); ?>

<div id="page_container">

<?php
$mkbase_header = apply_filters('mkbase_header_variant', '');
if ($mkbase_header) {
    get_template_part('template-parts/header/layouts/header', $mkbase_header);
} else {
    get_template_part('template-parts/header/layouts/header-default');
}
?>
<div class="mk-nav-backdrop js-menu-close" hidden></div>
