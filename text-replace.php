<?php
/**
 * @package Text_Replace
 * @author Scott Reilly
 * @version 3.5
 */
/*
Plugin Name: Text Replace
Version: 3.5
Plugin URI: http://coffee2code.com/wp-plugins/text-replace/
Author: Scott Reilly
Author URI: http://coffee2code.com/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: text-replace
Domain Path: /lang/
Description: Replace text with other text. Handy for creating shortcuts to common, lengthy, or frequently changing text/HTML, or for smilies.

Compatible with WordPress 3.6+ through 3.8+.

=>> Read the accompanying readme.txt file for instructions and documentation.
=>> Also, visit the plugin's homepage for additional information and updates.
=>> Or visit: http://wordpress.org/plugins/text-replace/

TODO:
	* Facilitate multi-line replacement strings
	* Shortcode and template tag to display listing of all supported text hovers (filterable)
*/

/*
	Copyright (c) 2004-2014 by Scott Reilly (aka coffee2code)

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

defined( 'ABSPATH' ) or die();

if ( ! class_exists( 'c2c_TextReplace' ) ) :

require_once( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'c2c-plugin.php' );

final class c2c_TextReplace extends C2C_Plugin_037 {

	/**
	 * @var c2c_TextReplace The one true instance
	 */
	private static $instance;

	/**
	 * Get singleton instance.
	 *
	 * @since 3.5
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) )
			self::$instance = new self();

		return self::$instance;
	}

	/**
	 * Constructor
	 */
	protected function __construct() {
		parent::__construct( '3.5', 'text-replace', 'c2c', __FILE__, array() );
		register_activation_hook( __FILE__, array( __CLASS__, 'activation' ) );

		return self::$instance = $this;
	}

	/**
	 * Handles activation tasks, such as registering the uninstall hook.
	 *
	 * @since 3.1
	 */
	public function activation() {
		register_uninstall_hook( __FILE__, array( __CLASS__, 'uninstall' ) );
	}

	/**
	 * Handles uninstallation tasks, such as deleting plugin options.
	 */
	public static function uninstall() {
		delete_option( 'c2c_text_replace' );
	}

	/**
	 * Handle plugin updates.
	 *
	 * @since 3.2.1
	 *
	 * @param string $old_version The version number of the old version of
	 *        the plugin. '0.0' indicates no version previously stored
	 * @param array $options Array of all plugin options
	 */
	protected function handle_plugin_upgrade( $old_version, $options ) {
		if ( version_compare( $old_version, '3.2.1', '<' ) ) {
			// Plugin got upgraded from a version earlier than 3.2.1
			// Logic was inverted for case_sensitive.
			$options['case_sensitive'] = ! $options['case_sensitive'];
		}
		return $options; // Important!
	}

	/**
	 * Initializes the plugin's configuration and localizable text variables.
	 */
	protected function load_config() {
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
			'replace_once' => array( 'input' => 'checkbox', 'default' => false,
				'label' => __( 'Only text replace once per term per post?', $this->textdomain ),
				'help' => __( 'If checked, then each term will only be replaced the first time it appears in a post.', $this->textdomain )
			),
			'case_sensitive' => array( 'input' => 'checkbox', 'default' => true,
					'label' => __( 'Case sensitive text replacement?', $this->textdomain ),
					'help' => __( 'If unchecked, then a replacement for :wp: would also replace :WP:.', $this->textdomain )
			)
		);
	}

	/**
	 * Override the plugin framework's register_filters() to actually register actions against filters.
	 */
	public function register_filters() {
		$filters = apply_filters( 'c2c_text_replace_filters', array( 'the_content', 'the_excerpt', 'widget_text' ) );
		foreach ( (array) $filters as $filter ) {
			add_filter( $filter, array( $this, 'text_replace' ), 2 );
		}

		// Note that the priority must be set high enough to avoid <img> tags inserted by the text replace process from
		// getting omitted as a result of the comment text sanitation process, if you use this plugin for smilies, for instance.
		add_filter( 'get_comment_text',    array( $this, 'text_replace_comment_text' ), 11 );
		add_filter( 'get_comment_excerpt', array( $this, 'text_replace_comment_text' ), 11 );
	}

	/**
	 * Outputs the text above the setting form
	 *
	 * @param string $localized_heading_text (optional) Localized page heading text.
	 * @return void (Text will be echoed.)
	 */
	public function options_page_description( $localized_heading_text = '' ) {
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
		echo __( 'Text inside of HTML tags (such as tag names and attributes) will not be matched. So, for example, you can\'t expect the :mycss: shortcut to work in: &lt;a href="" :mycss:&gt;text&lt;/a&gt;.', $this->textdomain );
		echo '</li><li>';
		echo __( 'HTML is allowed.', $this->textdomain );
		echo '</li><li>';
		echo __( 'Only use quotes it they are actual part of the original or replacement strings.', $this->textdomain );
		echo '</li><li><strong><em>';
		echo __( 'Define only one shortcut per line.', $this->textdomain );
		echo '</em></strong></li><li><strong><em>';
		echo __( 'Shortcuts must not span multiple lines.', $this->textdomain );
		echo '</em></strong></li></ul>';
	}

	/**
	 * Text replaces comment text if enabled.
	 *
	 * @since 3.5
	 *
	 * @param string $text The comment text
	 * @return string
	 */
	public function text_replace_comment_text( $text ) {
		$options = $this->get_options();

		if ( apply_filters( 'c2c_text_replace_comments', $options['text_replace_comments'] ) ) {
			$text = $this->text_replace( $text );
		}

		return $text;
	}

	/**
	 * Perform text replacements
	 *
	 * @param string $text Text to be processed for text replacements
	 * @return string Text with replacements already processed
	 */
	public function text_replace( $text ) {
		$options         = $this->get_options();
		$text_to_replace = apply_filters( 'c2c_text_replace',                $options['text_to_replace'] );
		$case_sensitive  = apply_filters( 'c2c_text_replace_case_sensitive', $options['case_sensitive'] ) === true;
		$limit           = apply_filters( 'c2c_text_replace_once',           $options['replace_once'] ) === true ? '1' : '-1';
		$preg_flags      = $case_sensitive ? 's' : 'si';

		$text = ' ' . $text . ' ';

		foreach ( $text_to_replace as $old_text => $new_text ) {

			if ( strpos( $old_text, '<' ) !== false || strpos( $old_text, '>' ) !== false ) {
				// If only doing one replacement, need to handle specially since there is
				// no built-in, non-preg_replace method to do a single replacement.
				if ( '1' === $limit ) {
					$pos = $case_sensitive ? strpos( $text, $old_text ) : stripos( $text, $old_text );
					if ( $pos !== false ) {
						$text = substr_replace( $text, $new_text, $pos, strlen( $old_text ) );
					}
				} else {
					if ( $case_sensitive ) {
						$text = str_replace( $old_text, $new_text, $text );
					} else {
						$text = str_ireplace( $old_text, $new_text, $text );
					}
				}
			} else {
				$old_text = preg_quote( $old_text, '|' );
				$text = preg_replace( "|(?!<.*?)$old_text(?![^<>]*?>)|$preg_flags", addcslashes( $new_text, '\\$' ), $text, $limit );
			}

		}

		return trim( $text );
	} //end text_replace()

} // end c2c_TextReplace

c2c_TextReplace::get_instance();

endif; // end if !class_exists()
