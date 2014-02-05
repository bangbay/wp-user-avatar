<?php
/**
 * @package WP User Avatar
 * @version 1.8
 */

// Define global variables
$avatar_default = get_option('avatar_default');
$show_avatars = get_option('show_avatars');
$wpua_allow_upload = get_option('wp_user_avatar_allow_upload');
$wpua_avatar_default = get_option('avatar_default_wp_user_avatar');
$wpua_disable_gravatar = get_option('wp_user_avatar_disable_gravatar');
$wpua_edit_avatar = get_option('wp_user_avatar_edit_avatar');
$wpua_resize_crop = get_option('wp_user_avatar_resize_crop');
$wpua_resize_h = get_option('wp_user_avatar_resize_h');
$wpua_resize_upload = get_option('wp_user_avatar_resize_upload');
$wpua_resize_w = get_option('wp_user_avatar_resize_w');
$wpua_tinymce = get_option('wp_user_avatar_tinymce');
$mustache_original = WPUA_URL.'images/wpua.png';
$mustache_medium = WPUA_URL.'images/wpua-300x300.png';
$mustache_thumbnail = WPUA_URL.'images/wpua-150x150.png';
$mustache_avatar = WPUA_URL.'images/wpua-96x96.png';
$mustache_admin = WPUA_URL.'images/wpua-32x32.png';

// Check for updates
$wpua_default_avatar_updated = get_option('wp_user_avatar_default_avatar_updated');
$wpua_users_updated = get_option('wp_user_avatar_users_updated');
$wpua_media_updated = get_option('wp_user_avatar_media_updated');

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

// Check for custom image sizes
$all_sizes = array_merge(get_intermediate_image_sizes(), array('original'));

// Settings saved to wp_options
function wpua_options(){
  add_option('avatar_default_wp_user_avatar', "");
  add_option('wp_user_avatar_allow_upload', '0');
  add_option('wp_user_avatar_disable_gravatar', '0');
  add_option('wp_user_avatar_edit_avatar', '1');
  add_option('wp_user_avatar_resize_crop', '0');
  add_option('wp_user_avatar_resize_h', '96');
  add_option('wp_user_avatar_resize_upload', '0');
  add_option('wp_user_avatar_resize_w', '96');
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
  function wpua_check_user_role($role, $user_id=null){
    global $current_user;
    $user = is_numeric($user_id) ? get_userdata($user_id) : $current_user->ID;
    if(empty($user)){
      return false;
    }
    return in_array($role, (array) $user->roles);
  }

  // Remove menu items
  function wpua_subscriber_remove_menu_pages(){
    global $current_user;
    if(wpua_check_user_role('subscriber', $current_user->ID)){
      remove_menu_page('edit.php');
      remove_menu_page('edit-comments.php');
      remove_menu_page('tools.php');
    }
  }
  add_action('admin_menu', 'wpua_subscriber_remove_menu_pages');

  // Remove menu bar items
  function wpua_subscriber_remove_menu_bar_items(){
    global $current_user, $wp_admin_bar;
    if(wpua_check_user_role('subscriber', $current_user->ID)){
      $wp_admin_bar->remove_menu('comments');
      $wp_admin_bar->remove_menu('new-content');
    }
  }
  add_action('wp_before_admin_bar_render', 'wpua_subscriber_remove_menu_bar_items');

  // Remove dashboard items
  function wpua_subscriber_remove_dashboard_widgets(){
    global $current_user;
    if(wpua_check_user_role('subscriber', $current_user->ID)){
      remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
      remove_meta_box('dashboard_recent_drafts', 'dashboard', 'side');
      remove_meta_box('dashboard_right_now', 'dashboard', 'normal');
    }
  }
  add_action('wp_dashboard_setup', 'wpua_subscriber_remove_dashboard_widgets');

  // Restrict access to pages
  function wpua_subscriber_offlimits(){
    global $current_user, $pagenow, $wpua_edit_avatar;
    if((bool) $wpua_edit_avatar == 1){
      $offlimits = array('edit.php', 'edit-comments.php', 'post-new.php', 'tools.php');
    } else {
      $offlimits = array('edit.php', 'edit-comments.php', 'post.php', 'post-new.php', 'tools.php');
    }
    if(wpua_check_user_role('subscriber', $current_user->ID)){
      if(in_array($pagenow, $offlimits)){
        do_action('admin_page_access_denied');
        wp_die(__('You do not have sufficient permissions to access this page.'));
      }
    }
  }
  add_action('admin_init', 'wpua_subscriber_offlimits');
}

if((bool) $wpua_allow_upload == 1 && (bool) $wpua_edit_avatar == 1){
  // Give subscribers edit_posts capability
  function wpua_subscriber_add_cap(){
    global $blog_id, $wpdb;
    $wp_user_roles = $wpdb->get_blog_prefix($blog_id).'user_roles';
    $user_roles = get_option($wp_user_roles);
    $user_roles['subscriber']['capabilities']['edit_posts'] = true;
    update_option($wp_user_roles, $user_roles);
  }
  add_action('admin_init', 'wpua_subscriber_add_cap');
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
