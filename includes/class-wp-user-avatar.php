<?php
/**
 * Defines all profile and upload settings.
 *
 * @package WP User Avatar
 * @version 1.8.2
 */

class WP_User_Avatar {
  public function __construct() {
    global $current_user, $pagenow, $show_avatars, $wpua_allow_upload;
    // Add WPUA to profile
    if(current_user_can('upload_files') || ((bool) $wpua_allow_upload == 1 && is_user_logged_in())) {
      // Profile functions and scripts
      add_action('show_user_profile', array('wp_user_avatar', 'wpua_action_show_user_profile'));
      add_action('edit_user_profile', array($this, 'wpua_action_show_user_profile'));
      add_action('personal_options_update', array($this, 'wpua_action_process_option_update'));
      add_action('edit_user_profile_update', array($this, 'wpua_action_process_option_update'));
      if(!is_admin()) {
        add_action('show_user_profile', array($this, 'wpua_media_upload_scripts'));
        add_action('show_user_profile', array($this, 'wpua_media_upload_scripts'));
      }
      // Admin scripts
      if($pagenow == 'profile.php' || $pagenow == 'user-edit.php' || $pagenow == 'options-discussion.php' || ($pagenow == 'admin.php' && isset($_GET['page']) && $_GET['page'] == 'wp-user-avatar')) {
        add_action('admin_enqueue_scripts', array($this, 'wpua_media_upload_scripts'));
      }
      if(!current_user_can('upload_files')) {
        // Upload errors
        add_action('user_profile_update_errors', array($this, 'wpua_upload_errors'), 10, 3);
        // Prefilter upload size
        add_filter('wp_handle_upload_prefilter', array($this, 'wpua_handle_upload_prefilter'));
      }
    }
  }

  // Media Uploader
  public static function wpua_media_upload_scripts($user="") {
    global $current_user, $mustache_admin, $pagenow, $post, $show_avatars, $wpua_upload_size_limit;
    $user = ($pagenow == 'user-edit.php' && isset($_GET['user_id'])) ? get_user_by('id', $_GET['user_id']) : $current_user;
    wp_enqueue_script('jquery');
    if(current_user_can('upload_files')) {
      wp_enqueue_script('admin-bar');
      wp_enqueue_media(array('post' => $post));
      wp_enqueue_script('wp-user-avatar', WPUA_URL.'js/wp-user-avatar.js', array('jquery', 'media-editor'), WPUA_VERSION, true);
    } else {
      wp_enqueue_script('wp-user-avatar', WPUA_URL.'js/wp-user-avatar-user.js', array('jquery'), WPUA_VERSION, true);
    }
    wp_enqueue_style('wp-user-avatar', WPUA_URL.'css/wp-user-avatar.css', array('media-views'), WPUA_VERSION);
    // Admin scripts
    if($pagenow == 'options-discussion.php' || ($pagenow == 'admin.php' && isset($_GET['page']) && $_GET['page'] == 'wp-user-avatar')) {
      // Size limit slider
      wp_enqueue_script('jquery-ui-slider');
      wp_enqueue_style('wp-user-avatar-jqueryui', WPUA_URL.'css/jquery.ui.slider.css', "", null);
      // Remove/edit settings
      $wpua_custom_scripts = array('section' => __('Default Avatar'), 'edit_image' => __('Edit Image'), 'select_image' => __('Select Image'), 'avatar_thumb' => $mustache_admin);
      wp_localize_script('wp-user-avatar', 'wpua_custom', $wpua_custom_scripts);
      // Settings control
      wp_enqueue_script('wp-user-avatar-admin', WPUA_URL.'js/wp-user-avatar-admin.js', array('wp-user-avatar'), WPUA_VERSION, true);
      $wpua_admin_scripts = array('upload_size_limit' => $wpua_upload_size_limit, 'max_upload_size' => wp_max_upload_size());
      wp_localize_script('wp-user-avatar-admin', 'wpua_admin', $wpua_admin_scripts);
    } else {
      // User remove/edit settings
      $avatar_medium_src = (bool) $show_avatars == 1 ? wpua_get_avatar_original($user->user_email, 96) : includes_url().'images/blank.gif';
      $wpua_custom_scripts = array('section' => $user->display_name, 'edit_image' => __('Edit Image'), 'select_image' => __('Select Image'), 'avatar_thumb' => $avatar_medium_src);
      wp_localize_script('wp-user-avatar', 'wpua_custom', $wpua_custom_scripts);
    }
  }

