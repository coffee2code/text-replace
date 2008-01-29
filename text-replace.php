<?php
/*
Plugin Name: Text Replace
Version: 1.1
Plugin URI: http://www.coffee2code.com/wp-plugins/
Author: Scott Reilly
Author URI: http://www.coffee2code.com
Description: Replace text with other text in posts, etc.  Very handy to create shortcuts to commonly-typed and/or lengthy text/HTML, or for smilies.

=>> Visit the plugin's homepage for more information and latest updates  <<=

SPECIAL NOTE FOR UPGRADERS:
- If you have used v1.0 or prior of this plugin, you will have to copy your $text_to_replace array contents into the plugin's new option's page field.


INSTALLATION:

1. Download the file http://www.coffee2code.com/wp-plugins/text-replace.zip and unzip it into your 
/wp-content/plugins/ directory.
-OR-
Copy and paste the the code ( http://www.coffee2code.com/wp-plugins/text-replace.phps ) into a file called 
text-replace.php, and put that file into your /wp-content/plugins/ directory.
2. Activate the plugin from your WordPress admin 'Plugins' page.
3. In the Admin section of your site, edit the configuration options for the plugin (notably to define the shortcuts and their replacements) via the
"Text Replace" tab under "Options"
4. Use the shortcut in a post.


NOTES:

This plugin can be utilized to make shortcuts for frequently typed text, but keep these things in mind:

- Your best bet with defining shortcuts is to define something that would never otherwise appear in your text.  For instance, 
bookend the shortcut with colons:
	:wp: => <a href='http://wordpress.org'>WordPress</a>
	:aol: => <a href='http://www.aol.com'>America Online, Inc.</a>
Otherwise, you risk proper but undesired replacements:
	Hi => Hello
Would have the effect of changing "His majesty" to "Hellos majesty".

- List the more specific matches early, to avoid stomping on another of your shortcuts.  For example, if you have both ":p" and ":pout:"
as shortcuts, put ":pout:" first, otherwise, the ":p" will match against all the ":pout:" in your text.

- If you intend to use this plugin to handle smilies, you should probably disable WordPress's default smilie handler.

- This plugin is set to filter the_content, the_excerpt, and if uncommented, get_comment_text and get_comment_excerpt.

- SPECIAL CONSIDERATION: Be aware that the shortcut text that you use in your posts will be stored that way in 
the database (naturally).  While calls to display the posts will see the filtered, text replaced version, 
anything that operates directly on the database will not see the expanded replacement text.  So if you only
ever referred to "America Online" as ":aol:" (where ":aol:" => "<a href='http://www.aol.com'>America Online</a>"),
visitors to your site will see the linked, expanded text due to the text replace, but a database search would
never turn up a match for "America Online".

- However, a benefit of the replacement text not being saved to the database and instead evaluated when the data is being loaded
into a web page is that if the replacement text is modified, all pages making use of the shortcut will henceforth use the updated 
replacement text.

*/

