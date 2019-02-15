<?php

/* Admin function */
add_action('admin_head', 'usp_admin_vars');
/*
*  usp_admin_vars
*  Create admin variables and ajax nonce
*
*  @since 1.0
*/

function usp_admin_vars()
{
    ?>
    <script type='text/javascript'>
     /* <![CDATA[ */
    var usp_admin_localize = <?php echo json_encode(array(
        'ajax_admin_url' => admin_url('admin-ajax.php'),
        'usp_admin_nonce' => wp_create_nonce('usp_nonce'),
    ));
    ?>
    /* ]]> */
    </script>
<?php
}

add_action('admin_menu', 'usp_admin_menu');
/*
* usp_admin_menu
* Create Admin Menu
*
* @since 1.0
*/
function usp_admin_menu()
{
    $usp_settings_page = add_submenu_page('upload.php', 'Unsplash WP', 'Unsplash WP', 'edit_theme_options', 'unsplash', 'usp_settings_page');

   //Add our admin scripts
   add_action('load-'.$usp_settings_page, 'usp_load_admin_scripts');
}

/**
 * usp_load_admin_scripts
 * Load Admin CSS and JS.
 *
 * @since 1.0
 */
function usp_load_admin_scripts()
{
    add_action('admin_enqueue_scripts', 'usp_enqueue_admin_scripts');
}

/**
 * usp_enqueue_admin_scripts
 * Admin Enqueue Scripts.
 *
 * @since 1.0
 */
function usp_enqueue_admin_scripts()
{
    wp_enqueue_style('admin-css', RDAUI_ADMIN_URL.'css/admin.css');
    wp_enqueue_style('font-awesome', '//netdna.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css');
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-form');
}


add_action('media_buttons_context',  'usp_media_popup');
/*
* usp_media_popup
* Add pop up button to post/page editor
*
* @since 1.0
*/
function usp_media_popup( $context ) {

  //our popup's title
  $title = 'Unsplash WP';

  //append the icon
  $context .= "<a href='#TB_inline?width=1200&height=800%&inlineId=popup_container'
    class='button thickbox unsplash' title='Unsplash WP - Click photos to upload directly to your media library'>
    <span class='dashicons dashicons-format-gallery'></span> Unsplash WP</a>";

    return $context;
}

/*
* usp_media_popup_content
* Add pop up content to edit, new and post pages
*
* @since 1.0
*/

add_action('admin_head-post.php',  'usp_media_popup_content');
add_action('admin_head-post-new.php',  'usp_media_popup_content');
add_action('admin_head-edit.php',  'usp_media_popup_content');

function usp_media_popup_content() {
    wp_enqueue_style('admin-css', RDAUI_ADMIN_URL.'css/admin.css');
    wp_enqueue_style('font-awesome', '//netdna.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css');
    ?>

   <div id="popup_container" style="display:none;">
     <?php include RDAUI_PATH.'includes/unsplash-photos.php';
    ?>
   </div>
<?php

}

/*
*  usp_settings_page
*  Settings page
*
*  @since 1.0
*/

function usp_settings_page() {
    ?>
    <div class="admin cnkt-container settings" id="usp-settings">
        <div class="wrap">
           <div class="header-wrap">
            <h2><?php echo RDAUI_TITLE;
    ?> <span><?php echo RDAUI_VERSION;
    ?></span></h2>
            <p><?php _e('One click uploads of <a href="https://unsplash.com/" target="_blank">unsplash.com</a> stock photos directly to your media library', 'unsplash_plugin');
    ?></p>
         </div>
           <div class="cnkt-main">
               <div class="group">
                   <?php include RDAUI_PATH.'includes/unsplash-photos.php';
    ?>
                   <p class="back2top"><a href="#wpcontent"><i class="fa fa-chevron-up"></i> <?php _e('Back to Top', 'unsplash_plugin');
    ?></a></p>
               </div>
           </div>
           <div class="cnkt-sidebar">
              <div class="cta">

              <form action="options.php" method="post" id="usp_OptionsForm">
                   <?php
                    settings_fields('usp-setting-group');
                    do_settings_sections('unsplash');
                    //get the older values, wont work the first time
                    $options = get_option('_usp_settings'); ?>
                       <div class="submit-usp_OptionsForm">
                       <?php submit_button('Save Settings'); ?>
                       <div class="loading"></div>
                       </div>
                       <div id="saveResult"></div>
                  <script type="text/javascript">
                  jQuery(document).ready(function() {
                     jQuery('#usp_OptionsForm input[type=submit]').removeClass('button-primary');
                     jQuery('#usp_OptionsForm').submit(function() {
                        jQuery('.submit-usp_OptionsForm .loading').fadeIn();
                        jQuery(this).ajaxSubmit({
                           success: function(){
                              jQuery('.submit-usp_OptionsForm .loading').fadeOut(250, function(){
                                 window.location.reload();
                              });
                           },
                           error: function(){
                              alert('<?php _e('Sorry, settings could not be saved.', 'unsplash_plugin'); ?>');
                           }
                        });
                        return false;
                     });
                  });
                  </script>
                  </form>
              </div>
                <?php include  RDAUI_PATH . '/includes/cta/all-posts.php'; ?>
           </div>
        </div>
    </div>
<?php

}

