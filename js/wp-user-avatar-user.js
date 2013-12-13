jQuery(function(){
  // Add enctype to form with JavaScript as backup
  jQuery('#your-profile').attr('enctype', 'multipart/form-data');
  // Store WP User Avatar ID
  var wpuaID = jQuery('#wp-user-avatar').val();
  // Store WP User Avatar src
  var wpuaSrc = jQuery('#wpua-preview').find('img').attr('src');
  // Remove WP User Avatar
  jQuery('body').on('click', '#wpua-remove', function(e){
    e.preventDefault();
    jQuery('#wpua-original').remove();
    jQuery('#wpua-remove-button, #wpua-thumbnail').hide();
    jQuery('#wpua-preview').find('img:first').hide();
    jQuery('#wpua-preview').prepend('<img id="wpua-original" height="98" />');
    jQuery('#wpua-original').attr('src', wpua_custom.avatar_thumb);
    jQuery('#wp-user-avatar').val("");
    jQuery('#wpua-message, #wpua-original, #wpua-undo-button').show();
    jQuery('#wp_user_avatar_radio').trigger('click');
  });
  // Undo WP User Avatar
  jQuery('body').on('click', '#wpua-undo', function(e){
    e.preventDefault();
    jQuery('#wpua-original').remove();
    jQuery('#wpua-message, #wpua-undo-button').hide();
    jQuery('#wpua-remove-button, #wpua-thumbnail').show();
    jQuery('#wpua-preview').find('img:first').attr('src', wpuaSrc).show();
    jQuery('#wp-user-avatar').val(wpuaID);
    jQuery('#wp_user_avatar_radio').trigger('click');
  });
});
