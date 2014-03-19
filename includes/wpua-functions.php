<?php
/**
 * Core user functions.
 * 
 * @package WP User Avatar
 * @version 1.8.10
 */

// Returns true if user has Gravatar-hosted image
function wpua_has_gravatar($id_or_email, $has_gravatar=false, $user="", $email="") {
  if(!is_object($id_or_email) && !empty($id_or_email)) {
    // Find user by ID or e-mail address
    $user = is_numeric($id_or_email) ? get_user_by('id', $id_or_email) : get_user_by('email', $id_or_email);
    // Get registered user e-mail address
    $email = !empty($user) ? $user->user_email : "";
  }
  // Check if Gravatar image returns 200 (OK) or 404 (Not Found)
  $hash = md5(strtolower(trim($email)));
  $gravatar = 'http://www.gravatar.com/avatar/'.$hash.'?d=404';
  $data = wp_cache_get($hash);
  if(false === $data) {
    $response = wp_remote_head($gravatar);
    $data = is_wp_error($response) ? 'not200' : $response['response']['code'];
    wp_cache_set($hash, $data, $group="", $expire=60*5);
  }
  $has_gravatar = ($data == '200') ? true : false;
  return $has_gravatar;
}

// Returns true if user has wp_user_avatar
function has_wp_user_avatar($id_or_email="", $has_wpua=false, $user="", $user_id="") {
  global $blog_id, $wpdb;
  if(!is_object($id_or_email) && !empty($id_or_email)) {
    // Find user by ID or e-mail address
    $user = is_numeric($id_or_email) ? get_user_by('id', $id_or_email) : get_user_by('email', $id_or_email);
    // Get registered user ID
    $user_id = !empty($user) ? $user->ID : "";
  }
  $wpua = get_user_meta($user_id, $wpdb->get_blog_prefix($blog_id).'user_avatar', true);
  $has_wpua = !empty($wpua) && wp_attachment_is_image($wpua) ? true : false;
  return $has_wpua;
}

// Replace get_avatar only in get_wp_user_avatar
function wpua_get_avatar_filter($avatar, $id_or_email="", $size="", $default="", $alt="") {
  global $avatar_default, $mustache_admin, $mustache_avatar, $mustache_medium, $mustache_original, $mustache_thumbnail, $post, $wpua_avatar_default, $wpua_disable_gravatar;
  // User has WPUA
  if(is_object($id_or_email)) {
    if(!empty($id_or_email->comment_author_email)) {
      $avatar = get_wp_user_avatar($id_or_email, $size, $default, $alt);
    } else {
      $avatar = get_wp_user_avatar('unknown@gravatar.com', $size, $default, $alt);
    }
  } else {
    if(has_wp_user_avatar($id_or_email)) {
      $avatar = get_wp_user_avatar($id_or_email, $size, $default, $alt);
    // User has Gravatar and Gravatar is not disabled
    } elseif((bool) $wpua_disable_gravatar != 1 && wpua_has_gravatar($id_or_email)) {
      $avatar = $avatar;
    // User doesn't have WPUA or Gravatar and Default Avatar is wp_user_avatar, show custom Default Avatar
    } elseif($avatar_default == 'wp_user_avatar') {
      // Show custom Default Avatar
      if(!empty($wpua_avatar_default) && wp_attachment_is_image($wpua_avatar_default)) {
        // Get image
        $wpua_avatar_default_image = wp_get_attachment_image_src($wpua_avatar_default, array($size,$size));
        // Image src
        $default = $wpua_avatar_default_image[0];
        // Add dimensions if numeric size
        $dimensions = ' width="'.$wpua_avatar_default_image[1].'" height="'.$wpua_avatar_default_image[2].'"';
      } else {
        // Get mustache image based on numeric size comparison
        if($size > get_option('medium_size_w')) {
          $default = $mustache_original;
        } elseif($size <= get_option('medium_size_w') && $size > get_option('thumbnail_size_w')) {
          $default = $mustache_medium;
        } elseif($size <= get_option('thumbnail_size_w') && $size > 96) {
          $default = $mustache_thumbnail;
        } elseif($size <= 96 && $size > 32) {
          $default = $mustache_avatar;
        } elseif($size <= 32) {
          $default = $mustache_admin;
        }
        // Add dimensions if numeric size
        $dimensions = ' width="'.$size.'" height="'.$size.'"';
      }
      // Construct the img tag
      $avatar = '<img src="'.$default.'"'.$dimensions.' alt="'.$alt.'" class="avatar avatar-'.$size.' wp-user-avatar wp-user-avatar-'.$size.' photo avatar-default" />';
    }
  }
  return $avatar;
}
add_filter('get_avatar', 'wpua_get_avatar_filter', 10, 6);

