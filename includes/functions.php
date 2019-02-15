<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function rda_get_random_cue_playlist_id() {

    $args = array(
        'post_type'     => 'cue_playlist',
        'numberposts'   => 1,
        'orderby'       => 'rand',
    );

    $posts = get_posts( $args );

    if ( $posts ) :

        foreach ( $posts as $post ) : setup_postdata($post);
            $post_id = $post->ID;
        endforeach;

    endif;

    return $post_id;
}

add_action( 'rda_admin_import_complete', 'rda_admin_import_complete' );
/**
 * Importer complete
 */
function rda_admin_import_complete() {
    flush_rewrite_rules();
}
