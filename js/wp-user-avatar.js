function setWPUserAvatar(attachment, imageURL){
  jQuery('#wp-user-avatar', window.parent.document).val(attachment);
  jQuery('#wp-user-avatar-preview', window.parent.document).find('img').attr('src', imageURL).attr('width', '96').removeAttr('height', '');
  jQuery('#wp-user-avatar-message', window.parent.document).show();
  jQuery('#remove-wp-user-avatar', window.parent.document).show();
  if(typeof(wp) != 'undefined'){
    wp.media.wpUserAvatar.frame().close()
  } else {
    window.parent.tb_remove();
  }
}
