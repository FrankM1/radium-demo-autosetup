<?php 
add_action( 'rda_admin_after_import_form_featured_image_item', 'rda_unsplash_form_featured_image_source_controls' );
function rda_unsplash_form_featured_image_source_controls() {
    echo '<label for="import-post-featured_image_source">';
        echo '<select name="featured_image_source">';
            echo '<option value="unsplash" selected>Unsplush</option>';
            echo '<option value="folder">Folder</option>';
        echo '</select>';
    echo '</label>';
}