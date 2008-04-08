<?php
/*
Plugin Name: Text Replace
Version: 2.0
Plugin URI: http://coffee2code.com/wp-plugins/text-replace
Author: Scott Reilly
Author URI: http://coffee2code.com
Description: Replace text with other text in posts, etc.  Very handy to create shortcuts to commonly-typed and/or lengthy text/HTML, or for smilies.

This plugin can be utilized to make shortcuts for frequently typed text, but keep these things in mind:

- Your best bet with defining shortcuts is to define something that would never otherwise appear in your text.  For instance, 
bookend the shortcut with colons:
	:wp: => <a href='http://wordpress.org'>WordPress</a>
	:aol: => <a href='http://www.aol.com'>America Online, Inc.</a>
Otherwise, you risk proper but undesired replacements:
	Hi => Hello
Would have the effect of changing "His majesty" to "Hellos majesty".

- List the more specific matches early, to avoid stomping on another of your shortcuts.  For example, if you have both 
":p" and ":pout:" as shortcuts, put ":pout:" first, otherwise, the ":p" will match against all the ":pout:" in your text.

- If you intend to use this plugin to handle smilies, you should probably disable WordPress's default smilie handler.

- This plugin is set to filter the_content, the_excerpt, and optionally, get_comment_text and get_comment_excerpt.

- SPECIAL CONSIDERATION: Be aware that the shortcut text that you use in your posts will be stored that way in 
the database (naturally).  While calls to display the posts will see the filtered, text replaced version, 
anything that operates directly on the database will not see the expanded replacement text.  So if you only
ever referred to "America Online" as ":aol:" (where ":aol:" => "<a href='http://www.aol.com'>America Online</a>"),
visitors to your site will see the linked, expanded text due to the text replace, but a database search would
never turn up a match for "America Online".

- However, a benefit of the replacement text not being saved to the database and instead evaluated when the data is being
loaded into a web page is that if the replacement text is modified, all pages making use of the shortcut will henceforth 
use the updated replacement text.

- SPECIAL NOTE FOR UPGRADERS: If you have used v1.0 or prior of this plugin, you will have to copy your $text_to_replace
array contents into the plugin's new option's page field.

Compatible with WordPress 2.2+, 2.3+, and 2.5.

=>> Read the accompanying readme.txt file for more information.  Also, visit the plugin's homepage
=>> for more information and the latest updates

Installation:

1. Download the file http://coffee2code.com/wp-plugins/text-replace.zip and unzip it into your 
/wp-content/plugins/ directory.
2. Activate the plugin through the 'Plugins' admin menu in WordPress
3. Go to the new Options -> Text Replace admin options page.  Optionally customize the options.
(For WordPress 2.5 this would be Settings -> Text Replace)
4. Start using the shortcuts in posts.  (Also applies to shortcuts already defined in older posts as well)

*/

