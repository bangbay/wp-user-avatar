<?php
/**
 * Media Library List Table class.
 *
 * @package WordPress
 * @subpackage List_Table
 * @since 3.1.0
 * @access private
 */

class WP_User_Avatar_List_Table extends WP_List_Table {
  function __construct($args = array()){
    global $avatars_array, $post, $wpua_avatar_default;
    $q = array(
      'post_type' => 'attachment',
      'post_status' => 'inherit',
      'meta_query' => array(
        array(
          'key' => '_wp_attachment_wp_user_avatar',
          'value' => '',
          'compare' => '!='
        )
      )
    );
    $avatars_wp_query = new WP_Query($q);
    $avatars_array = array();
    while($avatars_wp_query->have_posts()) : $avatars_wp_query->the_post();
      $avatars_array[] = $post->ID;
    endwhile;
    $avatars_array[] = $wpua_avatar_default;
    parent::__construct(array(
      'plural' => 'media',
      'screen' => isset($args['screen']) ? $args['screen'] : null
    ));
  }

  function ajax_user_can(){
    return current_user_can('upload_files');
  }

  function prepare_items(){
    global $avatars_array, $lost, $wpdb, $wp_query, $post_mime_types, $avail_post_mime_types, $post;
    $q = $_REQUEST;
    $q['post__in'] = $avatars_array;
    list($post_mime_types, $avail_post_mime_types) = wp_edit_attachments_query($q);
    $this->is_trash = isset($_REQUEST['status']) && 'trash' == $_REQUEST['status'];
    $this->set_pagination_args(array(
      'total_items' => $wp_query->found_posts,
      'total_pages' => $wp_query->max_num_pages,
      'per_page' => $wp_query->query_vars['posts_per_page'],
    ));
  }

