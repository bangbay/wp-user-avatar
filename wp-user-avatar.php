<?php
/**
 * @package WP User Avatar
 * @version 1.3.6
 */
/*
Plugin Name: WP User Avatar
Plugin URI: http://wordpress.org/extend/plugins/wp-user-avatar/
Description: Use any image in your WordPress Media Libary as a custom user avatar. Add your own Default Avatar.
Version: 1.3.6
Author: Bangbay Siboliban
Author URI: http://siboliban.org/
*/

// Define paths and variables
define('WP_USER_AVATAR_VERSION', '1.3.6');
define('WP_USER_AVATAR_FOLDER', basename(dirname(__FILE__)));
define('WP_USER_AVATAR_ABSPATH', trailingslashit(str_replace('\\','/', WP_PLUGIN_DIR.'/'.WP_USER_AVATAR_FOLDER)));
define('WP_USER_AVATAR_URLPATH', trailingslashit(plugins_url(WP_USER_AVATAR_FOLDER)));

// Define global variables
$avatar_default = get_option('avatar_default');
$avatar_default_wp_user_avatar = get_option('avatar_default_wp_user_avatar');
$show_avatars = get_option('show_avatars');
$mustache_original = WP_USER_AVATAR_URLPATH.'images/wp-user-avatar.png';
$mustache_medium = WP_USER_AVATAR_URLPATH.'images/wp-user-avatar-300x300.png';
$mustache_thumbnail = WP_USER_AVATAR_URLPATH.'images/wp-user-avatar-150x150.png';
$mustache_avatar = WP_USER_AVATAR_URLPATH.'images/wp-user-avatar-96x96.png';
$mustache_admin = WP_USER_AVATAR_URLPATH.'images/wp-user-avatar-32x32.png';
$ssl = is_ssl() ? 's' : '';

// Load add-ons
include_once(WP_USER_AVATAR_ABSPATH.'includes/tinymce.php');

// Initialize default settings
register_activation_hook(__FILE__, 'wp_user_avatar_options');

// Remove user metadata and options on plugin delete
register_uninstall_hook(__FILE__, 'wp_user_avatar_delete_setup');

// Settings saved to wp_options
function wp_user_avatar_options(){
  add_option('avatar_default_wp_user_avatar','');
}
add_action('init', 'wp_user_avatar_options');

// Update default avatar to new format
function wp_user_avatar_default_avatar(){
  global $avatar_default, $avatar_default_wp_user_avatar, $mustache_original;
  // If default avatar is the old mustache URL, update it
  if($avatar_default == $mustache_original){
    update_option('avatar_default', 'wp_user_avatar');
  }
  // If user had an image URL as the default avatar, replace with ID instead
  if(!empty($avatar_default_wp_user_avatar)){
    $avatar_default_wp_user_avatar_image = wp_get_attachment_image_src($avatar_default_wp_user_avatar, 'medium');
    if($avatar_default == $avatar_default_wp_user_avatar_image[0]){
      update_option('avatar_default', 'wp_user_avatar');
    }
  }
}
add_action('init', 'wp_user_avatar_default_avatar');

// Rename user meta to match database settings
function wp_user_avatar_user_meta(){
  global $wpdb, $blog_id;
  $wp_user_avatar_metakey = $wpdb->get_blog_prefix($blog_id).'user_avatar';
  // If database tables start with something other than wp_
  if($wp_user_avatar_metakey != 'wp_user_avatar'){
    $users = get_users();
    // Move current user metakeys to new metakeys
    foreach($users as $user){
      $wp_user_avatar = get_user_meta($user->ID, 'wp_user_avatar', true);
      if(!empty($wp_user_avatar)){
        update_user_meta($user->ID, $wpdb->get_blog_prefix($blog_id).'user_avatar', $wp_user_avatar);
        delete_user_meta($user->ID, 'wp_user_avatar');
      }
    }
  }
}
add_action('init', 'wp_user_avatar_user_meta');

