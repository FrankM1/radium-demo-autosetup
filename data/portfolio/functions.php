<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'rda_admin_import_add_importers', 'rda_portfolio_add_importers' );
/**
 * [rda_portfolio_add_importers description]
 * @param [type] $importers [description]
 */
function rda_portfolio_add_importers( $importers ) {

    $importers['portfolio'] = array(
        'new' => array(
            'title'             => 'New Portfolios',
            'message_report'    => '%s portfolio entries',
            'callback'          => 'rda_import_new_portfolio',
            'slug'              => 'new-portfolio'
        ),
        'existing' => array(
            'title'             => 'Existing Portfolios',
            'message_report'    => '%s portfolios updated',
            'callback'          => 'rda_import_to_existing_portfolio',
            'slug'              => 'override-portfolio-content'
        ),
        'meta' => array(
            'title'             => 'Portfolio meta',
            'message_report'    => '%s portfolio meta updated',
            'callback'          => 'rda_import_to_portfolio_meta',
            'slug'              => 'override-portfolio-meta'
        ),
        'featured_image' => array(
            'title'             => 'Featured Image',
            'message_report'    => '%s portfolio images updated',
            'callback'          => 'rda_import_to_portfolio_featured_unsplash_images',
            'slug'              => 'override-portfolio-featured-unsplash-images'
        ),
        'colors' => array(
            'title'             => 'Portfolio accent color from featured image',
            'message_report'    => '%s portfolio colors updated',
            'callback'          => 'rda_import_to_existing_portfolio_colors',
            'slug'              => 'override-portfolio-colors'
        )
    );

    return $importers;

}

/*
 *  Importer engine - Portfolio
 */
function rda_import_new_portfolio() {

    $posts = array();

    $post_data = require_once( rda_get_plugin_slug() . '/data/portfolio/data/new.php' );

    $count = 0;

    if ( ! empty( $post_data ) ) {
        foreach ( $post_data as $post ) {

            if ( empty( $post['post_title'] ) || empty( $post['post_content'] ) ) continue;

            $count++;

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
            $post_args = array(
                'post_type'     => 'portfolio',
                'post_title'    => $post['post_title'],
                'post_content'  => $post['post_content'],
                'post_excerpt'  => $post['post_excerpt'],
                'post_status'   => 'publish',
                'post_author'   => 1,
            );

            // Insert the post into the database.
            $post_id = wp_insert_post( $post_args );

            wp_set_object_terms( $post_id, $post['taxonomy']['portfolio_category'], 'portfolio_category', false );
            wp_set_object_terms( $post_id, $post['taxonomy']['portfolio_tag'], 'portfolio_tag', false );

            if ( $post_id ) {
                set_post_format( $post_id , $post['post_format'] );

                foreach ( $post['meta'] as $meta => $value) {

                    if ( $value === '' || $value === '0' ) {
                        delete_post_meta( $post_id, $meta );
                    } else {
                        update_post_meta( $post_id, $meta, $value );
                    }
                }

                // get images
                $attachment = rda_import_image_uploader_featured_image( rda_get_plugin_slug() . '/data/portfolio/data/post-' . $count . '/images/featured', $post_id );

                if ( ! $attachment ) {
                    builder_write_log( $post['post_title'] );
                }

                if ( is_object( $attachment ) ) {
                    set_post_thumbnail($post_id, $attachment->ID);
                }

                // import gallery images
                if ( $post['post_format'] === 'gallery' ) {

                    // Import images
                    $attachments = rda_import_image_uploader_gallery_images( rda_get_plugin_slug() . '/data/portfolio/data/post-' . $count . '/images/gallery', $post_id );

                    if ( is_object( $attachment ) && ! $post['gallery']['ids'] ) {

                        $gallery = array();

                        foreach ( $attachments as $attachment => $value ) {
                            $gallery[] = $value->ID;
                        }

                        $gallery = array_filter( array_unique( $gallery ) );

                        foreach ( $gallery as $new_value ) {
                            add_post_meta( $post_id, $post['gallery']['meta_key'], $new_value, false );
                        }

                    } elseif( $post['gallery']['ids'] ) {

                        $gallery = array_values( $post['gallery']['ids'] );
                        $gallery = array_filter( array_unique( $gallery ) );

                        foreach ( $gallery as $new_value ) {
                            add_post_meta( $post_id, $post['gallery']['meta_key'], $new_value, false );
                        }
                    }

                } elseif ( $post['post_format'] === 'video' ) {

                }

                $posts[] = $post_id;
            }
        }
    }

    return $posts;
}

/*
*  Importer engine - existing-portfolio content
*/
function rda_import_to_existing_portfolio() {

    $posts = array();

    $args = array(
        'post_type' => 'portfolio',
        'numberposts' => -1
    );

    $posts = get_posts( $args );

    $post_data = require_once( rda_get_plugin_slug() . '/data/portfolio/data/existing.php' );

    if ( $posts ) :

        foreach ( $posts as $post ) : setup_postdata($post);

            if ( $post->ID ) {

                $post_format = get_post_format( $post->ID ) ? get_post_format( $post->ID ) : 'standard';

                if ( empty ( $post_data[$post_format] ) || empty ( $post_data[$post_format]['post_content'] ) ) {
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
                    $posts[] = $post_id;
                }

            }

        endforeach;

    endif;

    return $posts;
}

/*
 *  Importer engine - existing portfolio content
 */
function rda_import_to_portfolio_meta() {

    $posts = array();

    // add general post meta
    $args = array(
        'post_type' => 'portfolio',
        'numberposts' => -1
    );

    $posts = get_posts( $args );

    $post_data = require_once( rda_get_plugin_slug() . '/data/portfolio/data/post-meta.php' );

    if ( $posts ) :

        foreach ( $posts as $post ) : setup_postdata( $post );

            if ( $post->ID ) {

                $post_format = get_post_format( $post->ID ) ? get_post_format( $post->ID ) : 'standard';

                foreach ( $post_data[$post_format] as $meta => $value) {

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
*  Importer engine - existing-portfolio content
*/
function rda_import_to_existing_portfolio_colors() {

    $posts = array();

    $args = array(
        'post_type' => 'portfolio',
        'numberposts' => -1
    );

    $posts = get_posts( $args );

    if ( $posts ) :

        foreach ( $posts as $post ) : setup_postdata($post);

            if ( $post->ID ) {

                $post_id = rda_import_to_portfolio_image_colors( $post->ID );

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
function rda_import_to_portfolio_image_colors( $post_id ) {

    $post = get_post( $post_id );
    $post_id = $post->ID;

    /* Make sure we have the post data. */
    if ( ! $post_id )
        return;

    if ( $post->post_status === 'trash' || $post->post_status === 'auto-draft' )
        return;

    /* Validate the correct post type and make sure it's not a post revision. */
    if ( $post->post_type === 'portfolio' && ! wp_is_post_revision( $post_id ) ) {

        $tweets_strings = new Radium_Image_Colors();
        $tweets_strings->generate_and_save_color( $post_id );

        return $post_id;
    }

    return false;
}

/*
 *  Importer engine - existing portfolio content
 */
function rda_import_to_portfolio_featured_unsplash_images() {

    $args = array(
        'post_type'   => array( 'portfolio' ),
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