/*
Copyright (c) 2004-2005 by Scott Reilly (aka coffee2code)

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation 
files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, 
modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the 
Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR
IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

//Define the text to be replaced
//Careful not to define text that could match partially when you don't want it to:
//   i.e.  "Me" => "Scott"
//	would also inadvertantly change "Men" to be "Scottn"
	
//$text_to_replace = array(
//	":wp:" => "<a href='http://wordpress.org'>WordPress</a>",
//	":wpwiki:" => "<a href='http://wiki.wordpress.org'>WordPress Wiki</a>",
//	":codex:" => "<a href='http://codex.wordpress.org'>WordPress Codex</a>",
//);

if (is_plugin_page()) :
	c2c_admin_text_replace();
else :

function c2c_text_replace( $text, $case_sensitive=false ) {
	$oldchars = array("(", ")", "[", "]", "?", ".", ",", "|", "\$", "*", "+", "^", "{", "}");
	$newchars = array("\(", "\)", "\[", "\]", "\?", "\.", "\,", "\|", "\\\$", "\*", "\+", "\^", "\{", "\}");
	$options = get_option('c2c_text_replace');
	$text_to_replace = $options['text_to_replace'];
	if (!empty($text_to_replace)) {
		foreach ($text_to_replace as $old_text => $new_text) {
			$old_text = str_replace($oldchars, $newchars, $old_text);
			// Old method for string replacement.
			//$text = preg_replace("|([\s\>]*)(".$old_text.")([\s\<\.,;:\\/\-]*)|imsU" , "$1".$new_text."$3", $text);
			// New method.  WILL match string within string, but WON'T match within tags
			$preg_flags = ($case_sensitive) ? 's' : 'si';
			$text = preg_replace("|(?!<.*?)$old_text(?![^<>]*?>)|$preg_flags", $new_text, $text);
		}
	}
	return $text;
} //end c2c_text_replace()

// Admin interface code

function c2c_admin_add_text_replace() {
	// Add menu under Options:
	$c = add_options_page('Text Replace Options', 'Text Replace', 10, __FILE__);	//, 'c2c_admin_text_replace'
	// Create option in options database if not there already:
	$options = array();
	//$text_to_replace = ;
	$options['text_to_replace'] = array(
		":wp:" => "<a href='http://wordpress.org'>WordPress</a>",
		":wpwiki:" => "<a href='http://wiki.wordpress.org'>WordPress Wiki</a>",
		":codex:" => "<a href='http://codex.wordpress.org'>WordPress Codex</a>",
	);
	add_option('c2c_text_replace', $options, 'Options for the Text Replace plugin by coffee2code');
} //end c2c_admin_add_text_replace()

function c2c_admin_text_replace() {
	// See if user has submitted form
	if ( isset($_POST['submitted']) ) {
		$options = array();
		
		$text_to_replace = $_POST['text_to_replace'];
		if ( !empty($text_to_replace) ) {
			$replacement_array = array();
			foreach (explode("\n", $text_to_replace) AS $line) {
				list($shortcut, $text) = array_map('trim', explode("=>", $line, 2));
				if (!empty($shortcut)) $replacement_array[str_replace('\\', '', $shortcut)] = str_replace('\\', '', $text);
			}
			$options['text_to_replace'] = $replacement_array;
		}
		
		// Remember to put all the other options into the array or they'll get lost!
		update_option('c2c_text_replace', $options);
		echo '<div class="updated"><p>Plugin settings saved.</p></div>';
	}
	
	// Draw the Options page for the plugin.
	$options = get_option('c2c_text_replace');
	$text_to_replace = $options['text_to_replace'];
	$replacements = '';
	foreach ($text_to_replace AS $shortcut => $replacement) {
		$replacements .= "$shortcut => $replacement\n";
	}
	$action_url = $_SERVER[PHP_SELF] . '?page=' . basename(__FILE__);
echo <<<END
	<div class='wrap'>\n
		<h2>Text Replace Plugin Options</h2>\n
		<p>Text Replace is a plugin that allows you to replace text with other text in posts, etc.  
		Very handy to create shortcuts to commonly-typed and/or lengthy text/HTML, or for smilies.</p>
		
<form name="textreplace" action="$action_url" method="post">	
	<fieldset class="option">
		<input type="hidden" name="submitted" value="1" />
		<legend>Shortcuts and text replacements</legened>
		
		<p>Define shortcuts and text replacement expansions here.  The format should be like this:</p>
		
		<blockquote><code>:wp: => &lt;a href='http://wordpress.org'>WordPress&lt;/a></code></blockquote>
		
		<p>Where <code>:wp:</code> is the shortcut you intend to use in your posts, and the <code>&lt;a href='http://wordpress.org'>WordPress&lt;/a></code>
		would be what you want the shortcut to be replaced with when the post is shown on your site.</p>
		</p>
		
		<p>Other considerations:</p>
		
		<ul>
		<li>List the more specific matches early, to avoid stomping on another of your shortcuts.  For example, if you 
		have both <code>:p</code> and <code>:pout:</code> as shortcuts, put <code>:pout:</code> first; otherwise, the 
		<code>:p</code> will match against all the <code>:pout:</code> in your text.</li>
		<li>If you intend to use this plugin to handle smilies, you should probably disable WordPress's default smilie handler.</li>
		<li><strong><em>Define only one shortcut per line.</em></strong></li>
		<li><strong><em>Shortcuts must not span multiple lines.</em></strong></li>
		</ul>
		
		<textarea name="text_to_replace" id="text_to_replace" style="width: 98%; font-family: \"Courier New\", Courier, mono;" rows="15" cols="40">$replacements</textarea>
	</fieldset>
	<div class="submit"><input type="submit" name="Submit" value="Save changes &raquo;" /></div>
</form>
	</div>
END;
} //end c2c_admin_text_replace()
add_action('admin_menu', 'c2c_admin_add_text_replace');
// Hack due to WP1.5
//add_action('option_gmt_offset', 'c2c_admin_text_replace');

add_filter('the_content', 'c2c_text_replace', 2);
add_filter('the_excerpt', 'c2c_text_replace', 2);
// Uncomment this next line if you wish to allow users to be able to use text-replacement.  Note that the
//	priority must be set high enough to avoid <img> tags inserted by the text replace process from getting omitted 
//	as a result of the comment text sanitation process, if you use this plugin for smilies, for instance.
//add_filter('get_comment_text', 'c2c_text_replace', 10);
//add_filter('get_comment_excerpt', 'c2c_text_replace', 10);

endif;

?>