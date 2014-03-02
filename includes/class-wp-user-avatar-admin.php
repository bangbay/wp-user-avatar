<?php
/**
 * Defines all of administrative, activation, and deactivation settings.
 *
 * @package WP User Avatar
 * @version 1.8.2
 */

class WP_User_Avatar_Admin {
  public function __construct() {
    global $show_avatars, $wpua_allow_upload, $wpua_tinymce;
    // Initialize default settings
    register_activation_hook(__FILE__, array($this, 'wpua_options'));    
    // Settings saved to wp_options
    add_action('admin_init', array($this, 'wpua_options'));    
    // Remove subscribers edit_posts capability
    register_deactivation_hook(__FILE__, array($this, 'wpua_deactivate'));
    // Translations
    load_plugin_textdomain('wp-user-avatar', "", WPUA_FOLDER.'/lang');
    // Admin menu settings
    add_action('admin_menu', array($this, 'wpua_admin'));    
    // Default avatar
    add_filter('default_avatar_select', array($this, 'wpua_add_default_avatar'), 10);
    add_filter('whitelist_options', array($this, 'wpua_whitelist_options'), 10);
    // Additional plugin info
    add_filter('plugin_action_links', array($this, 'wpua_action_links'), 10, 2);
    add_filter('plugin_row_meta', array($this, 'wpua_row_meta'), 10, 2);
    // Hide column in Users table if default avatars are enabled
    if((bool) $show_avatars == 0 && is_admin()){
      add_filter('manage_users_columns', array($this, 'wpua_add_column'), 10, 1);
      add_filter('manage_users_custom_column', array($this, 'wpua_show_column'), 10, 3);
    }
    // Media states
    add_filter('display_media_states', array($this, 'wpua_add_media_state'), 10, 1);
    // Load TinyMCE only if enabled
    if((bool) $wpua_tinymce == 1){
      include_once(WPUA_INC.'wpua-tinymce.php');
    }
  }

