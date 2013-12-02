jQuery(function(){
  // Add enctype to form with JavaScript as backup
  jQuery('#your-profile').attr('enctype', 'multipart/form-data');
  // Remove WP User Avatar
  jQuery('body').on('click', '#wpua-remove', function(e){
    e.preventDefault();
    jQuery(this).hide();
    jQuery('#wpua-edit, #wpua-thumbnail').hide();
    jQuery('#wpua-preview').find('img').attr('src', wpua_custom.avatar_thumb).removeAttr('width', "").removeAttr('height', "");
    jQuery('#wp-user-avatar').val("");
    jQuery('#wpua-message').show();
    jQuery('#wp_user_avatar_radio').trigger('click');
  });
});
