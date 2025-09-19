<?php
add_action('after_setup_theme', function () {
  add_theme_support('title-tag');
  add_theme_support('custom-logo', [
    'height'      => 120,
    'width'       => 120,
    'flex-height' => true,
    'flex-width'  => true,
  ]);
});
add_action('wp_enqueue_scripts', function () {
  wp_enqueue_style('farset-sandbox-style', get_stylesheet_uri(), [], '0.0.2');
});
