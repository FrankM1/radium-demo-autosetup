<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$grid = require_once(rda_get_plugin_slug() . '/data/portfolio/data/items/grid.php');
$carousel = require_once(rda_get_plugin_slug() . '/data/portfolio/data/items/carousel.php');
$media = require_once(rda_get_plugin_slug() . '/data/portfolio/data/items/media-types.php');
$formats = require_once(rda_get_plugin_slug() . '/data/portfolio/data/items/post-formats.php');

$posts = array_merge( $grid, $carousel );
$posts = array_merge( $posts, $media );
$posts = array_merge( $posts, $formats );

return $posts;
