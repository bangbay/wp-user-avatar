<?php
/**
 * Defines all profile and upload settings.
 *
 * @package WP User Avatar
 * @version 1.8.10
 */

class WP_User_Avatar {
  public function __construct() {
    global $pagenow, $show_avatars, $wpua_admin, $wpua_allow_upload;
    // Add WPUA to profile
    if($this->wpua_is_author_or_above() || ((bool) $wpua_allow_upload == 1 && is_user_logged_in())) {
      // Profile functions and scripts
      add_action('show_user_profile', array('wp_user_avatar', 'wpua_action_show_user_profile'));
      add_action('edit_user_profile', array($this, 'wpua_action_show_user_profile'));
      add_action('personal_options_update', array($this, 'wpua_action_process_option_update'));
      add_action('edit_user_profile_update', array($this, 'wpua_action_process_option_update'));
      // Admin scripts
      $pages = array('profile.php', 'options-discussion.php', 'user-edit.php');
      if(in_array($pagenow, $pages) || $wpua_admin->wpua_is_menu_page()) {
        add_action('admin_enqueue_scripts', array($this, 'wpua_media_upload_scripts'));
      }
      // Front pages
      if(!is_admin()){
        add_action('show_user_profile', array($this, 'wpua_media_upload_scripts'));
        add_action('edit_user_profile', array($this, 'wpua_media_upload_scripts'));
      }
      if(!$this->wpua_is_author_or_above()) {
        // Upload errors
        add_action('user_profile_update_errors', array($this, 'wpua_upload_errors'), 10, 3);
        // Prefilter upload size
        add_filter('wp_handle_upload_prefilter', array($this, 'wpua_handle_upload_prefilter'));
      }
    }
    add_filter('media_view_settings', array($this, 'wpua_media_view_settings'), 10, 1);
  }

  // Avatars have no parent posts
  public function wpua_media_view_settings($settings) {
    global $post, $wpua_is_profile;
    // Get post ID so not to interfere with media uploads
    $post_id = is_object($post) ? $post->ID : 0;
    // Don't use post ID on front pages if there's a WPUA uploader
    $settings['post']['id'] = (!is_admin() && $wpua_is_profile == 1) ? 0 : $post_id;
    return $settings;
  }

  // Media Uploader
  public static function wpua_media_upload_scripts($user="") {
    global $current_user, $mustache_admin, $pagenow, $post, $show_avatars, $wp_user_avatar, $wpua_admin, $wpua_is_profile, $wpua_upload_size_limit;
    // This is a profile page
    $wpua_is_profile = true;
    $user = ($pagenow == 'user-edit.php' && isset($_GET['user_id'])) ? get_user_by('id', $_GET['user_id']) : $current_user;
    wp_enqueue_script('jquery');
    if($wp_user_avatar->wpua_is_author_or_above()) {
      wp_enqueue_script('admin-bar');
      wp_enqueue_media(array('post' => $post));
      wp_enqueue_script('wp-user-avatar', WPUA_URL.'js/wp-user-avatar.js', array('jquery', 'media-editor'), WPUA_VERSION, true);
    } else {
      wp_enqueue_script('wp-user-avatar', WPUA_URL.'js/wp-user-avatar-user.js', array('jquery'), WPUA_VERSION, true);
    }
    wp_enqueue_style('wp-user-avatar', WPUA_URL.'css/wp-user-avatar.css', array('media-views'), WPUA_VERSION);
    // Admin scripts
    if($pagenow == 'options-discussion.php' || $wpua_admin->wpua_is_menu_page()) {
      // Size limit slider
      wp_enqueue_script('jquery-ui-slider');
      wp_enqueue_style('wp-user-avatar-jqueryui', WPUA_URL.'css/jquery.ui.slider.css', "", null);
      // Remove/edit settings
      $wpua_custom_scripts = array('section' => __('Default Avatar'), 'edit_image' => __('Choose Image'), 'select_image' => __('Select Image'), 'avatar_thumb' => $mustache_admin);
      wp_localize_script('wp-user-avatar', 'wpua_custom', $wpua_custom_scripts);
      // Settings control
      wp_enqueue_script('wp-user-avatar-admin', WPUA_URL.'js/wp-user-avatar-admin.js', array('wp-user-avatar'), WPUA_VERSION, true);
      $wpua_admin_scripts = array('upload_size_limit' => $wpua_upload_size_limit, 'max_upload_size' => wp_max_upload_size());
      wp_localize_script('wp-user-avatar-admin', 'wpua_admin', $wpua_admin_scripts);
    } else {
      // User remove/edit settings
      $avatar_medium_src = (bool) $show_avatars == 1 ? wpua_get_avatar_original($user->user_email, 96) : includes_url().'images/blank.gif';
      $wpua_custom_scripts = array('section' => $user->display_name, 'edit_image' => __('Choose Image'), 'select_image' => __('Select Image'), 'avatar_thumb' => $avatar_medium_src);
      wp_localize_script('wp-user-avatar', 'wpua_custom', $wpua_custom_scripts);
    }
  }

