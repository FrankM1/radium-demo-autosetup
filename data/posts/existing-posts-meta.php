<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$products = array(
    'all' => array(
        '_radium_primary_category' => '',
    ),
    'standard' => array(
        '_radium_featured' => '0', //leave  the value empty
        '_radium_editors_pick' => '0', //leave the value empty
        '_radium_carousel_slider' => '0', //leave the value empty
        '_radium_featured_image_size' => 'wide',
        '_radium_featured_image_aspect_ratio' => '',
        '_radium_source_title' => 'Attribution Title',
        '_radium_source_url' => '#',
    ),
    'video' => array(
        '_radium_video_id' => ''
    ),
    'audio' => array(
        '_radium_featured_image_size' => 'none',
        '_radium_playlist_id' => rda_get_random_cue_playlist_id(),
        '_radium_poster' => '',
    ),
    'gallery' => array(
        '_radium_featured_image_size' => 'none',
        '_radium_gallery_images' => '',
    ),
    'aside' => array(
        '_radium_featured_image_size' => 'none',
    ),
    'chat' => array(
        '_radium_featured_image_size' => 'none',
    ),
    'link' => array(
        '_radium_featured_image_size' => 'none',
    ),
    'image' => array(
        '_radium_featured_image_size' => 'wide',
    ),
    'quote' => array(
        '_radium_featured_image_size' => 'none',
    ),
    'status' => array(
        '_radium_featured_image_size' => 'none',
    ),
);

return $products;
