<?php

/**
 * Get Remote json feed and cache.
 *
 * @param string $transientname transient name
 * @param string $url           remote feed url
 * @param int    $interval      time in seconds
 *
 * @return string json feed
 */
function radium_unsplush_get_transient_remote_json( $name, $collection_id, $page, $per_page ) {

    $transientname = 'usp_' . $name . $collection_id . $page . $per_page;

    $stale_cache_name = 'stalecache_' . $transientname;
    // we generate a consistent name for the backup data

     //delete_option( $stale_cache_name );
     //get_transient( $transientname );

    if ( false === ( $json = get_transient( $transientname ) ) ) {
        // get the remote data as before, but this time...

        $api = Crew\Unsplash\HttpClient::init([
            'applicationId' => '5746b12f75e91c251bddf6f83bd2ad0d658122676e9bd2444e110951f9a04af8',
            'utmSource'     => 'Instant images',
        ]);

        try {

            $collection = Crew\Unsplash\Collection::find( $collection_id );
            $photos = $collection->photos( $page, $per_page );

            $images = [];
 
            foreach ( $photos as $photo ) {
                $images[] = $photo->urls['full'];
            }

            $json = json_encode( $images );
            // no errors!  we store the remote data in the $json variable.

            if ( ! get_option( $stale_cache_name ) ) {
                add_option( $stale_cache_name, $json, '', 'no' );
                // Store the data in the $json variable in the options table as a backup.
                // We _could_ have just used update_option(), but by using add_option() with 'no' in the third arg
                // we keep the option from being 'autoloaded' into memory and reducing memory usage.
            } else {
                update_option( $stale_cache_name, $json );
                // update_option() preserves the 'autoload' setting of a previously created option.
            }

        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
            $json = get_option( $stale_cache_name );
        }

        set_transient( $transientname, $json, 1800 );
        // Regardless of whether we got the data from the remote site or our local backup, we store that data in the transient.
        // We won't try to regenerate that data until the transient expires.
    }

    return $json;
}

function radium_unsplush_get_random_image_from_collection( $min_count ) {
    $photos = [];
    $page = 0;

    for ( $n = 0; $n <= ( $min_count + 50 ); $n++ ) {
        $page++;
        $photos = array_merge( $photos, json_decode( radium_unsplush_get_transient_remote_json( 'collection', '1092658', $page, 20 ), true ) );
    }

    return $photos;
}

/**
 * Set remmote image as featured image
 *
 * @param  $post_id
 * @param  $image
 */
function radium_unsplash_attach_remote_image( $post_id, $image, $desc = '', $image_source = 'unsplash' ) {

    set_time_limit( 200 );

    $image_source = ! empty( $_POST['source'] ) ? $_POST['source'] : $image_source;

    global $wp_filesystem;

    // Instantiate the Wordpress filesystem.
    if ( empty( $wp_filesystem ) ) {
        require_once( ABSPATH . '/wp-admin/includes/file.php' );
        WP_Filesystem();
    }

    // only need these if performing outside of admin environment
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    $upload_folder_base = 'radium-demo-autosetup';

    $wp_upload_dir = wp_upload_dir();

    $upload_path_base = trailingslashit( $wp_upload_dir['basedir'] . '/' . $upload_folder_base .'/' );
    $upload_path = trailingslashit( $wp_upload_dir['basedir'] . '/' . $upload_folder_base . '/tmp/' );
    $upload_url = trailingslashit( $wp_upload_dir['baseurl'] . '/' . $upload_folder_base . '/tmp/' );

    // Make tmp directory to temporarily store images
    wp_mkdir_p( $upload_path ); // Make a new folder for storing our file

    $message['error'] = false;

    // Is directory writeable
    if ( ! is_writable( $upload_path_base ) ) {
        $message['message'] = __( 'Unable to save image, check your server permissions.', 'unsplash_plugin' );
        $message['error'] = true;
        return $message;
    }

    if ( $image_source === 'unsplash' ) {
        $file_path = $upload_path . basename( $image ) . '.jpg';

        $pattern = '/\?/';
        $replacement = '_';
        $file_path = preg_replace( $pattern, $replacement, $file_path );

        $pattern = '/\=/';
        $replacement = '_';
        $file_path = preg_replace( $pattern, $replacement, $file_path );

    } else {
        $file_path = $upload_path . basename( $image );
    }

    if ( ! file_exists( $file_path ) ) {

        // Generate temp. image
        // Lets use cURL
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $image );
        curl_setopt( $ch, CURLOPT_HEADER, 0 );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_BINARYTRANSFER, 1 );
        $picture = curl_exec( $ch );
        curl_close( $ch );

        if ( ! $wp_filesystem->put_contents( $file_path, $picture, FS_CHMOD_FILE ) ) {

            // Set default return value
            $message['message'][] = __( 'Unable to save temp image, check your server permissions.', 'unsplash_plugin' );
            $message['error'] = true;
            return $message;
        }

        $message['message'][] = __( 'Temp file successfully uploaded to media library.', 'unsplash_plugin' );
    }

    $attachment = rda_import_file_uploader( $file_path, $post_id );

    if ( ! $attachment ) {

        $message['message'][] = $attachment;
        $message['error'] = true;

        return $message;
    }

    // if so, we found our image. set it as thumbnail
    set_post_thumbnail( $post_id, $attachment->ID );

    $message['message'][] = $post_id;
    $message['message'][] = $attachment->ID;

    $message['message'][] = $file_path;
    $message['message'][] = __( 'Image imported.', 'unsplash_plugin');

    return $message;
}

add_action('wp_ajax_radium_unsplush_upload_image', 'radium_unsplush_upload_image'); // Ajax Save Repeater
/*
*  radium unsplush upload image
*  Upload Image Ajax Function
*
*  @since 1.0
*/

function radium_unsplush_upload_image() {

    // Check our nonce, if they don't match then bounce!
    if ( ! current_user_can( 'edit_theme_options' ) ) {
        die();
    }

    ini_set( 'max_execution_time', 3000 ); // 300 seconds = 5 minutes

    rda_import_to_post_featured_unsplash_images();

    wp_die();
}

add_action( 'wp_ajax_usp_upload_image', 'radium_usp_upload_image' ); // Ajax Save Repeater
/**
 *  Upload Image Ajax Function
 *
 *  @since 1.0
 */
function radium_usp_upload_image() {

    // Check our nonce, if they don't match then bounce!
    if ( ! current_user_can( 'edit_theme_options' ) || ! wp_verify_nonce( $_POST['nonce'], 'usp_nonce' ) ) {
        die();
    }

    // Get image variables
    $image_url = Trim( stripslashes( $_POST['image'] ) ); // Image url
    $desc = Trim( stripslashes( $_POST['description'] ) ); // image description

    $message = radium_unsplash_attach_remote_image( '', $image_url, $desc );
    echo json_encode( $message );

    die();
}
