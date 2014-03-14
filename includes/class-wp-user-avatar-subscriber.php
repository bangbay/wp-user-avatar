<?php
/**
 * Settings only for subscribers and contributors.
 *
 * @package WP User Avatar
 * @version 1.8.9
 */

class WP_User_Avatar_Subscriber {
  public function __construct() {
    global $wp_user_avatar, $wpua_allow_upload;
    if((bool) $wpua_allow_upload == 1) {
      add_action('user_edit_form_tag', array($this, 'wpua_add_edit_form_multipart_encoding'));
      // Only Subscribers lack delete_posts capability
      if(!current_user_can('delete_posts') && !$wp_user_avatar->wpua_is_author_or_above()) {
        add_action('admin_menu', array($this, 'wpua_subscriber_remove_menu_pages'));
        add_action('wp_before_admin_bar_render', array($this, 'wpua_subscriber_remove_menu_bar_items'));
        add_action('wp_dashboard_setup', array($this, 'wpua_subscriber_remove_dashboard_widgets'));
        add_action('admin_init', array($this, 'wpua_subscriber_offlimits'));
      }
    }
    add_action('admin_init', array($this, 'wpua_subscriber_capability'));
  }

  // Allow multipart data in form
  public function wpua_add_edit_form_multipart_encoding() {
    echo ' enctype="multipart/form-data"';
  }

  // Remove menu items
  public function wpua_subscriber_remove_menu_pages() {
    remove_menu_page('edit.php');
    remove_menu_page('edit-comments.php');
    remove_menu_page('tools.php');
  }

  // Remove menu bar items
  public function wpua_subscriber_remove_menu_bar_items() {
    global $wp_admin_bar;
    $wp_admin_bar->remove_menu('comments');
    $wp_admin_bar->remove_menu('new-content');
  }

  // Remove dashboard items
  public function wpua_subscriber_remove_dashboard_widgets() {
    remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
    remove_meta_box('dashboard_recent_drafts', 'dashboard', 'side');
    remove_meta_box('dashboard_right_now', 'dashboard', 'normal');
  }

  // Restrict access to pages
  public function wpua_subscriber_offlimits() {
    global $pagenow, $wpua_edit_avatar;
    $offlimits = array('edit.php', 'edit-comments.php', 'post-new.php', 'tools.php');
    if((bool) $wpua_edit_avatar != 1) {
      array_push($offlimits, 'post.php');
    }
    if(in_array($pagenow, $offlimits)) {
      do_action('admin_page_access_denied');
      wp_die(__('You do not have sufficient permissions to access this page.'));
    }
  }

  // Give subscribers edit_posts capability
  public function wpua_subscriber_capability() {
    global $blog_id, $wpdb, $wpua_allow_upload, $wpua_edit_avatar;;
    $wp_user_roles = $wpdb->get_blog_prefix($blog_id).'user_roles';
    $user_roles = get_option($wp_user_roles);
    if((bool) $wpua_allow_upload == 1 && (bool) $wpua_edit_avatar == 1) {
      $user_roles['subscriber']['capabilities']['edit_posts'] = true;      
    } else {
      unset($user_roles['subscriber']['capabilities']['edit_posts']);
    }
    update_option($wp_user_roles, $user_roles);
  }
}

// Initialize WP_User_Avatar_Subscriber
function wpua_subcriber_init() {
  global $wpua_subscriber;
  $wpua_subscriber = new WP_User_Avatar_Subscriber();
}
add_action('init', 'wpua_subcriber_init');
