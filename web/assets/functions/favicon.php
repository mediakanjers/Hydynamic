<?php
/**
 * Favicon, app-iconen en web app manifest.
 *
 * Bestanden: /assets/images/favicon/ (gemaakt van het H-beeldmerk uit het logo).
 * - favicon.svg (moderne browsers), favicon.ico + 96px PNG (oudere browsers)
 * - apple-touch-icon.png (iOS, 180px)
 * - web-app-manifest-*.png (Android/PWA, incl. maskable variant)
 * - /site.webmanifest wordt dynamisch door WordPress geserveerd (naam, kleuren)
 *
 * Een "Site-pictogram" in Instellingen > Algemeen of het favicon-veld in
 * Mediakanjers > Website instellingen is dan niet nodig.
 */

if (!defined('ABSPATH')) exit;

function hyd_favicon_url($file) {
    return get_stylesheet_directory_uri() . '/assets/images/favicon/' . $file;
}

// <head>: icoon-, manifest- en themakleur-tags
add_action('wp_head', function () {
    $v = '2026-11-04';
    echo '<link rel="icon" type="image/png" href="' . esc_url(hyd_favicon_url('favicon-96x96.png?v=' . $v)) . '" sizes="96x96">' . "\n";
    echo '<link rel="icon" type="image/svg+xml" href="' . esc_url(hyd_favicon_url('favicon.svg?v=' . $v)) . '">' . "\n";
    echo '<link rel="shortcut icon" href="' . esc_url(hyd_favicon_url('favicon.ico?v=' . $v)) . '">' . "\n";
    echo '<link rel="apple-touch-icon" sizes="180x180" href="' . esc_url(hyd_favicon_url('apple-touch-icon.png?v=' . $v)) . '">' . "\n";
    echo '<meta name="apple-mobile-web-app-title" content="Hydynamic">' . "\n";
    echo '<link rel="manifest" href="' . esc_url(home_url('/site.webmanifest')) . '">' . "\n";
    echo '<meta name="theme-color" content="#1A1B29">' . "\n";
}, 2);

// WordPress' eigen site-icoon-tags niet dubbel tonen
remove_action('wp_head', 'wp_site_icon', 99);

// /favicon.ico (dat browsers automatisch opvragen) wijst naar ons icoon
add_filter('get_site_icon_url', function ($url, $size) {
    if ($url && strpos($url, 'wp-includes/images/w-logo') === false) return $url; // handmatig ingesteld site-icoon wint
    return hyd_favicon_url($size <= 32 ? 'favicon-32x32.png' : ($size <= 180 ? 'apple-touch-icon.png' : 'web-app-manifest-512x512.png'));
}, 10, 2);

// Ook in de admin en op het inlogscherm
foreach (['admin_head', 'login_head'] as $hook) {
    add_action($hook, function () {
        echo '<link rel="icon" type="image/svg+xml" href="' . esc_url(hyd_favicon_url('favicon.svg')) . '">' . "\n";
        echo '<link rel="icon" type="image/png" href="' . esc_url(hyd_favicon_url('favicon-96x96.png')) . '" sizes="96x96">' . "\n";
    });
}

// /site.webmanifest
add_action('parse_request', function () {
    $path = trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
    $home = trim(parse_url(home_url('/'), PHP_URL_PATH) ?? '', '/');
    if ($path !== ltrim($home . '/site.webmanifest', '/')) return;

    $manifest = [
        'name'             => get_bloginfo('name') ?: 'Hydynamic Holland BV',
        'short_name'       => 'Hydynamic',
        'description'      => get_bloginfo('description'),
        'lang'             => str_replace('_', '-', get_locale()),
        'start_url'        => home_url('/'),
        'scope'            => home_url('/'),
        'display'          => 'standalone',
        'background_color' => '#FFFFFF',
        'theme_color'      => '#1A1B29',
        'icons'            => [
            ['src' => hyd_favicon_url('web-app-manifest-192x192.png'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => hyd_favicon_url('web-app-manifest-512x512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => hyd_favicon_url('web-app-manifest-maskable-512x512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
        ],
    ];

    status_header(200);
    header('Content-Type: application/manifest+json; charset=utf-8');
    header('Cache-Control: public, max-age=86400');
    echo wp_json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}, 0);