// Get original avatar, for when user removes wp_user_avatar
function wpua_get_avatar_original($id_or_email, $size="", $default="", $alt="") {
  global $avatar_default, $mustache_avatar, $wpua_avatar_default, $wpua_disable_gravatar;
  // Remove get_avatar filter
  remove_filter('get_avatar', 'wpua_get_avatar_filter');
  if((bool) $wpua_disable_gravatar != 1) {
    // User doesn't have Gravatar and Default Avatar is wp_user_avatar, show custom Default Avatar
    if(!wpua_has_gravatar($id_or_email) && $avatar_default == 'wp_user_avatar') {
      // Show custom Default Avatar
      if(!empty($wpua_avatar_default) && wp_attachment_is_image($wpua_avatar_default)) {
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
      $default = !empty($matches) ? $matches [0] [1] : "";
    }
  } else {
    if(!empty($wpua_avatar_default) && wp_attachment_is_image($wpua_avatar_default)) {
      $wpua_avatar_default_image = wp_get_attachment_image_src($wpua_avatar_default, array($size,$size));
      $default = $wpua_avatar_default_image[0];
    } else {
      $default = $mustache_avatar;
    }
  }
  // Enable get_avatar filter
  add_filter('get_avatar', 'wpua_get_avatar_filter', 10, 6);
  return $default;
}

// Find WPUA, show get_avatar if empty
function get_wp_user_avatar($id_or_email="", $size='96', $align="", $alt="", $email='unknown@gravatar.com') {
  global $all_sizes, $avatar_default, $blog_id, $post, $wpdb, $_wp_additional_image_sizes;
  // Checks if comment
  if(is_object($id_or_email)) {
    // Checks if comment author is registered user by user ID
    if($id_or_email->user_id != 0) {
      $email = $id_or_email->user_id;
    // Checks that comment author isn't anonymous
    } elseif(!empty($id_or_email->comment_author_email)) {
      // Checks if comment author is registered user by e-mail address
      $user = get_user_by('email', $id_or_email->comment_author_email);
      // Get registered user info from profile, otherwise e-mail address should be value
      $email = !empty($user) ? $user->ID : $id_or_email->comment_author_email;
    }
    $alt = $id_or_email->comment_author;
  } else {
    if(!empty($id_or_email)) {
      // Find user by ID or e-mail address
      $user = is_numeric($id_or_email) ? get_user_by('id', $id_or_email) : get_user_by('email', $id_or_email);
    } else {
      // Find author's name if id_or_email is empty
      $author_name = get_query_var('author_name');
      if(is_author()) {
        // On author page, get user by page slug
        $user = get_user_by('slug', $author_name);
      } else {
        // On post, get user by author meta
        $user_id = get_the_author_meta('ID');
        $user = get_user_by('id', $user_id);
      }
    }
    // Set user's ID and name
    if(!empty($user)) {
      $email = $user->ID;
      $alt = $user->display_name;
    }
  }
  // Checks if user has WPUA
  $wpua_meta = get_the_author_meta($wpdb->get_blog_prefix($blog_id).'user_avatar', $email);
  // Add alignment class
  $alignclass = !empty($align) && ($align == 'left' || $align == 'right' || $align == 'center') ? ' align'.$align : ' alignnone';
  // User has WPUA, bypass get_avatar
  if(!empty($wpua_meta)) {
    // Numeric size use size array
    $get_size = is_numeric($size) ? array($size,$size) : $size;
    // Get image src
    $wpua_image = wp_get_attachment_image_src($wpua_meta, $get_size);
    // Add dimensions to img only if numeric size was specified
    $dimensions = is_numeric($size) ? ' width="'.$wpua_image[1].'" height="'.$wpua_image[2].'"' : "";
    // Construct the img tag
    $avatar = '<img src="'.$wpua_image[0].'"'.$dimensions.' alt="'.$alt.'" class="avatar avatar-'.$size.' wp-user-avatar wp-user-avatar-'.$size.$alignclass.' photo" />';
  } else {
    // Check for custom image sizes
    if(in_array($size, $all_sizes)) {
      if(in_array($size, array('original', 'large', 'medium', 'thumbnail'))) {
        $get_size = ($size == 'original') ? get_option('large_size_w') : get_option($size.'_size_w');
      } else {
        $get_size = $_wp_additional_image_sizes[$size]['width'];
      }
    } else {
      // Numeric sizes leave as-is
      $get_size = $size;
    }
    // User with no WPUA uses get_avatar
    $avatar = get_avatar($email, $get_size, $default="", $alt="");
    // Remove width and height for non-numeric sizes
    if(in_array($size, array('original', 'large', 'medium', 'thumbnail'))) {
      $avatar = preg_replace('/(width|height)=\"\d*\"\s/', "", $avatar);
      $avatar = preg_replace("/(width|height)=\'\d*\'\s/", "", $avatar);
    }
    $str_replacemes = array('wp-user-avatar ', 'wp-user-avatar-'.$get_size.' ', 'wp-user-avatar-'.$size.' ', 'avatar-'.$get_size, 'photo');
    $str_replacements = array("", "", "", 'avatar-'.$size, 'wp-user-avatar wp-user-avatar-'.$size.$alignclass.' photo');
    $avatar = str_replace($str_replacemes, $str_replacements, $avatar);
  }
  return $avatar;
}

// Return just the image src
function get_wp_user_avatar_src($id_or_email, $size="", $align="") {
  $wpua_image_src = "";
  // Gets the avatar img tag
  $wpua_image = get_wp_user_avatar($id_or_email, $size, $align);
  // Takes the img tag, extracts the src
  if(!empty($wpua_image)) {
    $output = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $wpua_image, $matches, PREG_SET_ORDER);
    $wpua_image_src = !empty($matches) ? $matches [0] [1] : "";
  }
  return $wpua_image_src;
}