  // Add to edit user profile
  public static function wpua_action_show_user_profile($user) {
    global $blog_id, $current_user, $post, $show_avatars, $wpdb, $wp_user_avatar, $wpua_allow_upload, $wpua_edit_avatar, $wpua_upload_size_limit_with_units;
    // Get WPUA attachment ID
    $wpua = get_user_meta($user->ID, $wpdb->get_blog_prefix($blog_id).'user_avatar', true);
    // Show remove button if WPUA is set
    $hide_remove = !has_wp_user_avatar($user->ID) ? 'wpua-hide' : "";
    // If avatars are enabled, get original avatar image or show blank
    $avatar_medium_src = (bool) $show_avatars == 1 ? wpua_get_avatar_original($user->user_email, 96) : includes_url().'images/blank.gif';
    // Check if user has wp_user_avatar, if not show image from above
    $avatar_medium = has_wp_user_avatar($user->ID) ? get_wp_user_avatar_src($user->ID, 'medium') : $avatar_medium_src;
    // Check if user has wp_user_avatar, if not show image from above
    $avatar_thumbnail = has_wp_user_avatar($user->ID) ? get_wp_user_avatar_src($user->ID, 96) : $avatar_medium_src;
    $edit_attachment_link = add_query_arg(array('post' => $wpua, 'action' => 'edit'), admin_url('post.php'));
  ?>
    <?php do_action('wpua_before_avatar'); ?>
    <input type="hidden" name="wp-user-avatar" id="wp-user-avatar" value="<?php echo $wpua; ?>" />
    <?php if(current_user_can('upload_files')) : // Button to launch Media Uploader ?>
      <p id="wpua-add-button"><button type="button" class="button" id="wpua-add" name="wpua-add"><?php _e('Choose Image'); ?></button></p>
    <?php elseif(!current_user_can('upload_files') && !has_wp_user_avatar($current_user->ID)) : // Upload button ?>
      <p id="wpua-upload-button">
        <input name="wpua-file" id="wpua-file" type="file" />
        <button type="submit" class="button" id="wpua-upload" name="submit" value="<?php _e('Upload'); ?>"><?php _e('Upload'); ?></button>
      </p>
      <p id="wpua-upload-messages">
        <span id="wpua-max-upload"><?php printf(__('Maximum upload file size: %d%s.'), esc_html($wpua_upload_size_limit_with_units), esc_html('KB')); ?></span>
        <span id="wpua-allowed-files"><?php _e('Allowed Files'); ?>: <?php _e('<code>jpg jpeg png gif</code>'); ?></span>
      </p>
    <?php elseif((bool) $wpua_edit_avatar == 1 && !current_user_can('upload_files') && has_wp_user_avatar($current_user->ID) && $wp_user_avatar->wpua_author($wpua, $current_user->ID)) : // Edit button ?>
      <p id="wpua-edit-button"><button type="button" class="button" id="wpua-edit" name="wpua-edit" onclick="window.open('<?php echo $edit_attachment_link; ?>', '_self');"><?php _e('Edit Image'); ?></button></p>
    <?php endif; ?>
    <p id="wpua-preview">
      <img src="<?php echo $avatar_medium; ?>" alt="" />
      <?php _e('Original Size'); ?>
    </p>
    <p id="wpua-thumbnail">
      <img src="<?php echo $avatar_thumbnail; ?>" alt="" />
      <?php _e('Thumbnail'); ?>
    </p>
    <p id="wpua-remove-button" class="<?php echo $hide_remove; ?>"><button type="button" class="button" id="wpua-remove" name="wpua-remove"><?php _e('Remove Image'); ?></button></p>
    <p id="wpua-undo-button"><button type="button" class="button" id="wpua-undo" name="wpua-undo"><?php _e('Undo'); ?></button></p>
    <?php do_action('wpua_after_avatar'); ?>
  <?php
  }

  // Add upload error messages
  public static function wpua_upload_errors($errors, $update, $user) {
    global $wpua_upload_size_limit;
    if($update && !empty($_FILES['wpua-file'])) {
      $size = $_FILES['wpua-file']['size'];
      $type = $_FILES['wpua-file']['type'];
      // Allow only JPG, GIF, PNG
      if(!empty($type) && !preg_match('/(jpe?g|gif|png)$/i', $type)) {
        $errors->add('wpua_file_type', __('This file is not an image. Please try another.'));
      }
      // Upload size limit
      if(!empty($size) && $size > $wpua_upload_size_limit) {
        $errors->add('wpua_file_size', __('Memory exceeded. Please try another smaller file.'));
      }
    }
  }

  // Set upload size limit for users without upload_files capability
  public function wpua_handle_upload_prefilter($file) {
    global $wpua_upload_size_limit;
    $size = $file['size'];
    if(!empty($size) && $size > $wpua_upload_size_limit) {
      function wpua_file_size_error($errors, $update, $user) {
        $errors->add('wpua_file_size', __('Memory exceeded. Please try another smaller file.'));
      }
      add_action('user_profile_update_errors', 'wpua_file_size_error', 10, 3);
      return null;
    }
    return $file;
  }

