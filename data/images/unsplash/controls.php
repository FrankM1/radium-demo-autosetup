<?php 
add_action( 'rda_admin_after_import_form_featured_image_item', 'rda_unsplash_form_featured_image_controls' );
add_action( 'rda_admin_after_import_form_video_featured_image_item', 'rda_unsplash_form_featured_image_controls' );
add_action( 'rda_admin_after_import_form_portfolio_featured_image_item', 'rda_unsplash_form_featured_image_controls' );
add_action( 'rda_admin_after_import_form_page_featured_image_item', 'rda_unsplash_form_featured_image_controls' );
/**
 * Undocumented function
 *
 * @return void
 */
function rda_unsplash_form_featured_image_controls( $setting ) {

    $slug = $setting['slug'];

    echo '<label for="' . $slug . '-unsplash_source">';
        echo '<select id="' . $slug . '-unsplash_source" name="unsplash_source">';
            echo '<option value="collection" selected>Collection</option>';
            echo '<option value="curated_collection">Curated Collection Photos</option>';
            echo '<option value="user">User Photos</option>';
            echo '<option value="search">Search keyword</option>';
        echo '</select>';
    echo '</label>';

    echo '<label for="' . $slug . '-unsplash_id">';
        echo '<input id="' . $slug . '-unsplash_id" type="text" name="unsplash_id" value="1092658" />';
    echo '</label>';

}