// Check if avatar_upload is in use
function wpua_has_shortcode() {
  global $post;
  $content = !empty($post->post_content) ? $post->post_content : null;
  $has_shortcode = has_shortcode($content, 'avatar_upload') ? true : false;
  return $has_shortcode;
}

// Before wrapper for profile
function wpua_before_avatar() {
  do_action('wpua_before_avatar');
}
add_action('wpua_before_avatar', 'wpua_do_before_avatar');

// After wrapper for profile
function wpua_after_avatar() {
  do_action('wpua_after_avatar');
}
add_action('wpua_after_avatar', 'wpua_do_after_avatar');

// Before avatar container
function wpua_do_before_avatar() { ?>
  <?php if(class_exists('bbPress') && bbp_is_edit()) : // Add to bbPress profile with same style ?>
    <h2 class="entry-title"><?php _e('Avatar'); ?></h2>
    <fieldset class="bbp-form">
      <legend><?php _e('Image'); ?></legend>
  <?php elseif(class_exists('WPUF_Main') && wpuf_has_shortcode('wpuf_editprofile')) : // Add to WP User Frontend profile with same style ?>
    <fieldset>
      <legend><?php _e('Avatar') ?></legend>
      <table class="wpuf-table">
        <tr>
          <th><label for="wp_user_avatar"><?php _e('Image'); ?></label></th>
          <td>
  <?php elseif(wpua_has_shortcode()) : // Add to profile without table ?>
    <div class="wpua-edit-container">
      <h3><?php _e('Avatar') ?></h3>
  <?php else : // Add to profile with admin style ?>
    <h3><?php _e('Avatar') ?></h3>
    <table class="form-table">
      <tr>
        <th><label for="wp_user_avatar"><?php _e('Image'); ?></label></th>
        <td>
  <?php endif; ?>
  <?php
}

// After avatar container
function wpua_do_after_avatar() { ?>
  <?php if(class_exists('bbPress') && bbp_is_edit()) : // Add to bbPress profile with same style ?>
    </fieldset>
  <?php elseif(class_exists('WPUF_Main') && wpuf_has_shortcode('wpuf_editprofile')) : // Add to WP User Frontend profile with same style ?>
          </td>
        </tr>
      </table>
    </fieldset>
  <?php elseif(wpua_has_shortcode()) : // Add to profile without table ?>
    </div>
  <?php else : // Add to profile with admin style ?>
        </td>
      </tr>
    </table>
  <?php endif; ?>
  <?php
}

// Dontation message
function wpua_do_donation_message() { ?>
  <div class="updated">
    <p><?php _e('Do you like WP User Avatar?', 'wp-user-avatar'); ?> <a href="http://siboliban.org/donate" target="_blank"><?php _e('Make a donation.', 'wp-user-avatar'); ?></a></p>
  </div>
 <?php 
}

// Filter for the inevitable complaints about the donation message :(
function wpua_donation_message() {
  do_action('wpua_donation_message');
}
add_action('wpua_donation_message', 'wpua_do_donation_message');