  // Add to edit user profile
  public static function wpua_action_show_user_profile($user) {
    global $blog_id, $current_user, $show_avatars, $wpdb, $wp_user_avatar, $wpua_allow_upload, $wpua_edit_avatar, $wpua_upload_size_limit_with_units;
    // Get WPUA attachment ID
    $wpua = get_user_meta($user->ID, $wpdb->get_blog_prefix($blog_id).'user_avatar', true);
    // Show remove button if WPUA is set
    $hide_remove = !has_wp_user_avatar($user->ID) ? 'wpua-hide' : "";
    // Hide image tags if show avatars is off
    $hide_images = !has_wp_user_avatar($user->ID) && (bool) $show_avatars == 0 ? 'wpua-no-avatars' : "";
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
    <?php if($wp_user_avatar->wpua_is_author_or_above()) : // Button to launch Media Uploader ?>
      <p id="wpua-add-button"><button type="button" class="button" id="wpua-add" name="wpua-add"><?php _e('Choose Image'); ?></button></p>
    <?php elseif(!$wp_user_avatar->wpua_is_author_or_above() && !has_wp_user_avatar($current_user->ID)) : // Upload button ?>
      <p id="wpua-upload-button">
        <input name="wpua-file" id="wpua-file" type="file" />
        <button type="submit" class="button" id="wpua-upload" name="submit" value="<?php _e('Upload'); ?>"><?php _e('Upload'); ?></button>
      </p>
      <p id="wpua-upload-messages">
        <span id="wpua-max-upload"><?php printf(__('Maximum upload file size: %d%s.'), esc_html($wpua_upload_size_limit_with_units), esc_html('KB')); ?></span>
        <span id="wpua-allowed-files"><?php _e('Allowed Files'); ?>: <?php _e('<code>jpg jpeg png gif</code>'); ?></span>
      </p>
    <?php elseif((bool) $wpua_edit_avatar == 1 && !$wp_user_avatar->wpua_is_author_or_above() && has_wp_user_avatar($current_user->ID) && $wp_user_avatar->wpua_author($wpua, $current_user->ID)) : // Edit button ?>
      <p id="wpua-edit-button"><button type="button" class="button" id="wpua-edit" name="wpua-edit" onclick="window.open('<?php echo $edit_attachment_link; ?>', '_self');"><?php _e('Edit Image'); ?></button></p>
    <?php endif; ?>
    <div id="wpua-images" class="<?php echo $hide_images; ?>">
      <p id="wpua-preview">
        <img src="<?php echo $avatar_medium; ?>" alt="" />
        <span class="description"><?php _e('Original Size'); ?></span>
      </p>
      <p id="wpua-thumbnail">
        <img src="<?php echo $avatar_thumbnail; ?>" alt="" />
        <span class="description"><?php _e('Thumbnail'); ?></span>
      </p>
      <p id="wpua-remove-button" class="<?php echo $hide_remove; ?>"><button type="button" class="button" id="wpua-remove" name="wpua-remove"><?php _e('Remove Image'); ?></button></p>
      <p id="wpua-undo-button"><button type="button" class="button" id="wpua-undo" name="wpua-undo"><?php _e('Undo'); ?></button></p>
    </div>
    <?php do_action('wpua_after_avatar'); ?>
  <?php
  }

  // Add upload error messages
  public static function wpua_upload_errors($errors, $update, $user) {
    global $wpua_upload_size_limit;
    if($update && !empty($_FILES['wpua-file'])) {
      $size = $_FILES['wpua-file']['size'];
      $type = $_FILES['wpua-file']['type'];
      $upload_dir = wp_upload_dir();
      // Allow only JPG, GIF, PNG
      if(!empty($type) && !preg_match('/(jpe?g|gif|png)$/i', $type)) {
        $errors->add('wpua_file_type', __('This file is not an image. Please try another.'));
      }
      // Upload size limit
      if(!empty($size) && $size > $wpua_upload_size_limit) {
        $errors->add('wpua_file_size', __('Memory exceeded. Please try another smaller file.'));
      }
      // Check if directory is writeable
      if(!is_writeable($upload_dir['path'])) {
        $errors->add('wpua_file_directory', sprintf(__('Unable to create directory %s. Is its parent directory writable by the server?'), $upload_dir['path']));
      }
    }
  }

