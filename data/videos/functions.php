<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'rda_admin_import_add_importers', 'rda_video_add_importers' );
/**
 * [rda_video_add_importers description]
 * @param [type] $importers [description]
 */
function rda_video_add_importers( $importers ) {

    $importers['video'] = array(
        'existing' => array(
            'title'             => 'Existing Videos',
            'message_report'    => '%s Videos updated',
            'callback'          => 'rda_import_to_existing_video',
            'slug'              => 'override-video-content',
        ),
    );

    $importers['video_featured_image'] = array(
        'existing' => array(
            'title'             => 'Featured Image Videos',
            'message_report'    => '%s Video Images updated',
            'callback'          => 'rda_import_to_video_featured_unsplash_images',
            'slug'              => 'override-video-featured-unsplash-images'
        ),
    );

    return $importers;

}

/*
* Importer engine - existing-video content
*/
function rda_import_to_existing_video() {

    $posts = array();

    $args = array(
        'post_type' => video_central_get_video_post_type(),
        'numberposts' => -1,
    );

    $posts = get_posts( $args );

    $post_data = require_once( rda_get_plugin_slug() . '/data/posts/data/existing.php' );

    if ( $posts ) :

        foreach ( $posts as $post ) : setup_postdata( $post );

            if ( $post->ID ) {

                $post_format = video_central_get_video_post_type();

                if ( empty( $post_data[$post_format] ) || empty( $post_data[$post_format]['post_content'] ) ) {
                    continue;
                }

                $post_args = array(
                    'ID'           => $post->ID,
                    'post_content' => $post_data[$post_format]['post_content'],
                    'post_excerpt' => $post_data[$post_format]['post_excerpt'],
                );

                $post_id = wp_update_post( $post_args, true );

                if ( is_wp_error( $post_id ) ) {
                    $errors = $post_id->get_error_messages();
                    foreach ($errors as $error) {
                        $posts[]['errors'] = $error;
                    }
                } else {
                    update_post_meta( $post_id, '_video_central_description', $post_data[$post_format]['post_excerpt'] );
                    $posts[] = $post_id;

                }

            }

        endforeach;

    endif;

    return $posts;
}


/*
 *  Importer engine - existing video content
 */
function rda_import_to_video_featured_unsplash_images() {

    $args = array(
        'post_type'   => video_central_get_video_post_type(),
        'numberposts' => -1,
    );

    $posts = get_posts( $args );

    $message['error'] = '';

    if ( $posts ) :

        $images = radium_unsplush_get_random_images( count( $posts ) );

        update_option( 'radium_unsplush_get_random_image', $images );

        foreach ( $posts as $post ) : setup_postdata( $post );

            if ( $post->ID ) {

                $images = get_option( 'radium_unsplush_get_random_image' ); // unique random

                $index = array_rand( $images, 1 ); // Get image variables

                $image = $images[$index];

                $message = radium_unsplash_attach_remote_image( $post->ID, $image );

                unset( $images[$index] );

                update_option( 'radium_unsplush_get_random_image', $images );

                if ( ! $message['error'] ) {
                    $posts[] = $post->ID;
                }
            }

        endforeach;

    endif;

    return $posts;
}
