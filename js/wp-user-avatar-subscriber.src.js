// Remove WP User Avatar
function removeWPUserAvatar(avatar_thumb){
  jQuery('body').on('click', '#remove-wp-user-avatar', function(e){
    e.preventDefault();
    jQuery(this).hide();
    jQuery('#wp-user-avatar-preview').find('img').attr('src', avatar_thumb).removeAttr('width', '').removeAttr('height', '');
    jQuery('#wp-user-avatar').val('');
    jQuery('#wp-user-avatar-message, #wp-user-avatar-notice').show();
    jQuery('#wp_user_avatar_radio').trigger('click');
  });
}

// All uploads in profile form
jQuery(document).ready(function(){
  jQuery('#your-profile', '#bbp-your-profile').attr('enctype', 'multipart/form-data');
});
