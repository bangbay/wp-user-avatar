<?php
/**
 * @package WP User Avatar
 * @version  1.5.4
 */
/*
Plugin Name: WP User Avatar
Plugin URI: http://wordpress.org/plugins/wp-user-avatar/
Description: Use any image from your WordPress Media Library as a custom user avatar. Add your own Default Avatar.
Author: Bangbay Siboliban
Author URI: http://siboliban.org/
Version:  1.5.4
Text Domain: wp-user-avatar
Domain Path: /lang/
*/

if(!defined('ABSPATH')){
  die(__('You are not allowed to call this page directly.'));
  @header('Content-Type:'.get_option('html_type').';charset='.get_option('blog_charset'));
}

// Define paths and variables
define('WPUA_VERSION', ' 1.5.4');
define('WPUA_FOLDER', basename(dirname(__FILE__)));
define('WPUA_ABSPATH', trailingslashit(str_replace('\\', '/', WP_PLUGIN_DIR.'/'.WPUA_FOLDER)));
define('WPUA_URLPATH', trailingslashit(plugins_url(WPUA_FOLDER)));

// Define global variables
$avatar_default = get_option('avatar_default');
$show_avatars = get_option('show_avatars');

$wpua_allow_upload = get_option('wp_user_avatar_allow_upload');
$wpua_avatar_default = get_option('avatar_default_wp_user_avatar');
$wpua_disable_gravatar = get_option('wp_user_avatar_disable_gravatar');
$wpua_edit_avatar = get_option('wp_user_avatar_edit_avatar');
$wpua_tinymce = get_option('wp_user_avatar_tinymce');

$mustache_original = WPUA_URLPATH.'images/wp-user-avatar.png';
$mustache_medium = WPUA_URLPATH.'images/wp-user-avatar-300x300.png';
$mustache_thumbnail = WPUA_URLPATH.'images/wp-user-avatar-150x150.png';
$mustache_avatar = WPUA_URLPATH.'images/wp-user-avatar-96x96.png';
$mustache_admin = WPUA_URLPATH.'images/wp-user-avatar-32x32.png';
$ssl = is_ssl() ? 's' : "";

// Check for updates
$wpua_default_avatar_updated = get_option('wp_user_avatar_default_avatar_updated');
$wpua_users_updated = get_option('wp_user_avatar_users_updated');
$wpua_media_updated = get_option('wp_user_avatar_media_updated');

// Max upload size
if(!function_exists('wp_max_upload_size')){
  require_once(ABSPATH.'wp-admin/includes/template.php');
}

// Server upload size limit
$upload_size_limit = wp_max_upload_size();
// Convert to KB
if($upload_size_limit > 1024){
  $upload_size_limit /= 1024;
}
$upload_size_limit_with_units = (int) $upload_size_limit.'KB';

// User upload size limit
$wpua_user_upload_size_limit = get_option('wp_user_avatar_upload_size_limit');
if($wpua_user_upload_size_limit == 0 || $wpua_user_upload_size_limit > wp_max_upload_size()){
  $wpua_user_upload_size_limit = wp_max_upload_size();
}
// Value in bytes
$wpua_upload_size_limit = $wpua_user_upload_size_limit;
// Convert to KB
if($wpua_user_upload_size_limit > 1024){
  $wpua_user_upload_size_limit /= 1024;
}
$wpua_upload_size_limit_with_units = (int) $wpua_user_upload_size_limit.'KB';

// Load add-ons
if((bool) $wpua_tinymce == 1){
  include_once(WPUA_ABSPATH.'includes/tinymce.php');
}

// Load translations
load_plugin_textdomain('wp-user-avatar', '', WPUA_FOLDER.'/lang');

// Initialize default settings
register_activation_hook(WPUA_ABSPATH.'wp-user-avatar.php', 'wpua_options');

// Remove subscribers edit_posts capability
register_deactivation_hook(WPUA_ABSPATH.'wp-user-avatar.php', 'wpua_deactivate');

// Settings saved to wp_options
function wpua_options(){
  add_option('avatar_default_wp_user_avatar', "");
  add_option('wp_user_avatar_allow_upload', '0');
  add_option('wp_user_avatar_disable_gravatar', '0');
  add_option('wp_user_avatar_edit_avatar', '1');
  add_option('wp_user_avatar_tinymce', '1');
  add_option('wp_user_avatar_upload_size_limit', '0');
}
add_action('admin_init', 'wpua_options');

// Update default avatar to new format
if(empty($wpua_default_avatar_updated)){
  function wpua_default_avatar(){
    global $avatar_default, $mustache_original, $wpua_avatar_default;
    // If default avatar is the old mustache URL, update it
    if($avatar_default == $mustache_original){
      update_option('avatar_default', 'wp_user_avatar');
    }
    // If user had an image URL as the default avatar, replace with ID instead
    if(!empty($wpua_avatar_default)){
      $wpua_avatar_default_image = wp_get_attachment_image_src($wpua_avatar_default, 'medium');
      if($avatar_default == $wpua_avatar_default_image[0]){
        update_option('avatar_default', 'wp_user_avatar');
      }
    }
    update_option('wp_user_avatar_default_avatar_updated', '1');
  }
  add_action('admin_init', 'wpua_default_avatar');
}

// Rename user meta to match database settings
if(empty($wpua_users_updated)){
  function wpua_user_meta(){
    global $blog_id, $wpdb;
    $wpua_metakey = $wpdb->get_blog_prefix($blog_id).'user_avatar';
    // If database tables start with something other than wp_
    if($wpua_metakey != 'wp_user_avatar'){
      $users = get_users();
      // Move current user metakeys to new metakeys
      foreach($users as $user){
        $wpua = get_user_meta($user->ID, 'wp_user_avatar', true);
        if(!empty($wpua)){
          update_user_meta($user->ID, $wpua_metakey, $wpua);
          delete_user_meta($user->ID, 'wp_user_avatar');
        }
      }
    }
    update_option('wp_user_avatar_users_updated', '1'); 
  }
  add_action('admin_init', 'wpua_user_meta');
}

