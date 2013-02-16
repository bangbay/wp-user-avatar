<?php
/**
 * @package WP User Avatar
 * @version 1.2.4
 */
/*
Plugin Name: WP User Avatar
Plugin URI: http://wordpress.org/extend/plugins/wp-user-avatar/
Description: Use any image in your WordPress Media Libary as a custom user avatar. Add your own Default Avatar.
Version: 1.2.4
Author: Bangbay Siboliban
Author URI: http://siboliban.org/
*/

// Define paths and variables
define('WP_USER_AVATAR_FOLDER', basename(dirname(__FILE__)));
define('WP_USER_AVATAR_ABSPATH', trailingslashit(str_replace('\\','/', WP_PLUGIN_DIR.'/'.WP_USER_AVATAR_FOLDER)));
define('WP_USER_AVATAR_URLPATH', trailingslashit(plugins_url(WP_USER_AVATAR_FOLDER)));

// Load add-ons
include_once(WP_USER_AVATAR_ABSPATH.'includes/tinymce.php');

// Initialize default settings
register_activation_hook(__FILE__, 'wp_user_avatar_options');

// Remove user metadata on plugin delete
register_uninstall_hook(__FILE__, 'wp_user_avatar_delete_setup');

// Settings saved to wp_options
function wp_user_avatar_options(){
  add_option('avatar_default_wp_user_avatar','');
}
add_action('init', 'wp_user_avatar_options');

