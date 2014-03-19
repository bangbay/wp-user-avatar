<?php
/**
 * Media Library view of all avatars in use.
 *
 * @package WP User Avatar
 * @version 1.8.10
 */
  /** WordPress Administration Bootstrap */
  require_once(ABSPATH.'wp-admin/admin.php');

  if(!current_user_can('upload_files'))
    wp_die(__('You do not have permission to upload files.'));

  $wp_list_table = $this->_wpua_get_list_table('WP_User_Avatar_List_Table');
  $pagenum = $wp_list_table->get_pagenum();

  // Handle bulk actions
  $doaction = $wp_list_table->current_action();

  if($doaction) {
    check_admin_referer('bulk-media');

    if(isset($_REQUEST['media'])) {
      $post_ids = $_REQUEST['media'];
    } elseif(isset($_REQUEST['ids'])) {
      $post_ids = explode(',', $_REQUEST['ids']);
    }

    $location = esc_url(add_query_arg(array('page' => 'wp-user-avatar-library'), 'admin.php'));
    if($referer = wp_get_referer()) {
      if(false !== strpos($referer, 'admin.php'))
        $location = remove_query_arg(array('trashed', 'untrashed', 'deleted', 'message', 'ids', 'posted'), $referer);
    }
    switch($doaction) {
      case 'delete':
        if(!isset($post_ids))
          break;
        foreach((array) $post_ids as $post_id_del) {
          if(!current_user_can( 'delete_post', $post_id_del))
            wp_die(__( 'You are not allowed to delete this post.'));
          if(!wp_delete_attachment($post_id_del))
            wp_die(__('Error in deleting.'));
        }
      $location = add_query_arg('deleted', count($post_ids), $location);
      break;
    }
    wp_redirect($location);
    exit;
  } elseif(!empty($_GET['_wp_http_referer'])) {
    wp_redirect(remove_query_arg(array('_wp_http_referer', '_wpnonce'), wp_unslash($_SERVER['REQUEST_URI'])));
    exit;
  }
  $wp_list_table->prepare_items();
  wp_enqueue_script('wp-ajax-response');
  wp_enqueue_script('jquery-ui-draggable');
  wp_enqueue_script('media');
?>
<div class="wrap">
  <h2>
    <?php _e('Avatars');
      if(!empty($_REQUEST['s'])) {
        printf('<span class="subtitle">'.__('Search results for &#8220;%s&#8221;').'</span>', get_search_query());
      }
    ?>
  </h2>
  <?php
    $message = '';
    if(!empty($_GET['deleted']) && $deleted = absint($_GET['deleted'])) {
      $message = sprintf(_n('Media attachment permanently deleted.', '%d media attachments permanently deleted.', $deleted), number_format_i18n($_GET['deleted']));
      $_SERVER['REQUEST_URI'] = remove_query_arg(array('deleted'), $_SERVER['REQUEST_URI']);
    }
    if(!empty($message)) : ?>
    <div id="message" class="updated"><p><?php echo $message; ?></p></div>
  <?php endif; ?>
  <?php $wp_list_table->views(); ?>
  <form id="posts-filter" action="" method="get">
    <?php $wp_list_table->search_box(__('Search'), 'media' ); ?>
    <?php $wp_list_table->display(); ?>
    <div id="ajax-response"></div>
    <?php find_posts_div(); ?>
    <br class="clear" />
  </form>
</div>
