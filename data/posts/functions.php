<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'rda_admin_import_add_importers', 'rda_post_add_importers' );
/**
 * [rda_post_add_importers description]
 * @param [type] $importers [description]
 */
function rda_post_add_importers( $importers ) {

    $importers['post'] = array(
        'new' => array(
            'title'             => 'New Posts',
            'message_report'    => '%s post entries',
            'callback'          => 'rda_import_new_posts',
            'slug'              => 'new-post'
        ),
        'existing' => array(
            'title'             => 'Existing Posts',
            'message_report'    => '%s posts updated',
            'callback'          => 'rda_import_to_existing_posts',
            'slug'              => 'override-post-content'
        ),
        'meta' => array(
            'title'             => 'Post meta',
            'message_report'    => '%s post meta updated',
            'callback'          => 'rda_import_to_existing_posts_meta',
            'slug'              => 'override-post-meta'
        ),
        'featured_image' => array(
            'title'             => 'Featured Image',
            'message_report'    => '%s post images updated',
            'callback'          => 'rda_import_to_post_featured_unsplash_images',
            'slug'              => 'override-post-featured-unsplash-images'
        ),
        'colors' => array(
            'title'             => 'Post accent color from featured image',
            'message_report'    => '%s post colors updated',
            'callback'          => 'rda_import_to_existing_post_colors',
            'slug'              => 'override-post-colors'
        )
    );

    return $importers;

}

/*
*  Importer engine - USERS
*/
function rda_import_new_posts() {
    /** @var $wpdb WPDB */
    global $wpdb;
    $posts = array();

    $posts_data = require_once( rda_get_plugin_slug() . '/data/posts/data/new.php' );

    foreach ( $posts_data as $post ) {

        if ( empty( $post['post_title'] ) || empty( $post['post_content'] ) ) continue;

        $user_id = get_current_user_id();

        $defaults = array(
            'post_author' => $user_id,
            'post_content' => '',
            'post_content_filtered' => '',
            'post_title' => '',
            'post_excerpt' => '',
            'post_status' => 'draft',
            'post_type' => 'post',
            'comment_status' => '',
            'post_password' => '',
        );

        // Gather post data.
        $my_post = array(
            'post_title'    => $post['post_title'],
            'post_content'  => $post['post_content'],
            'post_excerpt'  => $post['post_excerpt'],
            'post_status'   => 'publish',
            'post_author'   => 1,
        );

        // Insert the post into the database.
        $post_id = wp_insert_post( $my_post );

        set_post_format( $post_id , $post['post_format'] );

        $posts[] = $post_id;
    }

    return $posts;
}

/*
*  Importer engine - existing-posts content
*/
function rda_import_to_existing_posts() {

    $posts = array();

    $args = array(
        'post_type' => 'post',
        'numberposts' => -1
    );

    $posts = get_posts( $args );

    $posts_data = require_once( rda_get_plugin_slug() . '/data/posts/data/existing.php' );

    if ( $posts ) :

        foreach ( $posts as $post ) : setup_postdata($post);

            if ( $post->ID ) {

                $post_format = get_post_format( $post->ID ) ? get_post_format( $post->ID ) : 'standard';

                if ( empty ( $posts_data[$post_format] ) || empty ( $posts_data[$post_format]['post_content'] ) ) {
                    continue;
                }

                $my_post = array(
                    'ID'           => $post->ID,
                    'post_content' => $posts_data[$post_format]['post_content'],
                    'post_excerpt' => $posts_data[$post_format]['post_excerpt'],
                );

                $post_id = wp_update_post( $my_post, true );

                if ( is_wp_error( $post_id ) ) {
                    $errors = $post_id->get_error_messages();
                    foreach ($errors as $error) {
                        $posts[]['errors'] = $error;
                    }
                } else {
                    $posts[] = $post_id;
                }

            }

        endforeach;

    endif;

    return $posts;
}

/*
 *  Importer engine - existing posts content
 */