// Remove user metadata
function wp_user_avatar_delete_setup(){
  $users = get_users();
  foreach($users as $user){
    delete_user_meta($user->ID, 'wp_user_avatar');
  }
  delete_option('avatar_default_wp_user_avatar');
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
      $wp_user_avatar = get_user_meta($user->ID, 'wp_user_avatar', true);
      $hide_notice = has_wp_user_avatar($user->ID) ? ' style="display:none;"' : '';
      $hide_remove = !has_wp_user_avatar($user->ID) ? ' style="display:none;"' : '';
      $avatar_full_src = get_option('show_avatars') == '1' ? get_avatar_original($user->user_email, 96) : includes_url().'images/blank.gif';
      $avatar_full = has_wp_user_avatar($user->ID) ?  get_wp_user_avatar_src($user->ID, 'medium') : $avatar_full_src;
      global $current_user;
      $profile = $current_user->ID == $user->ID ? 'Profile' : 'User';
    ?>
      <?php if(is_admin()) : ?>
        <h3><?php _e('WP User Avatar') ?></h3>
        <table class="form-table">
          <tr>
            <th><label for="wp_user_avatar"><?php _e('WP User Avatar'); ?></label></th>
            <td>
              <input type="hidden" name="wp-user-avatar" id="wp-user-avatar" value="<?php echo $wp_user_avatar; ?>" />
              <p><button type="button" class="button" id="add-wp-user-avatar"><?php _e('Edit WP User Avatar'); ?></button></p>
              <p id="wp-user-avatar-preview"><?php echo '<img src="'.$avatar_full.'" alt="" />'; ?></p>
              <?php if(get_option('show_avatars') == '1') : ?>
                <p id="wp-user-avatar-notice"<?php echo $hide_notice; ?>>This is your default avatar.</p>
              <?php endif; ?>
              <p><button type="button" class="button" id="remove-wp-user-avatar"<?php echo $hide_remove; ?>><?php _e('Remove'); ?></button></p>
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
          <p id="wp-user-avatar-preview"><?php echo '<img src="'.$avatar_full.'" alt="" />'; ?></p>
          <?php if(get_option('show_avatars') == '1') : ?>
            <p id="wp-user-avatar-notice"<?php echo $hide_notice; ?>>This is your default avatar.</p>
          <?php endif; ?>
          <p><button type="button" class="button" id="remove-wp-user-avatar"<?php echo $hide_remove; ?>><?php _e('Remove'); ?></button></p>
          <p id="wp-user-avatar-message"><?php _e('Press "Update '.$profile.'" to save your changes.'); ?></p>
        </fieldset>
      <?php endif; ?>
      <?php
      echo edit_default_wp_user_avatar($user->display_name, $avatar_full_src, $avatar_full_src);
    }

    // Update user meta
    function action_process_option_update($user_id){
      update_user_meta($user_id, 'wp_user_avatar', (isset($_POST['wp-user-avatar']) ? $_POST['wp-user-avatar'] : ''));
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
      if(!function_exists('wp_enqueue_media')){
        wp_enqueue_script('jquery-1.7', 'https://ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.min.js');
        wp_enqueue_script('media-upload');
        wp_enqueue_script('thickbox');
        wp_enqueue_style('thickbox');
      } else {
        wp_enqueue_media();
      }
      wp_enqueue_script('wp-user-avatar', WP_USER_AVATAR_URLPATH.'js/wp-user-avatar.js');
      wp_enqueue_style('wp-user-avatar', WP_USER_AVATAR_URLPATH.'css/wp-user-avatar.css');
    }
  }

  // Uploader scripts
  function edit_default_wp_user_avatar($section, $avatar_full, $avatar_thumb){ ?>  
    <script type="text/javascript">
      jQuery(function(){
        <?php if(function_exists('wp_enqueue_media')) : // Backbone uploader for WP 3.5+ ?>
          openMediaUploader("<?php echo $section; ?>");
        <?php else : // Fall back to Thickbox uploader ?>
          openThickboxUploader("<?php echo $section; ?>", "<?php echo get_admin_url(); ?>media-upload.php?post_id=0&type=image&tab=library&TB_iframe=1");
        <?php endif; ?>
        removeWPUserAvatar("<?php echo htmlspecialchars_decode($avatar_full); ?>", "<?php echo htmlspecialchars_decode($avatar_thumb); ?>");
      });
    </script>
  <?php
  }

  // Add default avatar
  function add_default_wp_user_avatar($avatar_list){
    $avatar_default = get_option('avatar_default');
    $avatar_default_wp_user_avatar = get_option('avatar_default_wp_user_avatar');
    $mustache_full = WP_USER_AVATAR_URLPATH.'images/wp-user-avatar.png';
    $mustache_thumb = WP_USER_AVATAR_URLPATH.'images/wp-user-avatar-32x32.png';
    if(!empty($avatar_default_wp_user_avatar)){
      $avatar_full_src = wp_get_attachment_image_src($avatar_default_wp_user_avatar, 'medium');
      $avatar_thumb_src = wp_get_attachment_image_src($avatar_default_wp_user_avatar, array(32,32));
      $avatar_full = $avatar_full_src[0];
      $avatar_thumb = $avatar_thumb_src[0];
      $hide_remove = '';
    } else {
      $avatar_full = $mustache_full;
      $avatar_thumb = $mustache_thumb;
      $hide_remove = ' style="display:none;"';
    }
    $selected_avatar = ($avatar_default == $avatar_full) ? ' checked="checked" ' : '';
    $avatar_thumb_img = '<div id="wp-user-avatar-preview"><img src="'.$avatar_thumb.'" width="32" /></div>';
    $wp_user_avatar_list = "\n\t<label><input type='radio' name='avatar_default' id='wp_user_avatar_radio' value='$avatar_full'$selected_avatar /> ";
    $wp_user_avatar_list .= preg_replace("/src='(.+?)'/", "src='\$1&amp;forcedefault=1'", $avatar_thumb_img);
    $wp_user_avatar_list .= ' WP User Avatar</label>';
    $wp_user_avatar_list .= '<p style="padding-left:15px;"><button type="button" class="button" id="add-wp-user-avatar">Edit WP User Avatar</button>';
    $wp_user_avatar_list .= '&nbsp;&nbsp;&nbsp;&nbsp;<a href="#" id="remove-wp-user-avatar"'.$hide_remove.'>Remove</a></p>';
    $wp_user_avatar_list .= '<input type="hidden" id="wp-user-avatar" name="avatar_default_wp_user_avatar" value="'.$avatar_default_wp_user_avatar.'">';
    $wp_user_avatar_list .= '<p id="wp-user-avatar-message">Press "Save Changes" to save your changes.</p>';
    $wp_user_avatar_list .= edit_default_wp_user_avatar('Default Avatar', $mustache_full, $mustache_thumb);
    return $wp_user_avatar_list.$avatar_list;
  }
  add_filter('default_avatar_select', 'add_default_wp_user_avatar');

  // Add default avatar field to whitelist
  function wp_user_avatar_whitelist_options($whitelist_options){
    $whitelist_options['discussion'] = array( 'default_pingback_flag', 'default_ping_status', 'default_comment_status', 'comments_notify', 'moderation_notify', 'comment_moderation', 'require_name_email', 'comment_whitelist', 'comment_max_links', 'moderation_keys', 'blacklist_keys', 'show_avatars', 'avatar_rating', 'avatar_default', 'close_comments_for_old_posts', 'close_comments_days_old', 'thread_comments', 'thread_comments_depth', 'page_comments', 'comments_per_page', 'default_comments_page', 'comment_order', 'comment_registration', 'avatar_default_wp_user_avatar' );
    return $whitelist_options;
  }
  add_filter('whitelist_options', 'wp_user_avatar_whitelist_options');

  // Returns true if user has wp_user_avatar
  function has_wp_user_avatar($user_id = ''){
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
  function get_wp_user_avatar($id_or_email = '', $size = '96', $align = ''){
    global $post, $comment;
    // Find user ID on comment, author page, or post
    if(is_object($id_or_email)){
      if($comment->user_id != '0'){
        $id_or_email = $comment->user_id;
      } elseif(!empty($comment->comment_author_email)){
        $id_or_email = $comment->comment_author_email;
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
      $wp_user_avatar = '<img src="'.$wp_user_avatar_image[0].'"'.$dimensions.' alt="'.$alt.'" class="wp-user-avatar wp-user-avatar-'.$size.$alignclass.' avatar avatar-'.$size.' photo" />';
    } else {
      $default = '';
      $alt = '';
      if($size == 'original' || $size == 'large'){
        $get_size = get_option('large_size_w');
      } elseif($size == 'medium'){
        $get_size = get_option('medium_size_w');
      } elseif($size == 'thumbnail'){
        $get_size = get_option('thumbnail_size_w');
      } else {
        $get_size = $size;
      }
      $avatar = get_avatar($id_or_email, $get_size, $default, $alt);
      $gravatar = str_replace("class='", "class='wp-user-avatar wp-user-avatar-".$get_size.$alignclass." ", $avatar);
      $wp_user_avatar = $gravatar;
    }
    return $wp_user_avatar;
  }

  // Return just the image src
  function get_wp_user_avatar_src($id_or_email, $size = '', $align = ''){
    $wp_user_avatar_image = get_wp_user_avatar($id_or_email, $size, $align);
    $output = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $wp_user_avatar_image, $matches, PREG_SET_ORDER);
    $wp_user_avatar_image_src = $matches [0] [1];
    return $wp_user_avatar_image_src;
  }

  // Replace get_avatar()
  function get_wp_user_avatar_alt($avatar, $id_or_email, $size = '', $default = '', $alt = false){
    global $post, $pagenow, $comment;
    // Find user ID on comment, author page, or post
    if(is_object($id_or_email)){
      if($comment->user_id != '0'){
        $id_or_email = $comment->user_id;
      } elseif(!empty($comment->comment_author_email)){
        $id_or_email = $comment->comment_author_email;
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
      $alt = $user->display_name;
    }
    $wp_user_avatar_meta = !empty($id_or_email) ? get_the_author_meta('wp_user_avatar', $id_or_email) : '';
    if(!empty($wp_user_avatar_meta) && $pagenow != 'options-discussion.php'){
      $wp_user_avatar_image = wp_get_attachment_image_src($wp_user_avatar_meta, array($size,$size));
      $dimensions = is_numeric($size) ? ' width="'.$wp_user_avatar_image[1].'" height="'.$wp_user_avatar_image[2].'"' : '';
      $wp_user_avatar = '<img src="'.$wp_user_avatar_image[0].'"'.$dimensions.' alt="'.$alt.'" class="wp-user-avatar wp-user-avatar-'.$size.' avatar avatar-'.$size.' photo" />';
    } else {
      $gravatar = str_replace("class='", "class='wp-user-avatar wp-user-avatar-".$size." ", $avatar);
      $wp_user_avatar = $gravatar;
    }
    return $wp_user_avatar;
  }
  add_filter('get_avatar', 'get_wp_user_avatar_alt', 10, 6);

  // Shortcode
  function wp_user_avatar_shortcode($atts, $content){
    // EXAMPLE USAGE:
    // [avatar size="medium"]

    // Set shortcode attributes
    extract(shortcode_atts(array('user' => '', 'size' => '96', 'align' => '', 'link' => ''), $atts));
    $get_user = get_user_by('slug', $user);
    $id_or_email = !empty($get_user) ? $get_user->ID : '';
    if(!empty($link)){
      if($link == 'file'){
        $image_link = get_wp_user_avatar_src($id_or_email, 'original', $align);
        $link_class = $link;
      } elseif($link == 'attachment'){
        $image_link = get_attachment_link(get_the_author_meta('wp_user_avatar', $id_or_email));
        $link_class = $link;
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

  // Get original avatar
  function get_avatar_original($id_or_email, $size = '', $default = '', $alt = false){
    remove_filter('get_avatar', 'get_wp_user_avatar_alt');
    $wp_user_avatar_image = get_avatar($id_or_email);
    $output = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $wp_user_avatar_image, $matches, PREG_SET_ORDER);
    $wp_user_avatar_image_src = $matches [0] [1];
    return $wp_user_avatar_image_src;
  }

  // Initialize wp_user_avatar
  function wp_user_avatar_load(){
    global $wp_user_avatar_instance;
    $wp_user_avatar_instance = new wp_user_avatar();
  }
  add_action('plugins_loaded','wp_user_avatar_load');
}
?>
