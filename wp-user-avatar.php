<?php
/**
 * @package WP User Avatar
 * @version 1.0
 */
/*
Plugin Name: WP User Avatar
Plugin URI: http://wordpress.org/extend/plugins/wp-user-avatar/
Description: Use any image in your WordPress Media Libary as a custom user avatar.
Version: 1.0
Author: Bangbay Siboliban
Author URI: http://siboliban.org/
*/

// Define paths and variables
define('WP_USER_AVATAR_FOLDER', basename(dirname(__FILE__)));
define('WP_USER_AVATAR_ABSPATH', trailingslashit(str_replace("\\","/", WP_PLUGIN_DIR.'/'.WP_USER_AVATAR_FOLDER)));
define('WP_USER_AVATAR_URLPATH', trailingslashit(plugins_url(WP_USER_AVATAR_FOLDER)));

// Initialize default settings
register_activation_hook(__FILE__, 'wp_user_avatar_options');

// Remove settings and metadata on plugin delete
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
      add_action('show_user_profile', array(&$this,'action_show_user_profile'));
      add_action('edit_user_profile', array(&$this,'action_show_user_profile'));
      add_action('personal_options_update', array(&$this,'action_process_option_update'));
      add_action('edit_user_profile_update', array(&$this,'action_process_option_update'));
      add_filter('attachment_fields_to_edit', array(&$this, 'add_wp_user_avatar_attachment_field_to_edit'), 10, 2); 
    }

    // Add to user profile edit
    function action_show_user_profile($user){
      $wp_user_avatar = get_usermeta($user->ID, 'wp_user_avatar', true);
      $hide = empty($wp_user_avatar) ? ' style="display:none;"' : '';
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
                  if(!empty($wp_user_avatar)){
                    echo get_wp_user_avatar($user->ID, 96);
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
            <p id="wp-user-avatar-preview-message"><?php _e('Press "Update Profile" to save your changes.'); ?></p>
          </td>
        </tr>
      </tbody>
    </table>
    <script type="text/javascript">
      jQuery(function($){
        $('#add-wp-user-avatar').click(function(e){
          e.preventDefault();
          tb_show('Edit <?php echo $user->display_name; ?>', 'media-upload.php?type=image&post_type=user&tab=library&TB_iframe=1');
        });
        $('#remove-wp-user-avatar').click(function(e){
          var gravatar = "<?php echo get_avatar($user->ID); ?>";
          if(gravatar == ''){
            gravatar = '<img src="<?php echo includes_url().'images/blank.gif'; ?>" alt="" />';
          }
          e.preventDefault();
          $(this).hide();
          $('#wp-user-avatar-preview').find('img').replaceWith(gravatar);
          $('#wp-user-avatar').val('');
          $('#wp-user-avatar-preview-message').show();
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
      $image = wp_get_attachment_image_src($post->ID, array(96,96));
      $button .= $pagenow.'<button type="button" class="button" id="set-wp-user-avatar-image" onclick="setWPUserAvatar(\''.$post->ID.'\', \''.$image[0].'\')">Set WP User Avatar</button>';
      $button .= "<script type='text/javascript'>
        function setWPUserAvatar(attachment, imageURL){
          jQuery('#wp-user-avatar', window.parent.document).val(attachment);
          jQuery('#wp-user-avatar-preview', window.parent.document).find('img').attr('src', imageURL).attr('width', '96').removeAttr('height', '');
          jQuery('#wp-user-avatar-preview-message', window.parent.document).show();
          jQuery('#remove-wp-user-avatar', window.parent.document).show();
          window.parent.tb_remove();
        }
      </script>";
      $fields['wp-user-avatar'] = array(
        'label' => __('WP User Avatar'),
        'input' => 'html',
        'html' => $button
      );
      return $fields;
    }
  }

  // Initialize wp_user_avatar
  global $wp_user_avatar_instance;
  $wp_user_avatar_instance = new wp_user_avatar();

  // Add column to Users page
  function add_wp_user_avatar_column($columns){
    $columns['wp-user-avatar'] = 'WP User Avatar';
    return $columns;
  }
  add_filter('manage_users_columns', 'add_wp_user_avatar_column');

  // Show thumbnail of wp_user_avatar
  function show_wp_user_avatar_column_content($value, $column_name, $user_id){
    $wp_user_avatar = get_usermeta($user_id, 'wp_user_avatar', true);
    $wp_user_avatar_image = wp_get_attachment_image($wp_user_avatar, array(32,32));
    if('wp-user-avatar' == $column_name){
      return $wp_user_avatar_image;
    }
  }
  add_action('manage_users_custom_column', 'show_wp_user_avatar_column_content', 10, 3);
}

// Find wp_user_avatar, show get_avatar if empty
function get_wp_user_avatar($id_or_email = '', $size = '96', $default = '', $alt = ''){
  global $post;
  $author_name = get_query_var('author_name');
  // Find author ID on author page or post
  if(!empty($id_or_email)){
    $user = is_numeric($id_or_email) ? get_user_by('id', $id_or_email) : get_user_by('email', $id_or_email);
  } else {
    $user = is_author() ? get_user_by('slug', $author_name) : get_the_author_meta('id');
  }
  $id_or_email = $user->ID;
  $alt = $user->display_name;
  $wp_user_avatar_meta = get_the_author_meta('wp_user_avatar', $id_or_email);
  if(!empty($wp_user_avatar_meta)){
    $get_size = is_numeric($size) ? array($size,$size) : $size;
    $wp_user_avatar_image = wp_get_attachment_image_src($wp_user_avatar_meta, $get_size);
    $dimensions = is_numeric($size) ? ' width="'.$wp_user_avatar_image[1].'" height="'.$wp_user_avatar_image[2].'"' : '';
    $wp_user_avatar = '<img src="'.$wp_user_avatar_image[0].'"'.$dimensions.' alt="'.$alt.'" />';
  } else {
    $wp_user_avatar = get_avatar($id_or_email, $size, $default, $alt);
  }
  return $wp_user_avatar;
}

// Media uploader
function media_upload_js(){
  wp_enqueue_script('media-upload');
  wp_enqueue_script('thickbox');
}

function media_upload_css(){
  wp_enqueue_style('thickbox');
  wp_enqueue_style('mulubox', WP_USER_AVATAR_URLPATH.'css/wp-user-avatar.css');
}

add_action('admin_print_scripts', 'media_upload_js');
add_action('admin_print_styles', 'media_upload_css');

?>
