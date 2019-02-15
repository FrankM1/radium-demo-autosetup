<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'rda_admin_import_complete', 'rda_admin_import_complete' );
/**
 * Importer complete
 */
function rda_admin_import_complete() {
    flush_rewrite_rules();
}
