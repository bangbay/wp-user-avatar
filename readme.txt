=== WP User Avatar ===

Contributors: bangbay
Donate link: http://siboliban.org/donate
Tags: author image, author photo, author avatar, avatar, bbPress, profile avatar, profile image, user avatar, user image, user photo
Requires at least: 3.1
Tested up to: 3.5.1
Stable tag: 1.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Use any image in your WordPress Media Libary as a custom user avatar. Add your own Default Avatar.

== Description ==

WordPress currently only allows you to use custom avatars that are uploaded through [gravatar.com](http://gravatar.com/). WP User Avatar enables you to use any photo uploaded into your Media Library as an avatar. This means you use the same uploader and library as your posts. No extra folders or image editing functions are necessary.

WP User Avatar also lets you:

* Upload your own Default Avatar in your Discussion settings.
* Show the user's [gravatar.com](http://gravatar.com/) avatar or Default Avatar if the user doesn't have a WP User Avatar image.
* Use the <code>[avatar]</code> shortcode in your posts. The shortcode will work with any theme, whether it has avatar support or not.

This plugin uses the Media uploader introduced in WordPress 3.5, but is also backwards-compatible to WordPress 3.1. It is also compatible with WordPress Multisite.

== Installation ==

1. Download, install, and activate the WP User Avatar plugin.
2. On your edit profile page, click "Edit WP User Avatar".
3. Choose an image, then click "Set WP User Avatar".
4. Click "Update Profile".
5. Upload your own Default Avatar in your Discussion settings (optional).
6. Choose a theme that has avatar support. In your theme, manually replace <code>get_avatar</code> with <code>get_wp_user_avatar</code> or leave <code>get_avatar</code> as-is. [Read about the differences here](http://wordpress.org/extend/plugins/wp-user-avatar/faq/).
7. You can also use the <code>[avatar]</code> shortcode in your posts. The shortcode will work with any theme, whether it has avatar support or not.

**Example Usage**

Within [The Loop](http://codex.wordpress.org/The_Loop), you may be using:

`<?php echo get_avatar(get_the_author_meta('ID'), 96); ?>`

Replace this function with:

`<?php echo get_wp_user_avatar(get_the_author_meta('ID'), 96); ?>`

You can also use the values "original", "large", "medium", or "thumbnail" for your avatar size:

`<?php echo get_wp_user_avatar(get_the_author_meta('ID'), 'medium'); ?>`

You can also add an alignment of "left", "right", or "center":

`<?php echo get_wp_user_avatar(get_the_author_meta('ID'), 96, 'left'); ?>`

On an author page outside of [The Loop](http://codex.wordpress.org/The_Loop), you may be using:

`<?php
  $user = get_user_by('slug', $author_name); 
  echo get_avatar($user->ID, 96);
?>`

Replace this function with:

`<?php
  $user = get_user_by('slug', $author_name);
  echo get_wp_user_avatar($user->ID, 96);
?>`

If you leave the options blank, WP User Avatar will detect whether you're inside [The Loop](http://codex.wordpress.org/The_Loop) or on an author page and return the correct avatar in the default 96x96 size:

`<?php echo get_wp_user_avatar(); ?>`

The function <code>get_wp_user_avatar</code> can also fall back to <code>get_avatar</code> if there is no WP User Avatar image. For this to work, "Show Avatars" must be checked in your Discussion settings. When this setting is enabled, you will see the user's [gravatar.com](http://gravatar.com/) avatar or Default Avatar.

**Other Available Functions**

= [avatar] shortcode =

You can use the <code>[avatar]</code> shortcode in your posts. It will detect the author of the post or you can specify an author by username. You can specify a size, alignment, and link, but they are optional. For links, you can link to the original image file, attachment page, or a custom URL.

`[avatar user="admin" size="medium" align="left" link="file"]`

= get_wp_user_avatar_src =

Works just like <code>get_wp_user_avatar</code> but returns just the image src. This is useful if you would like to link a thumbnail-sized avatar to a larger version of the image:

`<a href="<?php echo get_wp_user_avatar_src($user_id, 'large'); ?>">
  <?php echo get_wp_user_avatar($user_id, 'thumbnail'); ?>
</a>`

= has_wp_user_avatar =

Returns true if the user has a WP User Avatar image. You can specify the user ID, or leave it blank to detect the author within [The Loop](http://codex.wordpress.org/The_Loop) or author page:

`<?php
  if ( has_wp_user_avatar($user_id) ) {
    echo get_wp_user_avatar($user_id, 96);
  } else {
    echo '<img src="my-alternate-image.jpg" />';
  }
?>`

== Frequently Asked Questions ==

= How do I use WP User Avatar? =

First, choose a theme that has avatar support. In your theme, you have a choice of manually replacing <code>get_avatar</code> with <code>get_wp_user_avatar</code>, or leaving <code>get_avatar</code> as-is. Here are the differences:

= get_wp_user_avatar =

1. Allows you to use the values "original", "large", "medium", or "thumbnail" for your avatar size.
2. Doesn't add a fixed width and height to the image if you use the aforementioned values. This will give you more flexibility to resize the image with CSS.
3. Optionally adds CSS classes "alignleft", "alignright", or "aligncenter" to position your avatar.
4. Shows nothing if the user has no WP User Avatar image.
5. Shows the user's [gravatar.com](http://gravatar.com/) avatar or Default Avatar only if "Show Avatars" is enabled in your Discussion settings.

= get_avatar =

1. Requires you to enable "Show Avatars" in your Discussion settings to show any avatars.
2. Accepts only numeric values for your avatar size.
3. Always adds a fixed width and height to your image. This may cause problems if you use responsive CSS in your theme.
4. Shows the user's [gravatar.com](http://gravatar.com/) avatar or Default Avatar if the user doesn't have a WP User Avatar image. (Choosing "Blank" as your Default Avatar still generates a transparent image file.)
5. Requires no changes to your theme files if you are currently using <code>get_avatar</code>.

[Read more about get_avatar in the WordPress Function Reference](http://codex.wordpress.org/Function_Reference/get_avatar).

= Can I create a custom Default Avatar? =
In your Discussion settings, you can upload your own Default Avatar.

= Can I insert WP User Avatar directly into a post? =

You can use the <code>[avatar]</code> shortcode in your posts. It will detect the author of the post or you can specify an author by username. You can specify a size, alignment, and link, but they are optional. For links, you can link to the original image file, attachment page, or a custom URL.

`[avatar user="admin" size="96" align="left" link="file"]`

Outputs:

`<a href="{fileURL}" class="wp-user-avatar-link wp-user-avatar-file">
  <img src="{imageURL}" width="96" height="96" class="wp-user-avatar wp-user-avatar-96 alignleft" />
</a>`

= Can Contributors or Subscribers choose their own WP User Avatar image? =

Users need <code>upload_files</code> capability to choose their own WP User Avatar image. This means that only Administrators, Editors, and Authors can choose their own WP User Avatar image. Contributors and Subscribers cannot upload images. Administators can choose WP User Avatar images for Contributors and Subscribers.

[Read more about Roles and Capabilities here](http://codex.wordpress.org/Roles_and_Capabilities).

= Will WP User Avatar work with comment author avatars? =

Yes, for registered users. Non-registered comment authors will show their [gravatar.com](http://gravatar.com/) avatars or Default Avatar.

= Will WP User Avatar work with bbPress? =

Yes, but only users that have <code>upload_files</code> capability can choose their own WP User Avatar image.

= Will WP User Avatar work with WordPress Multisite? =

Yes, however, each site has its own avatar settings. If you set a WP User Avatar image on one site, you have to set it again for different sites in your network.

= How can I see which users have an avatar? =

For Administrators, WP User Avatar adds a column with avatar thumbnails to your Users list table. If "Show Avatars" is enabled in your Discussion settings, you will see avatars to the left of each username instead of in a new column.

= What CSS can I use with WP User Avatar? =

WP User Avatar will add the CSS classes "wp-user-avatar" and "wp-user-avatar-{size}" to your image. If you add an alignment, the corresponding alignment class will be added:

`<?php echo get_wp_user_avatar($user_id, 96, 'left'); ?>`

Outputs:

`<img src="{imageURL}" width="96" height="96" class="wp-user-avatar wp-user-avatar-96 alignleft" />`

**Note:** "alignleft", "alignright", and aligncenter" are common WordPress CSS classes, but not every theme supports them. Contact the theme author to add those CSS classes.

If you use the values "original", "large", "medium", or "thumbnail", no width or height will be added to the image. This will give you more flexibility to resize the image with CSS:

`<?php echo get_wp_user_avatar($user_id, 'medium'); ?>`

Outputs:

`<img src="{imageURL}" class="wp-user-avatar wp-user-avatar-medium" />`

**Note:** WordPress adds more CSS classes to the avatar not listed here.

If you use the <code>[avatar]</code> shortcode, WP User Avatar will add the CSS class "wp-user-avatar-link" to the link. It will also add CSS classes based on link type.

* Image File: wp-user-avatar-file
* Attachment: wp-user-avatar-attachment
* Custom URL: wp-user-avatar-custom

`[avatar user="admin" size="96" align="left" link="attachment"]`

Outputs:

`<a href="{attachmentURL}" class="wp-user-avatar-link wp-user-avatar-attachment">
  <img src="{imageURL}" width="96" height="96" class="wp-user-avatar wp-user-avatar-96 alignleft" />
</a>`

= What other functions are available for WP User Avatar? =
* <code>get_wp_user_avatar_src</code>: retrieves just the image URL
* <code>has_wp_user_avatar</code>: checks if the user has a WP User Avatar image
* [See example usage here](http://wordpress.org/extend/plugins/wp-user-avatar/installation/)

== Screenshots ==

1. WP User Avatar lets you upload your own Default Avatar.
2. WP User Avatar adds a field to your edit profile page.
3. After you've chosen a WP User Avatar image, you will see the option to remove it.
4. WP User Avatar adds a button to insert the [avatar] shortcode in the Visual Editor.
5. Options for the [avatar] shortcode.

== Changelog ==

= 1.3 =
* Add: Multisite support
* Bug Fix: Warnings if no user found
* Update: Enable action_show_user_profile for any class using show_user_profile hook

= 1.2.6 =
* Bug Fix: options-discussion.php page doesn't show default avatars

= 1.2.5 =
* Bug Fix: Comment author showing wrong avatar
* Bug Fix: Avatar adds fixed dimensions when non-numeric size is used
* Update: Use local image for default avatar instead of calling image from Gravatar

= 1.2.4 =
* Bug Fix: Show default avatar when user removes custom avatar
* Bug Fix: Default Avatar save setting

= 1.2.3 =
* Bug Fix: Show default avatar when user removes custom avatar
* Bug Fix: Default Avatar save setting

= 1.2.2 =
* Add: Ability for bbPress users to edit avatar on front profile page
* Add: Link options for shortcode
* Bug Fix: Show WP User Avatar only to users with upload_files capability

= 1.2.1 =
* Add: TinyMCE button
* Update: Clean up redundant code
* Update: Compatibility only back to WordPress 3.1

= 1.2 =
* Add: Default Avatar setting

= 1.1.7 =
* Bug Fix: Change update_usermeta to update_user_meta

= 1.1.6 =
* Bug Fix: Image not showing in user profile edit

= 1.1.5a =
* Update: readme.txt

= 1.1.5 =
* Bug Fix: Remove stray curly bracket

= 1.1.4 =
* Bug Fix: Change get_usermeta to get_user_meta
* Bug Fix: Non-object warning when retrieving user ID

= 1.1.3 =
* Bug Fix: Comment author with no e-mail address

= 1.1.2 =
* Remove: Unused variables

= 1.1.1 =
* Bug Fix: Capabilities error in comment avatar

= 1.1 =
* Add: Add filter for get_avatar
* Add: CSS alignment classes
* Add: Replace comment author avatar
* Add: Shortcode
* Update: readme.txt

= 1.0.2 =
* Update: FAQ
* Remove: CSS that hides "Insert into Post"

= 1.0.1 =
* Add: CSS classes to image output

= 1.0 =
* Initial release

== Upgrade Notice ==

= 1.3 =
* New Feature: Multisite support

= 1.2.2 =
* New Features: Link options for shortcode, bbPress integration

= 1.2.1 =
* New Feature: Shortcode insertion button for Visual Editor

= 1.2 =
* New Feature: Default Avatar customization

= 1.1 =
* New Features: [avatar] shortcode, direct replacement of get_avatar() and comment author avatar, more CSS classes
