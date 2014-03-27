<?php
/**
 * Public user functions.
 * 
 * @package WP User Avatar
 * @version 1.9
 */

function has_wp_user_avatar($id_or_email="", $has_wpua="", $user="", $user_id="") {
  global $wpua_functions;
  return $wpua_functions->has_wp_user_avatar($id_or_email, $has_wpua, $user, $user_id);
}

function get_wp_user_avatar($id_or_email="", $size="", $default="", $alt="") {
  global $wpua_functions;
  return $wpua_functions->get_wp_user_avatar($id_or_email, $size, $default, $alt);
}

function get_wp_user_avatar_src($id_or_email="", $size="", $align="") {
  global $wpua_functions;
  return $wpua_functions->get_wp_user_avatar_src($id_or_email, $size, $align);
}

// Before wrapper for profile
function wpua_before_avatar() {
  do_action('wpua_before_avatar');
}

// After wrapper for profile
function wpua_after_avatar() {
  do_action('wpua_after_avatar');
}

// Before avatar container
function wpua_do_before_avatar() {
  global $wpua_functions;
?>
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
  <?php elseif($wpua_functions->wpua_has_shortcode()) : // Add to profile without table ?>
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
add_action('wpua_before_avatar', 'wpua_do_before_avatar');

// After avatar container
function wpua_do_after_avatar() {
  global $wpua_functions;
?>
  <?php if(class_exists('bbPress') && bbp_is_edit()) : // Add to bbPress profile with same style ?>
    </fieldset>
  <?php elseif(class_exists('WPUF_Main') && wpuf_has_shortcode('wpuf_editprofile')) : // Add to WP User Frontend profile with same style ?>
          </td>
        </tr>
      </table>
    </fieldset>
  <?php elseif($wpua_functions->wpua_has_shortcode()) : // Add to profile without table ?>
    </div>
  <?php else : // Add to profile with admin style ?>
        </td>
      </tr>
    </table>
  <?php endif; ?>
  <?php
}
add_action('wpua_after_avatar', 'wpua_do_after_avatar');

// Filter for the inevitable complaints about the donation message :(
function wpua_donation_message() {
  do_action('wpua_donation_message');
}
// Donation message
function wpua_do_donation_message() { ?>
  <div class="updated">
    <p><?php _e('Do you like WP User Avatar?', 'wp-user-avatar'); ?> <a href="http://siboliban.org/donate" target="_blank"><?php _e('Make a donation.', 'wp-user-avatar'); ?></a></p>
  </div>
 <?php 
}
add_action('wpua_donation_message', 'wpua_do_donation_message');
