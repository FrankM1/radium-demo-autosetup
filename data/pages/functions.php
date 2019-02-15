<?php
add_filter( 'rda_admin_import_add_importers', 'rda_page_add_importers' );
/**
 * [rda_page_addrda_page_add_importers_importers description]
 * @param [type] $importers [description]
 */
function rda_page_add_importers( $importers ) {

    $importers['page'] = array(
        'new' => array(
            'title'             => 'New Pages',
            'message_report'    => '%s page entries',
            'callback'          => 'rda_import_new_pages',
            'slug'              => 'new-page',
        ),
        'existing' => array(
            'title'             => 'Existing Pages',
            'message_report'    => '%s pages updated',
            'callback'          => 'rda_import_to_existing_pages',
            'slug'              => 'override-page-content',
        ),
        'meta' => array(
            'title'             => 'Page meta',
            'message_report'    => '%s page meta updated',
            'callback'          => 'rda_import_to_existing_pages_meta',
            'slug'              => 'override-page-meta',
        ),
        'featured_image' => array(
            'title'             => 'Featured Image',
            'message_report'    => '%s page images updated',
            'callback'          => 'rda_import_to_page_featured_unsplash_images',
            'slug'              => 'override-page-featured-unsplash-images',
        ),
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

    $posts_data = require_once( rda_get_plugin_slug() . '/data/pages/data/new.php' );

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
            'post_type' => 'page',
            'comment_status' => '',
            'post_password' => '',
        );

        // Gather page data.
        $my_post = array(
            'post_title'    => $post['post_title'],
            'post_content'  => $post['post_content'],
            'post_excerpt'  => $post['post_excerpt'],
            'post_status'   => 'publish',
            'post_author'   => 1,
        );

        // Insert the page into the database.
        $post_id = wp_insert_post( $my_post );

        set_post_format( $post_id , $post['post_format'] );

        $posts[] = $post_id;
    }

    return $posts;
}

/*
*  Importer engine - existing-pages content
*/
function rda_import_to_existing_posts() {

    $posts = array();

    $args = array(
        'post_type' => 'page',
        'numberposts' => -1
    );

    $posts = get_posts( $args );

    $posts_data = require_once( rda_get_plugin_slug() . '/data/pages/data/existing.php' );

    if ( $posts ) :

        foreach ( $posts as $post ) : setup_postdata( $post );

            if ( $post->ID ) {

                $post_format = get_post_format( $post->ID ) ? get_post_format( $post->ID ) : 'standard';

                if ( empty( $posts_data[$post_format] ) || empty( $posts_data[$post_format]['post_content'] ) ) {
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
 *  Importer engine - existing pages content
 */
function rda_import_to_existing_posts_meta() {

    $posts = array();

    // add general page meta
    $args = array(
        'post_type'   => 'page',
        'numberposts' => -1,
    );

    $posts = get_posts( $args );

    $posts_data = require_once( rda_get_plugin_slug() . '/data/pages/data/pages-meta.php' );

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

    return $posts;
}

/*
 *  Importer engine - existing pages content
 */
function rda_import_to_post_featured_unsplash_images() {

    $args = array(
        'post_type'   => 'page',
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
