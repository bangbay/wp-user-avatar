<?php
/**
 * @package WP User Avatar
 * @version 1.2.2
 */

function myplugin_addbuttons() {
  // Don't bother doing this stuff if the current user lacks permissions
  if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') )
    return;

  // Add only in Rich Editor mode
  if(get_user_option('rich_editing') == 'true'){
    add_filter('mce_external_plugins', 'add_myplugin_tinymce_plugin');
    add_filter('mce_buttons', 'register_myplugin_button');
  }
}

function register_myplugin_button($buttons) {
  array_push($buttons, 'separator', 'wpUserAvatar');
  return $buttons;
}

// Load the TinyMCE plugin : editor_plugin.js (wp2.5)
function add_myplugin_tinymce_plugin($plugin_array) {
  $plugin_array['wpUserAvatar'] = WP_USER_AVATAR_URLPATH.'includes/tinymce/editor_plugin.js';
  return $plugin_array;
}

// init process for button control
add_action('init', 'myplugin_addbuttons');

// Call TinyMCE window content via admin-ajax
function wp_user_avatar_ajax_tinymce(){
  if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') )
    die('You are not allowed to call this page directly.');
  include_once(WP_USER_AVATAR_ABSPATH.'includes/tinymce/window.php');
  die();
}
add_action('wp_ajax_wp_user_avatar_tinymce', 'wp_user_avatar_ajax_tinymce');

?>
