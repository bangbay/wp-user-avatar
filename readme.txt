=== WP User Avatar ===

Contributors: bangbay
Donate link: http://siboliban.org/donate
Tags: author image, author photo, author avatar, avatar, profile avatar, profile image, profile photo, user avatar, user image, user photo
Requires at least: 3.0
Tested up to: 3.5
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Use any image in your WordPress Media Libary as a custom user avatar.

== Description ==

WordPress currently only allows you to use custom avatars that are uploaded through gravatar.com. WP User Avatar enables you to use any photo uploaded into your Media Library as an avatar. This means any image you've uploaded for a page or post is available for you to use as an avatar. No extra folders or image editing functions are necessary.

To use WP User Avatar in your theme, replace anywhere you use the function get_avatar() with get_wp_user_avatar(). get_wp_user_avatar() accepts the same fields as get_avatar() with added functionality.

This plugin uses the new Media Uploader introduced in WordPress 3.5, but is also backwards-compatible to WordPress 3.0.

== Installation ==

1. Download, install, and activate the WP User Avatar plugin.
2. Choose a profile to edit.
3. In edit mode, click "Edit WP User Avatar".
4. Choose an image, then click "Set WP User Avatar".
5. Click "Update Profile".
6. In your theme, use the function get_wp_user_avatar() in place of get_avatar().

== Frequently Asked Questions ==

= How do I use WP User Avatar? =

In your theme, replace get_avatar with get_wp_user_avatar().

**Examples:**

Within The Loop, you may be using:

`<?php echo get_avatar(get_the_author_meta('ID'), 96); ?>`

Replace this function with:

`<?php echo get_wp_user_avatar(get_the_author_meta('ID'), 96); ?>`

You can also use the values "original", "large", "medium", and "thumbnail" for your avatar size:

`<?php echo get_wp_user_avatar(get_the_author_meta('ID'), 'medium'); ?>`

On an author page outside of The Loop, you may be using:

`<?php $user = get_user_by('slug', $author_name); echo get_avatar($user->ID, 96); ?>`

Replace this function with:

`<?php $user = get_user_by('slug', $author_name); echo get_wp_user_avatar($user->ID, 96); ?>`

If you leave the options blank, WP User Avatar will detect whether you're inside or outside The Loop and return the correct avatar in the default 96x96 size:

`<?php echo get_wp_user_avatar(); ?>`

get_wp_user_avatar() will also fall back to get_avatar() if no WP User Avatar image is set. For this to work, "Show Avatars" must be checked in your Discussion settings.

== Screenshots ==

1. See thumbnails of WP User Avatar in the Users section.
2. WP User Avatar adds a field to your profile in edit mode.

== Changelog ==

= 1.0 =
* Initial release

== Upgrade Notice ==
