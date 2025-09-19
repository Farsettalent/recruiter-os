<?php
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('farsettalent-style', get_stylesheet_uri(), [], '1.6');
});
?>