**WP User Avatar**

Download the plugin at: http://wordpress.org/extend/plugins/wp-user-avatar/

**Description**

WordPress currently only allows you to use custom avatars that are uploaded through gravatar.com. WP User Avatar enables you to use any photo uploaded into your Media Library as an avatar. This means you use the same uploader and library as your posts. No extra folders or image editing functions are necessary.

To use WP User Avatar, choose a theme that has avatar support. In your theme, manually replace get_avatar with get_wp_user_avatar or leave get_avatar as-is. get_wp_user_avatar has functionality not available in get_avatar. [Read about the differences here](http://wordpress.org/extend/plugins/wp-user-avatar/faq/).

WP User Avatar also lets you:

* Upload your own Default Avatar in your Discussion Settings.
* Show the user's gravatar.com avatar or the Default Avatar if the user doesn't have a WP User Avatar image.
* Use the shortcode <code>[avatar]</code> in your posts. The shortcode will work with any theme, whether it has avatar support or not.

[Read more about get_avatar in the WordPress Function Reference](http://codex.wordpress.org/Function_Reference/get_avatar).

This plugin uses the new Media Uploader introduced in WordPress 3.5, but is also backwards-compatible to WordPress 3.0.