/*
Copyright (c) 2004-2008 by Scott Reilly (aka coffee2code)

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

if ( !class_exists('TextReplace') ) :

class TextReplace {
	var $admin_options_name = 'c2c_text_replace';
	var $nonce_field = 'update-text_replace';
	var $show_admin = true;	// Change this to false if you don't want the plugin's admin page shown.
	var $config = array();
	var $options = array(); // Don't use this directly

	function TextReplace() {
		$this->config = array(
			// input can be 'checkbox', 'text', 'textarea', 'hidden', or 'none'
			'text_to_replace' => array('input' => 'textarea', 'datatype' => 'hash', 'default' => array(
					":wp:" => "<a href='http://wordpress.org'>WordPress</a>",
					":codex:" => "<a href='http://codex.wordpress.org'>WordPress Codex</a>",
					":coffee2code:" => "<a href='http://coffee2code.com' title='coffee2code'>coffee2code</a>"
				), 'label' => '',
				'help' => '',
				'input_attributes' => 'style="width: 98%; font-family: \"Courier New\", Courier, mono;" rows="15" cols="40"'
			),
			'text_replace_comments' => array('input' => 'checkbox', 'default' => false,
					'label' => 'Enable text replacement in comments?',
					'help' => '')
		);

		add_action('admin_menu', array(&$this, 'admin_menu'));		

		add_filter('the_content', array(&$this, 'text_replace'), 2);
		add_filter('the_excerpt', array(&$this, 'text_replace'), 2);
		// Note that the priority must be set high enough to avoid <img> tags inserted by the text replace process from 
		// getting omitted as a result of the comment text sanitation process, if you use this plugin for smilies, for instance.
		$options = $this->get_options();
		if ( $options['text_replace_comments'] ) {
			add_filter('get_comment_text', array(&$this, 'text_replace'), 11);
			add_filter('get_comment_excerpt', array(&$this, 'text_replace'), 11);
		}
	}

	function install() {
		$this->options = $this->get_options();
		update_option($this->admin_options_name, $this->options);
	}

	function admin_menu() {
		if ( $this->show_admin )
			add_options_page('Text Replace', 'Text Replace', 9, basename(__FILE__), array(&$this, 'options_page'));
	}

	function get_options() {
		if ( !empty($this->options)) return $this->options;
		// Derive options from the config
		$options = array();
		foreach (array_keys($this->config) as $opt) {
			$options[$opt] = $this->config[$opt]['default'];
		}
        $existing_options = get_option($this->admin_options_name);
        if (!empty($existing_options)) {
            foreach ($existing_options as $key => $value)
                $options[$key] = $value;
        }            
		$this->options = $options;
        return $options;
	}

	function options_page() {
		$options = $this->get_options();
		// See if user has submitted form
		if ( isset($_POST['submitted']) ) {
			check_admin_referer($this->nonce_field);

			foreach (array_keys($options) AS $opt) {
				$options[$opt] = $_POST[$opt];
				if (($this->config[$opt]['input'] == 'checkbox') && !$options[$opt])
					$options[$opt] = 0;
				if ($this->config[$opt]['datatype'] == 'array')
					$options[$opt] = explode(',', str_replace(array(', ', ' ', ','), ',', $options[$opt]));
				elseif ($this->config[$opt]['datatype'] == 'hash') {
					if ( !empty($options[$opt]) ) {
						$new_values = array();
						foreach (explode("\n", $options[$opt]) AS $line) {
							list($shortcut, $text) = array_map('trim', explode("=>", $line, 2));
							if (!empty($shortcut)) $new_values[str_replace('\\', '', $shortcut)] = str_replace('\\', '', $text);
						}
						$options[$opt] = $new_values;
					}
				}
			}
			// Remember to put all the other options into the array or they'll get lost!
			update_option($this->admin_options_name, $options);

			echo "<div class='updated'><p>Plugin settings saved.</p></div>";
		}

		$action_url = $_SERVER[PHP_SELF] . '?page=' . basename(__FILE__);

		echo <<<END
		<div class='wrap'>
			<h2>Text Replace Plugin Options</h2>
			<p>Text Replace is a plugin that allows you to replace text with other text in posts, etc.  
			Very handy to create shortcuts to commonly-typed and/or lengthy text/HTML, or for smilies.</p>
						
			<form name="text_replace" action="$action_url" method="post">	

			<h3>Shortcuts and text replacements</h3>

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
			<li>Be careful not to define text that could match partially when you don't want it to:<br />
			i.e.  <code>Me => Scott</code> would also inadvertantly change "Men" to be "Scottn"</li>
			<li>If you intend to use this plugin to handle smilies, you should probably disable WordPress's default smilie handler.</li>
			<li><strong><em>Define only one shortcut per line.</em></strong></li>
			<li><strong><em>Shortcuts must not span multiple lines (auto-wordwrapping in the textarea is fine).</em></strong></li>
			</ul>

END;
				wp_nonce_field($this->nonce_field);
		echo '<table width="100%" cellspacing="2" cellpadding="5" class="optiontable editform form-table">';
				foreach (array_keys($options) as $opt) {
					$input = $this->config[$opt]['input'];
					if ($input == 'none') continue;
					$label = $this->config[$opt]['label'];
					$value = $options[$opt];
					if ($input == 'checkbox') {
						$checked = ($value == 1) ? 'checked=checked ' : '';
						$value = 1;
					} else {
						$checked = '';
					};
					if ($this->config[$opt]['datatype'] == 'array')
						$value = implode(', ', $value);
					elseif ($this->config[$opt]['datatype'] == 'hash') {
						$new_value = '';
						foreach ($value AS $shortcut => $replacement) {
							$new_value .= "$shortcut => $replacement\n";
						}
						$value = $new_value;
					}
					echo "<tr valign='top'>";
					if ($this->config[$opt]['input'] == 'textarea') {
						echo "<td colspan='2'>";
						if ($label) echo "<strong>$label</strong><br />";
						echo "<textarea name='$opt' id='$opt' {$this->config[$opt]['input_attributes']}>" . $value . '</textarea>';
					} else {
						echo "<th scope='row'>$label</th><td>";
						echo "<input name='$opt' type='$input' id='$opt' value='$value' $checked {$this->config[$opt]['input_attributes']} />";
					}
					if ($this->config[$opt]['help']) {
						echo "<br /><span style='color:#777; font-size:x-small;'>";
						echo $this->config[$opt]['help'];
						echo "</span>";
					}
					echo "</td></tr>";
				}
		echo <<<END
			</table>
			<input type="hidden" name="submitted" value="1" />
			<div class="submit"><input type="submit" name="Submit" value="Save Changes" /></div>
		</form>
			</div>
END;
		$logo = get_option('siteurl') . '/wp-content/plugins/' . basename($_GET['page'], '.php') . '/c2c_minilogo.png';
		echo <<<END
		<style type="text/css">
			#c2c {
				text-align:center;
				color:#888;
				background-color:#ffffef;
				padding:5px 0 0;
				margin-top:12px;
				border-style:solid;
				border-color:#dadada;
				border-width:1px 0;
			}
			#c2c div {
				margin:0 auto;
				padding:5px 40px 0 0;
				width:45%;
				min-height:40px;
				background:url('$logo') no-repeat top right;
			}
			#c2c span {
				display:block;
				font-size:x-small;
			}
		</style>
		<div id='c2c' class='wrap'>
			<div>
			This plugin brought to you by <a href="http://coffee2code.com" title="coffee2code.com">Scott Reilly, aka coffee2code</a>.
			<span><a href="http://coffee2code.com/donate" title="Please consider a donation">Did you find this plugin useful?</a></span>
			</div>
		</div>
END;
	}

	function text_replace( $text, $case_sensitive=false ) {
		$oldchars = array("(", ")", "[", "]", "?", ".", ",", "|", "\$", "*", "+", "^", "{", "}");
		$newchars = array("\(", "\)", "\[", "\]", "\?", "\.", "\,", "\|", "\\\$", "\*", "\+", "\^", "\{", "\}");
		$options = $this->get_options();
		$text_to_replace = $options['text_to_replace'];
		$text = ' ' . $text . ' ';
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
		return trim($text);
	} //end text_replace()

} // end TextReplace

endif; // end if !class_exists()
if ( class_exists('TextReplace') ) :
	// Get the ball rolling
	$text_replace = new TextReplace();
	// Actions and filters
	if (isset($text_replace)) {
		register_activation_hook( __FILE__, array(&$text_replace, 'install') );
	}
endif;

?>