  // Set upload size limit 
  public function wpua_handle_upload_prefilter($file) {
    global $wpua_upload_size_limit;
    $size = $file['size'];
    if(!empty($size) && $size > $wpua_upload_size_limit) {
      function wpua_file_size_error($errors, $update, $user) {
        $errors->add('wpua_file_size', __('Memory exceeded. Please try another smaller file.'));
      }
      add_action('user_profile_update_errors', 'wpua_file_size_error', 10, 3);
      return;
    }
    return $file;
  }

  // Update user meta
  public static function wpua_action_process_option_update($user_id) {
    global $blog_id, $post, $wpdb, $wp_user_avatar, $wpua_resize_crop, $wpua_resize_h, $wpua_resize_upload, $wpua_resize_w;
    // Check if user has publish_posts capability
    if($wp_user_avatar->wpua_is_author_or_above()) {
      $wpua_id = isset($_POST['wp-user-avatar']) ? intval($_POST['wp-user-avatar']) : "";
      // Remove old attachment postmeta
      delete_metadata('post', null, '_wp_attachment_wp_user_avatar', $user_id, true);
      // Create new attachment postmeta
      add_post_meta($wpua_id, '_wp_attachment_wp_user_avatar', $user_id);
      // Update usermeta
      update_user_meta($user_id, $wpdb->get_blog_prefix($blog_id).'user_avatar', $wpua_id);
    } else {
      // Remove attachment info if avatar is blank
      if(isset($_POST['wp-user-avatar']) && empty($_POST['wp-user-avatar'])) {
        // Uploads by user
        $q = array(
          'author' => $user_id,
          'post_type' => 'attachment',
          'post_status' => 'inherit',
          'posts_per_page' => '-1',
          'meta_query' => array(
            array(
              'key' => '_wp_attachment_wp_user_avatar',
              'value' => '',
              'compare' => '!='
            )
          )
        );
        $avatars_wp_query = new WP_Query($q);
        while($avatars_wp_query->have_posts()) : $avatars_wp_query->the_post();
          wp_delete_attachment($post->ID);
        endwhile;
        wp_reset_query();
        // Remove attachment postmeta
        delete_metadata('post', null, '_wp_attachment_wp_user_avatar', $user_id, true);
        // Remove usermeta
        update_user_meta($user_id, $wpdb->get_blog_prefix($blog_id).'user_avatar', "");
      }
      // Create attachment from upload
      if(isset($_POST['submit']) && $_POST['submit'] && !empty($_FILES['wpua-file'])) {
        $name = $_FILES['wpua-file']['name'];
        $file = wp_handle_upload($_FILES['wpua-file'], array('test_form' => false));
        $type = $_FILES['wpua-file']['type'];
        $upload_dir = wp_upload_dir();
        if(is_writeable($upload_dir['path'])) {
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
              // Remove old attachment postmeta
              delete_metadata('post', null, '_wp_attachment_wp_user_avatar', $user_id, true);
              // Create new attachment postmeta
              update_post_meta($attachment_id, '_wp_attachment_wp_user_avatar', $user_id);
              // Update usermeta
              update_user_meta($user_id, $wpdb->get_blog_prefix($blog_id).'user_avatar', $attachment_id);
            }
          }
        }
      }
    }
  }

  // Check who owns image
  private function wpua_author($attachment_id, $user_id, $wpua_author=false) {
    $attachment = get_post($attachment_id);
    if(!empty($attachment) && $attachment->post_author == $user_id) {
      $wpua_author = true;
    }
    return $wpua_author;
  }

  // Check if current user has at least Author privileges
  public function wpua_is_author_or_above() {
    $is_author_or_above = (current_user_can('edit_published_posts') && current_user_can('upload_files') && current_user_can('publish_posts') && current_user_can('delete_published_posts')) ? true : false;
    return $is_author_or_above;
  }
}

// Initialize WP_User_Avatar
function wpua_init() {
  global $wp_user_avatar;
  $wp_user_avatar = new WP_User_Avatar();
}
add_action('init', 'wpua_init');
