// Media uploader
function wpuaMediaUploader(section, edit_text, insert_text){
  wp.media.wpUserAvatar = {
    get: function(){
      return wp.media.view.settings.post.wpUserAvatarId;
    },
    set: function(id){
      var settings = wp.media.view.settings;
      settings.post.wpUserAvatarId = id;
      settings.post.wpUserAvatarSrc = jQuery('div.attachment-info').find('img').attr('src');
      if(settings.post.wpUserAvatarId){
        // Set WP User Avatar
        jQuery('#wp-user-avatar', window.parent.document).val(settings.post.wpUserAvatarId);
        jQuery('#wpua-preview', window.parent.document).find('img').attr('src', settings.post.wpUserAvatarSrc).removeAttr('height', "");
        jQuery('#wpua-message', window.parent.document).show();
        jQuery('#wpua-undo-button', window.parent.document).show();
        jQuery('#wpua-remove-button', window.parent.document).hide();
        jQuery('#wpua-thumbnail', window.parent.document).hide();
        jQuery('#wp_user_avatar_radio', window.parent.document).trigger('click');
        wp.media.wpUserAvatar.frame().close();
        jQuery('#wp_user_avatar_radio').trigger('click');
      }
    },
    frame: function(){
      if(this._frame){
        return this._frame;
      }
      this._frame = wp.media({
        state: 'library',
        states: [ new wp.media.controller.Library({ title: edit_text + ": " + section }) ]
      });
      this._frame.on('open', function(){
        var selection = this.state().get('selection');
        id = jQuery('#wp-user-avatar').val();
        attachment = wp.media.attachment(id);
        attachment.fetch();
        selection.add(attachment ? [ attachment ] : []);
      }, this._frame);
      this._frame.on('toolbar:create:select', function(toolbar){
        this.createSelectToolbar(toolbar, {
          text: insert_text
        });
      }, this._frame);
      this._frame.state('library').on('select', this.select);
      return this._frame;
    },
    select: function(id){
      var settings = wp.media.view.settings,
      selection = this.get('selection').single();
      wp.media.wpUserAvatar.set(selection ? selection.id : -1);
    },
    init: function(){
      jQuery('body').on('click', '#wpua-add', function(e){
        e.preventDefault();
        e.stopPropagation();
        wp.media.wpUserAvatar.frame().open();
      });
    }
  };
  jQuery(wp.media.wpUserAvatar.init);
}

jQuery(function(){
  // Add enctype to form with JavaScript as backup
  jQuery('#your-profile').attr('enctype', 'multipart/form-data');
  // Remove/edit settings
  if(typeof(wp) != 'undefined'){
    wpuaMediaUploader(wpua_custom.section, wpua_custom.edit_image, wpua_custom.select_image);
  }
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