// Add media state to existing avatars
if(empty($wpua_media_updated)){
  function wpua_media_state(){
    global $blog_id, $wpdb;
    // Find all users with WPUA
    $wpua_metakey = $wpdb->get_blog_prefix($blog_id).'user_avatar';
    $wpuas = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->usermeta WHERE meta_key = %s AND meta_value != %d AND meta_value != %d", $wpua_metakey, 0, ""));
    foreach($wpuas as $usermeta){
      add_post_meta($usermeta->meta_value, '_wp_attachment_wp_user_avatar', $usermeta->user_id);
    }
    update_option('wp_user_avatar_media_updated', '1');
  }
  add_action('admin_init', 'wpua_media_state');
}

// Settings for Subscribers
if((bool) $wpua_allow_upload == 1){
  // Allow multipart data in form
  function wpua_add_edit_form_multipart_encoding(){
    echo ' enctype="multipart/form-data"';
  }
  add_action('user_edit_form_tag', 'wpua_add_edit_form_multipart_encoding');

  // Check user role
  function check_user_role($role, $user_id=null){
    global $current_user;
    $user = is_numeric($user_id) ? get_userdata($user_id) : $current_user->ID;
    if(empty($user)){
      return false;
    }
    return in_array($role, (array) $user->roles);
  }

  // Give subscribers edit_posts capability
  function wpua_subscriber_add_cap(){
    global $blog_id, $wpdb;
    $wp_user_roles = $wpdb->get_blog_prefix($blog_id).'user_roles';
    $user_roles = get_option($wp_user_roles);
    $user_roles['subscriber']['capabilities']['edit_posts'] = true;
    update_option($wp_user_roles, $user_roles);
  }
  add_action('admin_init', 'wpua_subscriber_add_cap');

  // Remove menu items
  function wpua_subscriber_remove_menu_pages(){
    global $current_user;
    if(check_user_role('subscriber', $current_user->ID)){
      remove_menu_page('edit.php');
      remove_menu_page('edit-comments.php');
      remove_menu_page('tools.php');
    }
  }
  add_action('admin_menu', 'wpua_subscriber_remove_menu_pages');

  // Remove menu bar items
  function wpua_subscriber_remove_menu_bar_items(){
    global $current_user, $wp_admin_bar;
    if(check_user_role('subscriber', $current_user->ID)){
      $wp_admin_bar->remove_menu('comments');
      $wp_admin_bar->remove_menu('new-content');
    }
  }
  add_action('wp_before_admin_bar_render', 'wpua_subscriber_remove_menu_bar_items');

  // Remove dashboard items
  function wpua_subscriber_remove_dashboard_widgets(){
    global $current_user;
    if(check_user_role('subscriber', $current_user->ID)){
      remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
      remove_meta_box('dashboard_recent_drafts', 'dashboard', 'side');
      remove_meta_box('dashboard_right_now', 'dashboard', 'normal');
    }
  }
  add_action('wp_dashboard_setup', 'wpua_subscriber_remove_dashboard_widgets');

  // Restrict access to pages
  function wpua_subscriber_offlimits(){
    global $current_user, $pagenow, $wpua_edit_avatar;
    if($wpua_edit_avatar == 1){
      $offlimits = array('edit.php', 'edit-comments.php', 'post-new.php', 'tools.php');
    } else {
      $offlimits = array('edit.php', 'edit-comments.php', 'post.php', 'post-new.php', 'tools.php');
    }
    if(check_user_role('subscriber', $current_user->ID)){
      if(in_array($pagenow, $offlimits)){
        do_action('admin_page_access_denied');
        wp_die(__('You do not have sufficient permissions to access this page.'));
      }
    }
  }
  add_action('admin_init', 'wpua_subscriber_offlimits');
}

// Remove subscribers edit_posts capability
function wpua_subscriber_remove_cap(){
  global $blog_id, $wpdb;
  $wp_user_roles = $wpdb->get_blog_prefix($blog_id).'user_roles';
  $user_roles = get_option($wp_user_roles);
  unset($user_roles['subscriber']['capabilities']['edit_posts']);
  update_option($wp_user_roles, $user_roles);
}

// On deactivation
function wpua_deactivate(){
  // Remove subscribers edit_posts capability
  wpua_subscriber_remove_cap();
  // Reset all default avatar to Mystery Man
  update_option('avatar_default', 'mystery');
}

