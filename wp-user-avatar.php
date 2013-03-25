<?php
/**
 * @package WP User Avatar
 * @version 1.2.5
 */
/*
Plugin Name: WP User Avatar
Plugin URI: http://wordpress.org/extend/plugins/wp-user-avatar/
Description: Use any image in your WordPress Media Libary as a custom user avatar. Add your own Default Avatar.
Version: 1.2.5
Author: Bangbay Siboliban
Author URI: http://siboliban.org/
*/

// Define paths and variables
define('WP_USER_AVATAR_FOLDER', basename(dirname(__FILE__)));
define('WP_USER_AVATAR_ABSPATH', trailingslashit(str_replace('\\','/', WP_PLUGIN_DIR.'/'.WP_USER_AVATAR_FOLDER)));
define('WP_USER_AVATAR_URLPATH', trailingslashit(plugins_url(WP_USER_AVATAR_FOLDER)));

// Define global variables
$avatar_default = get_option('avatar_default');
$avatar_default_wp_user_avatar = get_option('avatar_default_wp_user_avatar');
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
  if($avatar_default == $mustache_original){
    update_option('avatar_default', 'wp_user_avatar');
  }
  if(!empty($avatar_default_wp_user_avatar)){
    $avatar_default_wp_user_avatar_image = wp_get_attachment_image_src($avatar_default_wp_user_avatar, 'medium');
    if($avatar_default == $avatar_default_wp_user_avatar_image[0]){
      update_option('avatar_default', 'wp_user_avatar');
    }
  }
}
add_action('init', 'wp_user_avatar_default_avatar');

// Remove user metadata and options on plugin delete
function wp_user_avatar_delete_setup(){
  $users = get_users();
  foreach($users as $user){
    delete_user_meta($user->ID, 'wp_user_avatar');
  }
  delete_option('avatar_default_wp_user_avatar');
  update_option('avatar_default', 'mystery');
}

