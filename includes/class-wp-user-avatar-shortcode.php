<?php
/**
 * @package WP User Avatar
 * @version 1.8
 */

class WP_User_Avatar_Shortcode {
  function __construct(){
    add_shortcode('avatar', array($this, 'wpua_shortcode'));
  }

  // Shortcode
  function wpua_shortcode($atts, $content=null){
    global $all_sizes, $blog_id, $post, $wpdb;
    // Set shortcode attributes
    extract(shortcode_atts(array('user' => "", 'size' => '96', 'align' => "", 'link' => "", 'target' => ""), $atts));
    // Find user by ID, login, slug, or e-mail address
    if(!empty($user)){
      $user = is_numeric($user) ? get_user_by('id', $user) : get_user_by('login', $user);
      $user = empty($user) ? get_user_by('slug', $user) : $user;
      $user = empty($user) ? get_user_by('email', $user) : $user;
    } else {
      // Find author's name if id_or_email is empty
      $author_name = get_query_var('author_name');
      if(is_author()){
        // On author page, get user by page slug
        $user = get_user_by('slug', $author_name);
      } else {
        // On post, get user by author meta
        $user_id = get_the_author_meta('ID');
        $user = get_user_by('id', $user_id);
      }
    }
    // Numeric sizes leave as-is
    $get_size = $size;
    // Check for custom image sizes if there are captions
    if(!empty($content)){
      if(in_array($size, $all_sizes)){
        if(in_array($size, array('original', 'large', 'medium', 'thumbnail'))){
          $get_size = ($size == 'original') ? get_option('large_size_w') : get_option($size.'_size_w');
        } else {
          $get_size = $_wp_additional_image_sizes[$size]['width'];
        }
      }
    }
    // Get user ID
    $id_or_email = !empty($user) ? $user->ID : 'unknown@gravatar.com';
    // Check if link is set
    if(!empty($link)){
      // CSS class is same as link type, except for URL
      $link_class = $link;
      if($link == 'file'){
        // Get image src
        $link = get_wp_user_avatar_src($id_or_email, 'original');
      } elseif($link == 'attachment'){
        // Get attachment URL
        $link = get_attachment_link(get_the_author_meta($wpdb->get_blog_prefix($blog_id).'user_avatar', $id_or_email));
      } else {
        // URL
        $link_class = 'custom';
      }
      // Open in new window
      $target_link = !empty($target) ? ' target="'.$target.'"' : "";
      // Wrap the avatar inside the link
      $html = '<a href="'.$link.'" class="wp-user-avatar-link wp-user-avatar-'.$link_class.'"'.$target_link.'>'.get_wp_user_avatar($id_or_email, $get_size, $align).'</a>';
    } else {
      $html = get_wp_user_avatar($id_or_email, $get_size, $align);
    }
    // Check if caption is set
    if(!empty($content)){
      // Get attachment ID
      $wpua = get_user_meta($id_or_email, $wpdb->get_blog_prefix($blog_id).'user_avatar', true);
      // Clean up caption
      $content = trim($content);
      $content = preg_replace('/\r|\n/', "", $content);
      $content = preg_replace('/<\/p><p>/', "", $content, 1);
      $content = preg_replace('/<\/p><p>$/', "", $content);
      $content = str_replace('</p><p>', "<br /><br />", $content);
      $avatar = do_shortcode(image_add_caption($html, $wpua, $content, $title="", $align, $link, $get_size, $alt=""));
    } else {
      $avatar = $html;
    }
    return $avatar;
  }
}
new WP_User_Avatar_Shortcode;