  // Update user meta
  public static function wpua_action_process_option_update($user_id) {
    global $blog_id, $wpdb, $wp_user_avatar, $wpua_resize_crop, $wpua_resize_h, $wpua_resize_upload, $wpua_resize_w;
    // Check if user has upload_files capability
    if(current_user_can('upload_files')) {
      $wpua_id = isset($_POST['wp-user-avatar']) ? intval($_POST['wp-user-avatar']) : "";
      $wpdb->query($wpdb->prepare("DELETE FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %d", '_wp_attachment_wp_user_avatar', $user_id));
      add_post_meta($wpua_id, '_wp_attachment_wp_user_avatar', $user_id);
      update_user_meta($user_id, $wpdb->get_blog_prefix($blog_id).'user_avatar', $wpua_id);
    } else {
      // Remove attachment info if avatar is blank
      if(isset($_POST['wp-user-avatar']) && empty($_POST['wp-user-avatar'])) {
        // Uploads by user
        $attachments = $wpdb->get_results($wpdb->prepare("SELECT $wpdb->posts.ID FROM $wpdb->posts, $wpdb->postmeta WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id AND $wpdb->posts.post_author = %d AND $wpdb->posts.post_type = %s AND $wpdb->postmeta.meta_key = %s AND $wpdb->postmeta.meta_value = $wpdb->posts.post_author", $user_id, 'attachment', '_wp_attachment_wp_user_avatar'));
        foreach($attachments as $attachment) {
          // Delete attachment if not used by another user
          if(!$wp_user_avatar->wpua_image($attachment->ID, $user_id)) {
            wp_delete_attachment($attachment->ID);
          }
        }
        update_user_meta($user_id, $wpdb->get_blog_prefix($blog_id).'user_avatar', "");
      }
      // Create attachment from upload
      if(isset($_POST['submit']) && $_POST['submit'] && !empty($_FILES['wpua-file'])) {
        $name = $_FILES['wpua-file']['name'];
        $file = wp_handle_upload($_FILES['wpua-file'], array('test_form' => false));
        $type = $_FILES['wpua-file']['type'];
        if(!empty($type) && preg_match('/(jpe?g|gif|png)$/i', $type)) {
          // Resize uploaded image
          if((bool) $wpua_resize_upload == 1) {
            // Original image
            $uploaded_image = wp_get_image_editor($file['file']);
            // Check for errors
            if(!is_wp_error($uploaded_image)) {
              // Resize image
              $uploaded_image->resize($wpua_resize_w, $wpua_resize_h, $wpua_resize_crop);
              // Save image
              $resized_image = $uploaded_image->save($file['file']);
            }
          }
          // Break out file info
          $name_parts = pathinfo($name);
          $name = trim(substr($name, 0, -(1 + strlen($name_parts['extension']))));
          $url = $file['url'];
          $file = $file['file'];
          $title = $name;
          // Use image exif/iptc data for title if possible
          if($image_meta = @wp_read_image_metadata($file)) {
            if(trim($image_meta['title']) && !is_numeric(sanitize_title($image_meta['title']))) {
              $title = $image_meta['title'];
            }
          }
          // Construct the attachment array
          $attachment = array(
            'guid'           => $url,
            'post_mime_type' => $type,
            'post_title'     => $title,
            'post_content'   => ""
          );
          // This should never be set as it would then overwrite an existing attachment
          if(isset($attachment['ID'])) {
            unset($attachment['ID']);
          }
          // Save the attachment metadata
          $attachment_id = wp_insert_attachment($attachment, $file);
          if(!is_wp_error($attachment_id)) {
            wp_update_attachment_metadata($attachment_id, wp_generate_attachment_metadata($attachment_id, $file));
            $wpdb->query($wpdb->prepare("DELETE FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %d", '_wp_attachment_wp_user_avatar', $user_id));
            add_post_meta($attachment_id, '_wp_attachment_wp_user_avatar', $user_id);
            update_user_meta($user_id, $wpdb->get_blog_prefix($blog_id).'user_avatar', $attachment_id);
          }
        }
      }
    }
  }

  // Check if image is used as WPUA
  private function wpua_image($attachment_id, $user_id, $wpua_image=false) {
    global $wpdb;
    $wpua = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->postmeta WHERE post_id = %d AND meta_key = %s AND meta_value != %d", $attachment_id, '_wp_attachment_wp_user_avatar', $user_id));
    if(!empty($wpua)) {
      $wpua_image = true;
    }
    return $wpua_image;
  }

  // Check who owns image
  private function wpua_author($attachment_id, $user_id, $wpua_author=false) {
    $attachment = get_post($attachment_id);
    if(!empty($attachment) && $attachment->post_author == $user_id) {
      $wpua_author = true;
    }
    return $wpua_author;
  }
}

// Initialize WP_User_Avatar
function wpua_init() {
  global $wp_user_avatar;
  $wp_user_avatar = new WP_User_Avatar();
}
add_action('init', 'wpua_init');
