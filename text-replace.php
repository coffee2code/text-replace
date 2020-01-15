<?php
/**
 * Plugin Name: Text Replace
 * Version:     3.9
 * Plugin URI:  http://coffee2code.com/wp-plugins/text-replace/
 * Author:      Scott Reilly
 * Author URI:  http://coffee2code.com/
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: text-replace
 * Description: Replace text with other text. Handy for creating shortcuts to common, lengthy, or frequently changing text/HTML, or for smilies.
 *
 * Compatible with WordPress 4.9+ through 5.3+.
 *
 * =>> Read the accompanying readme.txt file for instructions and documentation.
 * =>> Also, visit the plugin's homepage for additional information and updates.
 * =>> Or visit: https://wordpress.org/plugins/text-replace/
 *
 * @package Text_Replace
 * @author  Scott Reilly
 * @version 3.9
 */

/*
	Copyright (c) 2004-2020 by Scott Reilly (aka coffee2code)

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

final class c2c_TextReplace extends c2c_TextReplace_Plugin_050 {

	/**
	 * Name of plugin's setting.
	 *
	 * @since 3.8
	 * @var string
	 */
	const SETTING_NAME = 'c2c_text_replace';

	/**
	 * The one true instance.
	 *
	 * @var c2c_TextReplace
	 */
	private static $instance;

	/**
	 * Get singleton instance.
	 *
	 * @since 3.5
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	protected function __construct() {
		parent::__construct( '3.9', 'text-replace', 'c2c', __FILE__, array() );
		register_activation_hook( __FILE__, array( __CLASS__, 'activation' ) );

		return self::$instance = $this;
	}

	/**
	 * Handles activation tasks, such as registering the uninstall hook.
	 *
	 * @since 3.1
	 */
	public static function activation() {
		register_uninstall_hook( __FILE__, array( __CLASS__, 'uninstall' ) );
	}

	/**
	 * Handles uninstallation tasks, such as deleting plugin options.
	 */
	public static function uninstall() {
		delete_option( self::SETTING_NAME );
	}

	/**
	 * Handle plugin updates.
	 *
	 * @since 3.2.1
	 *
	 * @param string $old_version The version number of the old version of
	 *                            the plugin. '0.0' indicates no version
	 *                            previously stored
	 * @param array  $options     Array of all plugin options
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
		$this->name      = __( 'Text Replace', 'text-replace' );
		$this->menu_name = __( 'Text Replace', 'text-replace' );

		$this->config = array(
			'text_to_replace' => array(
				'input'            => 'textarea',
				'datatype'         => 'hash',
				'default'          => array(
					":wp:"          => "<a href='https://wordpress.org'>WordPress</a>",
					":codex:"       => "<a href='https://codex.wordpress.org'>WordPress Codex</a>",
					":coffee2code:" => "<a href='http://coffee2code.com' title='coffee2code'>coffee2code</a>"
				),
				'allow_html'       => true,
				'no_wrap'          => true,
				'input_attributes' => 'rows="15" cols="40"',
				'label'            => '',
				'help'             => '',
			),
			'text_replace_comments' => array(
				'input'            => 'checkbox',
				'default'          => false,
				'label'            => __( 'Enable text replacement in comments?', 'text-replace' ),
				'help'             => '',
			),
			'replace_once' => array(
				'input'            => 'checkbox',
				'default'          => false,
				'label'            => __( 'Only text replace once per term per post?', 'text-replace' ),
				'help'             => __( 'If checked, then each term will only be replaced the first time it appears in a post.', 'text-replace' ),
			),
			'case_sensitive' => array(
				'input'            => 'checkbox',
				'default'          => true,
				'label'            => __( 'Case sensitive text replacement?', 'text-replace' ),
				'help'             => __( 'If unchecked, then a replacement for :wp: would also replace :WP:.', 'text-replace' ),
			),
			'when' => array(
				'input'            => 'select',
				'default'          => 'early',
				'options'          => array( 'early', 'late' ),
				'label'            => __( 'When to process text?', 'text-replace' ),
				'help'             => sprintf( __( "Text replacements can happen 'early' (before most other text processing for posts) or 'late' (after most other text processing for posts). By default the plugin handles text early, but depending on the replacements you've defined and the plugins you're using, you can eliminate certain conflicts by switching to 'late'. Finer-grained control can be achieved via the <code>%s</code> filter.", 'text-replace' ), 'c2c_text_replace_filter_priority' ),
			),
		);
	}

	/**
	 * Override the plugin framework's register_filters() to actually register actions against filters.
	 */
	public function register_filters() {
		$options = $this->get_options();

		/**
		 * Filters third party plugin/theme hooks that get processed for hover text.
		 *
		 * Use this to amend or remove support for hooks present in third party
		 * plugins and themes.
		 *
		 * Currently supported plugins:
		 * - Advanced Custom Fields
		 *    'acf/format_value/type=text',
		 *    'acf/format_value/type=textarea',
		 *    'acf/format_value/type=url',
		 *    'acf_the_content',
		 * - Elementor
		 *    'elementor/frontend/the_content',
		 *    'elementor/widget/render_content',
		 *
		 * @since 3.9
		 *
		 * @param array $filters The third party filters that get processed for
		 *                       hover text. See filter inline docs for defaults.
		 */
		$filters = (array) apply_filters( 'c2c_text_replace_third_party_filters', array(
			// Support for Advanced Custom Fields plugin.
			'acf/format_value/type=text',
			'acf/format_value/type=textarea',
			'acf/format_value/type=url',
			'acf_the_content',
			// Support for Elementor plugin.
			'elementor/frontend/the_content',
			'elementor/widget/render_content',
		) );

		// Add in relevant stock WP filters.
		$filters = array_merge( $filters, array( 'the_content', 'the_excerpt', 'widget_text' ) );

		/**
		 * Filters the hooks that get processed for hover text.
		 *
		 * @since 3.0
		 *
		 * @param array $filters The filters that get processed for text.
		 *                       replacement Default ['the_content',
		 *                       'the_excerpt', 'widget_text'] plus third-party
		 *                       filters defined via the
		 *                       `c2c_text_replace_third_party_filters` filter.
		 */
		$filters = (array) apply_filters( 'c2c_text_replace_filters', $filters );

		$default_priority = ( 'late' === $options[ 'when'] ) ? 1000 : 2;

		foreach ( $filters as $filter ) {
			/**
			 * Filters the priority for attaching the text replacement handler to
			 * a hook.
			 *
			 * @since 3.9
			 *
			 * @param int    $priority The priority for the 'c2c_text_replace'
			 *                         filter. Default 2 if 'when' setting
			 *                         value is 'early', else 1000.
			 * @param string $filter   The filter name.
			 */
			$priority = (int) apply_filters( 'c2c_text_replace_filter_priority', $default_priority, $filter );

			add_filter( $filter, array( $this, 'text_replace' ), $priority );
		}

		// Note that the priority must be set high enough to avoid <img> tags inserted by the text replace process from
		// getting omitted as a result of the comment text sanitation process, if you use this plugin for smilies, for instance.
		add_filter( 'get_comment_text',    array( $this, 'text_replace_comment_text' ), 11 );
		add_filter( 'get_comment_excerpt', array( $this, 'text_replace_comment_text' ), 11 );
	}

	/**
	 * Outputs the text above the setting form.
	 *
	 * @param string $localized_heading_text Optional. Localized page heading text.
	 */
	public function options_page_description( $localized_heading_text = '' ) {
		parent::options_page_description( __( 'Text Replace Settings', 'text-replace' ) );

		echo '<p>' . __( 'Text Replace is a plugin that allows you to replace text with other text in posts, etc. Very handy to create shortcuts to commonly-typed and/or lengthy text/HTML, or for smilies.', 'text-replace' ) . '</p>';
		echo '<div class="c2c-hr">&nbsp;</div>';
		echo '<h3>' . __( 'Shortcuts and text replacements', 'text-replace' ) . '</h3>';
		echo '<p>' . __( 'Define shortcuts and text replacement expansions here. The format should be like this:', 'text-replace' ) . '</p>';
		echo "<blockquote><code>:wp: => &lt;a href='https://wordpress.org'>WordPress&lt;/a></code></blockquote>";
		echo '<p>' . __( 'Where <code>:wp:</code> is the shortcut you intend to use in your posts and the <code>&lt;a href=\'https://wordpress.org\'>WordPress&lt;/a></code> would be what you want the shortcut to be replaced with when the post is shown on your site.', 'text-replace' ) . '</p>';
		echo '<p>' . __( 'Other considerations:', 'text-replace' ) . '</p>';
		echo '<ul class="c2c-plugin-list"><li>';
		echo __( 'Be careful not to define text that could match partially when you don\'t want it to:<br />i.e.  <code>Me => Scott</code> would also inadvertently change "Men" to be "Scottn"', 'text-replace' );
		echo '</li><li>';
		echo __( 'If you intend to use this plugin to handle smilies, you should probably disable WordPress\'s default smilie handler.', 'text-replace' );
		echo '</li><li>';
		echo __( 'Text inside of HTML tags (such as tag names and attributes) will not be matched. So, for example, you can\'t expect the :mycss: shortcut to work in: &lt;a href="" :mycss:&gt;text&lt;/a&gt;.', 'text-replace' );
		echo '</li><li>';
		echo __( 'HTML is allowed.', 'text-replace' );
		echo '</li><li>';
		echo __( 'Only use quotes it they are actual part of the original or replacement strings.', 'text-replace' );
		echo '</li><li><strong><em>';
		echo __( 'Define only one shortcut per line.', 'text-replace' );
		echo '</em></strong></li><li><strong><em>';
		echo __( 'Shortcuts must not span multiple lines.', 'text-replace' );
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

		/**
		 * Filters if comments should be processed for text replacement.
		 *
		 * @since 3.0
		 *
		 * @param bool $include_comments Should comments be processed for text
		 *                               replacement? Default is value set in
		 *                               plugin settings, which is initially
		 *                               false.
		 */
		if ( (bool) apply_filters( 'c2c_text_replace_comments', $options['text_replace_comments'] ) ) {
			$text = $this->text_replace( $text );
		}

		return $text;
	}

	/**
	 * Perform text replacements.
	 *
	 * @param string  $text Text to be processed for text replacements
	 * @return string Text with replacements already processed
	 */
	public function text_replace( $text ) {
		$options         = $this->get_options();

		/**
		 * Filters the list of text that will get replaced.
		 *
		 * @since 3.0
		 *
		 * @param array $text_to_replace Associative array of text to replace
		 *                               and respective replacement text. Default
		 *                               is value set in plugin settings.
		 */
		$text_to_replace = (array) apply_filters( 'c2c_text_replace',                $options['text_to_replace'] );

		/**
		 * Filters if text matching for text replacement should be case sensitive.
		 *
		 * @since 3.0
		 *
		 * @param bool $case_sensitive Should text matching for text replacement
		 *                             be case sensitive? Default is value set in
		 *                             plugin settings, which is initially true.
		 */
		$case_sensitive  = (bool)  apply_filters( 'c2c_text_replace_case_sensitive', $options['case_sensitive'] );

		/**
		 * Filters if text replacement should be limited to once per phrase per
		 * piece of text being processed regardless of how many times the phrase
		 * appears.
		 *
		 * @since 3.5
		 *
		 * @param bool $replace_once Should text hovering be limited to once
		 *                           per term per post? Default is value set in
		 *                           plugin settings, which is initially false.
		 */
		$limit           = (bool)  apply_filters( 'c2c_text_replace_once',           $options['replace_once'] );

		$preg_flags      = $case_sensitive ? 'ms' : 'msi';
		$mb_regex_encoding = null;

		// Bail early if there are no replacements defined.
		if ( ! $text_to_replace || ( isset( $text_to_replace[0] ) && ! $text_to_replace[0] ) ) {
			return $text;
		}

		$text = ' ' . $text . ' ';

		$can_do_mb = function_exists( 'mb_regex_encoding' ) && function_exists( 'mb_ereg_replace' ) && function_exists( 'mb_strlen' );

		// Store original mb_regex_encoding and then set it to UTF-8.
		if ( $can_do_mb ) {
			$mb_regex_encoding = mb_regex_encoding();
			mb_regex_encoding( 'UTF-8' );
		}

		if ( $text_to_replace ) {
			// Sort array descending by key length. This way longer, more precise
			// strings take precedence over shorter strings, preventing premature
			// partial replacements.
			// E.g. if "abc" and "abc def" are both defined for linking and in that
			// order, the string "abc def ghi" would match on "abc def", the longer
			// string rather than the shorter, less precise "abc".
			$keys = array_map( $can_do_mb ? 'mb_strlen' : 'strlen', array_keys( $text_to_replace ) );
			array_multisort( $keys, SORT_DESC, $text_to_replace );
		}

		foreach ( $text_to_replace as $old_text => $new_text ) {

			// If the text to be replaced includes a '<' or '>', do direct string replacement.
			if ( strpos( $old_text, '<' ) !== false || strpos( $old_text, '>' ) !== false ) {
				// If only doing one replacement, need to handle specially since there is
				// no built-in, non-preg_replace method to do a single replacement.
				if ( $limit ) {
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
			}
			// Otherwise use preg_replace() to avoid replacing text inside HTML tags.
			else {
				$old_text = preg_quote( $old_text, '~' );
				$new_text = addcslashes( $new_text, '\\$' );

				// If the string to be linked includes '&', consider '&amp;' and '&#038;' equivalents.
				// Visual editor will convert the former, but users aren't aware of the conversion.
				if ( false !== strpos( $old_text, '&' ) ) {
					$old_text = str_replace( '&', '&(amp;|#038;)?', $old_text );
				}

				// Allow spaces in linkable text to represent any number of whitespace chars.
				$old_text = preg_replace( '/\s+/', '\s+', $old_text );

				// WILL match string within string, but WON'T match within tags.
				$regex = "(?!<.*?){$old_text}(?![^<>]*?>)";

				// If the text to be replaced has multibyte character(s), use
				// mb_ereg_replace() if possible.
				if ( $can_do_mb && ( strlen( $old_text ) != mb_strlen( $old_text ) ) ) {
					// NOTE: mb_ereg_replace() does not support limiting the number of
					// replacements, hence the different handling if replacing once.
					if ( $limit ) {
						// Find first occurrence of the search string.
						mb_ereg_search_init( $text, $old_text, $preg_flags );
						$pos = mb_ereg_search_pos();

						// Only do the replacement if the search string was found.
						if ( false !== $pos ) {
							$match = mb_ereg_search_getregs();
							$text  = mb_substr( $text, 0, $pos[0] )
								. $new_text
								. mb_substr( $text, $pos[0] + mb_strlen( $match[0] ) );
						}
					} else {
						$text = mb_ereg_replace( $regex, $new_text, $text, $preg_flags );
					}
				} else {
					$text = preg_replace( "~{$regex}~{$preg_flags}", $new_text, $text, ( $limit ? 1 : -1 ) );
				}
			}

		}

		// Restore original mb_regexp_encoding, if changed.
		if ( $mb_regex_encoding ) {
			mb_regex_encoding( $mb_regex_encoding );
		}

		return trim( $text );
	} //end text_replace()

} // end c2c_TextReplace

add_action( 'plugins_loaded', array( 'c2c_TextReplace', 'get_instance' ) );

endif; // end if !class_exists()