/*
*  usp_admin_init()
*  Initiate the plugin, create our setting variables.
*
*  @since 1.0
*/

add_action('admin_init', 'usp_admin_init');
function usp_admin_init()
{

    register_setting(
        'usp-setting-group',
        'usp_settings',
        'usp_sanitize_settings'
    );

    add_settings_section(
        'usp_general_settings',
        'Plugin Settings',
        'usp_general_settings_callback',
        'unsplash'
    );

    // Download Width
    add_settings_field(
        '_usp_dw',
        __('Set Upload Image Width', 'unsplash_plugin'),
        'usp_dw_callback',
        'unsplash',
        'usp_general_settings'
    );

    // Download Height
    add_settings_field(
        '_usp_dh',
        __('Set Upload Image Height', 'unsplash_plugin'),
        'usp_dh_callback',
        'unsplash',
        'usp_general_settings'
    );

    // Images per page
    add_settings_field(
        '_usp_pp',
        __('Images Per Page', 'unsplash_plugin'),
        'usp_pp_callback',
        'unsplash',
        'usp_general_settings'
    );
}

/*
*  usp_general_settings_callback
*  Some general settings text
*
*  @since 1.0
*/

function usp_general_settings_callback()
{
    //echo '<p>' . __('Customize your file download', 'unsplash_plugin') . '</p>';

    $json = radium_unsplush_get_transient_remote_json('unsplash_list', 'https://unsplash.it/list');

}

/*
*  usp_sanitize_settings
*  Sanitize our form fields
*
*  @since 1.0
*/

function usp_sanitize_settings($input)
{
    return $input;
}

/*
*  _usp_dw_callback
*  File download width
*
*  @since 1.0
*/

function usp_dw_callback()
{
    $options = get_option('usp_settings');

    if (!isset($options['_usp_dw'])) {
        $options['_usp_dw'] = '1600';
    }

    echo '<label for="usp_settings[_usp_dw]">'.__('Width:', 'unsplash_plugin').'</label><input type="number" id="usp_settings[_usp_dw]" name="usp_settings[_usp_dw]" value="'.$options['_usp_dw'].'" class="sm" step="20" max="3200" /> ';
}

/*
*  _usp_dh_callback
*  File download height
*
*  @since 1.0
*/

function usp_dh_callback()
{
    $options = get_option('usp_settings');

    if (!isset($options['_usp_dh'])) {
        $options['_usp_dh'] = '900';
    }

    echo '<label for="usp_settings[_usp_dh]">'.__('Height:', 'unsplash_plugin').'</label><input type="number" id="usp_settings[_usp_dh]" name="usp_settings[_usp_dh]" value="'.$options['_usp_dh'].'" class="sm" step="20" max="3200" /> ';
}

/*
*  usp_pp_callback
*  # of images / page
*
*  @since 1.0
*/

function usp_pp_callback()
{
    $options = get_option('usp_settings');

    if (!isset($options['_usp_pp'])) {
        $options['_usp_pp'] = '20';
    }

    echo '<label for="usp_settings[_usp_pp]">'.__('# Per Page:', 'unsplash_plugin').'</label><input type="number" id="usp_settings[_usp_pp]" name="usp_settings[_usp_pp]" value="'.$options['_usp_pp'].'" class="sm" step="5" max="60" /> ';
}
