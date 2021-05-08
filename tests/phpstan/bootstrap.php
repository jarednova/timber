<?php

define( 'WP_CONTENT_DIR', '' );
define( 'WP_CONTENT_URL', '' );
define( 'TIMBER_LOC', '' );

function wpml_object_id_filter($locations, $nav_menu) { return 'string'; }
function get_coauthors($id) { return array(); }
function get_field($selector, $post_id = false, $format_value = true) { return 'string'; }
function get_field_object($selector, $post_id = false, $format_value = true, $load_value = true) { return new \WP_Post(0); }
function update_field($selector, $value, $post_id = false) { return $selector === ''; }
function get_fields($post_id = false, $format_value = true) { return array(); }

// Loads twig_array_filter()
new Twig\Extension\CoreExtension();
