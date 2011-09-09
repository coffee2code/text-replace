=== Text Replace ===
Contributors: coffee2code
Donate link: http://coffee2code.com/donate
Tags: text, replace, shortcut, shortcuts, post, post content, coffee2code
Requires at least: 3.0
Tested up to: 3.2
Stable tag: 3.1.1
Version: 3.1.1

Replace text with other text. Handy for creating shortcuts to common, lengthy, or frequently changing text/HTML, or for smilies.

== Description ==

Replace text with other text. Handy for creating shortcuts to common, lengthy, or frequently changing text/HTML, or for smilies.

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

* This plugin is set to filter the_content, the_excerpt, widget_text, and optionally, get_comment_text and get_comment_excerpt.  the filter 'c2c_text_replace_filters' can be used to add or modify the list of filters affected.

* **SPECIAL CONSIDERATION:** Be aware that the shortcut text that you use in your posts will be stored that way in the database (naturally).  While calls to display the posts will see the filtered, text replaced version, anything that operates directly on the database will not see the expanded replacement text.  So if you only ever referred to "America Online" as ":aol:" (where ":aol:" => "<a href='http://www.aol.com'>America Online</a>"), visitors to your site will see the linked, expanded text due to the text replace, but a database search would never turn up a match for "America Online".

* However, a benefit of the replacement text not being saved to the database and instead evaluated when the data is being loaded into a web page is that if the replacement text is modified, all pages making use of the shortcut will henceforth use the updated replacement text.

