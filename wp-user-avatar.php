<?php
/**
 * @package WP User Avatar
 * @version 1.1.5a
 */
/*
Plugin Name: WP User Avatar
Plugin URI: http://wordpress.org/extend/plugins/wp-user-avatar/
Description: Use any image in your WordPress Media Libary as a custom user avatar.
Version: 1.1.5a
Author: Bangbay Siboliban
Author URI: http://siboliban.org/
*/

// Define paths and variables
define('WP_USER_AVATAR_FOLDER', basename(dirname(__FILE__)));
define('WP_USER_AVATAR_ABSPATH', trailingslashit(str_replace('\\','/', WP_PLUGIN_DIR.'/'.WP_USER_AVATAR_FOLDER)));
define('WP_USER_AVATAR_URLPATH', trailingslashit(plugins_url(WP_USER_AVATAR_FOLDER)));

// Remove user metadata on plugin delete
register_uninstall_hook(__FILE__, 'wp_user_avatar_delete_setup');

// Remove user metadata
function wp_user_avatar_delete_setup(){
  $users = get_users();
  foreach($users as $user){
    delete_user_meta($user->ID, 'wp_user_avatar');
  }
}

// WP User Avatar
if(!class_exists('wp_user_avatar')){
  class wp_user_avatar{
    function wp_user_avatar(){
      add_action('show_user_profile', array('wp_user_avatar','action_show_user_profile'));
      add_action('edit_user_profile', array($this,'action_show_user_profile'));
      add_action('personal_options_update', array($this,'action_process_option_update'));
      add_action('edit_user_profile_update', array($this,'action_process_option_update'));
      if(!function_exists('wp_enqueue_media')){
        add_filter('attachment_fields_to_edit', array($this, 'add_wp_user_avatar_attachment_field_to_edit'), 10, 2); 
      }
      if(get_option('show_avatars') != '1'){
        add_filter('manage_users_columns', array($this, 'add_wp_user_avatar_column'), 10, 1);
        add_filter('manage_users_custom_column', array($this, 'show_wp_user_avatar_column'), 10, 3);
      }
      add_action('admin_enqueue_scripts', array($this, 'media_upload_scripts'));
    }

    // Add to user profile edit
    function action_show_user_profile($user){
      $hide = !has_wp_user_avatar($user->ID) ? ' style="display:none;"' : '';
    ?>
    <h3><?php _e('WP User Avatar') ?></h3>
    <table class="form-table">
      <tbody>
        <tr>
          <th><label for="wp_user_avatar"><?php _e('WP User Avatar'); ?></label></th>
          <td>
            <input type="hidden" name="wp-user-avatar" id="wp-user-avatar" value="<?php echo $wp_user_avatar ?>" />
            <p><button type="button" class="button" id="add-wp-user-avatar"><?php _e('Edit WP User Avatar'); ?></button></p>
            <div id="wp-user-avatar-preview">
              <p>
                <?php
                  if(has_wp_user_avatar($user->ID)){
                    echo get_wp_user_avatar($user->ID, 'medium');
                  } else {
                    if(get_option('show_avatars') == '1'){
                      echo get_avatar($user->ID, 96);
                    } else {
                      echo '<img src="'.includes_url().'images/blank.gif" alt="" />';
                    }
                  }
                ?>
              </p>
            </div>
            <p><button type="button" class="button" id="remove-wp-user-avatar"<?php echo $hide; ?>><?php _e('Remove'); ?></button></p>
            <p id="wp-user-avatar-message"><?php _e('Press "Update Profile" to save your changes.'); ?></p>
          </td>
        </tr>
      </tbody>
    </table>
    <script type="text/javascript">
      jQuery(function($){
        <?php if(function_exists('wp_enqueue_media')) : // Use Backbone uploader for WP 3.5+ ?>
          wp.media.wpUserAvatar = {
            get: function() {
              return wp.media.view.settings.post.wpUserAvatarId;
            },
            set: function(id) {
              var settings = wp.media.view.settings;
              settings.post.wpUserAvatarId = id;
              settings.post.wpUserAvatarSrc = $('div.attachment-info').find('img').attr('src');
              if(settings.post.wpUserAvatarId)
                setWPUserAvatar(settings.post.wpUserAvatarId, settings.post.wpUserAvatarSrc);
            },
            frame: function(){
              if(this._frame)
                return this._frame;
              this._frame = wp.media({
                state: 'library',
                states: [ new wp.media.controller.Library({ title: "Edit WP User Avatar: <?php echo $user->display_name; ?>" }) ]
              });
              this._frame.on('open', function(){
                var selection = this.state().get('selection');
                id = jQuery('#wp-user-avatar').val();
                attachment = wp.media.attachment(id);
                attachment.fetch();
                selection.add(attachment ? [ attachment ] : []);
              }, this._frame);
              this._frame.on('toolbar:create:select', function(toolbar){
                this.createSelectToolbar(toolbar, {
                  text: 'Set WP User Avatar'
                });
              }, this._frame);
              this._frame.state('library').on('select', this.select);
              return this._frame;
            },
            select: function(id) {
              var settings = wp.media.view.settings,
                selection = this.get('selection').single();
              wp.media.wpUserAvatar.set(selection ? selection.id : -1);
            },
            init: function() {
              $('body').on('click', '#add-wp-user-avatar', function(e){
                e.preventDefault();
                e.stopPropagation();
                wp.media.wpUserAvatar.frame().open();
              });
            }
          };
          $(wp.media.wpUserAvatar.init);
        <?php else : // Fall back to Thickbox uploader ?>
          $('#add-wp-user-avatar').click(function(e){
            e.preventDefault();
            tb_show('Edit WP User Avatar: <?php echo $user->display_name; ?>', 'media-upload.php?type=image&post_type=user&tab=library&TB_iframe=1');
          });
        <?php endif; ?>
      });
      jQuery(function($){
        $('#remove-wp-user-avatar').click(function(e){
          var gravatar = '<img src="<?php echo includes_url().'images/blank.gif'; ?>" alt="" />';
          e.preventDefault();
          $(this).hide();
          $('#wp-user-avatar-preview').find('img').replaceWith(gravatar);
          $('#wp-user-avatar').val('');
          $('#wp-user-avatar-message').show();
        });
      });
    </script>
    <?php
    }
    // Update user meta
    function action_process_option_update($user_id){
      update_usermeta($user_id, 'wp_user_avatar', (isset($_POST['wp-user-avatar']) ? $_POST['wp-user-avatar'] : ''));
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
      wp_enqueue_script('media-upload');
      wp_enqueue_script('thickbox');
      if(function_exists('wp_enqueue_media')){
        wp_enqueue_media();
      }
      wp_enqueue_script('wp-user-avatar', WP_USER_AVATAR_URLPATH.'js/wp-user-avatar.js');
      wp_enqueue_style('thickbox');
      wp_enqueue_style('wp-user-avatar', WP_USER_AVATAR_URLPATH.'css/wp-user-avatar.css');
    }
  }
  // Initialize wp_user_avatar
  global $wp_user_avatar_instance;
  $wp_user_avatar_instance = new wp_user_avatar();
}

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
    $id_or_email = $comment->user_id != '0' ? $comment->user_id : $comment->comment_author_email;
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
    $id_or_email = !empty($user) ? $user->ID: '';
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
    $id_or_email = $comment->user_id != '0' ? $comment->user_id : $comment->comment_author_email;
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
  extract(shortcode_atts(array('user' => '', 'size' => '96', 'align' => ''), $atts));
  $get_user = get_user_by('slug', $user);
  $id_or_email = !empty($get_user) ? $get_user->ID : '';
  $wp_user_avatar = get_wp_user_avatar($id_or_email, $size, $align);
  return $wp_user_avatar;
}
add_shortcode('avatar','wp_user_avatar_shortcode');

?>
