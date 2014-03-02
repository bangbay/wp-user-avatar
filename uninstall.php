<?php
/**
 * @package WP User Avatar
 * @version 1.8.2
 */

// Remove user metadata and options on plugin delete
if(!defined('WP_UNINSTALL_PLUGIN')) {
  die(__('You are not allowed to call this page directly.'));
}

global $wpdb, $blog_id, $switched;
$users = get_users();

// Remove settings for all sites in multisite
if(is_multisite()) {
  $blogs = $wpdb->get_results("SELECT * FROM $wpdb->blogs");
  foreach($users as $user) {
    foreach($blogs as $blog) {
      delete_user_meta($user->ID, $wpdb->get_blog_prefix($blog->blog_id).'user_avatar');
    }
  }
  foreach($blogs as $blog) {
    switch_to_blog($blog->blog_id);
    delete_option('avatar_default_wp_user_avatar');
    delete_option('wp_user_avatar_allow_upload');
    delete_option('wp_user_avatar_disable_gravatar');
    delete_option('wp_user_avatar_edit_avatar');
    delete_option('wp_user_avatar_load_scripts');
    delete_option('wp_user_avatar_resize_crop');
    delete_option('wp_user_avatar_resize_h');
    delete_option('wp_user_avatar_resize_upload');
    delete_option('wp_user_avatar_resize_w');
    delete_option('wp_user_avatar_tinymce');
    delete_option('wp_user_avatar_upload_size_limit');
    delete_option('wp_user_avatar_default_avatar_updated');
    delete_option('wp_user_avatar_media_updated');
    delete_option('wp_user_avatar_users_updated');
  }
} else {
  foreach($users as $user) {
    delete_user_meta($user->ID, $wpdb->get_blog_prefix($blog_id).'user_avatar');
  }
  delete_option('avatar_default_wp_user_avatar');
  delete_option('wp_user_avatar_allow_upload');
  delete_option('wp_user_avatar_disable_gravatar');
  delete_option('wp_user_avatar_edit_avatar');
  delete_option('wp_user_avatar_load_scripts');
  delete_option('wp_user_avatar_resize_crop');
  delete_option('wp_user_avatar_resize_h');
  delete_option('wp_user_avatar_resize_upload');
  delete_option('wp_user_avatar_resize_w');
  delete_option('wp_user_avatar_tinymce');
  delete_option('wp_user_avatar_upload_size_limit');
  delete_option('wp_user_avatar_default_avatar_updated');
  delete_option('wp_user_avatar_media_updated');
  delete_option('wp_user_avatar_users_updated');
}

// Delete post meta
delete_post_meta_by_key('_wp_attachment_wp_user_avatar');

// Reset all default avatars to Mystery Man
update_option('avatar_default', 'mystery');