  function get_views(){
    global $avatars_array;
    $type_links = array();
    $_total_posts = count($avatars_array);
    $class = (empty($_GET['post_mime_type']) && !isset($_GET['status'])) ? ' class="current"' : '';
    $type_links['all'] = "<a href='admin.php?page=wp-user-avatar'$class>".sprintf(_nx( 'All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $_total_posts, 'uploaded files'), number_format_i18n($_total_posts)).'</a>';
    return $type_links;
  }

  function get_bulk_actions(){
    $actions = array();
    $actions['delete'] = __('Delete Permanently');
    return $actions;
  }

  function extra_tablenav($which){ ?>
    <div class="alignleft actions">
      <?php
        if('top' == $which && !is_singular() && !$this->is_trash){
          $this->months_dropdown('attachment');
          do_action( 'restrict_manage_posts' );
          submit_button(__('Filter'), 'button', false, false, array('id' => 'post-query-submit'));
        }
        if($this->is_trash && current_user_can( 'edit_others_posts')){
          submit_button(__('Empty Trash'), 'apply', 'delete_all', false );
        }
      ?>
    </div>
    <?php
  }

  function current_action(){
    if(isset($_REQUEST['delete_all']) || isset($_REQUEST['delete_all2'])){
      return 'delete_all';
    }
    return parent::current_action();
  }

  function has_items(){
    return have_posts();
  }

  function no_items(){
    _e('No media attachments found.');
  }

  function get_columns(){
    $posts_columns = array();
    $posts_columns['cb'] = '<input type="checkbox" />';
    $posts_columns['icon'] = '';
    $posts_columns['title'] = _x('File', 'column name');
    $posts_columns['author'] = __('Author');
    $posts_columns['parent'] = _x('Uploaded to', 'column name');
    $posts_columns['date'] = _x('Date', 'column name');
    $posts_columns = apply_filters('manage_media_columns', $posts_columns );
    return $posts_columns;
  }

  function get_sortable_columns(){
    return array(
      'title'    => 'title',
      'author'   => 'author',
      'parent'   => 'parent',
      'date'     => array('date', true),
    );
  }

  function display_rows(){
    global $post, $wpdb;

    add_filter('the_title','esc_html');
    $alt = '';

    while (have_posts()) : the_post();
      $user_can_edit = current_user_can('edit_post', $post->ID);

      if($this->is_trash && $post->post_status != 'trash' || !$this->is_trash && $post->post_status == 'trash')
        continue;

      $alt = ('alternate' == $alt) ? '' : 'alternate';
      $post_owner = (get_current_user_id() == $post->post_author) ? 'self' : 'other';
      $att_title = _draft_or_post_title();
  ?>
  <tr id='post-<?php echo $post->ID; ?>' class='<?php echo trim($alt.' author-'.$post_owner.' status-'.$post->post_status); ?>' valign="top">
    <?php
    list( $columns, $hidden ) = $this->get_column_info();
    foreach($columns as $column_name => $column_display_name){
      $class = "class='$column_name column-$column_name'";
      $style = '';
      if(in_array($column_name, $hidden)){
        $style = ' style="display:none;"';
      }
      $attributes = $class.$style;
      switch($column_name){
        case 'cb':
        ?>
          <th scope="row" class="check-column">
            <?php if ( $user_can_edit ) { ?>
              <label class="screen-reader-text" for="cb-select-<?php the_ID(); ?>"><?php echo sprintf( __( 'Select %s' ), $att_title );?></label>
              <input type="checkbox" name="media[]" id="cb-select-<?php the_ID(); ?>" value="<?php the_ID(); ?>" />
            <?php } ?>
          </th>
        <?php
        break;
        case 'icon':
          $attributes = 'class="column-icon media-icon"'.$style;
          ?>
            <td <?php echo $attributes ?>><?php
              if ( $thumb = wp_get_attachment_image( $post->ID, array( 80, 60 ), true ) ) {
                if ( $this->is_trash || ! $user_can_edit ) {
                  echo $thumb;
                } else {
              ?>
                <a href="<?php echo get_edit_post_link( $post->ID, true ); ?>" title="<?php echo esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;' ), $att_title ) ); ?>">
                  <?php echo $thumb; ?>
                </a>

              <?php } }
              ?>
            </td>
          <?php
        break;
        case 'title':
?>
    <td <?php echo $attributes ?>><strong>
      <?php if ( $this->is_trash || ! $user_can_edit ) {
        echo $att_title;
      } else { ?>
      <a href="<?php echo get_edit_post_link( $post->ID, true ); ?>"
        title="<?php echo esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;' ), $att_title ) ); ?>">
        <?php echo $att_title; ?></a>
      <?php };
      _media_states( $post ); ?></strong>
      <p>
<?php
      if ( preg_match( '/^.*?\.(\w+)$/', get_attached_file( $post->ID ), $matches ) )
        echo esc_html( strtoupper( $matches[1] ) );
      else
        echo strtoupper( str_replace( 'image/', '', get_post_mime_type() ) );
?>
      </p>
<?php
    echo $this->row_actions( $this->_get_row_actions( $post, $att_title ) );
?>
    </td>
<?php
    break;

  case 'author':
?>
    <td <?php echo $attributes ?>><?php
      printf( '<a href="%s">%s</a>',
        esc_url( add_query_arg( array( 'author' => get_the_author_meta('ID') ), 'upload.php' ) ),
        get_the_author()
      );
    ?></td>
<?php
    break;

  case 'desc':
?>
    <td <?php echo $attributes ?>><?php echo has_excerpt() ? $post->post_excerpt : ''; ?></td>
<?php
    break;

  case 'date':
    if ( '0000-00-00 00:00:00' == $post->post_date ) {
      $h_time = __( 'Unpublished' );
    } else {
      $m_time = $post->post_date;
      $time = get_post_time( 'G', true, $post, false );
      if ( ( abs( $t_diff = time() - $time ) ) < DAY_IN_SECONDS ) {
        if ( $t_diff < 0 )
          $h_time = sprintf( __( '%s from now' ), human_time_diff( $time ) );
        else
          $h_time = sprintf( __( '%s ago' ), human_time_diff( $time ) );
      } else {
        $h_time = mysql2date( __( 'Y/m/d' ), $m_time );
      }
    }
?>
    <td <?php echo $attributes ?>><?php echo $h_time ?></td>
<?php
    break;

  case 'parent':
    if ( $post->post_parent > 0 )
      $parent = get_post( $post->post_parent );
    else
      $parent = false;

    if ( $parent ) {
      $title = _draft_or_post_title( $post->post_parent );
      $parent_type = get_post_type_object( $parent->post_type );
?>
      <td <?php echo $attributes ?>><strong>
        <?php if ( current_user_can( 'edit_post', $post->post_parent ) && $parent_type->show_ui ) { ?>
          <a href="<?php echo get_edit_post_link( $post->post_parent ); ?>">
            <?php echo $title ?></a><?php
        } else {
          echo $title;
        } ?></strong>,
        <?php echo get_the_time( __( 'Y/m/d' ) ); ?>
      </td>
<?php
    } else {
?>
      <td <?php echo $attributes ?>><?php _e( '(Unattached)' ); ?><br />
      <?php if ( $user_can_edit ) { ?>
        <a class="hide-if-no-js"
          onclick="findPosts.open( 'media[]','<?php echo $post->ID ?>' ); return false;"
          href="#the-list">
          <?php _e( 'Attach' ); ?></a>
      <?php } ?></td>
<?php
    }
  break;

  case 'comments':
    $attributes = 'class="comments column-comments num"'.$style;
  ?>
    <td <?php echo $attributes ?>>
      <div class="post-com-count-wrapper">
        <?php
          $pending_comments = get_pending_comments_num( $post->ID );
          $this->comments_bubble( $post->ID, $pending_comments );
        ?>
      </div>
    </td>
  <?php
    break;
  }
}
?>
  </tr>
<?php endwhile;
  }

  function _get_row_actions($post, $att_title){
    $actions = array();
    if(current_user_can('edit_post', $post->ID) && !$this->is_trash){
      $actions['edit'] = '<a href="'.get_edit_post_link($post->ID, true).'">'.__('Edit').'</a>';
    }
    if(current_user_can('delete_post', $post->ID)){
      if($this->is_trash){
        $actions['untrash'] = "<a class='submitdelete' href='".wp_nonce_url("post.php?action=untrash&amp;post=$post->ID", 'untrash-post_'.$post->ID)."'>".__('Restore')."</a>";
      } elseif (EMPTY_TRASH_DAYS && MEDIA_TRASH){
        $actions['trash'] = "<a class='submitdelete' href='".wp_nonce_url("post.php?action=trash&amp;post=$post->ID", 'trash-post_'. $post->ID)."'>".__('Trash')."</a>";
      }
      if($this->is_trash || !EMPTY_TRASH_DAYS || !MEDIA_TRASH){
        $delete_ays = (!$this->is_trash && !MEDIA_TRASH) ? " onclick='return showNotice.warn();'" : '';
        $actions['delete'] = "<a class='submitdelete'$delete_ays href='".wp_nonce_url( "post.php?action=delete&amp;post=$post->ID", 'delete-post_'.$post->ID)."'>".__('Delete Permanently')."</a>";
      }
    }
    if(!$this->is_trash){
      $title =_draft_or_post_title($post->post_parent);
      $actions['view'] = '<a href="'.get_permalink($post->ID).'" title="'.esc_attr(sprintf(__('View &#8220;%s&#8221;'), $title)).'" rel="permalink">'.__('View').'</a>';
    }
    $actions = apply_filters('media_row_actions', $actions, $post);
    return $actions;
  }
}
