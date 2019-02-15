<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'rda_admin_import_add_importers', 'rda_users_add_importers' );
/**
 * [rda_product_add_importers description]
 * @param [type] $importers [description]
 */
function rda_users_add_importers( $importers ) {

    $importers['users'] = array(
        'users' => array(
            'title'             => 'New Users',
            'message_report'    => '%s user entries',
            'callback'          => 'rda_import_users',
            'slug'              => 'new-users'
        ),
    );

    return $importers;

}
/*
*  Importer engine - USERS
*/
function rda_import_users() {
    /** @var $wpdb WPDB */
    global $wpdb;
    $products = array();

    $products_data = require_once( rda_get_plugin_slug() . '/data/users/data/users.php' );

    foreach ( $products_data as $user ) {

        $product_id = wp_insert_user(
            array(
               'user_login'      => $user['login'],
               'user_pass'       => $user['pass'],
               'display_name'    => $user['display_name'],
               'user_email'      => $user['email'],
               'user_registered' => rda_get_random_date( 45, 1 ),

            )
        );

        $query[] = $wpdb->last_query;

        $name = explode( ' ', $user['display_name'] );
        update_user_meta( $product_id, 'first_name', $name[0] );
        update_user_meta( $product_id, 'last_name', isset( $name[1] ) ? $name[1] : '' );

        $products[] = $product_id;
    }

    return $products;
}

/**
 * Get random user ids
 *
 * @param  integer $count  [description]
 * @param  string  $output [description]
 * @return string  ids
 */
function rda_get_random_users_ids( $count = 1, $output = 'array' ) {
    /** @var $wpdb WPDB */
    global $wpdb;
    $limit = '';
    $data  = array();

    if ( $count > 0 ) {
        $limit = ' LIMIT ' . $count;
    }

    $products = $wpdb->get_results( "SELECT ID FROM {$wpdb->users} ORDER BY rand() {$limit}" );

    // reformat the array
    foreach ( $products as $user ) {
        $data[] = $user->ID;
    }

    if ( $output == 'array' ) {
        return $data;
    } elseif ( $output == 'string' ) {
        return implode( ',', $data );
    }

    return false;
}
