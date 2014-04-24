<?php
/**
 * Defines widgets.
 *
 * @package WP User Avatar
 * @version 1.9.5
 */

class WP_User_Avatar_Profile_Widget extends WP_Widget {
  /**
   * Constructor
   */
  public function __construct() {
    $widget_ops = array('classname' => 'widget_wp_user_avatar', 'description' => __('Insert').' '.__('[avatar_upload]', 'wp-user-avatar').'.');
    parent::__construct('wp_user_avatar_profile', __('WP User Avatar', 'wp-user-avatar'), $widget_ops);
  }

  /**
   * Add [avatar_upload] to widget
   * @since 1.9.4
   * @param array $args
   * @param array $instance
   * @uses object $wpua_shortcode
   * @uses add_filter()
   * @uses apply_filters()
   * @uses is_user_logged_in()
   * @uses remove_filter()
   * @uses wpua_edit_shortcode()
   */
  public function widget($args, $instance) {
    global $wpua_shortcode;
    extract($args);
    $instance = apply_filters('wpua_widget_instance', $instance);
    $title = apply_filters('widget_title', empty($instance['title']) ? "" : $instance['title'], $instance, $this->id_base);
    // Show widget only for logged-in users
    if(is_user_logged_in()) {  
      echo $before_widget;
      if($title){
        echo $before_title.$title.$after_title;
      }
      // Remove profile title
      add_filter('wpua_profile_title', '__return_null');
      // Get [avatar_upload] shortcode
      echo $wpua_shortcode->wpua_edit_shortcode("");
      remove_filter('wpua_profile_title', '__return_null');
    }
  }

  /**
   * Set title
   * @param array $instance
   * @uses wp_parse_args()
   */
  public function form($instance) {
    $instance = wp_parse_args((array) $instance, array('title' => ""));
    $title = isset($instance['title']) ? esc_attr($instance['title']) : "";
  ?>
    <p>
      <label for="<?php echo $this->get_field_id('title'); ?>">
        <?php _e('Title:'); ?>
        <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
      </label>
    </p>
  <?php
  }

  /**
   * Update widget
   * @param array $new_instance
   * @param array $old_instance
   * @uses wp_parse_args()
   * @return array
   */
  public function update($new_instance, $old_instance) {
    $instance = $old_instance;
    $new_instance = wp_parse_args((array) $new_instance, array('title' => ""));
    $instance['title'] = strip_tags($new_instance['title']);
    return $instance;
  }
}
