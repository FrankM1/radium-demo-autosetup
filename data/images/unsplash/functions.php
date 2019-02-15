<?php
use Crew\Unsplash as UnsplashAPi;

function radium_unsplush_get_random_images( $min_count ) {

    if ( esc_attr( $_POST['featured_image_source'] ) === 'unsplash' ) {
        $source = $_POST['unsplash_source'];
        $id = $_POST['unsplash_id'];

        switch ( $source ) {
            case 'curated_collection':
                $images = radium_unsplush_get_random_image_from_curated_collection( $id, $min_count );
                break;
            case 'user':
                $images = radium_unsplush_get_random_image_from_user( $id, $min_count );
                break;
            case 'search':
                $images = radium_unsplush_get_random_image_from_search( $id, $min_count );
                break;
            default:
                $images = radium_unsplush_get_random_image_from_collection( $id, $min_count );
                break;
        }
    }

    return $images;
}

function radium_unsplush_get_random_image_from_curated_collection( $id, $min_count ) {

    $photos = [];
    $page = 0;

    for ( $n = 0; $n <= ( $min_count + 50 ); $n++ ) {
        $page++;
        $photos = array_merge( $photos, json_decode( radium_unsplush_get_transient_remote_json( $id, 'curated_collection', $page, 20 ), true ) );
    }

    return $photos;
}

function radium_unsplush_get_random_image_from_user( $id, $min_count ) {

    $photos = [];
    $page = 0;

    for ( $n = 0; $n <= ( $min_count + 50 ); $n++ ) {
        $page++;
        $photos = array_merge( $photos, json_decode( radium_unsplush_get_transient_remote_json( $id, 'user', $page, 20 ), true ) );
    }

    return $photos;
}

function radium_unsplush_get_random_image_from_search( $id, $min_count ) {

    $photos = [];
    $page = 0;

    for ( $n = 0; $n <= ( $min_count + 50 ); $n++ ) {
        $page++;
        $photos = array_merge( $photos, json_decode( radium_unsplush_get_transient_remote_json( $id, 'search', $page, 20 ), true ) );
    }

    return $photos;
}

function radium_unsplush_get_random_image_from_collection( $id, $min_count ) {

    $photos = [];
    $page = 0;

    for ( $n = 0; $n <= ( $min_count + 50 ); $n++ ) {
        $page++;
        $photos = array_merge( $photos, json_decode( radium_unsplush_get_transient_remote_json( $id, 'collection', $page, 20 ), true ) );
    }

    return $photos;
}

/**
 * Get Remote json feed and cache.
 *
 * @param string $transientname transient name
 * @param string $url           remote feed url
 * @param int    $interval      time in seconds
 *
 * @return string json feed
 */
function radium_unsplush_get_transient_remote_json( $query_id, $name, $page, $per_page ) {

    $transientname = 'rda_' . $name . $query_id . $page . $per_page;

    $stale_cache_name = 'stalecache_' . $transientname; // we generate a consistent name for the backup data

     // delete_option( $stale_cache_name );
     // delete_transient( $transientname );

    if ( false === ( $json = get_transient( $transientname ) ) ) { // get the remote data as before, but this time...

        $api = UnsplashAPi\HttpClient::init([
            'applicationId' => '5746b12f75e91c251bddf6f83bd2ad0d658122676e9bd2444e110951f9a04af8',
            'utmSource'     => 'Instant images',
        ]);

        try {

            switch ( $name ) {
                case 'curated_collection':
                    $collection = UnsplashAPi\CuratedCollection::find( $query_id );
                    $photos = $collection->photos( $page, $per_page );
                    break;
                case 'user':
                    $user = UnsplashAPi\User::find( str_replace( '@', '', $query_id ) );
                    $photos = $user->photos( $page, $per_page );
                    break;
                case 'search':
                    $photos = UnsplashAPi\Photo::search( $query_id, '', $page, $per_page );
                    break;
                default:
                    $collection = UnsplashAPi\Collection::find( $query_id );
                    $photos = $collection->photos( $page, $per_page );
                    break;
            }

            $images = [];

            foreach ( $photos as $photo ) {
                $images[] = $photo->urls['full'];
            }

            $json = json_encode( $images ); // no errors!  we store the remote data in the $json variable.

            if ( ! get_option( $stale_cache_name ) ) {
                add_option( $stale_cache_name, $json, '', 'no' );
                // Store the data in the $json variable in the options table as a backup.
                // We _could_ have just used update_option(), but by using add_option() with 'no' in the third arg
                // we keep the option from being 'autoloaded' into memory and reducing memory usage.
            } else {
                update_option( $stale_cache_name, $json ); // update_option() preserves the 'autoload' setting of a previously created option.
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
    require_once( ABSPATH . 'wp-admin/includes/media.php' );
    require_once( ABSPATH . 'wp-admin/includes/file.php' );
    require_once( ABSPATH . 'wp-admin/includes/image.php' );

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
    $message['message'][] = __( 'Image imported.', 'unsplash_plugin' );

    return $message;
}
