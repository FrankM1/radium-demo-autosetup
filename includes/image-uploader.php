<?php

function rda_import_image_uploader_does_file_exists( $filename ) {
    global $wpdb;

    return intval( $wpdb->get_var( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_value LIKE '%/$filename'" ) );
}

/**
 * Retrieves the attachment ID from the file URL
 *
 * @since 1.0.0
 *
 * @param  [type] $file_name [description]
 * @return [type]            [description]
 */
function rda_import_image_uploader_get_image_id( $file_name ) {
    global $wpdb;
    $attachment = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid='%s';", $file_name ) );
    return $attachment[0];
}

/**
 * Get attachment id from image name
 *
 * @since 1.0.0
 *
 * @param  [type] $title [description]
 * @return [type]        [description]
 */
function rda_import_get_attachment_by_title( $title ) {

    global $wpdb;

    $attachments = $wpdb->get_results( "SELECT * FROM $wpdb->posts WHERE post_title = '$title' AND post_type = 'attachment' ", OBJECT );

    if ( $attachments ) {
        return $attachments[0];
    } else {
        return false;
    }
}

/**
 * [rda_upload_featured_image description]
 *
 * @param  [type] $folder [description]
 * @return [type]         [description]
 */
function rda_import_image_uploader_featured_image( $path, $post_id ) {

    global $wp_filesystem;

    if ( empty( $wp_filesystem ) ) {
        require_once( ABSPATH . '/wp-admin/includes/file.php' );
        WP_Filesystem();
    }

    if ( $wp_filesystem->is_dir( $path ) ) {
        $filelist = $wp_filesystem->dirlist( $path, false, false );

        $count = 0;
        foreach ( $filelist as $file_name => $file_details ) {
            $file_path = $path . '/' . $file_details['name'];
            $count++;
            if ( $count == 1 ) break;
        }

        return rda_import_file_uploader( $file_path, $post_id );
    }

    return false;
}

/**
 * @param  [type] $path [description]
 * @return [type]       [description]
 */
function rda_import_image_uploader_gallery_images( $path, $post_id = null ) {

    global $wp_filesystem;

    if ( empty( $wp_filesystem ) ) {
        require_once( ABSPATH . '/wp-admin/includes/file.php' );
        WP_Filesystem();
    }

    if ( $wp_filesystem->is_dir( $path ) ) {
        $filelist = $wp_filesystem->dirlist( $path, false, false );

        foreach ( $filelist as $file_name => $file_details ) {
            $file_path = $path . '/' . $file_details['name'];
            $attachments[] = rda_import_file_uploader( $file_path, $post_id );
        }

        return $attachments;
    }
}

/**
 * [rda_import_image_uploader description]
 * @param  [type] $file [description]
 * @return [type]       [description]
 */
function rda_import_file_uploader( $file_path, $parent_post_id = 0) {

    if ( ! file_exists( $file_path ) )
        return false;

    $filename = basename( $file_path );

    $filename_info = pathinfo( $file_path );

    $attachment = rda_import_get_attachment_by_title( $filename_info['filename'] );

    if ( $attachment ) {
        return $attachment;
    }

    $upload_file = wp_upload_bits( $filename, null, file_get_contents( $file_path ) );

    if ( ! $upload_file['error'] ) {

        $wp_filetype = wp_check_filetype( $filename, null );

        $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_parent'    => $parent_post_id,
            'post_title'     => preg_replace( '/\.[^.]+$/', '', $filename ),
            'post_content'   => '',
            'post_status'    => 'inherit',
        );

        $attachment_id = wp_insert_attachment( $attachment, $upload_file['file'], $parent_post_id );

        if ( ! is_wp_error( $attachment_id ) ) {

            require_once( ABSPATH . "wp-admin" . '/includes/image.php' );

            $attachment_data = wp_generate_attachment_metadata( $attachment_id, $upload_file['file'] );

            wp_update_attachment_metadata( $attachment_id, $attachment_data );

            $attachment = get_post( $attachment_id );

            if ( ! is_wp_error( $attachment ) ) {
                return $attachment;
            }
        }
    }

    return false;
}
