<?php
/**
 * Media Library view of all avatars in use.
 *
 * @package WP User Avatar
 * @version 1.8.9
 */

  $wp_list_table = $this->_wpua_get_list_table('WP_User_Avatar_List_Table');
  $wp_list_table->prepare_items();
  wp_enqueue_script('wp-ajax-response');
  wp_enqueue_script('jquery-ui-draggable');
  wp_enqueue_script('media');
?>
  <div class="wrap">
    <h2><?php _e('Avatars'); ?></h2>
<?php
$message = '';
if(!empty($_GET['posted'])) {
  $message = __('Media attachment updated.');
  $_SERVER['REQUEST_URI'] = remove_query_arg(array('posted'), $_SERVER['REQUEST_URI']);
}

if(!empty($_GET['attached']) && $attached = absint($_GET['attached'])) {
  $message = sprintf( _n('Reattached %d attachment.', 'Reattached %d attachments.', $attached), $attached);
  $_SERVER['REQUEST_URI'] = remove_query_arg(array('attached'), $_SERVER['REQUEST_URI']);
}

if(!empty($_GET['deleted']) && $deleted = absint($_GET['deleted'])) {
  $message = sprintf(_n('Media attachment permanently deleted.', '%d media attachments permanently deleted.', $deleted), number_format_i18n($_GET['deleted']));
  $_SERVER['REQUEST_URI'] = remove_query_arg(array('deleted'), $_SERVER['REQUEST_URI']);
}

if(!empty($_GET['trashed']) && $trashed = absint($_GET['trashed'])) {
  $message = sprintf(_n('Media attachment moved to the trash.', '%d media attachments moved to the trash.', $trashed), number_format_i18n( $_GET['trashed']));
  $message .= ' <a href="'.esc_url(wp_nonce_url( 'upload.php?doaction=undo&action=untrash&ids='.(isset($_GET['ids']) ? $_GET['ids'] : ''), "bulk-media" )).'">'.__('Undo').'</a>';
  $_SERVER['REQUEST_URI'] = remove_query_arg(array('trashed'), $_SERVER['REQUEST_URI']);
}

if(!empty($_GET['untrashed']) && $untrashed = absint($_GET['untrashed'])) {
  $message = sprintf(_n('Media attachment restored from the trash.', '%d media attachments restored from the trash.', $untrashed), number_format_i18n($_GET['untrashed']));
  $_SERVER['REQUEST_URI'] = remove_query_arg(array('untrashed'), $_SERVER['REQUEST_URI']);
}

$messages[1] = __('Media attachment updated.');
$messages[2] = __('Media permanently deleted.');
$messages[3] = __('Error saving media attachment.');
$messages[4] = __('Media moved to the trash.').' <a href="'.esc_url(wp_nonce_url('upload.php?doaction=undo&action=untrash&ids='.(isset($_GET['ids']) ? $_GET['ids'] : ''), "bulk-media")).'">'.__('Undo').'</a>';
$messages[5] = __('Media restored from the trash.');

if(!empty($_GET['message']) && isset($messages[$_GET['message']])) {
  $message = $messages[$_GET['message']];
  $_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
}

if(!empty($message)) { ?>
<div id="message" class="updated"><p><?php echo $message; ?></p></div>
<?php } ?>
  <?php $wp_list_table->views(); ?>
  <form id="posts-filter" action="" method="get">
    <?php $wp_list_table->display(); ?>
    <div id="ajax-response"></div>
    <?php find_posts_div(); ?>
    <br class="clear" />
  </form>
</div>
