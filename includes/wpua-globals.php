<?php
/**
 * Global variables used in plugin.
 *
 * @package WP User Avatar
 * @version 1.8.8
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
if($upload_size_limit > 1024) {
  $upload_size_limit /= 1024;
}
$upload_size_limit_with_units = (int) $upload_size_limit.'KB';

// User upload size limit
$wpua_user_upload_size_limit = get_option('wp_user_avatar_upload_size_limit');
if($wpua_user_upload_size_limit == 0 || $wpua_user_upload_size_limit > wp_max_upload_size()) {
  $wpua_user_upload_size_limit = wp_max_upload_size();
}
// Value in bytes
$wpua_upload_size_limit = $wpua_user_upload_size_limit;
// Convert to KB
if($wpua_user_upload_size_limit > 1024) {
  $wpua_user_upload_size_limit /= 1024;
}
$wpua_upload_size_limit_with_units = (int) $wpua_user_upload_size_limit.'KB';

// Check for custom image sizes
$all_sizes = array_merge(get_intermediate_image_sizes(), array('original'));