// WP User Avatar
if(!class_exists('wp_user_avatar')){
  class wp_user_avatar{
    function wp_user_avatar(){
      global $current_screen, $current_user, $pagenow, $show_avatars, $wpua_allow_upload, $wpua_upload_size_limit;
      // Add WPUA to profile
      if(current_user_can('upload_files') || ((bool) $wpua_allow_upload == 1 && is_user_logged_in())){
        // For themes that use this function
        if(!function_exists('get_current_screen')){
          require_once(ABSPATH.'wp-admin/includes/screen.php');
        }
        // Profile functions and scripts
        add_action('show_user_profile', array('wp_user_avatar', 'wpua_action_show_user_profile'));
        add_action('edit_user_profile', array($this, 'wpua_action_show_user_profile'));
        add_action('personal_options_update', array($this, 'wpua_action_process_option_update'));
        add_action('edit_user_profile_update', array($this, 'wpua_action_process_option_update'));
        add_action('show_user_profile', array($this, 'wpua_media_upload_scripts'));
        add_action('edit_user_profile', array($this, 'wpua_media_upload_scripts'));
        // Admin scripts
        if($pagenow == 'options-discussion.php' || ($pagenow == 'options-general.php' && isset($_GET['page']) && $_GET['page'] == 'wp-user-avatar')){
          add_action('admin_enqueue_scripts', array($this, 'wpua_media_upload_scripts'));
        }
        // Prefilter upload size
        if(!current_user_can('upload_files')){
          add_filter('wp_handle_upload_prefilter', array($this, 'wpua_handle_upload_prefilter'), 10, 1);
        }
        // Admin menu settings
        add_action('admin_menu', 'wpua_admin');
        add_filter('plugin_action_links', array($this, 'wpua_plugin_settings_links'), 10, 2);
        // Hide column in Users table if default avatars are enabled
        if(is_admin() && (bool) $show_avatars == 0){
          add_filter('manage_users_columns', array($this, 'wpua_add_column'), 10, 1);
          add_filter('manage_users_custom_column', array($this, 'wpua_show_column'), 10, 3);
        }
      }
    }

    // Add to edit user profile
    function wpua_action_show_user_profile($user){
      global $blog_id, $current_user, $post, $show_avatars, $wpdb, $wpua_allow_upload, $wpua_upload_size_limit_with_units;
      // Get WPUA attachment ID
      $wpua = get_user_meta($user->ID, $wpdb->get_blog_prefix($blog_id).'user_avatar', true);
      // Show remove button if WPUA is set
      $hide_remove = !has_wp_user_avatar($user->ID) ? ' wpua-hide' : "";
      // If avatars are enabled, get original avatar image or show blank
      $avatar_medium_src = (bool) $show_avatars == 1 ? wpua_get_avatar_original($user->user_email, 96) : includes_url().'images/blank.gif';
      // Check if user has wp_user_avatar, if not show image from above
      $avatar_medium = has_wp_user_avatar($user->ID) ? get_wp_user_avatar_src($user->ID, 'medium') : $avatar_medium_src;
      // Check if user has wp_user_avatar, if not show image from above
      $avatar_thumbnail = has_wp_user_avatar($user->ID) ? get_wp_user_avatar_src($user->ID, 96) : $avatar_medium_src;
      // Change text on message based on current user
      $profile = ($current_user->ID == $user->ID) ? '&ldquo;'.__('Update Profile').'&rdquo;' : '&ldquo;'.__('Update User').'&rdquo;';
    ?>
      <?php if(class_exists('bbPress') && bbp_is_edit()) : // Add to bbPress profile with same style ?>
        <h2 class="entry-title"><?php _e('Avatar'); ?></h2>
        <fieldset class="bbp-form">
          <legend><?php _e('Image'); ?></legend>
      <?php else : // Add to profile with admin style ?>
        <h3><?php _e('Avatar') ?></h3>
        <table class="form-table">
          <tr>
            <th><label for="wp_user_avatar"><?php _e('Image'); ?></label></th>
            <td>
      <?php endif; ?>
      <input type="hidden" name="wp-user-avatar" id="wp-user-avatar" value="<?php echo $wpua; ?>" />
      <?php if(current_user_can('upload_files')) : // Button to launch Media uploader ?>
        <p><button type="button" class="button" id="wpua-add" name="wpua-add"><?php _e('Edit Image'); ?></button></p>
      <?php elseif(!current_user_can('upload_files') && !has_wp_user_avatar($current_user->ID)) : // Upload button ?>
        <input name="wp-user-avatar-file" id="wpua-file" type="file" />
        <button type="submit" class="button" id="upload-wp-user-avatar" name="upload-wp-user-avatar" value="<?php _e('Upload'); ?>"><?php _e('Upload'); ?></button>
        <p>
          <?php printf(__('Maximum upload file size: %d%s.'), esc_html($wpua_upload_size_limit_with_units), esc_html('KB')); ?>
          <br />
          <?php _e('Allowed Files'); ?>: <?php _e('<code>jpg jpeg png gif</code>'); ?>
        </p>
      <?php elseif(!current_user_can('upload_files') && has_wp_user_avatar($current_user->ID) && wpua_author($wpua, $current_user->ID)) : // Edit button ?>
        <?php $edit_attachment_link = add_query_arg(array('post' => $wpua, 'action' => 'edit'), admin_url('post.php')); ?>
        <p><button type="button" class="button" id="wpua-edit" name="wpua-edit" onclick="window.open('<?php echo $edit_attachment_link; ?>', '_self');"><?php _e('Edit Image'); ?></button></p>
      <?php endif; ?>
      <p id="wpua-preview">
        <img src="<?php echo $avatar_medium; ?>" alt="" />
        <?php _e('Original Size'); ?>
      </p>
      <p id="wpua-thumbnail">
        <img src="<?php echo $avatar_thumbnail; ?>" alt="" />
        <?php _e('Thumbnail'); ?>
      </p>
      <p><button type="button" class="button<?php echo $hide_remove; ?>" id="wpua-remove" name="wpua-remove"><?php _e('Remove'); ?></button></p>
      <p id="wpua-message"><?php printf(__('Click %s to save your changes', 'wp-user-avatar'), $profile); ?></p>
      <?php if(class_exists('bbPress') && bbp_is_edit()) : // Add to bbPress profile with same style ?>
        </fieldset>
      <?php else : // Add to profile with admin style ?>
            </td>
          </tr>
        </table>
      <?php endif; ?>
    <?php
    }

    // Set upload size limit for users without upload_files capability
    function wpua_handle_upload_prefilter($file){
      global $wpua_upload_size_limit, $wpua_upload_size_limit_with_units;
      $size = $file['size'];
      if($size > $wpua_upload_size_limit){
        $file['error'] = __('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.');
      }
      return $file;
    }

    // Update user meta
    function wpua_action_process_option_update($user_id){
      global $blog_id, $wpdb;
      // Check if user has upload_files capability
      if(current_user_can('upload_files')){
        $wpua_id = isset($_POST['wp-user-avatar']) ? intval($_POST['wp-user-avatar']) : "";
        $wpdb->query($wpdb->prepare("DELETE FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %d", '_wp_attachment_wp_user_avatar', $user_id));
        add_post_meta($wpua_id, '_wp_attachment_wp_user_avatar', $user_id);
        update_user_meta($user_id, $wpdb->get_blog_prefix($blog_id).'user_avatar', $wpua_id);
      } else {
        if(isset($_POST['wp-user-avatar']) && empty($_POST['wp-user-avatar'])){
          // Uploads by user
          $attachments = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE post_author = %d AND post_type = %s", $user_id, 'attachment'));
          foreach($attachments as $attachment){
            // Delete attachment if not used by another user
            if(!wpua_image($attachment->ID, $user_id)){
              wp_delete_post($attachment->ID);
            }
          }
          update_user_meta($user_id, $wpdb->get_blog_prefix($blog_id).'user_avatar', "");
        }
        // Create attachment from upload
        if(isset($_POST['upload-wp-user-avatar']) && $_POST['upload-wp-user-avatar']){
          if(!function_exists('wp_handle_upload')){
            require_once(ABSPATH.'wp-admin/includes/admin.php');
            require_once(ABSPATH.'wp-admin/includes/file.php');
          }
          $name = $_FILES['wp-user-avatar-file']['name'];
          $file = wp_handle_upload($_FILES['wp-user-avatar-file'], array('test_form' => false));
          $type = $file['type'];
          // Allow only JPG, GIF, PNG
          if($file['error'] || !preg_match('/(jpe?g|gif|png)$/i', $type)){
            if($file['error']){
              wp_die($file['error']);
            } else {
              wp_die(__('Sorry, this file type is not permitted for security reasons.'));
            }
          }
          // Break out file info
          $name_parts = pathinfo($name);
          $name = trim(substr($name, 0, -(1 + strlen($name_parts['extension']))));
          $url = $file['url'];
          $file = $file['file'];
          $title = $name;
          // Use image exif/iptc data for title if possible
          if($image_meta = @wp_read_image_metadata($file)){
            if(trim($image_meta['title']) && !is_numeric(sanitize_title($image_meta['title']))){
              $title = $image_meta['title'];
            }
          }
          // Construct the attachment array
          $attachment = array(
            'guid'           => $url,
            'post_mime_type' => $type,
            'post_title'     => $title
          );
          // This should never be set as it would then overwrite an existing attachment
          if(isset($attachment['ID'])){
            unset($attachment['ID']);
          }
          // Save the attachment metadata
          $attachment_id = wp_insert_attachment($attachment, $file);
          if(!is_wp_error($attachment_id)){
            require_once(ABSPATH.'wp-admin/includes/image.php');
            wp_update_attachment_metadata($attachment_id, wp_generate_attachment_metadata($attachment_id, $file));
            $wpdb->query($wpdb->prepare("DELETE FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %d", '_wp_attachment_wp_user_avatar', $user_id));
            add_post_meta($attachment_id, '_wp_attachment_wp_user_avatar', $user_id);
            update_user_meta($user_id, $wpdb->get_blog_prefix($blog_id).'user_avatar', $attachment_id);
          }
        }
      }
    }

    // Add button to attach image for WP 3.4 and older
    function wpua_add_attachment_field_to_edit($fields, $post){
      $image = wp_get_attachment_image_src($post->ID, "medium");
      $button = '<button type="button" class="button" id="set-wp-user-avatar-image" name="set-wp-user-avatar-image" onclick="wpuaSetAvatar(\''.$post->ID.'\', \''.$image[0].'\')">'.__('Select Image').'</button>';
      $fields['wp-user-avatar'] = array(
        'label' => __('WP User Avatar', 'wp-user-avatar'),
        'input' => 'html',
        'html' => $button
      );
      return $fields;
    }

    // Add settings link on plugin page
    function wpua_plugin_settings_links($links, $file){
      if(basename($file) == basename(plugin_basename(__FILE__))){
        $settings_link = '<a href="'.add_query_arg(array('page' => 'wp-user-avatar'), admin_url('options-general.php')).'">'.__('Settings').'</a>';
        $links = array_merge($links, array($settings_link));
      }
      return $links;
    }

    // Add column to Users table
    function wpua_add_column($columns){
      return $columns + array('wp-user-avatar' => __('WP User Avatar', 'wp-user-avatar'));
    }

    // Show thumbnail in Users table
    function wpua_show_column($value, $column_name, $user_id){
      global $blog_id, $wpdb;
      $wpua = get_user_meta($user_id, $wpdb->get_blog_prefix($blog_id).'user_avatar', true);
      $wpua_image = wp_get_attachment_image($wpua, array(32,32));
      if($column_name == 'wp-user-avatar'){
        return $wpua_image;
      }
    }

    // Media uploader
    function wpua_media_upload_scripts($user=""){
      global $mustache_admin, $pagenow, $show_avatars, $wpua_upload_size_limit;
      wp_enqueue_script('jquery');
      if(current_user_can('upload_files')){
        wp_enqueue_script('admin-bar');
        wp_enqueue_media();
      }
      wp_enqueue_script('wp-user-avatar', WPUA_URLPATH.'js/wp-user-avatar.js', array('jquery'), WPUA_VERSION, true);
      wp_enqueue_style('wp-user-avatar', WPUA_URLPATH.'css/wp-user-avatar.css', "", WPUA_VERSION);
      // Admin scripts
      if($pagenow == 'options-discussion.php' || ($pagenow == 'options-general.php' && isset($_GET['page']) && $_GET['page'] == 'wp-user-avatar')){
        // Size limit slider
        wp_enqueue_script('jquery-ui-slider');
        wp_enqueue_style('wp-user-avatar-jqueryui', WPUA_URLPATH.'css/jquery.ui.slider.css', "", null);
        // Remove/edit settings
        $wpua_custom_scripts = array('section' => __('Default Avatar'), 'edit_image' => __('Edit Image'), 'select_image' => __('Select Image'), 'avatar_thumb' => $mustache_admin);
        wp_localize_script('wp-user-avatar', 'wpua_custom', $wpua_custom_scripts);
        // Settings control
        wp_enqueue_script('wp-user-avatar-admin', WPUA_URLPATH.'js/wp-user-avatar-admin.js', array('wp-user-avatar'), WPUA_VERSION, true);
        $wpua_admin_scripts = array('upload_size_limit' => $wpua_upload_size_limit, 'max_upload_size' => wp_max_upload_size());
        wp_localize_script('wp-user-avatar-admin', 'wpua_admin', $wpua_admin_scripts);
      } else {
        // User remove/edit settings
        $avatar_medium_src = (bool) $show_avatars == 1 ? wpua_get_avatar_original($user->user_email, 96) : includes_url().'images/blank.gif';
        $wpua_custom_scripts = array('section' => $user->display_name, 'edit_image' => __('Edit Image'), 'select_image' => __('Select Image'), 'avatar_thumb' => $avatar_medium_src);
        wp_localize_script('wp-user-avatar', 'wpua_custom', $wpua_custom_scripts);
      }
    }
  }

  // Returns true if user has Gravatar-hosted image
  function wpua_has_gravatar($id_or_email, $has_gravatar=false, $user="", $email=""){
    global $ssl;
    if(!is_object($id_or_email) && !empty($id_or_email)){
      // Find user by ID or e-mail address
      $user = is_numeric($id_or_email) ? get_user_by('id', $id_or_email) : get_user_by('email', $id_or_email);
      // Get registered user e-mail address
      $email = !empty($user) ? $user->user_email : "";
    }
    // Check if Gravatar image returns 200 (OK) or 404 (Not Found)
    if(!empty($email)){
      $hash = md5(strtolower(trim($email)));
      $gravatar = 'http'.$ssl.'://www.gravatar.com/avatar/'.$hash.'?d=404';
      $headers = @get_headers($gravatar);
      $has_gravatar = !preg_match("|200|", $headers[0]) ? false : true;
    }
    return $has_gravatar;
  }

  // Returns true if user has wp_user_avatar
  function has_wp_user_avatar($id_or_email="", $has_wpua=false, $user="", $user_id=""){
    global $blog_id, $wpdb;
    if(!is_object($id_or_email) && !empty($id_or_email)){
      // Find user by ID or e-mail address
      $user = is_numeric($id_or_email) ? get_user_by('id', $id_or_email) : get_user_by('email', $id_or_email);
      // Get registered user ID
      $user_id = !empty($user) ? $user->ID : "";
    }
    $wpua = get_user_meta($user_id, $wpdb->get_blog_prefix($blog_id).'user_avatar', true);
    $has_wpua = !empty($wpua) ? true : false;
    return $has_wpua;
  }

  // Replace get_avatar only in get_wp_user_avatar
  function wpua_get_avatar_filter($avatar, $id_or_email, $size="", $default="", $alt=""){
    global $avatar_default, $comment, $mustache_admin, $mustache_avatar, $mustache_medium, $mustache_original, $mustache_thumbnail, $post, $wpua_avatar_default, $wpua_disable_gravatar;
    // User has WPUA
    if(is_object($id_or_email)){
      if(!empty($comment->comment_author_email)){
        $avatar = get_wp_user_avatar($comment, $size, $default, $alt);
      } else {
        $avatar = get_wp_user_avatar('unknown@gravatar.com', $size, $default, $alt);
      }
    } else {
      if(has_wp_user_avatar($id_or_email)){
        $avatar = get_wp_user_avatar($id_or_email, $size, $default, $alt);
      // User has Gravatar and Gravatar is not disabled
      } elseif(wpua_has_gravatar($id_or_email) && $wpua_disable_gravatar != 1){
        $avatar = $avatar;
      // User doesn't have WPUA or Gravatar and Default Avatar is wp_user_avatar, show custom Default Avatar
      } elseif($avatar_default == 'wp_user_avatar'){
        // Show custom Default Avatar
        if(!empty($wpua_avatar_default)){
          // Get image
          $wpua_avatar_default_image = wp_get_attachment_image_src($wpua_avatar_default, array($size,$size));
          // Image src
          $default = $wpua_avatar_default_image[0];
          // Add dimensions if numeric size
          $dimensions = ' width="'.$wpua_avatar_default_image[1].'" height="'.$wpua_avatar_default_image[2].'"';
          $defaultcss = "";
        } else {
          // Get mustache image based on numeric size comparison
          if($size > get_option('medium_size_w')){
            $default = $mustache_original;
          } elseif($size <= get_option('medium_size_w') && $size > get_option('thumbnail_size_w')){
            $default = $mustache_medium;
          } elseif($size <= get_option('thumbnail_size_w') && $size > 96){
            $default = $mustache_thumbnail;
          } elseif($size <= 96 && $size > 32){
            $default = $mustache_avatar;
          } elseif($size <= 32){
            $default = $mustache_admin;
          }
          // Add dimensions if numeric size
          $dimensions = ' width="'.$size.'" height="'.$size.'"';
          $defaultcss = ' avatar-default';
        }
        // Construct the img tag
        $avatar = "<img src='".$default."'".$dimensions." alt='".$alt."' class='wp-user-avatar wp-user-avatar-".$size." avatar avatar-".$size." photo'".$defaultcss." />";
      }
    }
    return $avatar;
  }
  add_filter('get_avatar', 'wpua_get_avatar_filter', 10, 6);

  // Get original avatar, for when user removes wp_user_avatar
  function wpua_get_avatar_original($id_or_email, $size="", $default="", $alt=""){
    global $avatar_default, $mustache_avatar, $wpua_avatar_default, $wpua_disable_gravatar;
    // Remove get_avatar filter only in admin
    if(is_admin()){
      remove_filter('get_avatar', 'wpua_get_avatar_filter');
    }
    if((bool) $wpua_disable_gravatar != 1){
      // User doesn't have Gravatar and Default Avatar is wp_user_avatar, show custom Default Avatar
      if(!wpua_has_gravatar($id_or_email) && $avatar_default == 'wp_user_avatar'){
        // Show custom Default Avatar
        if(!empty($wpua_avatar_default)){
          $wpua_avatar_default_image = wp_get_attachment_image_src($wpua_avatar_default, array($size,$size));
          $default = $wpua_avatar_default_image[0];
        } else {
          $default = $mustache_avatar;
        }
      } else {
        // Get image from Gravatar, whether it's the user's image or default image
        $wpua_image = get_avatar($id_or_email, $size);
        // Takes the img tag, extracts the src
        $output = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $wpua_image, $matches, PREG_SET_ORDER);
        $default = $matches [0] [1];
      }
    } else {
      if(!empty($wpua_avatar_default)){
        $wpua_avatar_default_image = wp_get_attachment_image_src($wpua_avatar_default, array($size,$size));
        $default = $wpua_avatar_default_image[0];
      } else {
        $default = $mustache_avatar;
      }
    }
    return $default;
  }

  // Find WPUA, show get_avatar if empty
  function get_wp_user_avatar($id_or_email="", $size='96', $align="", $alt=""){
    global $avatar_default, $blog_id, $comment, $post, $wpdb;
    // Checks if comment
    if(is_object($id_or_email)){
      // Checks if comment author is registered user by user ID
      if($comment->user_id != 0){
        $id_or_email = $comment->user_id;
      // Checks that comment author isn't anonymous
      } elseif(!empty($comment->comment_author_email)){
        // Checks if comment author is registered user by e-mail address
        $user = get_user_by('email', $comment->comment_author_email);
        // Get registered user info from profile, otherwise e-mail address should be value
        $id_or_email = !empty($user) ? $user->ID : $comment->comment_author_email;
      }
      $alt = $comment->comment_author;
    } else {
      if(!empty($id_or_email)){
        // Find user by ID or e-mail address
        $user = is_numeric($id_or_email) ? get_user_by('id', $id_or_email) : get_user_by('email', $id_or_email);
      } else {
        // Find author's name if id_or_email is empty
        $author_name = get_query_var('author_name');
        if(is_author()){
          // On author page, get user by page slug
          $user = get_user_by('slug', $author_name);
        } else {
          // On post, get user by author meta
          $user_id = get_the_author_meta('ID');
          $user = get_user_by('id', $user_id);
        }
      }
      // Set user's ID and name
      if(!empty($user)){
        $id_or_email = $user->ID;
        $alt = $user->display_name;
      }
    }
    // Checks if user has WPUA
    $wpua_meta = !empty($id_or_email) ? get_the_author_meta($wpdb->get_blog_prefix($blog_id).'user_avatar', $id_or_email) : "";
    // Add alignment class
    $alignclass = !empty($align) ? ' align'.$align : "";
    // User has WPUA, bypass get_avatar
    if(!empty($wpua_meta)){
      // Numeric size use size array
      $get_size = is_numeric($size) ? array($size,$size) : $size;
      // Get image src
      $wpua_image = wp_get_attachment_image_src($wpua_meta, $get_size);
      // Add dimensions to img only if numeric size was specified
      $dimensions = is_numeric($size) ? ' width="'.$wpua_image[1].'" height="'.$wpua_image[2].'"' : "";
      // Construct the img tag
      $avatar = '<img src="'.$wpua_image[0].'"'.$dimensions.' alt="'.$alt.'" class="wp-user-avatar wp-user-avatar-'.$size.$alignclass.' avatar avatar avatar-'.$size.' photo" />';
    } else {
      // Get numeric sizes for non-numeric sizes based on media options
      if($size == 'original' || $size == 'large' || $size == 'medium' || $size == 'thumbnail'){
        $get_size = ($size == 'original') ? get_option('large_size_w') : get_option($size.'_size_w');
      } else {
        // Numeric sizes leave as-is
        $get_size = $size;
      }
      // User with no WPUA uses get_avatar
      $avatar = get_avatar($id_or_email, $get_size, $default="", $alt="");
      // Remove width and height for non-numeric sizes
      if(!is_numeric($size)){
        $avatar = preg_replace("/(width|height)=\'\d*\'\s/", "", $avatar);
        $avatar = preg_replace('/(width|height)=\"\d*\"\s/', "", $avatar);
        $avatar = str_replace('wp-user-avatar wp-user-avatar-'.$get_size.' ', "", $avatar);
        $avatar = str_replace("class='", "class='wp-user-avatar wp-user-avatar-".$size.$alignclass." ", $avatar);
      }
    }
    return $avatar;
  }

  // Return just the image src
  function get_wp_user_avatar_src($id_or_email, $size="", $align=""){
    $wpua_image_src = "";
    // Gets the avatar img tag
    $wpua_image = get_wp_user_avatar($id_or_email, $size, $align);
    // Takes the img tag, extracts the src
    if(!empty($wpua_image)){
      $output = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $wpua_image, $matches, PREG_SET_ORDER);
      $wpua_image_src = $matches [0] [1];
    }
    return $wpua_image_src;
  }

  // Shortcode
  function wpua_shortcode($atts, $content){
    global $blog_id, $wpdb;
    // Set shortcode attributes
    extract(shortcode_atts(array('user' => "", 'size' => '96', 'align' => "", 'link' => "", 'target' => ""), $atts));
    // Find user by ID, login, slug, or e-mail address
    if(!empty($user)){
      $user = is_numeric($user) ? get_user_by('id', $user) : get_user_by('login', $user);
      $user = empty($user) ? get_user_by('slug', $user) : $user;
      $user = empty($user) ? get_user_by('email', $user) : $user;
    }
    // Get user ID
    $id_or_email = !empty($user) ? $user->ID : "";
    // Check if link is set
    if(!empty($link)){
      // CSS class is same as link type, except for URL
      $link_class = $link;
      // Open in new window
      $target_link = !empty($target) ? ' target="'.$target.'"' : "";
      if($link == 'file'){
        // Get image src
        $image_link = get_wp_user_avatar_src($id_or_email, 'original', $align);
      } elseif($link == 'attachment'){
        // Get attachment URL
        $image_link = get_attachment_link(get_the_author_meta($wpdb->get_blog_prefix($blog_id).'user_avatar', $id_or_email));
      } else {
        // URL
        $image_link = $link;
        $link_class = 'custom';
      }
      // Wrap the avatar inside the link
      $avatar = '<a href="'.$image_link.'" class="wp-user-avatar-link wp-user-avatar-'.$link_class.'"'.$target_link.'>'.get_wp_user_avatar($id_or_email, $size, $align).'</a>';
    } else {
      // Get WPUA as normal
      $avatar = get_wp_user_avatar($id_or_email, $size, $align);
    }
    return $avatar;
  }
  add_shortcode('avatar', 'wpua_shortcode');

  // Add default avatar
  function wpua_add_default_avatar($avatar_list=null){
    global $avatar_default, $mustache_admin, $mustache_medium, $wpua_avatar_default, $wpua_disable_gravatar;
    // Remove get_avatar filter
    remove_filter('get_avatar', 'wpua_get_avatar_filter');
    // Set avatar_list variable
    $avatar_list = "";
    // Set avatar defaults
    $avatar_defaults = array(
      'mystery' => __('Mystery Man'),
      'blank' => __('Blank'),
      'gravatar_default' => __('Gravatar Logo'),
      'identicon' => __('Identicon (Generated)'),
      'wavatar' => __('Wavatar (Generated)'),
      'monsterid' => __('MonsterID (Generated)'),
      'retro' => __('Retro (Generated)')
    );
    // No Default Avatar, set to Mystery Man
    if(empty($avatar_default)){
      $avatar_default = 'mystery';
    }
    // Take avatar_defaults and get examples for unknown@gravatar.com
    foreach($avatar_defaults as $default_key => $default_name){
      $avatar = get_avatar('unknown@gravatar.com', 32, $default_key);
      $selected = ($avatar_default == $default_key) ? 'checked="checked" ' : "";
      $avatar_list .= "\n\t<label><input type='radio' name='avatar_default' id='avatar_{$default_key}' value='".esc_attr($default_key)."' {$selected}/> ";
      $avatar_list .= preg_replace("/src='(.+?)'/", "src='\$1&amp;forcedefault=1'", $avatar);
      $avatar_list .= ' '.$default_name.'</label>';
      $avatar_list .= '<br />';
    }
    // Show remove link if custom Default Avatar is set
    if(!empty($wpua_avatar_default)){
      $avatar_thumb_src = wp_get_attachment_image_src($wpua_avatar_default, array(32,32));
      $avatar_thumb = $avatar_thumb_src[0];
      $hide_remove = "";
    } else {
      $avatar_thumb = $mustache_admin;
      $hide_remove = ' class="wpua-hide"';
    }
    // Default Avatar is wp_user_avatar, check the radio button next to it
    $selected_avatar = ($avatar_default == 'wp_user_avatar' || (bool) $wpua_disable_gravatar == 1) ? ' checked="checked" ' : "";
    // Wrap WPUA in div
    $avatar_thumb_img = '<div id="wpua-preview"><img src="'.$avatar_thumb.'" width="32" /></div>';
    // Add WPUA to list
    $wpua_list = "\n\t<label><input type='radio' name='avatar_default' id='wp_user_avatar_radio' value='wp_user_avatar'$selected_avatar /> ";
    $wpua_list .= preg_replace("/src='(.+?)'/", "src='\$1'", $avatar_thumb_img);
    $wpua_list .= ' '.__('WP User Avatar', 'wp-user-avatar').'</label>';
    $wpua_list .= '<p id="wpua-edit"><button type="button" class="button" id="wpua-add" name="wpua-add">'.__('Edit Image').'</button>';
    $wpua_list .= '<a href="#" id="wpua-remove"'.$hide_remove.'>'.__('Remove').'</a></p>';
    $wpua_list .= '<input type="hidden" id="wp-user-avatar" name="avatar_default_wp_user_avatar" value="'.$wpua_avatar_default.'">';
    $wpua_list .= '<p id="wpua-message">'.sprintf(__('Click %s to save your changes', 'wp-user-avatar'), '&ldquo;'.__('Save Changes').'&rdquo;').'</p>';
    if($wpua_disable_gravatar != 1){
      return $wpua_list.'<div id="wp-avatars">'.$avatar_list.'</div>';
    } else {
      return $wpua_list;
    }
  }
  add_filter('default_avatar_select', 'wpua_add_default_avatar', 10);

  // Add default avatar_default to whitelist
  function wpua_whitelist_options($whitelist_options){
    $whitelist_options['discussion'][] = 'avatar_default_wp_user_avatar';
    return $whitelist_options;
  }
  add_filter('whitelist_options', 'wpua_whitelist_options', 10);

  // Add media state
  function wpua_add_media_state($media_states){
    global $post, $wpua_avatar_default;
    $is_wpua = get_post_custom_values('_wp_attachment_wp_user_avatar', $post->ID);
    if(!empty($is_wpua)){
      $media_states[] = __('Avatar');
    }
    if(!empty($wpua_avatar_default) && ($wpua_avatar_default == $post->ID)){
      $media_states[] = __('Default Avatar');
    }
    return apply_filters('wpua_add_media_state', $media_states);
  }
  add_filter('display_media_states', 'wpua_add_media_state', 10, 1);

  // Check if image is used as WPUA
  function wpua_image($attachment_id, $user_id, $wpua_image=false){
    global $wpdb;
    $wpua = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->postmeta WHERE post_id = %d AND meta_key = %s AND meta_value != %d", $attachment_id, '_wp_attachment_wp_user_avatar', $user_id));
    if(!empty($wpua)){
      $wpua_image = true;
    }
    return $wpua_image;
  }

  // Check who owns image
  function wpua_author($attachment_id, $user_id, $wpua_author=false){
    $attachment = get_post($attachment_id);
    if(!empty($attachment) && $attachment->post_author == $user_id){
      $wpua_author = true;
    }
    return $wpua_author;
  }

  // Admin page
  function wpua_options_page(){
    global $show_avatars, $upload_size_limit_with_units, $wpua_allow_upload, $wpua_disable_gravatar, $wpua_edit_avatar, $wpua_tinymce, $wpua_upload_size_limit, $wpua_upload_size_limit_with_units;
    // Give subscribers edit_posts capability
    if(isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true' && empty($wpua_allow_upload)){
      wpua_subscriber_remove_cap();
    }
    $hide_size = ($wpua_allow_upload != 1) ? ' class="wpua-hide"' : "";
  ?>
    <div class="wrap">
      <?php screen_icon(); ?>
      <h2><?php _e('WP User Avatar', 'wp-user-avatar'); ?></h2>
      <form method="post" action="options.php">
        <?php settings_fields('wpua-settings-group'); ?>
        <?php do_settings_fields('wpua-settings-group', ""); ?>
        <table class="form-table">
          <tr valign="top">
            <th scope="row"><?php _e('Settings'); ?></th>
            <td>
              <fieldset>
                <legend class="screen-reader-text"><span><?php _e('Settings'); ?></span></legend>
                <label for="wp_user_avatar_tinymce">
                  <input name="wp_user_avatar_tinymce" type="checkbox" id="wp_user_avatar_tinymce" value="1" <?php checked($wpua_tinymce, 1); ?> />
                  <?php _e('Add avatar button to Visual Editor', 'wp-user-avatar'); ?>
                </label>
                <br />
                <label for="wp_user_avatar_allow_upload">
                  <input name="wp_user_avatar_allow_upload" type="checkbox" id="wp_user_avatar_allow_upload" value="1" <?php checked($wpua_allow_upload, 1); ?> />
                  <?php _e('Allow Contributors & Subscribers to upload avatars', 'wp-user-avatar'); ?>
                </label>
                <br />
                <label for="wp_user_avatar_disable_gravatar">
                  <input name="wp_user_avatar_disable_gravatar" type="checkbox" id="wp_user_avatar_disable_gravatar" value="1" <?php checked($wpua_disable_gravatar, 1); ?> />
                  <?php _e('Disable Gravatar and use only local avatars', 'wp-user-avatar'); ?>
                </label>
              </fieldset>
            </td>
          </tr>
          <tr id="wpua-size-limit" valign="top"<?php echo $hide_size; ?>>
            <th scope="row">
              <label for="wp_user_avatar_upload_size_limit">
                <?php _e('Upload Size Limit (only for Contributors & Subscribers)', 'wp-user-avatar'); ?>
              </label>
             </th>
            <td>
              <fieldset>
                <legend class="screen-reader-text"><span><?php _e('Upload Size Limit (only for Contributors & Subscribers)', 'wp-user-avatar'); ?></span></legend>
                <input name="wp_user_avatar_upload_size_limit" type="text" id="wp_user_avatar_upload_size_limit" value="<?php echo $wpua_upload_size_limit; ?>" class="regular-text" />
                <span id="wpua-readable-size"><?php echo $wpua_upload_size_limit_with_units; ?></span>
                <span id="wpua-readable-size-error"><?php printf(__('%s exceeds the maximum upload size for this site.'), ""); ?></span>
                <div id="wpua-slider"></div>
                <span class="description"><?php printf(__('Maximum upload file size: %d%s.'), esc_html(wp_max_upload_size()), esc_html(' bytes ('.$upload_size_limit_with_units.')')); ?></span>
                <br />
                <label for="wp_user_avatar_edit_avatar">
                  <input name="wp_user_avatar_edit_avatar" type="checkbox" id="wp_user_avatar_edit_avatar" value="1" <?php checked($wpua_edit_avatar, 1); ?> />
                  <?php _e('Allow users to edit avatars', 'wp-user-avatar'); ?>
                </label>
              </fieldset>
            </td>
          </tr>
        </table>
        <h3 class="title"><?php _e('Avatars'); ?></h3>
        <p><?php _e('An avatar is an image that follows you from weblog to weblog appearing beside your name when you comment on avatar enabled sites. Here you can enable the display of avatars for people who comment on your site.'); ?></p>
        <table class="form-table">
          <tr valign="top">
          <th scope="row"><?php _e('Avatar Display'); ?></th>
          <td>
            <fieldset>
              <legend class="screen-reader-text"><span><?php _e('Avatar Display'); ?></span></legend>
              <label for="show_avatars">
              <input type="checkbox" id="show_avatars" name="show_avatars" value="1" <?php checked($show_avatars, 1); ?> />
              <?php _e('Show Avatars'); ?>
              </label>
            </fieldset>
            </td>
          </tr>
          <tr valign="top">
            <th scope="row"><?php _e('Maximum Rating'); ?></th>
            <td>
              <fieldset>
                <legend class="screen-reader-text"><span><?php _e('Maximum Rating'); ?></span></legend>
                <?php
                  $ratings = array(
                    'G' => __('G &#8212; Suitable for all audiences'),
                    'PG' => __('PG &#8212; Possibly offensive, usually for audiences 13 and above'),
                    'R' => __('R &#8212; Intended for adult audiences above 17'),
                    'X' => __('X &#8212; Even more mature than above')
                  );
                  foreach ($ratings as $key => $rating) :
                    $selected = (get_option('avatar_rating') == $key) ? 'checked="checked"' : "";
                    echo "\n\t<label><input type='radio' name='avatar_rating' value='" . esc_attr($key) . "' $selected/> $rating</label><br />";
                  endforeach;
                ?>
              </fieldset>
            </td>
          </tr>
          <tr valign="top">
            <th scope="row"><?php _e('Default Avatar') ?></th>
            <td class="defaultavatarpicker">
              <fieldset>
                <legend class="screen-reader-text"><span><?php _e('Default Avatar'); ?></span></legend>
                <?php _e('For users without a custom avatar of their own, you can either display a generic logo or a generated one based on their e-mail address.'); ?><br />
                <?php echo wpua_add_default_avatar(); ?>
              </fieldset>
            </td>
          </tr>
        </table>
        <?php submit_button(); ?>
      </form>
    </div>
    <?php
  }

  // Whitelist settings
  function wpua_admin_settings(){
    register_setting('wpua-settings-group', 'avatar_rating');
    register_setting('wpua-settings-group', 'avatar_default');
    register_setting('wpua-settings-group', 'avatar_default_wp_user_avatar', 'intval');
    register_setting('wpua-settings-group', 'show_avatars', 'intval');
    register_setting('wpua-settings-group', 'wp_user_avatar_tinymce', 'intval');
    register_setting('wpua-settings-group', 'wp_user_avatar_allow_upload', 'intval');
    register_setting('wpua-settings-group', 'wp_user_avatar_disable_gravatar', 'intval');
    register_setting('wpua-settings-group', 'wp_user_avatar_edit_avatar', 'intval');
    register_setting('wpua-settings-group', 'wp_user_avatar_upload_size_limit', 'intval');
  }

  // Add options page and settings
  function wpua_admin(){
    add_options_page(__('WP User Avatar', 'wp-user-avatar'), __('WP User Avatar', 'wp-user-avatar'), 'manage_options', 'wp-user-avatar', 'wpua_options_page');
    add_action('admin_init', 'wpua_admin_settings');
  }

  // Initialize WPUA after other plugins are loaded
  function wpua_load(){
    global $wpua_instance;
    $wpua_instance = new wp_user_avatar();
  }
  add_action('plugins_loaded', 'wpua_load');
}
?>
