<?php
/**
 * @package Text_Replace
 * @author Scott Reilly
 * @version 3.1
 */
/*
Plugin Name: Text Replace
Version: 3.1
Plugin URI: http://coffee2code.com/wp-plugins/text-replace/
Author: Scott Reilly
Author URI: http://coffee2code.com
Text Domain: text-replace
Description: Replace text with other text. Handy for creating shortcuts to common, lengthy, or frequently changing text/HTML, or for smilies.

Compatible with WordPress 3.0+, 3.1+, 3.2+.

=>> Read the accompanying readme.txt file for instructions and documentation.
=>> Also, visit the plugin's homepage for additional information and updates.
=>> Or visit: http://wordpress.org/extend/plugins/text-replace/

TODO:
	* Update screenshot for WP 3.2
	* Facilitate multi-line replacement strings

*/

/*
Copyright (c) 2004-2011 by Scott Reilly (aka coffee2code)

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


if ( ! class_exists( 'c2c_TextReplace' ) ) :

require_once( 'c2c-plugin.php' );

class c2c_TextReplace extends C2C_Plugin_023 {

	public static $instance;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		$this->c2c_TextReplace();
	}

	public function c2c_TextReplace() {
		// Be a singleton
		if ( ! is_null( self::$instance ) )
			return;

		$this->C2C_Plugin_023( '3.1', 'text-replace', 'c2c', __FILE__, array() );
		register_activation_hook( __FILE__, array( __CLASS__, 'activation' ) );
		self::$instance = $this;
	}

	/**
	 * Handles activation tasks, such as registering the uninstall hook.
	 *
	 * @since 3.1
	 *
	 * @return void
	 */
	public function activation() {
		register_uninstall_hook( __FILE__, array( __CLASS__, 'uninstall' ) );
	}

	/**
	 * Handles uninstallation tasks, such as deleting plugin options.
	 *
	 * This can be overridden.
	 *
	 * @return void
	 */
	public static function uninstall() {
		delete_option( 'c2c_text_replace' );
	}

	/**
	 * Override the plugin framework's register_filters() to actually actions against filters.
	 *
	 * @return void
	 */
	public function register_filters() {
		$filters = apply_filters( 'c2c_text_replace_filters', array( 'the_content', 'the_excerpt', 'widget_text' ) );
		foreach ( (array) $filters as $filter )
			add_filter( $filter, array( &$this, 'text_replace' ), 2 );

		// Note that the priority must be set high enough to avoid <img> tags inserted by the text replace process from
		// getting omitted as a result of the comment text sanitation process, if you use this plugin for smilies, for instance.
		$options = $this->get_options();
		if ( apply_filters( 'c2c_text_replace_comments', $options['text_replace_comments'] ) ) {
			add_filter( 'get_comment_text', array( &$this, 'text_replace' ), 11 );
			add_filter( 'get_comment_excerpt', array( &$this, 'text_replace' ), 11 );
		}
	}

	/**
	 * Initializes the plugin's configuration and localizable text variables.
	 *
	 * @return void
	 */
	public function load_config() {
		$this->name      = __( 'Text Replace', $this->textdomain );
		$this->menu_name = __( 'Text Replace', $this->textdomain );

		$this->config = array(
			'text_to_replace' => array( 'input' => 'textarea', 'datatype' => 'hash', 'default' => array(
					":wp:" => "<a href='http://wordpress.org'>WordPress</a>",
					":codex:" => "<a href='http://codex.wordpress.org'>WordPress Codex</a>",
					":coffee2code:" => "<a href='http://coffee2code.com' title='coffee2code'>coffee2code</a>"
				), 'allow_html' => true, 'no_wrap' => true, 'input_attributes' => 'rows="15" cols="40"',
				'label' => '', 'help' => ''
			),
			'text_replace_comments' => array( 'input' => 'checkbox', 'default' => false,
					'label' => __( 'Enable text replacement in comments?', $this->textdomain ),
					'help' => ''
			),
			'case_sensitive' => array( 'input' => 'checkbox', 'default' => false,
					'label' => __( 'Case sensitive text replacement?', $this->textdomain ),
					'help' => __( 'If checked, then a replacement for :wp: would also replace :WP:.', $this->textdomain )
			)
		);
	}

	/**
	 * Outputs the text above the setting form
	 *
	 * @return void (Text will be echoed.)
	 */
	public function options_page_description() {
		parent::options_page_description( __( 'Text Replace Settings', $this->textdomain ) );

		echo '<p>' . __( 'Text Replace is a plugin that allows you to replace text with other text in posts, etc. Very handy to create shortcuts to commonly-typed and/or lengthy text/HTML, or for smilies.', $this->textdomain ) . '</p>';
		echo '<div class="c2c-hr">&nbsp;</div>';
		echo '<h3>' . __( 'Shortcuts and text replacements', $this->textdomain ) . '</h3>';
		echo '<p>' . __( 'Define shortcuts and text replacement expansions here. The format should be like this:', $this->textdomain ) . '</p>';
		echo "<blockquote><code>:wp: => &lt;a href='http://wordpress.org'>WordPress&lt;/a></code></blockquote>";
		echo '<p>' . __( 'Where <code>:wp:</code> is the shortcut you intend to use in your posts and the <code>&lt;a href=\'http://wordpress.org\'>WordPress&lt;/a></code> would be what you want the shortcut to be replaced with when the post is shown on your site.', $this->textdomain ) . '</p>';
		echo '<p>' . __( 'Other considerations:', $this->textdomain ) . '</p>';
		echo '<ul class="c2c-plugin-list"><li>';
		echo __( 'List the more specific matches early, to avoid stomping on another of your shortcuts.  For example, if you have both <code>:p</code> and <code>:pout:</code> as shortcuts, put <code>:pout:</code> first; otherwise, the <code>:p</code> will match against all the <code>:pout:</code> in your text.', $this->textdomain );
		echo '</li><li>';
		echo __( 'Be careful not to define text that could match partially when you don\'t want it to:<br />i.e.  <code>Me => Scott</code> would also inadvertently change "Men" to be "Scottn"', $this->textdomain );
		echo '</li><li>';
		echo __( 'If you intend to use this plugin to handle smilies, you should probably disable WordPress\'s default smilie handler.', $this->textdomain );
		echo '</li><li>';
		echo __( 'HTML is allowed.', $this->textdomain );
		echo __( 'Only use quotes it they are actual part of the original or replacement strings.', $this->textdomain );
		echo '</li><li><strong><em>';
		echo __( 'Define only one shortcut per line.', $this->textdomain );
		echo '</em></strong></li><li><strong><em>';
		echo __( 'Shortcuts must not span multiple lines.', $this->textdomain );
		echo '</em></strong></li></ul>';
	}

	/**
	 * Perform text replacements
	 *
	 * @param string $text Text to be processed for text replacements
	 * @return string Text with replacements already processed
	 */
	public function text_replace( $text ) {
		$oldchars = array( "(", ")", "[", "]", "?", ".", ",", "|", "\$", "*", "+", "^", "{", "}" );
		$newchars = array( "\(", "\)", "\[", "\]", "\?", "\.", "\,", "\|", "\\\$", "\*", "\+", "\^", "\{", "\}" );
		$options = $this->get_options();
		$text_to_replace = apply_filters( 'c2c_text_replace', $options['text_to_replace'] );
		$case_sensitive = apply_filters( 'c2c_text_replace_case_sensitive', $options['case_sensitive'] );
		$preg_flags = ($case_sensitive) ? 's' : 'si';
		$text = ' ' . $text . ' ';
		if ( ! empty( $text_to_replace ) ) {
			foreach ( $text_to_replace as $old_text => $new_text ) {
				if ( strpos( $old_text, '<' ) !== false || strpos( $old_text, '>' ) !== false ) {
					$text = str_replace( $old_text, $new_text, $text );
				} else {
					$old_text = str_replace( $oldchars, $newchars, $old_text );
					$text = preg_replace( "|(?!<.*?)$old_text(?![^<>]*?>)|$preg_flags", $new_text, $text );
				}
			}
		}
		return trim( $text );
	} //end text_replace()

} // end c2c_TextReplace

// NOTICE: The 'c2c_text_replace' global is deprecated and will be removed in the plugin's version 3.2.
// Instead, use: c2c_TextReplace::$instance
$GLOBALS['c2c_text_replace'] = new c2c_TextReplace();

endif; // end if !class_exists()

?>