Links: [Plugin Homepage](http://coffee2code.com/wp-plugins/text-replace/) | [Author Homepage](http://coffee2code.com)


== Installation ==

1. Unzip `text-replace.zip` inside the `/wp-content/plugins/` directory (or install via the built-in WordPress plugin installer)
1. Activate the plugin through the 'Plugins' admin menu in WordPress
1. (optional) Go to the `Settings` -> `Text Replace` admin options page and customize the options (notably to define the shortcuts and their replacements).


== Frequently Asked Questions ==

= Does this plugin modify the post content in the database? =

No.  The plugin filters post content on-the-fly.

= Will this work for posts I wrote prior to installing this plugin? =

Yes, if they include strings that you've now defined as shortcuts.

= What post fields get handled by this plugin? =

By default, the plugin filters the post content, post excerpt fields, widget text, and optionally comments and comment excerpts.  You can use the 'c2c_text_replace_filters' filter to modify that behavior (see Filters section).

= How can I get text replacements to apply for post titles (or something not text-replaced by default)? =

You can add to the list of filters that get text replacements using something like this (added to your theme's functions.php file, for instance):

`add_filter( 'c2c_text_replace_filters', 'more_text_replacements' );
function more_text_replacements( $filters ) {
	$filters[] = 'the_title'; // Here you could put in the name of any filter you want
	return $filters;
}`

= Is the plugin case sensitive? =

By default, yes.  There is a setting you can change to make it case insensitive.  Or you can use the 'c2c_text_replace_case_sensitive' filter (see Filters section).

= I use :wp: all the time as a shortcut for WordPress, but when I search posts for the term "WordPress", I don't see posts where I used the shortcut; why not? =

Rest assured search engines will see those posts since they only ever see the posts after the shortcuts have been replaced.  However, WordPress's search function searches the database directly, where only the shortcut exists, so WordPress doesn't know about the replacement text you've defined.


== Screenshots ==

1. A screenshot of the admin options page for the plugin, where you define the terms/phrases/shortcuts and their related replacement text


== Filters ==

The plugin exposes four filters for hooking.  Typically, the code to utilize these hooks would go inside your active theme's functions.php file.

= c2c_text_replace_filters (filter) =

The 'c2c_text_replace_filters' hook allows you to customize what hooks get text replacement applied to them.

Arguments:

* $hooks (array): Array of hooks that will be text replaced.

Example:

`// Enable text replacement for post/page titles
add_filter( 'c2c_text_replace_filters', 'more_text_replacements' );
function more_text_replacements( $filters ) {
	$filters[] = 'the_title'; // Here you could put in the name of any filter you want
	return $filters;
}`

= c2c_text_replace_comments (filter) =

The 'c2c_text_replace_comments' hook allows you to customize or override the setting indicating if text replacement should be enabled in comments.

Arguments:

* $state (bool): Either true or false indicating if text replacement is enabled for comments.  This will be the value set via the plugin's settings page.

Example:

`// Prevent text replacement from ever being enabled.
add_filter( 'c2c_text_replace_comments', '__return_false' );`

= c2c_text_replace (filter) =

The 'c2c_text_replace' hook allows you to customize or override the setting defining all of the text replacement shortcuts and their replacements.

Arguments:

* $text_replacement_array (array): Array of text replacement shortcuts and their replacements.  This will be the value set via the plugin's settings page.

Example:

`// Add dynamic shortcuts
add_filter( 'c2c_text_replace', 'my_text_replacements' );
function my_text_replacements( $replacements ) {
	// Add replacement
	$replacements[':matt:'] => 'Matt Mullenweg';
	// Unset a replacement that we never want defined
	if ( isset( $replacements[':wp:'] ) )
		unset( $replacements[':wp:'] );
	// Important!
	return $replacements;
}`

= c2c_text_replace_case_sensitive (filter) =

The 'c2c_text_replace_case_sensitive' hook allows you to customize or override the setting indicating if text replacement should be case sensitive.

Arguments:

* $state (bool): Either true or false indicating if text replacement is case sensitive.  This will be the value set via the plugin's settings page.

Example:

`// Prevent text replacement from ever being case sensitive.
add_filter( 'c2c_text_replace_case_sensitive', '__return_false' );`


== Changelog ==

= 3.1.1 =
* Fix cross-browser (namely IE) handling of non-wrapping textarea text (flat out can't use CSS for it)
* Update plugin framework to version 028
* Change parent constructor invocation
* Create 'lang' subdirectory and move .pot file into it
* Regenerate .pot

= 3.1 =
* Fix to properly register activation and uninstall hooks
* Update plugin framework to version v023
* Save a static version of itself in class variable $instance
* Deprecate use of global variable $c2c_text_replace to store instance
* Add __construct() and activation()
* Note compatibility through WP 3.2+
* Drop compatibility with version of WP older than 3.0
* Minor code formatting changes (spacing)
* Fix plugin homepage and author links in description in readme.txt

= 3.0.2 =
* Update plugin framework to version 021
* Delete plugin options upon uninstallation
* Explicitly declare all class functions public static
* Note compatibility through WP 3.1+
* Update copyright date (2011)

= 3.0.1 =
* Update plugin framework to version 018
* Fix so that textarea displays vertical scrollbar when lines exceed visible textarea space

= 3.0 =
* Re-implementation by extending C2C_Plugin_012, which among other things adds support for:
    * Reset of options to default values
    * Better sanitization of input values
    * Offload of core/basic functionality to generic plugin framework
    * Additional hooks for various stages/places of plugin operation
    * Easier localization support
* Full localization support
* Allow for replacement of tags, not just text wrapped by tags
* Disable auto-wrapping of text in the textarea input field for replacements
* Support localization of strings
* Add option to indicate if text replacement should be case sensitive. Default is true.
* NOTE: The plugin is now by default case sensitive when searching for potential replacements
* For text_replace(), remove 'case_sensitive' argument
* Allow filtering of text replacements via 'c2c_text_replace' filter
* Allow filtering of hooks that get text replaced via 'c2c_text_replace_filters' filter
* Allow filtering/overriding of text_replace_comments option via 'c2c_text_replace_comments' filter
* Allow filtering/overriding of case_sensitive option via 'c2c_text_replace_case_sensitive' filter
* Filter 'widget_text' for text replacement
* Rename class from 'TextReplace' to 'c2c_TextReplace'
* Assign object instance to global variable, $c2c_text_replace, to allow for external manipulation
* Remove docs from top of plugin file (all that and more are in readme.txt)
* Change description
* Update readme
* Minor code reformatting (spacing)
* Add Filters and Upgrade Notice sections to readme.txt
* Add .pot file
* Update screenshot
* Add PHPDoc documentation
* Add package info to top of file
* Update copyright date
* Remove trailing whitespace

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


== Upgrade Notice ==

= 3.1.1 =
Bugfix release: fixed bug with cross-browser (mainly, IE) handling of non-wrapping textarea text; updated plugin framework; regenerated .pot file and put it into 'lang' subdirectory.

= 3.1 =
Recommended update. Highlights: updated compatibility through WP 3.2; dropped compatibility with version of WP older than 3.0; updated plugin framework, bugfix; and more.

= 3.0.2 =
Trivial update: updated plugin framework to v021; noted compatibility with WP 3.1+ and updated copyright date.

= 3.0 =
Significant and recommended update. Highlights: re-implementation; added more settings and hooks for customization; allow replacing HTML; allow case insensitivity; disable autowrap in textarea; misc improvements; verified WP 3.0 compatibility; dropped compatibility with WP older than 2.8.