<?php
/**
 * @package WP User Avatar
 * @version 1.9.12
 */

/*
Plugin Name: WP User Avatar
Plugin URI: http://wordpress.org/plugins/wp-user-avatar/
Description: Use any image from your WordPress Media Library as a custom user avatar. Add your own Default Avatar.
Author: Bangbay Siboliban
Author URI: http://siboliban.org/
Version: 1.9.12
Text Domain: wp-user-avatar
Domain Path: /lang/
*/

if(!defined('ABSPATH')) {
  die('You are not allowed to call this page directly.');
}

/**
 * Let's get started!
 */
class WP_User_Avatar_Setup {
  /**
   * Constructor
   * @since 1.9.2
   */
  public function __construct() {
    $this->_define_constants();
    $this->_load_wp_includes();
    $this->_load_wpua();
  }

  /**
   * Define paths
   * @since 1.9.2
   */
  private function _define_constants() {
    define('WPUA_VERSION', '1.9.12');
    define('WPUA_FOLDER', basename(dirname(__FILE__)));
    define('WPUA_DIR', plugin_dir_path(__FILE__));
    define('WPUA_INC', WPUA_DIR.'includes'.'/');
    define('WPUA_URL', plugin_dir_url(WPUA_FOLDER).WPUA_FOLDER.'/');
    define('WPUA_INC_URL', WPUA_URL.'includes'.'/');
  }

  /**
   * WordPress includes used in plugin
   * @since 1.9.2
   */
  private function _load_wp_includes() {
    require_once(ABSPATH.'wp-admin/includes/file.php');
    require_once(ABSPATH.'wp-admin/includes/image.php');
    require_once(ABSPATH.'wp-admin/includes/media.php');
    require_once(ABSPATH.'wp-admin/includes/screen.php');
    require_once(ABSPATH.'wp-admin/includes/template.php');
  }

  /**
   * Load WP User Avatar
   * @since 1.9.2
   * @uses bool $wpua_tinymce
   * @uses is_admin()
   */
  private function _load_wpua() {
    global $wpua_tinymce;
    require_once(WPUA_INC.'wpua-globals.php');
    require_once(WPUA_INC.'wpua-functions.php');
    require_once(WPUA_INC.'class-wp-user-avatar-admin.php');
    require_once(WPUA_INC.'class-wp-user-avatar.php');
    require_once(WPUA_INC.'class-wp-user-avatar-functions.php');
    // Only needed on front pages and if NextGEN Gallery isn't installed
    // if(!is_admin() && !defined('NEXTGEN_GALLERY_PLUGIN_DIR') && !defined('NGG_GALLERY_PLUGIN_DIR')) {
    //   require_once(WPUA_INC.'class-wp-user-avatar-resource-manager.php');
    //   WP_User_Avatar_Resource_Manager::init();
    // }
    require_once(WPUA_INC.'class-wp-user-avatar-shortcode.php');
    require_once(WPUA_INC.'class-wp-user-avatar-subscriber.php');
    require_once(WPUA_INC.'class-wp-user-avatar-update.php');
    require_once(WPUA_INC.'class-wp-user-avatar-widget.php');
    // Load TinyMCE only if enabled
    if((bool) $wpua_tinymce == 1) {
      require_once(WPUA_INC.'wpua-tinymce.php');
    }
  }
}

/**
 * Initialize
 */
new WP_User_Avatar_Setup();
