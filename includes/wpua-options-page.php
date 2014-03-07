<?php
/**
 * Admin page to change plugin options.
 *
 * @package WP User Avatar
 * @version 1.8.8
 */

global $show_avatars, $upload_size_limit_with_units, $wpua_allow_upload, $wpua_disable_gravatar, $wpua_edit_avatar, $wpua_resize_crop, $wpua_resize_h, $wpua_resize_upload, $wpua_resize_w, $wpua_subscriber, $wpua_tinymce, $wpua_upload_size_limit, $wpua_upload_size_limit_with_units;
$updated = false;
if(isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true') {
  $updated = true;
}
$hide_size = (bool) $wpua_allow_upload != 1 ? ' style="display:none;"' : "";
$hide_resize = (bool) $wpua_resize_upload != 1 ? ' style="display:none;"' : "";
?>

<?php if($updated) : ?>
  <div id="message" class="updated"><p><strong><?php _e( 'Settings saved.' ); ?></strong></p></div>
<?php endif; ?>

<div class="wrap">
  <h2><?php _e('WP User Avatar', 'wp-user-avatar'); ?></h2>
  <form method="post" action="<?php echo admin_url('options.php'); ?>">
    <?php settings_fields('wpua-settings-group'); ?>
    <?php do_settings_fields('wpua-settings-group', ""); ?>
    <?php do_action('wpua_donation_message'); ?>
    <table class="form-table">
      <tr valign="top">
        <th scope="row"><?php _e('Settings'); ?></th>
        <td>
          <fieldset>
            <legend class="screen-reader-text"><span><?php _e('Settings'); ?></span></legend>
            <label for="wp_user_avatar_tinymce">
              <input name="wp_user_avatar_tinymce" type="checkbox" id="wp_user_avatar_tinymce" value="1" <?php checked($wpua_tinymce, 1); ?> />
              <?php _e('Add avatar button to Visual Editor', 'wp-user-avatar'); ?>
            </label>
          </fieldset>
          <fieldset>
            <label for="wp_user_avatar_allow_upload">
              <input name="wp_user_avatar_allow_upload" type="checkbox" id="wp_user_avatar_allow_upload" value="1" <?php checked($wpua_allow_upload, 1); ?> />
              <?php _e('Allow Contributors & Subscribers to upload avatars', 'wp-user-avatar'); ?>
            </label>
          </fieldset>
          <fieldset>
            <label for="wp_user_avatar_disable_gravatar">
              <input name="wp_user_avatar_disable_gravatar" type="checkbox" id="wp_user_avatar_disable_gravatar" value="1" <?php checked($wpua_disable_gravatar, 1); ?> />
              <?php _e('Disable Gravatar and use only local avatars', 'wp-user-avatar'); ?>
            </label>
          </fieldset>
        </td>
      </tr>
    </table>
    <div id="wpua-contributors-subscribers"<?php echo $hide_size; ?>>
      <table class="form-table">
        <tr valign="top">
          <th scope="row">
            <label for="wp_user_avatar_upload_size_limit">
              <?php _e('Upload Size Limit', 'wp-user-avatar'); ?> <?php _e('(only for Contributors & Subscribers)', 'wp-user-avatar'); ?>
            </label>
          </th>
          <td>
            <fieldset>
              <legend class="screen-reader-text"><span><?php _e('Upload Size Limit', 'wp-user-avatar'); ?> <?php _e('(only for Contributors & Subscribers)', 'wp-user-avatar'); ?></span></legend>
              <input name="wp_user_avatar_upload_size_limit" type="text" id="wp_user_avatar_upload_size_limit" value="<?php echo $wpua_upload_size_limit; ?>" class="regular-text" />
              <span id="wpua-readable-size"><?php echo $wpua_upload_size_limit_with_units; ?></span>
              <span id="wpua-readable-size-error"><?php printf(__('%s exceeds the maximum upload size for this site.'), ""); ?></span>
              <div id="wpua-slider"></div>
              <span class="description"><?php printf(__('Maximum upload file size: %d%s.'), esc_html(wp_max_upload_size()), esc_html(' bytes ('.$upload_size_limit_with_units.')')); ?></span>
            </fieldset>
            <fieldset>
              <label for="wp_user_avatar_edit_avatar">
                <input name="wp_user_avatar_edit_avatar" type="checkbox" id="wp_user_avatar_edit_avatar" value="1" <?php checked($wpua_edit_avatar, 1); ?> />
                <?php _e('Allow users to edit avatars', 'wp-user-avatar'); ?>
              </label>
            </fieldset>
            <fieldset>
              <label for="wp_user_avatar_resize_upload">
                <input name="wp_user_avatar_resize_upload" type="checkbox" id="wp_user_avatar_resize_upload" value="1" <?php checked($wpua_resize_upload, 1); ?> />
                <?php _e('Resize avatars on upload', 'wp-user-avatar'); ?>
              </label>
            </fieldset>
            <fieldset id="wpua-resize-sizes"<?php echo $hide_resize; ?>
              <br />
              <br />
              <label for="wp_user_avatar_resize_w"><?php _e('Width'); ?></label>
              <input name="wp_user_avatar_resize_w" type="number" step="1" min="0" id="wp_user_avatar_resize_w" value="<?php form_option('wp_user_avatar_resize_w'); ?>" class="small-text" />
              <label for="wp_user_avatar_resize_h"><?php _e('Height'); ?></label>
              <input name="wp_user_avatar_resize_h" type="number" step="1" min="0" id="wp_user_avatar_resize_h" value="<?php form_option('wp_user_avatar_resize_h'); ?>" class="small-text" />
              <br />
              <input name="wp_user_avatar_resize_crop" type="checkbox" id="wp_user_avatar_resize_crop" value="1" <?php checked('1', $wpua_resize_crop); ?> />
              <label for="wp_user_avatar_resize_crop"><?php _e('Crop avatars to exact dimensions', 'wp-user-avatar'); ?></label>
            </fieldset>
          </td>
        </tr>
      </table>
    </div>
    <h3 class="title"><?php _e('Avatars'); ?></h3>
    <p><?php _e('An avatar is an image that follows you from weblog to weblog appearing beside your name when you comment on avatar enabled sites. Here you can enable the display of avatars for people who comment on your site.'); ?></p>
    <table class="form-table">
      <tr valign="top">
      <th scope="row"><?php _e('Avatar Display'); ?></th>
      <td>
        <fieldset>
          <legend class="screen-reader-text"><span><?php _e('Avatar Display'); ?></span></legend>
          <label for="show_avatars">
          <input type="checkbox" id="show_avatars" name="show_avatars" value="1" <?php checked($show_avatars, 1); ?> />
          <?php _e('Show Avatars'); ?>
          </label>
        </fieldset>
        </td>
      </tr>
      <?php if((bool) $wpua_disable_gravatar != 1) : ?>
        <tr valign="top" id="avatar-rating">
          <th scope="row"><?php _e('Maximum Rating'); ?></th>
          <td>
            <fieldset>
              <legend class="screen-reader-text"><span><?php _e('Maximum Rating'); ?></span></legend>
              <?php
                $ratings = array(
                  'G' => __('G &#8212; Suitable for all audiences'),
                  'PG' => __('PG &#8212; Possibly offensive, usually for audiences 13 and above'),
                  'R' => __('R &#8212; Intended for adult audiences above 17'),
                  'X' => __('X &#8212; Even more mature than above')
                );
                foreach ($ratings as $key => $rating) :
                  $selected = (get_option('avatar_rating') == $key) ? 'checked="checked"' : "";
                  echo "\n\t<label><input type='radio' name='avatar_rating' value='" . esc_attr($key) . "' $selected/> $rating</label><br />";
                endforeach;
              ?>
            </fieldset>
          </td>
        </tr>
      <?php else : ?>
        <input type="hidden" id="avatar_rating" name="avatar_rating" value="<?php echo get_option('avatar_rating'); ?>" />
      <?php endif; ?>
      <tr valign="top">
        <th scope="row"><?php _e('Default Avatar') ?></th>
        <td class="defaultavatarpicker">
          <fieldset>
            <legend class="screen-reader-text"><span><?php _e('Default Avatar'); ?></span></legend>
            <?php _e('For users without a custom avatar of their own, you can either display a generic logo or a generated one based on their e-mail address.'); ?><br />
            <?php echo $this->wpua_add_default_avatar(); ?>
          </fieldset>
        </td>
      </tr>
    </table>
    <?php submit_button(); ?>
  </form>
</div>