// WP User Avatar
if(!class_exists('wp_user_avatar')){
  class wp_user_avatar{
    function wp_user_avatar(){
      // Only works if user can upload files
      if(current_user_can('upload_files')){
        add_action('show_user_profile', array('wp_user_avatar','action_show_user_profile'));
        add_action('edit_user_profile', array($this,'action_show_user_profile'));
        add_action('personal_options_update', array($this,'action_process_option_update'));
        add_action('edit_user_profile_update', array($this,'action_process_option_update'));
        add_action('discussion_update', array($this,'action_process_option_update'));
        add_action('admin_enqueue_scripts', array($this, 'media_upload_scripts'));
      }
      // Only add attachment field for WP 3.4 and older
      if(!function_exists('wp_enqueue_media')){
        add_filter('attachment_fields_to_edit', array($this, 'add_wp_user_avatar_attachment_field_to_edit'), 10, 2); 
      }
      // Hide column in Users table if avatars are shown
      if(get_option('show_avatars') != '1'){
        add_filter('manage_users_columns', array($this, 'add_wp_user_avatar_column'), 10, 1);
        add_filter('manage_users_custom_column', array($this, 'show_wp_user_avatar_column'), 10, 3);
      }
      // Load scripts in front pages for logged in bbPress users
      if(class_exists('bbPress') && is_user_logged_in() && current_user_can('upload_files')){
        add_action('wp_enqueue_scripts', array($this, 'media_upload_scripts'));
      }
    }

    // Add to edit user profile
    function action_show_user_profile($user){
      global $current_user;
      $wp_user_avatar = get_user_meta($user->ID, 'wp_user_avatar', true);
      $hide_notice = has_wp_user_avatar($user->ID) ? ' class="hide-me"' : '';
      $hide_remove = !has_wp_user_avatar($user->ID) ? ' hide-me' : '';
      $avatar_medium_src = (get_option('show_avatars') == '1') ? get_avatar_original($user->user_email, 96) : includes_url().'images/blank.gif';
      $avatar_medium = has_wp_user_avatar($user->ID) ?  get_wp_user_avatar_src($user->ID, 'medium') : $avatar_medium_src;
      $profile = ($current_user->ID == $user->ID) ? 'Profile' : 'User';
    ?>
      <?php if(is_admin()) : ?>
        <h3><?php _e('WP User Avatar') ?></h3>
        <table class="form-table">
          <tr>
            <th><label for="wp_user_avatar"><?php _e('WP User Avatar'); ?></label></th>
            <td>
              <input type="hidden" name="wp-user-avatar" id="wp-user-avatar" value="<?php echo $wp_user_avatar; ?>" />
              <p><button type="button" class="button" id="add-wp-user-avatar"><?php _e('Edit WP User Avatar'); ?></button></p>
              <p id="wp-user-avatar-preview"><?php echo '<img src="'.$avatar_medium.'" alt="" />'; ?></p>
              <?php if(get_option('show_avatars') == '1') : ?>
                <p id="wp-user-avatar-notice"<?php echo $hide_notice; ?>><?php _e('This is your default avatar.'); ?></p>
              <?php endif; ?>
              <p><button type="button" class="button<?php echo $hide_remove; ?>" id="remove-wp-user-avatar"><?php _e('Remove'); ?></button></p>
              <p id="wp-user-avatar-message"><?php _e('Press "Update '.$profile.'" to save your changes.'); ?></p>
            </td>
          </tr>
        </table>
      <?php elseif(class_exists('bbPress')) : ?>
        <h2 class="entry-title"><?php _e('WP User Avatar'); ?></h2>
        <fieldset class="bbp-form">
          <legend><?php _e('WP User Avatar'); ?></legend>
          <input type="hidden" name="wp-user-avatar" id="wp-user-avatar" value="<?php echo $wp_user_avatar; ?>" />
          <p><button type="button" class="button" id="add-wp-user-avatar"><?php _e('Edit WP User Avatar'); ?></button></p>
          <p id="wp-user-avatar-preview"><?php echo '<img src="'.$avatar_medium.'" alt="" />'; ?></p>
          <?php if(get_option('show_avatars') == '1') : ?>
            <p id="wp-user-avatar-notice"<?php echo $hide_notice; ?>><?php _e('This is your default avatar.'); ?></p>
          <?php endif; ?>
          <p><button type="button" class="button" id="remove-wp-user-avatar"<?php echo $hide_remove; ?>><?php _e('Remove'); ?></button></p>
          <p id="wp-user-avatar-message"><?php _e('Press "Update '.$profile.'" to save your changes.'); ?></p>
        </fieldset>
      <?php endif; ?>
      <?php
      echo edit_default_wp_user_avatar($user->display_name, $avatar_medium_src, $avatar_medium_src);
    }

    // Update user meta
    function action_process_option_update($user_id){
      update_user_meta($user_id, 'wp_user_avatar', (isset($_POST['wp-user-avatar']) ? intval($_POST['wp-user-avatar']) : ''));
    }

    // Add button to attach image
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

    // Add column to Users page
    function add_wp_user_avatar_column($columns){
      return $columns + array('wp-user-avatar' => __('WP User Avatar'));;
    }

    // Show thumbnail of wp_user_avatar
    function show_wp_user_avatar_column($value, $column_name, $user_id){
      $wp_user_avatar = get_user_meta($user_id, 'wp_user_avatar', true);
      $wp_user_avatar_image = wp_get_attachment_image($wp_user_avatar, array(32,32));
      if($column_name == 'wp-user-avatar'){
        return $wp_user_avatar_image;
      }
    }

    // Media uploader
    function media_upload_scripts(){
      global $ssl;
      if(function_exists('wp_enqueue_media')){
        wp_enqueue_media();
      } else {
        wp_enqueue_script('jquery-1.7', 'http'.$ssl.'://ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.min.js');
        wp_enqueue_script('media-upload');
        wp_enqueue_script('thickbox');
        wp_enqueue_style('thickbox');
      }
      wp_enqueue_script('wp-user-avatar', WP_USER_AVATAR_URLPATH.'js/wp-user-avatar.js');
      wp_enqueue_style('wp-user-avatar', WP_USER_AVATAR_URLPATH.'css/wp-user-avatar.css');
    }
  }

  // Uploader scripts
  function edit_default_wp_user_avatar($section, $avatar_medium, $avatar_thumb){ ?>  
    <script type="text/javascript">
      jQuery(function(){
        <?php if(function_exists('wp_enqueue_media')) : // Backbone uploader for WP 3.5+ ?>
          openMediaUploader("<?php echo $section; ?>");
        <?php else : // Fall back to Thickbox uploader ?>
          openThickboxUploader("<?php echo $section; ?>", "<?php echo get_admin_url(); ?>media-upload.php?post_id=0&type=image&tab=library&TB_iframe=1");
        <?php endif; ?>
        removeWPUserAvatar("<?php echo htmlspecialchars_decode($avatar_medium); ?>", "<?php echo htmlspecialchars_decode($avatar_thumb); ?>");
      });
    </script>
  <?php
  }

  // Add default avatar
  function add_default_wp_user_avatar($avatar_list, $hide_remove=''){
    global $avatar_default, $avatar_default_wp_user_avatar, $mustache_medium, $mustache_admin;
    if(!empty($avatar_default_wp_user_avatar)){
      $avatar_medium_src = wp_get_attachment_image_src($avatar_default_wp_user_avatar, 'medium');
      $avatar_thumb_src = wp_get_attachment_image_src($avatar_default_wp_user_avatar, array(32,32));
      $avatar_medium = $avatar_medium_src[0];
      $avatar_thumb = $avatar_thumb_src[0];
    } else {
      $avatar_medium = $mustache_medium;
      $avatar_thumb = $mustache_admin;
      $hide_remove = ' class="hide-me"';
    }
    $selected_avatar = ($avatar_default == 'wp_user_avatar') ? ' checked="checked" ' : '';
    $avatar_thumb_img = '<div id="wp-user-avatar-preview"><img src="'.$avatar_thumb.'" width="32" /></div>';
    $wp_user_avatar_list = "\n\t<label><input type='radio' name='avatar_default' id='wp_user_avatar_radio' value='wp_user_avatar'$selected_avatar /> ";
    $wp_user_avatar_list .= preg_replace("/src='(.+?)'/", "src='\$1&amp;forcedefault=1'", $avatar_thumb_img);
    $wp_user_avatar_list .= ' '.__('WP User Avatar').'</label>';
    $wp_user_avatar_list .= '<p id="edit-wp-user-avatar"><button type="button" class="button" id="add-wp-user-avatar">'.__('Edit WP User Avatar').'</button>';
    $wp_user_avatar_list .= '<a href="#" id="remove-wp-user-avatar"'.$hide_remove.'>'.__('Remove').'</a></p>';
    $wp_user_avatar_list .= '<input type="hidden" id="wp-user-avatar" name="avatar_default_wp_user_avatar" value="'.$avatar_default_wp_user_avatar.'">';
    $wp_user_avatar_list .= '<p id="wp-user-avatar-message">'.__('Press "Save Changes" to save your changes.').'</p>';
    $wp_user_avatar_list .= edit_default_wp_user_avatar('Default Avatar', $mustache_medium, $mustache_admin);
    return $wp_user_avatar_list.$avatar_list;
  }
  add_filter('default_avatar_select', 'add_default_wp_user_avatar');

  // Add default avatar field to whitelist
  function wp_user_avatar_whitelist_options($whitelist_options){
    $whitelist_options['discussion'] = array('default_pingback_flag', 'default_ping_status', 'default_comment_status', 'comments_notify', 'moderation_notify', 'comment_moderation', 'require_name_email', 'comment_whitelist', 'comment_max_links', 'moderation_keys', 'blacklist_keys', 'show_avatars', 'avatar_rating', 'avatar_default', 'close_comments_for_old_posts', 'close_comments_days_old', 'thread_comments', 'thread_comments_depth', 'page_comments', 'comments_per_page', 'default_comments_page', 'comment_order', 'comment_registration', 'avatar_default_wp_user_avatar');
    return $whitelist_options;
  }
  add_filter('whitelist_options', 'wp_user_avatar_whitelist_options');

  // Returns true if user has Gravatar-hosted image
  function has_gravatar($id_or_email, $has_gravatar=false){
    global $ssl;
    $user = is_numeric($id_or_email) ? get_user_by('id', $id_or_email) : get_user_by('email', $id_or_email);
    $email = !empty($user) ? $user->user_email : $id_or_email;
    if(!empty($email)){
      $hash = md5(strtolower(trim($email)));
      $gravatar = 'http'.$ssl.'://www.gravatar.com/avatar/'.$hash.'?d=404';
      $headers = @get_headers($gravatar);
      $has_gravatar = !preg_match("|200|", $headers[0]) ? false : true;
    }
    return $has_gravatar;
  }

  // Returns true if user has wp_user_avatar
  function has_wp_user_avatar($user_id=''){
    global $post; 
    if(empty($user_id)){
      $author_name = get_query_var('author_name');
      $user = is_author() ? get_user_by('slug', $author_name) : get_the_author_meta('ID');
      $user_id = $user->ID;
    }
    $wp_user_avatar = get_user_meta($user_id, 'wp_user_avatar', true);
    if(!empty($wp_user_avatar)){
      return true;
    }
  }

  // Find wp_user_avatar, show get_avatar if empty
  function get_wp_user_avatar($id_or_email='', $size='96', $align=''){
    global $post, $comment, $avatar_default;
    // Find user ID on comment, author page, or post
    if(is_object($id_or_email)){
      if($comment->user_id != '0'){
        $id_or_email = $comment->user_id;
        $user = get_user_by('id', $id_or_email);
        $email = $user->user_email;
      } elseif(!empty($comment->comment_author_email)){
        $user = get_user_by('email', $comment->comment_author_email);
        $id_or_email = !empty($user) ? $user->ID : $comment->comment_author_email;
        $email = !empty($user) ? $user->user_email : $id_or_email;
      }
      $alt = $comment->comment_author;
    } else {
      if(!empty($id_or_email)){
        $user = is_numeric($id_or_email) ? get_user_by('id', $id_or_email) : get_user_by('email', $id_or_email);
      } else {
        $author_name = get_query_var('author_name');
        if(is_author()){
          $user = get_user_by('slug', $author_name);
        } else {
          $user_id = get_the_author_meta('ID');
          $user = get_user_by('id', $user_id);
        }
      }
      $id_or_email = !empty($user) ? $user->ID : '';
      $alt = $user->display_name;
    }
    $wp_user_avatar_meta = !empty($id_or_email) ? get_the_author_meta('wp_user_avatar', $id_or_email) : '';
    $alignclass = !empty($align) ? ' align'.$align : '';
    if(!empty($wp_user_avatar_meta)){
      $get_size = is_numeric($size) ? array($size,$size) : $size;
      $wp_user_avatar_image = wp_get_attachment_image_src($wp_user_avatar_meta, $get_size);
      $dimensions = is_numeric($size) ? ' width="'.$wp_user_avatar_image[1].'" height="'.$wp_user_avatar_image[2].'"' : '';
      $wp_user_avatar = '<img src="'.$wp_user_avatar_image[0].'"'.$dimensions.' alt="'.$alt.'" class="wp-user-avatar wp-user-avatar-'.$size.$alignclass.' avatar avatar avatar-'.$size.' photo" />';
    } else {
      if($size == 'original' || $size == 'large' || $size == 'medium' || $size == 'thumbnail'){
        $get_size = ($size == 'original') ? get_option('large_size_w') : get_option($size.'_size_w');
      } else {
        $get_size = $size;
      }
      $avatar = get_avatar($id_or_email, $get_size, $default='', $alt='');
      if(!is_numeric($size)){
        $avatar = preg_replace("/(width|height)=\'\d*\'\s/", '', $avatar);
        $avatar = preg_replace('/(width|height)=\"\d*\"\s/', '', $avatar);
        $avatar = str_replace('wp-user-avatar wp-user-avatar-'.$get_size.' ', '', $avatar);
        $avatar = str_replace("class='", "class='wp-user-avatar wp-user-avatar-".$size.$alignclass." ", $avatar);
      }
      $wp_user_avatar = $avatar;
    }
    return $wp_user_avatar;
  }

  // Return just the image src
  function get_wp_user_avatar_src($id_or_email, $size='', $align=''){
    $wp_user_avatar_image = get_wp_user_avatar($id_or_email, $size, $align);
    $output = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $wp_user_avatar_image, $matches, PREG_SET_ORDER);
    $wp_user_avatar_image_src = $matches [0] [1];
    return $wp_user_avatar_image_src;
  }

  // Replace get_avatar
  function get_wp_user_avatar_alt($avatar, $id_or_email, $size='', $default='', $alt=''){
    global $post, $pagenow, $comment, $avatar_default, $avatar_default_wp_user_avatar, $mustache_original, $mustache_medium, $mustache_thumbnail, $mustache_avatar, $mustache_admin;
    // Find user ID on comment, author page, or post
    $email = '';
    if(is_object($id_or_email)){
      if($comment->user_id != '0'){
        $id_or_email = $comment->user_id;
        $user = get_user_by('id', $id_or_email);
        $email = $user->user_email;
      } elseif(!empty($comment->comment_author_email)){
        $user = get_user_by('email', $comment->comment_author_email);
        $id_or_email = !empty($user) ? $user->ID : $comment->comment_author_email;
        $email = !empty($user) ? $user->user_email : $id_or_email;
      }
      $alt = $comment->comment_author;
    } else {
      if(!empty($id_or_email)){
        $user = is_numeric($id_or_email) ? get_user_by('id', $id_or_email) : get_user_by('email', $id_or_email);
      } else {
        $author_name = get_query_var('author_name');
        if(is_author()){
          $user = get_user_by('slug', $author_name);
        } else {
          $user_id = get_the_author_meta('ID');
          $user = get_user_by('id', $user_id);
        }
      }
      $id_or_email = $user->ID;
      $email = $user->user_email;
      $alt = $user->display_name;
    }
    $wp_user_avatar_meta = !empty($id_or_email) ? get_the_author_meta('wp_user_avatar', $id_or_email) : '';
    if(!empty($wp_user_avatar_meta) && $pagenow != 'options-discussion.php'){
      $wp_user_avatar_image = wp_get_attachment_image_src($wp_user_avatar_meta, array($size,$size));
      $dimensions = is_numeric($size) ? ' width="'.$wp_user_avatar_image[1].'" height="'.$wp_user_avatar_image[2].'"' : '';
      $default = $wp_user_avatar_image[0];
      $avatar = '<img src="'.$default.'"'.$dimensions.' alt="'.$alt.'" class="wp-user-avatar wp-user-avatar-'.$size.' avatar avatar-'.$size.' photo" />';
    } else {
      if(!has_gravatar($email) && $avatar_default == 'wp_user_avatar'){
        if(!empty($avatar_default_wp_user_avatar)){
          $avatar_default_wp_user_avatar_image = wp_get_attachment_image_src($avatar_default_wp_user_avatar, array($size,$size));
          $default = $avatar_default_wp_user_avatar_image[0];
          $dimensions = is_numeric($size) ? ' width="'.$avatar_default_wp_user_avatar_image[1].'" height="'.$avatar_default_wp_user_avatar_image[2].'"' : '';
        } else {
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
          $dimensions = is_numeric($size) ? ' width="'.$size.'" height="'.$size.'"' : '';
        }
        $avatar = "<img src='".$default."'".$dimensions." alt='".$alt."' class='wp-user-avatar wp-user-avatar-".$size." avatar avatar-".$size." photo avatar-default' />";
      }
    }
    $wp_user_avatar = $avatar;
    return $wp_user_avatar;
  }
  add_filter('get_avatar', 'get_wp_user_avatar_alt', 10, 6);

  // Get original avatar
  function get_avatar_original($id_or_email, $size='', $default='', $alt=''){
    global $avatar_default, $avatar_default_wp_user_avatar, $mustache_avatar;
    remove_filter('get_avatar', 'get_wp_user_avatar_alt');
    if(!has_gravatar($id_or_email) && $avatar_default == 'wp_user_avatar'){
      if(!empty($avatar_default_wp_user_avatar)){
        $avatar_default_wp_user_avatar_image = wp_get_attachment_image_src($avatar_default_wp_user_avatar, array($size,$size));
        $default = $avatar_default_wp_user_avatar_image[0];
      } else {
        $default = $mustache_avatar;
      }
    } else {
      $wp_user_avatar_image = get_avatar($id_or_email);
      $output = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $wp_user_avatar_image, $matches, PREG_SET_ORDER);
      $default = $matches [0] [1];
    }
    return $default;
  }

  // Shortcode
  function wp_user_avatar_shortcode($atts, $content){
    // EXAMPLE USAGE:
    // [avatar size="medium"]

    // Set shortcode attributes
    extract(shortcode_atts(array('user' => '', 'size' => '96', 'align' => '', 'link' => ''), $atts));
    $get_user = get_user_by('slug', $user);
    $id_or_email = !empty($get_user) ? $get_user->ID : '';
    if(!empty($link)){
      $link_class = $link;
      if($link == 'file'){
        $image_link = get_wp_user_avatar_src($id_or_email, 'original', $align);
      } elseif($link == 'attachment'){
        $image_link = get_attachment_link(get_the_author_meta('wp_user_avatar', $id_or_email));
      } else {
        $image_link = $link;
        $link_class = 'custom';
      }
      $wp_user_avatar = '<a href="'.$image_link.'" class="wp-user-avatar-link wp-user-avatar-'.$link_class.'">'.get_wp_user_avatar($id_or_email, $size, $align).'</a>';
    } else {
      $wp_user_avatar = get_wp_user_avatar($id_or_email, $size, $align);
    }
    return $wp_user_avatar;
  }
  add_shortcode('avatar','wp_user_avatar_shortcode');

  // Initialize wp_user_avatar
  function wp_user_avatar_load(){
    global $wp_user_avatar_instance;
    $wp_user_avatar_instance = new wp_user_avatar();
  }
  add_action('plugins_loaded','wp_user_avatar_load');
}
?>
