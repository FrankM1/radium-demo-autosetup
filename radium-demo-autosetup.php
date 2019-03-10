<?php
/*
Plugin Name: Radium Demo Autosetup
Description: Replicate any of the Radium Themes example sites in just a few clicks!
Author: Radium Themes
Author URI: http://www.radiumthemes.com
Version: 1.0.0
Text Domain: Radium-importer
License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require 'vendor/autoload.php';

define( 'RDA_VERSION', '1.1' );

add_action( 'init', 'rda_init' );
/**
 * [rda_init description]
 * @return [type] [description]
 */
function rda_init() {

    add_action( 'admin_menu', 'rda_admin_page', 99 );

    require_once( dirname( __FILE__ ) . '/data/images/sources/controls.php' );
    require_once( dirname( __FILE__ ) . '/data/images/unsplash/functions.php' );
    require_once( dirname( __FILE__ ) . '/data/images/unsplash/controls.php' );
    require_once( dirname( __FILE__ ) . '/includes/actions.php' );
    require_once( dirname( __FILE__ ) . '/includes/importers.php' );
    require_once( dirname( __FILE__ ) . '/includes/image-uploader.php' );

    // Importers
    require_once( dirname( __FILE__ ) . '/data/posts/functions.php' );
    require_once( dirname( __FILE__ ) . '/data/portfolio/functions.php' );
    require_once( dirname( __FILE__ ) . '/data/woocommerce/functions.php' );
    require_once( dirname( __FILE__ ) . '/data/users/functions.php' );
    require_once( dirname( __FILE__ ) . '/data/videos/functions.php' );
}

function rda_get_plugin_slug() {
    return dirname( __FILE__ );
}


function rda_admin_page() {
    if ( ! is_super_admin() ) {
        return;
    }

    add_submenu_page( 'tools.php',
        __( 'Demo Default Data', 'rda' ),
        __( 'Demo Default Data', 'rda' ),
        'manage_options',
        'rda-setup',
        'rda_admin_page_content'
    );
}

function rda_admin_page_content() {

    $importers = apply_filters( 'rda_admin_import_add_importers', array() );

    $imported = array();

    ?><div class="wrap"><?php

        ?><style type="text/css" scoped="">
            #message div.results div { divst-style: disc; margin-left: 25px; }
        </style>
        <h2><?php _e( 'Radium Default Data', 'rda' ); ?> <sup>v<?php echo RDA_VERSION ?></sup></h2><?php

        if ( ! empty( $_POST['rda-admin-clear'] ) ) {
            rda_clear_db();
            echo '<div id="message" class="updated fade"><p>' . __( 'Everything was deleted', 'rda' ) . '</p></div>';
        }

        if ( isset( $_POST['rda-admin-submit'] ) ) {

            ini_set( 'max_execution_time', 3000 ); // 300 seconds = 5 minutes

            // default values
            $products      = false;

            // Check nonce before we do anything
            check_admin_referer( 'rda-admin' );

            do_action( 'rda_admin_import_content' );

            if ( ! empty( $importers ) ) {

                foreach ( $importers as $importer => $value ) {

                    foreach ( $value as $handler => $setting ) {

                        $slug = 'import-' . $importer . '-' . $handler;

                        if ( isset( $_POST['rda'][$slug] ) ) {
                            $posts            = call_user_func( $setting['callback'] );
                            $imported[$importer] = sprintf( $setting['message_report'], number_format_i18n( count( $posts ) ) );
                        }
                    }
                }

                if ( ! empty( $_POST['rda'] ) ) {
                    do_action( 'rda_admin_import_complete' );
                }
            }

        ?><div id="message" class="updated fade">
            <p>
                <?php _e( 'Data was successfully imported', 'rda' );
                if ( count( $imported ) > 0 ) {
                    echo ':<div class="results"><div>';
                    echo implode( '</div><div>', $imported );
                    echo '</div></div>';
                } ?>
            </p>
        </div><?php } ?>

        <form action="" method="post" id="rda-admin-form">
            <script type="text/javascript">
                jQuery(document).ready(function () {

                    jQuery('input.import-select').on('change', function() {
                        jQuery('input.import-select').not(this).prop('checked', false);
                    });

                });
            </script>

            <p><?php _e( 'Please do not import users twice as this will cause lots of errors (believe me). Clear the data first if you wish to do so.', 'rda' ); ?></p>

            <div class="items"><?php

                do_action( 'rda_admin_before_import_form_items' );

                if ( ! empty( $importers ) ) {

                    foreach ( $importers as $importer => $value ) {

                        ?><fieldset>

                            <legend><?php echo ucwords( $importer ); ?>:</legend><?php

                            foreach ( $value as $handler => $setting ) {

                                ?><div class="import-<?php echo $importer; ?>-<?php echo $handler; ?>">
                                    <label for="import-<?php echo $importer; ?>-<?php echo $handler; ?>">
                                        <input type="checkbox" name="rda[import-<?php echo $importer; ?>-<?php echo $handler; ?>]" id="import-<?php echo $importer; ?>-<?php echo $handler; ?>" class="import-select import-<?php echo $importer; ?>-<?php echo $handler; ?>" class="import-select" value="1"/>
                                        <?php _e( 'Do you want to import', 'rda' ); ?> <?php echo $importer; ?> <?php echo $handler; ?>
                                    </label>
                                    <?php do_action( 'rda_admin_after_import_form_' . $handler . '_item', $setting ); ?>
                                </div><?php

                            }

                        ?></fieldset><?php
                    }
                }

                do_action( 'rda_admin_after_import_form_items' );

                ?>
            </div>
            <!-- .items -->

            <p class="submit">
                <input class="button-primary" type="submit" name="rda-admin-submit" id="rda-admin-submit" value="<?php esc_attr_e( 'Import Selected Data', 'rda' ); ?>"/>
                <input class="button" type="submit" name="rda-admin-clear" id="rda-admin-clear" value="<?php esc_attr_e( 'Clear Data', 'rda' ); ?>"/>
            </p>

            <fieldset style="border: 1px solid #ccc;padding: 0 10px;">
                <legend style="font-weight: bold;"><?php _e( 'Important Information', 'rda' ); ?></legend>
                <p><?php _e( 'All users have the same password: <code>1234567890</code>', 'rda' ); ?></p>
            </fieldset>

            <?php wp_nonce_field( 'rda-admin' ); ?>

        </form>

        <!-- #rda-admin-form -->
    </div><!-- .wrap -->
<?php

}
