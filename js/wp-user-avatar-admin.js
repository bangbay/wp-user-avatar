jQuery(function(){
  // Show size info only if allow uploads is checked
  jQuery('#wp_user_avatar_allow_upload').change(function(){
    jQuery('#wpua-contributors-subscribers').removeClass('wpua-hide').slideToggle(jQuery('#wp_user_avatar_allow_upload').is(':checked'));
  });
  // Show resize info only if resize uploads is checked
  jQuery('#wp_user_avatar_resize_upload').change(function(){
     jQuery('#wpua-resize-sizes').removeClass('wpua-hide').slideToggle(jQuery('#wp_user_avatar_resize_upload').is(':checked'));
  });
  // Hide Gravatars if disable Gravatars is checked
  jQuery('#wp_user_avatar_disable_gravatar').change(function(){
    if(jQuery('#wp-avatars').length){
      jQuery('#wp-avatars').slideToggle(!jQuery('#wp_user_avatar_disable_gravatar').is(':checked'));
      jQuery('#wp_user_avatar_radio').trigger('click');
    }
  });
  // Add size slider
  jQuery('#wpua-slider').slider({
    value: parseInt(wpua_admin.upload_size_limit),
    min: 0,
    max: parseInt(wpua_admin.max_upload_size),
    step: 1,
    slide: function(event, ui){
      jQuery('#wp_user_avatar_upload_size_limit').val(ui.value);
      jQuery('#wpua-readable-size').html(Math.floor(ui.value / 1024) + 'KB');
      jQuery('#wpua-readable-size-error').hide();
      jQuery('#wpua-readable-size').removeClass('wpua-error');
    }
  });
  // Update readable size on keyup
  jQuery('#wp_user_avatar_upload_size_limit').keyup(function(){
    var wpua_upload_size_limit = jQuery(this).val();
    wpua_upload_size_limit = wpua_upload_size_limit.replace(/\D/g, '');
    jQuery(this).val(wpua_upload_size_limit);
    jQuery('#wpua-readable-size').html(Math.floor(wpua_upload_size_limit / 1024) + 'KB');
    jQuery('#wpua-readable-size-error').toggle(wpua_upload_size_limit > parseInt(wpua_admin.max_upload_size));
    jQuery('#wpua-readable-size').toggleClass('wpua-error', wpua_upload_size_limit > parseInt(wpua_admin.max_upload_size));
  });
  jQuery('#wp_user_avatar_upload_size_limit').val(jQuery('#wpua-slider').slider('value'));
});
