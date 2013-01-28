function setWPUserAvatar(attachment, imageURL){
  jQuery('#wp-user-avatar', window.parent.document).val(attachment);
  jQuery('#wp-user-avatar-preview', window.parent.document).find('img').attr('src', imageURL).removeAttr('width', '').removeAttr('height', '');
  jQuery('#wp-user-avatar-message', window.parent.document).show();
  jQuery('#remove-wp-user-avatar', window.parent.document).show();
  jQuery('#gravatar-notice', window.parent.document).hide();
  jQuery('#wp_user_avatar_radio', window.parent.document).val(imageURL).trigger('click');
  if(typeof(wp) != 'undefined'){
    wp.media.wpUserAvatar.frame().close()
  } else {
    window.parent.tb_remove();
  }
}
