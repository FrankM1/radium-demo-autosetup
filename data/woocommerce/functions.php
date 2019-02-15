<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'rda_admin_import_add_importers', 'rda_product_add_importers' );
/**
 * [rda_product_add_importers description]
 * @param [type] $importers [description]
 */
function rda_product_add_importers( $importers ) {

    $importers['product'] = array(
        'new' => array(
            'title'             => 'New Products',
            'message_report'    => '%s product entries',
            'callback'          => 'rda_import_new_product',
            'slug'              => 'new-post'
        ),
        'existing' => array(
            'title'             => 'Existing Products',
            'message_report'    => '%s products updated',
            'callback'          => 'rda_import_to_existing_products',
            'slug'              => 'override-product-content'
        ),
        'meta' => array(
            'title'             => 'Post meta',
            'message_report'    => '%s product meta updated',
            'callback'          => 'rda_import_to_existing_products_meta',
            'slug'              => 'override-product-meta'
        )
    );

    return $importers;

}

/*
*  Importer engine - USERS
*/
function rda_import_new_products() {
    $products = array();

    $products_data = require_once( rda_get_plugin_slug() . '/data/woocommerce/data/new.php' );

    foreach ( $products_data as $user ) {
        $products[] = $product_id;
    }

    return $products;
}

/*
*  Importer engine - existing-products content
*/
function rda_import_to_existing_products() {

    $products = array();

    $args = array(
        'post_type' => 'product',
        'numberposts' => -1
    );

    $posts = get_posts( $args );

    $products_data = require_once( rda_get_plugin_slug() . '/data/woocommerce/data/existing.php' );

    if ( $posts ) :

        foreach ( $posts as $post ) : setup_postdata($post);

            if ( $post->ID ) {

                $my_post = array(
                    'ID'           => $post->ID,
                    'post_content' => $products_data[0]['post_content'],
                    'post_excerpt' => $products_data[0]['post_excerpt'],
                );

                $post_id = wp_update_post( $my_post, true );

                if ( is_wp_error( $post_id ) ) {
                    $errors = $post_id->get_error_messages();
                    foreach ($errors as $error) {
                        $products[]['errors'] = $error;
                    }
                } else {
                    $products[] = $post_id;
                }

            }

        endforeach;

    endif;

    return $products;
}

/*
 *  Importer engine - existing posts content
 */
function rda_import_to_existing_products_meta() {

    $posts = array();

    // add general post meta
    $args = array(
        'post_type' => 'product',
        'numberposts' => -1
    );

    $posts = get_posts( $args );

    $posts_data = require_once( rda_get_plugin_slug() . '/data/woocommerce/data/posts-meta.php' );

    if ( $posts ) :

        foreach ( $posts as $post ) : setup_postdata( $post );

            if ( $post->ID ) {

                foreach ( $posts_data as $meta => $value) {

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