function rda_import_to_existing_posts_meta() {

    $posts = array();

    // add general post meta
    $args = array(
        'post_type'   => 'post',
        'numberposts' => -1,
    );

    $posts = get_posts( $args );

    $posts_data = require_once( rda_get_plugin_slug() . '/data/posts/data/posts-meta.php' );

    if ( $posts ) :

        foreach ( $posts as $post ) : setup_postdata( $post );

            if ( $post->ID ) {

                $post_format = get_post_format( $post->ID ) ? get_post_format( $post->ID ) : 'standard';

                foreach ( $posts_data[$post_format] as $meta => $value) {

                    if ( $value === '' || $value === '0' ) {
                        delete_post_meta( $post->ID, $meta );
                    } else {
                        update_post_meta( $post->ID, $meta, $value );
                    }

                    $posts[] = $meta;
                }

            }

        endforeach;

    endif;

    wp_reset_postdata();

    // Random featured posts
    $args = array(
        'post_type'   => 'post',
        'numberposts' => 10,
        'orderby'     => 'rand',
    );

    $posts = get_posts( $args );

    if ( $posts ) :

        foreach ( $posts as $post ) : setup_postdata( $post );

            if ( $post->ID ) {

                $post_format = get_post_format( $post->ID ) ? get_post_format( $post->ID ) : 'standard';

                if ( has_post_thumbnail() || $post_format == 'image' || $post_format == 'gallery' ) {
                    update_post_meta( $post->ID, '_radium_featured', '1' );
                    $posts[] = $post->ID;
                }

            }

        endforeach;

    endif;

    wp_reset_postdata();

    // Random _radium_editors_pick
    $args = array(
        'post_type'   => 'post',
        'numberposts' => 10,
        'orderby'     => 'rand',
    );

    $posts = get_posts( $args );

    if ( $posts ) :

        foreach ( $posts as $post ) : setup_postdata( $post );

            if ( $post->ID ) {

                update_post_meta( $post->ID, '_radium_editors_pick', '1' );
                $posts[] = $post->ID;

            }

        endforeach;

    endif;

    wp_reset_postdata();

    // Random featured posts
    $args = array(
        'post_type'   => 'post',
        'numberposts' => 10,
        'orderby'     => 'rand',
        'meta_query'  => array(
            array(
                'key'     => '_thumbnail_id',
                'compare' => 'EXISTS'
            ),
        )
    );

    $posts = get_posts( $args );

    if ( $posts ) :

        foreach ( $posts as $post ) : setup_postdata( $post );

            if ( $post->ID ) {

                $post_format = get_post_format( $post->ID ) ? get_post_format( $post->ID ) : 'standard';

                update_post_meta( $post->ID, '_radium_carousel_slider', '1' );
                $posts[] = $post->ID;

            }

        endforeach;

    endif;

    wp_reset_postdata();

    return $posts;
}

/*
 *  Importer engine - existing posts content
 */
function rda_import_to_post_featured_unsplash_images() {

    $args = array(
        'post_type'   => 'post',
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

/*
*  Importer engine - existing-post content
*/
function rda_import_to_existing_post_colors() {

    $posts = array();

    $args = array(
        'post_type'   => 'post',
        'numberposts' => -1,
    );

    $posts = get_posts( $args );

    if ( $posts ) :

        foreach ( $posts as $post ) : setup_postdata( $post );
            if ( $post->ID ) {
                $post_id = rda_import_to_post_image_colors( $post->ID );
                if ( is_wp_error( $post_id ) ) {
                    $errors = $post_id->get_error_messages();
                    foreach ($errors as $error) {
                        $posts[]['errors'] = $error;
                    }
                } else {
                    $posts[] = $post_id;
                }
            }
        endforeach;

    endif;

    wp_reset_postdata();

    return $posts;
}

/*
 * Automatic color setting for Portfolios
 *
 * @since 2.1.3
 *
 * returtn void
 */
function rda_import_to_post_image_colors( $post_id ) {

    $post = get_post( $post_id );
    $post_id = $post->ID;

    /* Make sure we have the post data. */
    if ( ! $post_id )
        return;

    if ( $post->post_status === 'trash' || $post->post_status === 'auto-draft' )
        return;

    /* Validate the correct post type and make sure it's not a post revision. */
    if ( $post->post_type === 'post' && ! wp_is_post_revision( $post_id ) ) {

        $tweets_strings = new Radium_Image_Colors();
        $tweets_strings->generate_and_save_color( $post_id );

        return $post_id;
    }

    return false;
}
