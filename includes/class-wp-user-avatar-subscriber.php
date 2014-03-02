<?php
/**
 * Settings only for subscribers and contributors.
 *
 * @package WP User Avatar
 * @version 1.8.2
 */

class WP_User_Avatar_Subscriber {
  public function __construct() {
    global $wpua_allow_upload, $wpua_edit_avatar;
    if((bool) $wpua_allow_upload == 1) {
      add_action('user_edit_form_tag', array($this, 'wpua_add_edit_form_multipart_encoding'));
      add_action('admin_menu', array($this, 'wpua_subscriber_remove_menu_pages'));
      add_action('wp_before_admin_bar_render', array($this, 'wpua_subscriber_remove_menu_bar_items'));
      add_action('wp_dashboard_setup', array($this, 'wpua_subscriber_remove_dashboard_widgets'));
      add_action('admin_init', array($this, 'wpua_subscriber_offlimits'));
    }
    if((bool) $wpua_allow_upload == 1 && (bool) $wpua_edit_avatar == 1) {
      add_action('admin_init', array($this, 'wpua_subscriber_add_cap'));
    } else {
      add_action('admin_init', array($this, 'wpua_subscriber_remove_cap'));
    }
  }

  // Allow multipart data in form
  public function wpua_add_edit_form_multipart_encoding() {
    echo ' enctype="multipart/form-data"';
  }

  // Check user role
  private function wpua_check_user_role($role, $user_id=null) {
    global $current_user;
    $user = is_numeric($user_id) ? get_userdata($user_id) : $current_user->ID;
    if(empty($user)) {
      return false;
    }
    return in_array($role, (array) $user->roles);
  }

  // Remove menu items
  public function wpua_subscriber_remove_menu_pages() {
    global $current_user;
    if($this->wpua_check_user_role('subscriber', $current_user->ID)) {
      remove_menu_page('edit.php');
      remove_menu_page('edit-comments.php');
      remove_menu_page('tools.php');
    }
  }

  // Remove menu bar items
  public function wpua_subscriber_remove_menu_bar_items() {
    global $current_user, $wp_admin_bar;
    if($this->wpua_check_user_role('subscriber', $current_user->ID)) {
      $wp_admin_bar->remove_menu('comments');
      $wp_admin_bar->remove_menu('new-content');
    }
  }

  // Remove dashboard items
  public function wpua_subscriber_remove_dashboard_widgets() {
    global $current_user;
    if($this->wpua_check_user_role('subscriber', $current_user->ID)) {
      remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
      remove_meta_box('dashboard_recent_drafts', 'dashboard', 'side');
      remove_meta_box('dashboard_right_now', 'dashboard', 'normal');
    }
  }

  // Restrict access to pages
  public function wpua_subscriber_offlimits() {
    global $current_user, $pagenow, $wpua_edit_avatar;
    if((bool) $wpua_edit_avatar == 1) {
      $offlimits = array('edit.php', 'edit-comments.php', 'post-new.php', 'tools.php');
    } else {
      $offlimits = array('edit.php', 'edit-comments.php', 'post.php', 'post-new.php', 'tools.php');
    }
    if($this->wpua_check_user_role('subscriber', $current_user->ID)) {
      if(in_array($pagenow, $offlimits)) {
        do_action('admin_page_access_denied');
        wp_die(__('You do not have sufficient permissions to access this page.'));
      }
    }
  }

  // Give subscribers edit_posts capability
  public function wpua_subscriber_add_cap() {
    global $blog_id, $wpdb;
    $wp_user_roles = $wpdb->get_blog_prefix($blog_id).'user_roles';
    $user_roles = get_option($wp_user_roles);
    $user_roles['subscriber']['capabilities']['edit_posts'] = true;
    update_option($wp_user_roles, $user_roles);
  }

  // Remove subscribers edit_posts capability
  public function wpua_subscriber_remove_cap() {
    global $blog_id, $wpdb;
    $wp_user_roles = $wpdb->get_blog_prefix($blog_id).'user_roles';
    $user_roles = get_option($wp_user_roles);
    unset($user_roles['subscriber']['capabilities']['edit_posts']);
    update_option($wp_user_roles, $user_roles);
  }
}

$wpua_subscriber = new WP_User_Avatar_Subscriber();
