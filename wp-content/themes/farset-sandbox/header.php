<!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<header style="display:flex;align-items:center;gap:12px;padding:12px 16px;border-bottom:1px solid #eee;">
  <div><?php if (function_exists('the_custom_logo')) the_custom_logo(); ?></div>
  <div style="font-weight:700;font-size:20px;">Recruiter OS</div>
</header>

<section style="padding:28px 16px;background:#f7f7f7;border-bottom:1px solid #eee;">
  <div style="max-width:960px;margin:0 auto;display:flex;justify-content:space-between;align-items:center;gap:16px;">
    <h2 style="margin:0;">NI Hiring, made simple</h2>
    <a href="/contact" style="padding:10px 14px;border:1px solid #111;text-decoration:none;">Book a call</a>
  </div>
</section>