// Remove user metadata and options on plugin delete
function wp_user_avatar_delete_setup(){
  global $wpdb, $blog_id, $switched;
  $users = get_users();
  // Remove settings for all sites in multisite
  if(is_multisite()){
    $blogs = $wpdb->get_results($wpdb->prepare("SELECT * FROM wp_blogs"));
    foreach($users as $user){
      foreach($blogs as $blog){
        delete_user_meta($user->ID, $wpdb->get_blog_prefix($blog->blog_id).'user_avatar');
      }
    }
    foreach($blogs as $blog){
      switch_to_blog($blog->blog_id);
      delete_option('avatar_default_wp_user_avatar');
    }
  } else {
    foreach($users as $user){
      delete_user_meta($user->ID, $wpdb->get_blog_prefix($blog_id).'user_avatar');
    }
    delete_option('avatar_default_wp_user_avatar');
  }
  // Reset all default avatars to Mystery Man
  update_option('avatar_default', 'mystery');
}

// WP User Avatar
if(!class_exists('wp_user_avatar')){
  class wp_user_avatar{
    function wp_user_avatar(){
      global $current_user, $show_avatars;
      // Only works if user can upload files
      if(current_user_can('upload_files')){
        // Adds WPUA to profile
        add_action('show_user_profile', array('wp_user_avatar','action_show_user_profile'));
        add_action('edit_user_profile', array($this,'action_show_user_profile'));
        add_action('personal_options_update', array($this,'action_process_option_update'));
        add_action('edit_user_profile_update', array($this,'action_process_option_update'));
        // Adds WPUA to Discussion settings
        add_action('discussion_update', array($this,'action_process_option_update'));
        // Adds scripts to admin
        add_action('admin_enqueue_scripts', array($this, 'media_upload_scripts'));
        // Adds scripts to front pages
        add_action('wp_enqueue_scripts', array($this, 'media_upload_scripts'));
      }
      // Only add attachment field for WP 3.4 and older
      if(!function_exists('wp_enqueue_media')){
        add_filter('attachment_fields_to_edit', array($this, 'add_wp_user_avatar_attachment_field_to_edit'), 10, 2); 
      }
      // Hide column in Users table if default avatars are enabled
      if($show_avatars != '1'){
        add_filter('manage_users_columns', array($this, 'add_wp_user_avatar_column'), 10, 1);
        add_filter('manage_users_custom_column', array($this, 'show_wp_user_avatar_column'), 10, 3);
      }
    }

    // Add to edit user profile
    function action_show_user_profile($user){
      global $wpdb, $blog_id, $current_user, $show_avatars;
      // Get WPUA attachment ID
      $wp_user_avatar = get_user_meta($user->ID, $wpdb->get_blog_prefix($blog_id).'user_avatar', true);
      // Show remove button if WPUA is set
      $hide_notice = has_wp_user_avatar($user->ID) ? ' class="hide-me"' : '';
      $hide_remove = !has_wp_user_avatar($user->ID) ? ' hide-me' : '';
      // If avatars are enabled, get original avatar image or show blank
      $avatar_medium_src = ($show_avatars == '1') ? get_avatar_original($user->user_email, 96) : includes_url().'images/blank.gif';
      // Check if user has wp_user_avatar, if not show image from above
      $avatar_medium = has_wp_user_avatar($user->ID) ?  get_wp_user_avatar_src($user->ID, 'medium') : $avatar_medium_src;
      // Change text on message based on current user
      $profile = ($current_user->ID == $user->ID) ? 'Profile' : 'User';
    ?>
      <?php if(class_exists('bbPress') && !is_admin()) : // Add to bbPress profile with same style ?>
        <h2 class="entry-title"><?php _e('WP User Avatar'); ?></h2>
        <fieldset class="bbp-form">
          <legend><?php _e('WP User Avatar'); ?></legend>
      <?php else : // Add to profile with admin style ?>
        <h3><?php _e('WP User Avatar') ?></h3>
        <table class="form-table">
          <tr>
            <th><label for="wp_user_avatar"><?php _e('WP User Avatar'); ?></label></th>
            <td>
      <?php endif; ?>
      <input type="hidden" name="wp-user-avatar" id="wp-user-avatar" value="<?php echo $wp_user_avatar; ?>" />
      <p><button type="button" class="button" id="add-wp-user-avatar"><?php _e('Edit WP User Avatar'); ?></button></p>
      <p id="wp-user-avatar-preview"><?php echo '<img src="'.$avatar_medium.'" alt="" />'; ?></p>
      <?php if($show_avatars == '1') : ?>
        <p id="wp-user-avatar-notice"<?php echo $hide_notice; ?>><?php _e('This is your default avatar.'); ?></p>
      <?php endif; ?>
      <p><button type="button" class="button<?php echo $hide_remove; ?>" id="remove-wp-user-avatar"><?php _e('Remove'); ?></button></p>
      <p id="wp-user-avatar-message"><?php _e('Press "Update '.$profile.'" to save your changes.'); ?></p>
      <?php if(class_exists('bbPress') && !is_admin()) : // Add to bbPress profile with same style ?>
        </fieldset>
      <?php else : // Add to profile with admin style ?>
            </td>
          </tr>
        </table>
      <?php endif; ?>
      <?php
      // Add JS
      echo edit_default_wp_user_avatar($user->display_name, $avatar_medium_src);
    }

    // Update user meta
    function action_process_option_update($user_id){
      global $wpdb, $blog_id;
      update_user_meta($user_id, $wpdb->get_blog_prefix($blog_id).'user_avatar', (isset($_POST['wp-user-avatar']) ? intval($_POST['wp-user-avatar']) : ''));
    }

    // Add button to attach image for WP 3.4 and older
    function add_wp_user_avatar_attachment_field_to_edit($fields, $post){
      $image = wp_get_attachment_image_src($post->ID, "medium");
      $button = '<button type="button" class="button" id="set-wp-user-avatar-image" onclick="setWPUserAvatar(\''.$post->ID.'\', \''.$image[0].'\')">Set WP User Avatar</button>';
      $fields['wp-user-avatar'] = array(
        'label' => __('WP User Avatar'),
        'input' => 'html',
        'html' => $button
      );
      return $fields;
    }

    // Add column to Users table
    function add_wp_user_avatar_column($columns){
      return $columns + array('wp-user-avatar' => __('WP User Avatar'));
    }

    // Show thumbnail in Users table
    function show_wp_user_avatar_column($value, $column_name, $user_id){
      global $wpdb, $blog_id;
      $wp_user_avatar = get_user_meta($user_id, $wpdb->get_blog_prefix($blog_id).'user_avatar', true);
      $wp_user_avatar_image = wp_get_attachment_image($wp_user_avatar, array(32,32));
      if($column_name == 'wp-user-avatar'){
        return $wp_user_avatar_image;
      }
    }

    // Media uploader
    function media_upload_scripts(){
      if(function_exists('wp_enqueue_media')){
        wp_enqueue_media();
      } else {
        wp_enqueue_script('media-upload');
        wp_enqueue_script('thickbox');
        wp_enqueue_style('thickbox');
      }
      wp_enqueue_script('wp-user-avatar', WP_USER_AVATAR_URLPATH.'js/wp-user-avatar.js', '', WP_USER_AVATAR_VERSION);
      wp_enqueue_style('wp-user-avatar', WP_USER_AVATAR_URLPATH.'css/wp-user-avatar.css', '', WP_USER_AVATAR_VERSION);
    }
  }

  // Uploader scripts
  function edit_default_wp_user_avatar($section, $avatar_thumb){ ?>
    <script type="text/javascript">
      jQuery(function(){
        <?php if(function_exists('wp_enqueue_media')) : // Backbone uploader for WP 3.5+ ?>
          openMediaUploader("<?php echo $section; ?>");
        <?php else : // Fall back to Thickbox uploader ?>
          openThickboxUploader("<?php echo $section; ?>", "<?php echo get_admin_url(); ?>media-upload.php?post_id=0&type=image&tab=library&TB_iframe=1");
        <?php endif; ?>
        removeWPUserAvatar("<?php echo htmlspecialchars_decode($avatar_thumb); ?>");
      });
    </script>
  <?php
  }

  // Returns true if user has Gravatar-hosted image
  function has_gravatar($id_or_email, $has_gravatar=false, $user='', $email=''){
    global $ssl;
    if(!is_object($id_or_email) && !empty($id_or_email)){
      // Find user by ID or e-mail address
      $user = is_numeric($id_or_email) ? get_user_by('id', $id_or_email) : get_user_by('email', $id_or_email);
      // Get registered user e-mail address from profile, otherwise e-mail address should be value
      $email = !empty($user) ? $user->user_email : '';
    }
    // Check if Gravatar image returns 200 (OK) or 404 (Not Found)
    if(!empty($email)){
      $hash = md5(strtolower(trim($email)));
      $gravatar = 'http'.$ssl.'://www.gravatar.com/avatar/'.$hash.'?d=404';
      $headers = @get_headers($gravatar);
      $has_gravatar = !preg_match("|200|", $headers[0]) ? false : true;
    }
    return $has_gravatar;
  }

  // Returns true if user has wp_user_avatar
  function has_wp_user_avatar($id_or_email='', $has_wp_user_avatar=false, $user='', $user_id=''){
    global $wpdb, $blog_id;
    if(!is_object($id_or_email) && !empty($id_or_email)){
      // Find user by ID or e-mail address
      $user = is_numeric($id_or_email) ? get_user_by('id', $id_or_email) : get_user_by('email', $id_or_email);
      // Get registered user e-mail address from profile, otherwise e-mail address should be value
      $user_id = !empty($user) ? $user->ID : '';
    }
    $wp_user_avatar = get_user_meta($user_id, $wpdb->get_blog_prefix($blog_id).'user_avatar', true);
    $has_wp_user_avatar = !empty($wp_user_avatar) ? true : false;
    return $has_wp_user_avatar;
  }

  // Replace get_avatar only in get_wp_user_avatar
  function get_avatar_filter($avatar, $id_or_email, $size='', $default='', $alt=''){
    global $post, $comment, $avatar_default, $avatar_default_wp_user_avatar, $mustache_original, $mustache_medium, $mustache_thumbnail, $mustache_avatar, $mustache_admin;
    // User has WPUA
    if(is_object($id_or_email)){
      if(!empty($comment->comment_author_email)){
        $avatar = get_wp_user_avatar($comment->comment_author_email, $size, $default, $alt);
      } else {
        $avatar = get_wp_user_avatar('unknown@gravatar.com', $size, $default, $alt);
      }
    } else {
      if(has_wp_user_avatar($id_or_email)){
        $avatar = get_wp_user_avatar($id_or_email, $size, $default, $alt);
      // User has Gravatar
      } elseif(has_gravatar($id_or_email)){
        $avatar = $avatar;
      // User doesn't have WPUA or Gravatar and Default Avatar is wp_user_avatar, show custom Default Avatar
      } elseif($avatar_default == 'wp_user_avatar'){
        // Show custom Default Avatar
        if(!empty($avatar_default_wp_user_avatar)){
          // Get image
          $avatar_default_wp_user_avatar_image = wp_get_attachment_image_src($avatar_default_wp_user_avatar, array($size,$size));
          // Image src
          $default = $avatar_default_wp_user_avatar_image[0];
          // Add dimensions if numeric size
          $dimensions = ' width="'.$avatar_default_wp_user_avatar_image[1].'" height="'.$avatar_default_wp_user_avatar_image[2].'"';
        } else {
          // Get mustache image based on numeric size comparison
          if($size > get_option('medium_size_w')){
            $default = $mustache_original;
          } elseif($size <= get_option('medium_size_w') && $size > get_option('thumbnail_size_w')){
            $default = $mustache_medium;
          } elseif($size <= get_option('thumbnail_size_w') && $size > 96){
            $default = $mustache_thumbnail;
          } elseif($size <= 96 && $size > 32){
            $default = $mustache_avatar;
          } elseif($size <= 32){
            $default = $mustache_admin;
          }
          // Add dimensions if numeric size
          $dimensions = ' width="'.$size.'" height="'.$size.'"';
          $defaultcss = ' avatar-default';
        }
        // Construct the img tag
        $avatar = "<img src='".$default."'".$dimensions." alt='".$alt."' class='wp-user-avatar wp-user-avatar-".$size." avatar avatar-".$size." photo'".$defaultcss." />";
      }
    }
    return $avatar;
  }
  add_filter('get_avatar', 'get_avatar_filter', 10, 6);

  // Get original avatar, for when user removes wp_user_avatar
  function get_avatar_original($id_or_email, $size='', $default='', $alt=''){
    global $avatar_default, $avatar_default_wp_user_avatar, $mustache_avatar;
    // Remove get_avatar filter
    remove_filter('get_avatar', 'get_avatar_filter');
    // User doesn't Gravatar and Default Avatar is wp_user_avatar, show custom Default Avatar
    if(!has_gravatar($id_or_email) && $avatar_default == 'wp_user_avatar'){
      // Show custom Default Avatar
      if(!empty($avatar_default_wp_user_avatar)){
        $avatar_default_wp_user_avatar_image = wp_get_attachment_image_src($avatar_default_wp_user_avatar, array($size,$size));
        $default = $avatar_default_wp_user_avatar_image[0];
      } else {
        $default = $mustache_avatar;
      }
    } else {
      // Get image from Gravatar, whether it's the user's image or default image
      $wp_user_avatar_image = get_avatar($id_or_email);
      // Takes the img tag, extracts the src
      $output = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $wp_user_avatar_image, $matches, PREG_SET_ORDER);
      $default = $matches [0] [1];
    }
    return $default;
  }

  // Find WPUA, show get_avatar if empty
  function get_wp_user_avatar($id_or_email='', $size='96', $align='', $alt=''){
    global $post, $comment, $avatar_default, $wpdb, $blog_id;
    // Checks if comment
    if(is_object($id_or_email)){
      // Checks if comment author is registered user by user ID
      if($comment->user_id != '0'){
        $id_or_email = $comment->user_id;
        $user = get_user_by('id', $id_or_email);
      // Checks that comment author isn't anonymous
      } elseif(!empty($comment->comment_author_email)){
        // Checks if comment author is registered user by e-mail address
        $user = get_user_by('email', $comment->comment_author_email);
        // Get registered user info from profile, otherwise e-mail address should be value
        $id_or_email = !empty($user) ? $user->ID : $comment->comment_author_email;
      }
      $alt = $comment->comment_author;
    } else {
      if(!empty($id_or_email)){
        // Find user by ID or e-mail address
        $user = is_numeric($id_or_email) ? get_user_by('id', $id_or_email) : get_user_by('email', $id_or_email);
      } else {
        // Find author's name if id_or_email is empty
        $author_name = get_query_var('author_name');
        if(is_author()){
          // On author page, get user by page slug
          $user = get_user_by('slug', $author_name);
        } else {
          // On post, get user by author meta
          $user_id = get_the_author_meta('ID');
          $user = get_user_by('id', $user_id);
        }
      }
      // Set user's ID and name
      if(!empty($user)){
        $id_or_email = $user->ID;
        $alt = $user->display_name;
      }
    }
    // Checks if user has WPUA
    $wp_user_avatar_meta = !empty($id_or_email) ? get_the_author_meta($wpdb->get_blog_prefix($blog_id).'user_avatar', $id_or_email) : '';
    // Add alignment class
    $alignclass = !empty($align) ? ' align'.$align : '';
    // User has WPUA, bypass get_avatar
    if(!empty($wp_user_avatar_meta)){
      // Numeric size use size array
      $get_size = is_numeric($size) ? array($size,$size) : $size;
      // Get image src
      $wp_user_avatar_image = wp_get_attachment_image_src($wp_user_avatar_meta, $get_size);
      // Add dimensions to img only if numeric size was specified
      $dimensions = is_numeric($size) ? ' width="'.$wp_user_avatar_image[1].'" height="'.$wp_user_avatar_image[2].'"' : '';
      // Construct the img tag
      $avatar = '<img src="'.$wp_user_avatar_image[0].'"'.$dimensions.' alt="'.$alt.'" class="wp-user-avatar wp-user-avatar-'.$size.$alignclass.' avatar avatar avatar-'.$size.' photo" />';
    } else {
      // Get numeric sizes for non-numeric sizes based on media options
      if($size == 'original' || $size == 'large' || $size == 'medium' || $size == 'thumbnail'){
        $get_size = ($size == 'original') ? get_option('large_size_w') : get_option($size.'_size_w');
      } else {
        // Numeric sizes leave as-is
        $get_size = $size;
      }
      // User with no WPUA uses get_avatar
      $avatar = get_avatar($id_or_email, $get_size, $default='', $alt='');
      // Remove width and height for non-numeric sizes
      if(!is_numeric($size)){
        $avatar = preg_replace("/(width|height)=\'\d*\'\s/", '', $avatar);
        $avatar = preg_replace('/(width|height)=\"\d*\"\s/', '', $avatar);
        $avatar = str_replace('wp-user-avatar wp-user-avatar-'.$get_size.' ', '', $avatar);
        $avatar = str_replace("class='", "class='wp-user-avatar wp-user-avatar-".$size.$alignclass." ", $avatar);
      }
    }
    return $avatar;
  }

  // Return just the image src
  function get_wp_user_avatar_src($id_or_email, $size='', $align=''){
    $wp_user_avatar_image_src = '';
    // Gets the avatar img tag
    $wp_user_avatar_image = get_wp_user_avatar($id_or_email, $size, $align);
    // Takes the img tag, extracts the src
    if(!empty($wp_user_avatar_image)){
      $output = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $wp_user_avatar_image, $matches, PREG_SET_ORDER);
      $wp_user_avatar_image_src = $matches [0] [1];
    }
    return $wp_user_avatar_image_src;
  }

  // Shortcode
  function wp_user_avatar_shortcode($atts, $content){
    global $wpdb, $blog_id;
    // Set shortcode attributes
    extract(shortcode_atts(array('user' => '', 'size' => '96', 'align' => '', 'link' => '', 'target' => ''), $atts));
    // Find user by ID, login, slug, or e-mail address
    if(!empty($user)){
      $user = is_numeric($user) ? get_user_by('id', $user) : get_user_by('login', $user);
      $user = empty($user) ? get_user_by('slug', $user) : $user;
      $user = empty($user) ? get_user_by('email', $user) : $user;
    }
    // Get user ID
    $id_or_email = !empty($user) ? $user->ID : '';
    // Check if link is set
    if(!empty($link)){
      // CSS class is same as link type, except for URL
      $link_class = $link;
      // Open in new window
      $target_link = !empty($target) ? ' target="'.$target.'"' : '';
      if($link == 'file'){
        // Get image src
        $image_link = get_wp_user_avatar_src($id_or_email, 'original', $align);
      } elseif($link == 'attachment'){
        // Get attachmennt URL
        $image_link = get_attachment_link(get_the_author_meta($wpdb->get_blog_prefix($blog_id).'user_avatar', $id_or_email));
      } else {
        // URL
        $image_link = $link;
        $link_class = 'custom';
      }
      // Wrap the avatar inside the link
      $avatar = '<a href="'.$image_link.'" class="wp-user-avatar-link wp-user-avatar-'.$link_class.'"'.$target_link.'>'.get_wp_user_avatar($id_or_email, $size, $align).'</a>';
    } else {
      // Get WPUA as normal
      $avatar = get_wp_user_avatar($id_or_email, $size, $align);
    }
    return $avatar;
  }
  add_shortcode('avatar','wp_user_avatar_shortcode');

  // Add default avatar
  function add_default_wp_user_avatar($avatar_list){
    global $avatar_default, $avatar_default_wp_user_avatar, $mustache_medium, $mustache_admin;
    // Remove get_avatar filter
    remove_filter('get_avatar', 'get_avatar_filter');
    // Set avatar_list variable
    $avatar_list = '';
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
      $selected = ($avatar_default == $default_key) ? 'checked="checked" ' : '';
      $avatar_list .= "\n\t<label><input type='radio' name='avatar_default' id='avatar_{$default_key}' value='".esc_attr($default_key)."' {$selected}/> ";
      $avatar_list .= preg_replace("/src='(.+?)'/", "src='\$1&amp;forcedefault=1'", $avatar);
      $avatar_list .= ' '.$default_name.'</label>';
      $avatar_list .= '<br />';
    }
    // Show remove link if custom Default Avatar is set
    if(!empty($avatar_default_wp_user_avatar)){
      $avatar_thumb_src = wp_get_attachment_image_src($avatar_default_wp_user_avatar, array(32,32));
      $avatar_thumb = $avatar_thumb_src[0];
      $hide_remove = '';
    } else {
      $avatar_thumb = $mustache_admin;
      $hide_remove = ' class="hide-me"';
    }
    // Default Avatar is wp_user_avatar, check the radio button next to it
    $selected_avatar = ($avatar_default == 'wp_user_avatar') ? ' checked="checked" ' : '';
    // Wrap WPUA in div
    $avatar_thumb_img = '<div id="wp-user-avatar-preview"><img src="'.$avatar_thumb.'" width="32" /></div>';
    // Add WPUA to list
    $wp_user_avatar_list = "\n\t<label><input type='radio' name='avatar_default' id='wp_user_avatar_radio' value='wp_user_avatar'$selected_avatar /> ";
    $wp_user_avatar_list .= preg_replace("/src='(.+?)'/", "src='\$1'", $avatar_thumb_img);
    $wp_user_avatar_list .= ' '.__('WP User Avatar').'</label>';
    $wp_user_avatar_list .= '<p id="edit-wp-user-avatar"><button type="button" class="button" id="add-wp-user-avatar">'.__('Edit WP User Avatar').'</button>';
    $wp_user_avatar_list .= '<a href="#" id="remove-wp-user-avatar"'.$hide_remove.'>'.__('Remove').'</a></p>';
    $wp_user_avatar_list .= '<input type="hidden" id="wp-user-avatar" name="avatar_default_wp_user_avatar" value="'.$avatar_default_wp_user_avatar.'">';
    $wp_user_avatar_list .= '<p id="wp-user-avatar-message">'.__('Press "Save Changes" to save your changes.').'</p>';
    $wp_user_avatar_list .= edit_default_wp_user_avatar('Default Avatar', $mustache_admin);
    return $wp_user_avatar_list.$avatar_list;
  }
  add_filter('default_avatar_select', 'add_default_wp_user_avatar', 10);

  // Add default avatar_default to whitelist
  function wp_user_avatar_whitelist_options($whitelist_options){
    $whitelist_options['discussion'] = array('default_pingback_flag', 'default_ping_status', 'default_comment_status', 'comments_notify', 'moderation_notify', 'comment_moderation', 'require_name_email', 'comment_whitelist', 'comment_max_links', 'moderation_keys', 'blacklist_keys', 'show_avatars', 'avatar_rating', 'avatar_default', 'close_comments_for_old_posts', 'close_comments_days_old', 'thread_comments', 'thread_comments_depth', 'page_comments', 'comments_per_page', 'default_comments_page', 'comment_order', 'comment_registration', 'avatar_default_wp_user_avatar');
    return $whitelist_options;
  }
  add_filter('whitelist_options', 'wp_user_avatar_whitelist_options', 10);

  // Initialize WPUA after other plugins are loaded
  function wp_user_avatar_load(){
    global $wp_user_avatar_instance;
    $wp_user_avatar_instance = new wp_user_avatar();
  }
  add_action('plugins_loaded','wp_user_avatar_load');
}
?>
