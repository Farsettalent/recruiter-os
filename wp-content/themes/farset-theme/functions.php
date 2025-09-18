<?php
add_action('wp_enqueue_scripts', function () {
  wp_enqueue_style('farset-style', get_stylesheet_uri(), [], '0.0.1');
});
