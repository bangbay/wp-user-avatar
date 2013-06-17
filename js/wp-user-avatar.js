// Backbone uploader for WP 3.5
function openMediaUploader(section, edit_text, insert_text){
  wp.media.wpUserAvatar = {
    get: function(){
      return wp.media.view.settings.post.wpUserAvatarId;
    },
    set: function(id){
      var settings = wp.media.view.settings;
      settings.post.wpUserAvatarId = id;
      settings.post.wpUserAvatarSrc = jQuery('div.attachment-info').find('img').attr('src');
      if(settings.post.wpUserAvatarId){
        setWPUserAvatar(settings.post.wpUserAvatarId, settings.post.wpUserAvatarSrc);
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
      jQuery('body').on('click', '#add-wp-user-avatar', function(e){
        e.preventDefault();
        e.stopPropagation();
        wp.media.wpUserAvatar.frame().open();
      });
    }
  };
  jQuery(wp.media.wpUserAvatar.init);
}

// Thickbox uploader
function openThickboxUploader(section, iframe){
  jQuery('body').on('click', '#add-wp-user-avatar', function(e){
    e.preventDefault();
    tb_show('WP User Avatar: ' + section, iframe);
  });
}

// Set WP User Avatar
function setWPUserAvatar(attachment, imageURL){
  jQuery('#wp-user-avatar', window.parent.document).val(attachment);
  jQuery('#wp-user-avatar-preview', window.parent.document).find('img').attr('src', imageURL).removeAttr('width', '').removeAttr('height', '');
  jQuery('#wp-user-avatar-message', window.parent.document).show();
  jQuery('#remove-wp-user-avatar', window.parent.document).show();
  jQuery('#wp-user-avatar-thumbnail', window.parent.document).hide();
  jQuery('#wp_user_avatar_radio', window.parent.document).trigger('click');
  // Check if WP 3.5
  if(typeof(wp) != 'undefined'){
    wp.media.wpUserAvatar.frame().close()
  } else {
    window.parent.tb_remove();
  }
}

// Remove WP User Avatar
function removeWPUserAvatar(avatar_thumb){
  jQuery('body').on('click', '#remove-wp-user-avatar', function(e){
    e.preventDefault();
    jQuery(this).hide();
    jQuery('#edit-wp-user-avatar, #wp-user-avatar-thumbnail').hide();
    jQuery('#wp-user-avatar-preview').find('img').attr('src', avatar_thumb).removeAttr('width', '').removeAttr('height', '');
    jQuery('#wp-user-avatar').val('');
    jQuery('#wp-user-avatar-message').show();
    jQuery('#wp_user_avatar_radio').trigger('click');
  });
}
