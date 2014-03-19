<?php
/**
 * TinyMCE button for Visual Editor.
 *
 * @package WP User Avatar
 * @version 1.8.10
 */

function wpua_myplugin_addbuttons() {
  // Don't bother doing this stuff if the current user lacks permissions
  if(!current_user_can('edit_posts') && !current_user_can('edit_pages')) {
    return;
  }
  // Add only in Rich Editor mode
  if(get_user_option('rich_editing') == 'true') {
    add_filter('mce_external_plugins', 'wpua_add_myplugin_tinymce_plugin');
    add_filter('mce_buttons', 'wpua_register_myplugin_button');
  }
}
// init process for button control
add_action('init', 'wpua_myplugin_addbuttons');

function wpua_register_myplugin_button($buttons) {
  array_push($buttons, 'separator', 'wpUserAvatar');
  return $buttons;
}

// Load the TinyMCE plugin : editor_plugin.js (wp2.5)
function wpua_add_myplugin_tinymce_plugin($plugin_array) {
  $plugin_array['wpUserAvatar'] = WPUA_INC_URL.'tinymce/editor_plugin.js';
  return $plugin_array;
}

// Call TinyMCE window content via admin-ajax
function wpua_ajax_tinymce() {
  if(!current_user_can('edit_posts') && !current_user_can('edit_pages')) {
    die('You are not allowed to call this page directly.');
  }
  include_once(WPUA_INC.'tinymce/window.php');
  die();
}
add_action('wp_ajax_wp_user_avatar_tinymce', 'wpua_ajax_tinymce');
