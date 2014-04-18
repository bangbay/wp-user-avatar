// Media Uploader
(function($){
  wp.media.wpUserAvatar = {
    get: function() {
      return wp.media.view.settings.post.wpUserAvatarId;
    },
    set: function(id) {
      var settings = wp.media.view.settings;
      // Selected attachment ID
      settings.post.wpUserAvatarId = id;
      // Selected attachment image
      settings.post.wpUserAvatarSrc = $('div.attachment-info').find('img').attr('src');
      // Set WP User Avatar
      if(settings.post.wpUserAvatarId && settings.post.wpUserAvatarSrc) {
        $('#wp-user-avatar').val(settings.post.wpUserAvatarId);
        $('#wpua-images, #wpua-undo-button').show();
        $('#wpua-preview').find('img').attr('src', settings.post.wpUserAvatarSrc).removeAttr('height', "");
        $('#wpua-remove-button, #wpua-thumbnail').hide();
        $('#wp_user_avatar_radio').trigger('click');
      }
      // Close media modal
      wp.media.wpUserAvatar.frame().close();
    },
    frame: function() {
      // Check if frame is already declared
      if(this._frame) {
        return this._frame;
      }
      // Frame options
      this._frame = wp.media({
        library: {
          type: 'image'
        },
        multiple: false,
        title: $('#wpua-add').data('title')
      });
      // Run on frame open
      this._frame.on('open', function() {
        var id = $('#wp-user-avatar').val();
        if(id == "") {
          // If no WPUA is set, go to upload tab
          $('div.media-router').find('a:first').trigger('click');
        } else {
          // If WPUA is set, select attachment on open
          var selection = this.state().get('selection');
          attachment = wp.media.attachment(id);
          attachment.fetch();
          selection.add(attachment ? [ attachment ] : []);
        }
      }, this._frame);
      // Select attachment
      this._frame.state('library').on('select', this.select);
      return this._frame;
    },
    // Set attachment ID
    select: function(id) {
      selection = this.get('selection').single();
      wp.media.wpUserAvatar.set(selection ? selection.id : -1);
    },
    init: function() {
      // Open Media Uploader
      $('body').on('click', '#wpua-add', function(e) {
        e.preventDefault();
        e.stopPropagation();
        wp.media.wpUserAvatar.frame().open();
      });
    }
  };
})(jQuery);

jQuery(function($) {
  // Initialize Media Uploader
  if(typeof(wp) != 'undefined') {
    wp.media.wpUserAvatar.init();
  }
  // Add enctype to form with JavaScript as backup
  $('#your-profile').attr('enctype', 'multipart/form-data');
  // Store WP User Avatar ID
  var wpuaID = $('#wp-user-avatar').val();
  // Store WP User Avatar src
  var wpuaSrc = $('#wpua-preview').find('img').attr('src');
  // Remove WP User Avatar
  $('body').on('click', '#wpua-remove', function(e) {
    e.preventDefault();
    $('#wpua-original').remove();
    $('#wpua-remove-button, #wpua-thumbnail').hide();
    $('#wpua-preview').find('img:first').hide();
    $('#wpua-preview').prepend('<img id="wpua-original" />');
    $('#wpua-original').attr('src', wpua_custom.avatar_thumb);
    $('#wp-user-avatar').val("");
    $('#wpua-original, #wpua-undo-button').show();
    $('#wp_user_avatar_radio').trigger('click');
  });
  // Undo WP User Avatar
  $('body').on('click', '#wpua-undo', function(e) {
    e.preventDefault();
    $('#wpua-original').remove();
    $('#wpua-images').removeAttr('style');
    $('#wpua-undo-button').hide();
    $('#wpua-remove-button, #wpua-thumbnail').show();
    $('#wpua-preview').find('img:first').attr('src', wpuaSrc).show();
    $('#wp-user-avatar').val(wpuaID);
    $('#wp_user_avatar_radio').trigger('click');
  });
});