  // Settings saved to wp_options
  public function wpua_options() {
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

  // On deactivation
  public function wpua_deactivate() {
    global $wpua_subscriber;
    // Remove subscribers edit_posts capability
    $wpua_subscriber->wpua_subscriber_remove_cap();
    // Reset all default avatar to Mystery Man
    update_option('avatar_default', 'mystery');
  }

  // Add options page and settings
  public function wpua_admin() {
    add_menu_page(__('WP User Avatar', 'wp-user-avatar'), __('WP User Avatar', 'wp-user-avatar'), 'manage_options', 'wp-user-avatar', array($this, 'wpua_options_page'), WPUA_URL.'images/wpua-icon.png');
    add_submenu_page('wp-user-avatar', __('Settings'), __('Settings'), 'manage_options', 'wp-user-avatar', array($this, 'wpua_options_page'));
    add_submenu_page('wp-user-avatar', __('Library'), __('Library'), 'manage_options', 'wp-user-avatar-library', array($this, 'wpua_media_page'));
    add_action('admin_init', array($this, 'wpua_admin_settings'));
  }

  // Media page
  public function wpua_media_page() {
    require_once(WPUA_INC.'wpua-media-page.php');
  }

  // Options page
  public function wpua_options_page() {
    require_once(WPUA_INC.'wpua-options-page.php');
  }

  // Whitelist settings
  public function wpua_admin_settings() {
    register_setting('wpua-settings-group', 'avatar_rating');
    register_setting('wpua-settings-group', 'avatar_default');
    register_setting('wpua-settings-group', 'avatar_default_wp_user_avatar', 'intval');
    register_setting('wpua-settings-group', 'show_avatars', 'intval');
    register_setting('wpua-settings-group', 'wp_user_avatar_tinymce', 'intval');
    register_setting('wpua-settings-group', 'wp_user_avatar_allow_upload', 'intval');
    register_setting('wpua-settings-group', 'wp_user_avatar_disable_gravatar', 'intval');
    register_setting('wpua-settings-group', 'wp_user_avatar_edit_avatar', 'intval');
    register_setting('wpua-settings-group', 'wp_user_avatar_resize_crop', 'intval');
    register_setting('wpua-settings-group', 'wp_user_avatar_resize_h', 'intval');
    register_setting('wpua-settings-group', 'wp_user_avatar_resize_upload', 'intval');
    register_setting('wpua-settings-group', 'wp_user_avatar_resize_w', 'intval');
    register_setting('wpua-settings-group', 'wp_user_avatar_upload_size_limit', 'intval');
  }

  // Add default avatar
  public function wpua_add_default_avatar($avatar_list=null){
    global $avatar_default, $mustache_admin, $mustache_medium, $wpua_avatar_default, $wpua_disable_gravatar;
    // Remove get_avatar filter
    remove_filter('get_avatar', 'wpua_get_avatar_filter');
    // Set avatar_list variable
    $avatar_list = "";
    // Set avatar defaults
    $avatar_defaults = array(
      'mystery' => __('Mystery Man'),
      'blank' => __('Blank'),
      'gravatar_default' => __('Gravatar Logo'),
      'identicon' => __('Identicon (Generated)'),
      'wavatar' => __('Wavatar (Generated)'),
      'monsterid' => __('MonsterID (Generated)'),
      'retro' => __('Retro (Generated)')
    );
    // No Default Avatar, set to Mystery Man
    if(empty($avatar_default)){
      $avatar_default = 'mystery';
    }
    // Take avatar_defaults and get examples for unknown@gravatar.com
    foreach($avatar_defaults as $default_key => $default_name){
      $avatar = get_avatar('unknown@gravatar.com', 32, $default_key);
      $selected = ($avatar_default == $default_key) ? 'checked="checked" ' : "";
      $avatar_list .= "\n\t<label><input type='radio' name='avatar_default' id='avatar_{$default_key}' value='".esc_attr($default_key)."' {$selected}/> ";
      $avatar_list .= preg_replace("/src='(.+?)'/", "src='\$1&amp;forcedefault=1'", $avatar);
      $avatar_list .= ' '.$default_name.'</label>';
      $avatar_list .= '<br />';
    }
    // Show remove link if custom Default Avatar is set
    if(!empty($wpua_avatar_default) && wp_attachment_is_image($wpua_avatar_default)){
      $avatar_thumb_src = wp_get_attachment_image_src($wpua_avatar_default, array(32,32));
      $avatar_thumb = $avatar_thumb_src[0];
      $hide_remove = "";
    } else {
      $avatar_thumb = $mustache_admin;
      $hide_remove = ' class="wpua-hide"';
    }
    // Default Avatar is wp_user_avatar, check the radio button next to it
    $selected_avatar = ((bool) $wpua_disable_gravatar == 1 || $avatar_default == 'wp_user_avatar') ? ' checked="checked" ' : "";
    // Wrap WPUA in div
    $avatar_thumb_img = '<div id="wpua-preview"><img src="'.$avatar_thumb.'" width="32" /></div>';
    // Add WPUA to list
    $wpua_list = "\n\t<label><input type='radio' name='avatar_default' id='wp_user_avatar_radio' value='wp_user_avatar'$selected_avatar /> ";
    $wpua_list .= preg_replace("/src='(.+?)'/", "src='\$1'", $avatar_thumb_img);
    $wpua_list .= ' '.__('WP User Avatar', 'wp-user-avatar').'</label>';
    $wpua_list .= '<p id="wpua-edit"><button type="button" class="button" id="wpua-add" name="wpua-add">'.__('Choose Image').'</button>';
    $wpua_list .= '<span id="wpua-remove-button"'.$hide_remove.'><a href="#" id="wpua-remove">'.__('Remove').'</a></span><span id="wpua-undo-button"><a href="#" id="wpua-undo">'.__('Undo').'</a></span></p>';
    $wpua_list .= '<input type="hidden" id="wp-user-avatar" name="avatar_default_wp_user_avatar" value="'.$wpua_avatar_default.'">';
    if((bool) $wpua_disable_gravatar != 1){
      return $wpua_list.'<div id="wp-avatars">'.$avatar_list.'</div>';
    } else {
      return $wpua_list;
    }
  }

  // Add default avatar_default to whitelist
  public function wpua_whitelist_options($whitelist_options){
    $whitelist_options['discussion'][] = 'avatar_default_wp_user_avatar';
    return $whitelist_options;
  }

  // Add actions links on plugin page
  public function wpua_action_links($links, $file){
    if(basename($file) == basename(plugin_basename(__FILE__))){
      $settings_link = '<a href="'.add_query_arg(array('page' => 'wp-user-avatar'), admin_url('admin.php')).'">'.__('Settings').'</a>';
      $links = array_merge($links, array($settings_link));
    }
    return $links;
  }

  // Add row meta on plugin page
  public function wpua_row_meta($links, $file){
    if(basename($file) == basename(plugin_basename(__FILE__))){
      $support_link = '<a href="http://wordpress.org/support/plugin/wp-user-avatar" target="_blank">'.__('Support Forums').'</a>';
      $donate_link = '<a href="http://siboliban.org/donate" target="_blank">'.__('Donate', 'wp-user-avatar').'</a>';
      $links = array_merge($links, array($support_link, $donate_link));
    }
    return $links;
  }

  // Add column to Users table
  public function wpua_add_column($columns){
    return $columns + array('wp-user-avatar' => __('WP User Avatar', 'wp-user-avatar'));
  }

  // Show thumbnail in Users table
  public function wpua_show_column($value, $column_name, $user_id){
    global $blog_id, $wpdb;
    $wpua = get_user_meta($user_id, $wpdb->get_blog_prefix($blog_id).'user_avatar', true);
    $wpua_image = wp_get_attachment_image($wpua, array(32,32));
    if($column_name == 'wp-user-avatar'){ $value = $wpua_image; }
    return $value;
  }

  // Get list table
  public function _wpua_get_list_table($class, $args = array()){
    require_once(WPUA_INC.'class-wp-user-avatar-list-table.php');
    $args['screen'] = 'wp-user-avatar';
    return new $class($args);
  }

  // Add media states
  public function wpua_add_media_state($media_states) {
    global $post, $wpua_avatar_default;
    $is_wpua = get_post_custom_values('_wp_attachment_wp_user_avatar', $post->ID);
    if(!empty($is_wpua)) {
      $media_states[] = __('Avatar');
    }
    if(!empty($wpua_avatar_default) && ($wpua_avatar_default == $post->ID)) {
      $media_states[] = __('Default Avatar');
    }
    return apply_filters('wpua_add_media_state', $media_states);
  }
}

$wpua_admin = new WP_User_Avatar_Admin();
