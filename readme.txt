=== Text Replace ===
Contributors: coffee2code
Donate link: http://coffee2code.com/donate
Tags: text, replace, shortcuts, post, post content, coffee2code
Requires at least: 2.6
Tested up to: 2.8.1
Stable tag: 2.5
Version: 2.5

Replace text with other text in posts, pages, etc.  Very handy to create shortcuts to commonly-typed and/or lengthy text/HTML, or for smilies.

== Description ==

Replace text with other text in posts, pages, etc.  Very handy to create shortcuts to commonly-typed and/or lengthy text/HTML, or for smilies.

This plugin can be utilized to make shortcuts for frequently typed text, but keep these things in mind:

* Your best bet with defining shortcuts is to define something that would never otherwise appear in your text.  For instance, bookend the shortcut with colons:
	`:wp: => <a href='http://wordpress.org'>WordPress</a>`
	`:aol: => <a href='http://www.aol.com'>America Online, Inc.</a>`
Otherwise, you risk proper but undesired replacements:
	`Hi => Hello`
Would have the effect of changing "His majesty" to "Hellos majesty".

* List the more specific matches early, to avoid stomping on another of your shortcuts.  For example, if you have both 
`:p` and `:pout:` as shortcuts, put `:pout:` first, otherwise, the `:p` will match against all the `:pout:` in your text.

* If you intend to use this plugin to handle smilies, you should probably disable WordPress's default smilie handler.

* This plugin is set to filter the_content, the_excerpt, and optionally, get_comment_text and get_comment_excerpt.

* **SPECIAL CONSIDERATION:** Be aware that the shortcut text that you use in your posts will be stored that way in the database (naturally).  While calls to display the posts will see the filtered, text replaced version, anything that operates directly on the database will not see the expanded replacement text.  So if you only ever referred to "America Online" as ":aol:" (where ":aol:" => "<a href='http://www.aol.com'>America Online</a>"), visitors to your site will see the linked, expanded text due to the text replace, but a database search would never turn up a match for "America Online".

* However, a benefit of the replacement text not being saved to the database and instead evaluated when the data is being loaded into a web page is that if the replacement text is modified, all pages making use of the shortcut will henceforth use the updated replacement text.

== Installation ==

1. Unzip `text-replace.zip` inside the `/wp-content/plugins/` directory, or upload `text-replace.php` there
1. Activate the plugin through the 'Plugins' admin menu in WordPress
1. (optional) Go to the `Settings` -> `Text Replace` admin options page and customize the options (notably to define the shortcuts and their replacements).

== Frequently Asked Questions ==

= Does this plugin modify the post content in the database? =

No.  The plugin filters post content on-the-fly.

= Will this work for posts I wrote prior to installing this plugin? =

Yes, if they include strings that you've now defined as shortcuts.

= What post fields get handled by this plugin? =

The plugin filters the post content and post excerpt fields, and optionally comments and comment excerpts.

= Is the plugin case sensitive? =

Yes.

= I use :wp: all the time as a shortcut for WordPress, but when I search posts for the term "WordPress", I don't see posts where I used the shortcut; why not? =

Search engines will see those posts since they only ever see the posts after the shortcuts have been replaced.  However, WordPress's search function searches the database directly, where only the shortcut exists, so WordPress doesn't know about the replacement text you've defined.

== Screenshots ==

1. A screenshot of the admin options page for the plugin, where you define the terms/phrases/shortcuts and their related replacement text

== Changelog ==

= 2.5 =
* Fixed path-related issue for options page
* Added 'Settings' link for plugin on admin Plugins page
* Changed permission check
* More localization-related work
* Minor code reformatting (spacing)
* Removed hardcoded path
* Updated copyright
* Noted compatibility through 2.8+
* Dropped compatibility with versions of WP older than 2.6

= 2.0 =
* Handled case where shortcut appears at the very beginning or ending of the text
* Created its own class to encapsulate plugin functionality
* Added an admin options page
* Added option text_replace_comments (defaulted to false) to control whether text replacements should occur in comments
* Tweaked description and installation instructions
* Added compatibility note
* Updated copyright date
* Added readme.txt and screenshot
* Tested compatibility with WP 2.3.3 and 2.5

= 1.0 =
* Moved the array $text_to_replace outside of the function and into global space
* Renamed function from text_replace() to c2c_text_replace()
* Added installation instruction and notes to plugin file
* Verified that plugin works for WordPress v1.2+ and v1.5+

= 0.92 =
* Added optional argument $case_sensitive (defaulted to "false")
* Changed from BSD-new to MIT license

= 0.91 =
* Removed the need to escape special characters used in the shortcut text. Now "?", "(", ")", "[", "]", etc can be used without problems. However, the backspace character "\" should be avoided.
* Employed a new pattern for matching and replacing text. A huge advantage of this new matching pattern is that it won't match text in a tag (text appearing between "<" and ">").

= 0.9 =
* Initial release