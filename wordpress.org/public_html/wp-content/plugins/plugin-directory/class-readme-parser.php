<?php
namespace WordPressdotorg\Plugin_Directory;
/**
 * WordPress.org Plugin Readme Parser.
 *
 * Based on Baikonur_ReadmeParser from https://github.com/rmccue/WordPress-Readme-Parser
 *
 * Relies on \Michaelf\Markdown_Extra
 *
 * @package WordPressdotorg\Plugin_Directory
 */
class Readme_Parser {
	public $name              = '';
	public $tags              = array();
	public $requires          = '';
	public $tested            = '';
	public $contributors      = array();
	public $stable_tag        = '';
	public $donate_link       = '';
	public $short_description = '';
	public $sections          = array();
	public $upgrade_notice    = array();
	public $screenshots       = array();

	// These are the readme sections which we expect
	private $expected_sections = array(
		'description',
		'installation',
		'faq',
		'screenshots',
		'changelog',
		'upgrade_notice',
		'other_notes',
	);

	// We alias these sections, from => to
	private $alias_sections = array(
		'frequently_asked_questions' => 'faq',
		'change_log' => 'changelog',
		'screenshot' => 'screenshots',
	);

	// These are the valid header mappings for the header
	private $valid_headers = array(
			'tested'            => 'tested',
			'tested up to'      => 'tested',
			'requires'          => 'requires',
			'requires at least' => 'requires',
			'tags'              => 'tags',
			'contributors'      => 'contributors',
			'donate_link'       => 'donate_link',
			'stable_tag'        => 'stable_tag',
		);

	public function __construct( $file ) {
		$this->parse_readme( $file );
	}

	protected function parse_readme( $file ) {
		$contents = file( $file );

		$contents = array_map( array( $this, 'strip_newlines' ), $contents );

		// Strip BOM
		if ( strpos( $contents[0], "\xEF\xBB\xBF" ) === 0 ) {
			$contents[0] = substr( $contents[0], 3 );
		}

		$line       = $this->get_first_nonwhitespace( $contents );
		$this->name = $this->sanitize_text( trim( $line, "#= " ) );

		// Strip Github style header\n==== underlines
		if ( '' === trim( $contents[0], '=-' ) ) {
			array_shift( $contents );
		}

		// Handle readme's which do `=== Plugin Name ===\nMy SuperAwesomePlugin Name\n...`
		if ( 'plugin name' == strtolower( $this->name ) ) {
			$this->name = $line = $this->get_first_nonwhitespace( $contents );
			// Ensure that the line read wasn't an actual header
			if ( preg_match( '~^(' . implode( '|', array_keys( $this->valid_headers ) ) . ')\s*:~i', $line ) ) {
				$this->name = false;
				array_unshift( $contents, $line );
			}
		}

		// Parse headers
		$headers = array();

		$line = $this->get_first_nonwhitespace( $contents );
		do {
			$key = $value = null;
			if ( strpos( $line, ':' ) === false ) {
				// Some plugins have line-breaks within the headers.
				if ( ! empty( $line ) ) {
					break;
				} else {
					continue;
				}
			}

			$bits = explode( ':', trim( $line ), 2 );
			list( $key, $value ) = $bits;
			$key = strtolower( trim( $key, " \t*-\r\n" ) );
			if ( isset( $this->valid_headers[ $key ] ) ) {
				$headers[ $this->valid_headers[ $key ] ] = trim( $value );
			}
		} while ( ( $line = array_shift( $contents ) ) !== null );
		array_unshift( $contents, $line );

		if ( ! empty( $headers['tags'] ) ) {
			$this->tags = explode( ',', $headers['tags'] );
			$this->tags = array_map( 'trim', $this->tags );
		}
		if ( ! empty( $headers['requires'] ) ) {
			$this->requires = $headers['requires'];
		}
		if ( ! empty( $headers['tested'] ) ) {
			$this->tested = $headers['tested'];
		}
		if ( ! empty( $headers['contributors'] ) ) {
			$this->contributors = explode( ',', $headers['contributors'] );
			$this->contributors = array_map( 'trim', $this->contributors );
			$this->contributors = $this->sanitize_contributors( $this->contributors );
		}
		if ( ! empty( $headers['stable_tag'] ) ) {
			$this->stable_tag = $headers['stable_tag'];
		}
		if ( ! empty( $headers['donate_link'] ) ) {
			$this->donate_link = $headers['donate_link'];
		}

		// Parse the short description
		while ( ( $line = array_shift( $contents ) ) !== null ) {
			$trimmed = trim( $line );
			if ( empty( $trimmed ) ) {
				$this->short_description .= "\n";
				continue;
			}
			if ( ( '=' === $trimmed[0] && isset( $trimmed[1] ) && '=' === $trimmed[1] ) ||
			     ( '#' === $trimmed[0] && isset( $trimmed[1] ) && '#' === $trimmed[1] ) ) { // Stop after any Markdown heading
				array_unshift( $contents, $line );
				break;
			}

			$this->short_description .= $line . "\n";
		}
		$this->short_description = trim( $this->short_description );

		// Parse the rest of the body
		// Prefill the sections, we'll filter out empty sections later.
		$this->sections = array_fill_keys( $this->expected_sections, '' );
		$current = $section_name = $section_title = '';
		while ( ( $line = array_shift( $contents ) ) !== null ) {
			$trimmed = trim( $line );
			if ( empty( $trimmed ) ) {
				$current .= "\n";
				continue;
			}

			if ( ( '=' === $trimmed[0] && isset( $trimmed[1] ) && '=' === $trimmed[1] ) ||
			     ( '#' === $trimmed[0] && isset( $trimmed[1] ) && '#' === $trimmed[1] && isset( $trimmed[2] ) && '#' !== $trimmed[2] ) ) { // Stop only after a ## Markdown header, not a ###.
				if ( ! empty( $section_name ) ) {
					$this->sections[ $section_name ] .= trim( $current );
				}

				$current       = '';
				$section_title = trim( $line, "#= \t" );
				$section_name  = strtolower( str_replace( ' ', '_', $section_title ) );

				if ( isset( $this->alias_sections[ $section_name ] ) ) {
					$section_name = $this->alias_sections[ $section_name ];
				}

				// If we encounter an unknown section header, include the provided Title, we'll filter it to other_notes later.
				if ( ! in_array( $section_name, $this->expected_sections ) ) {
					$current .= '<h3>' . $section_title . '</h3>';
					$section_name = 'other_notes';
				}
				continue;
			}

			$current .= $line . "\n";
		}

		if ( ! empty( $section_name ) ) {
			$this->sections[ $section_name ] .= trim( $current );
		}

		// Filter out any empty sections.
		$this->sections = array_filter( $this->sections );

		// Use the description for the short description if not provided.
		if ( empty( $this->short_description ) && ! empty( $this->sections['description'] ) ) {
			$this->short_description = $this->sections['description'];
		}

		// Use the short description for the description section if not provided.
		if ( empty( $this->sections['description'] ) ) {
			$this->sections['description'] = $this->short_description;
		}

		// Sanitize and trim the short_description to match requirements
		$this->short_description = $this->sanitize_text( $this->short_description );
		$this->short_description = $this->trim_length( $this->short_description, 150 );

		// Parse out the Upgrade Notice section into it's own data
		if ( isset( $this->sections['upgrade_notice'] ) ) {
			$lines = explode( "\n", $this->sections['upgrade_notice'] );
			$version = null;
			while ( ( $line = array_shift( $lines ) ) !== null ) {
				$trimmed = trim( $line );
				if ( empty( $trimmed ) ) {
					continue;
				}

				if ( '=' === $trimmed[0] || '#' === $trimmed[0] ) {
					if ( ! empty( $current ) ) {
						$this->upgrade_notice[ $version ] = $this->sanitize_text( trim( $current ) );
					}

					$current = '';
					$version = trim( $line, "#= \t" );
					continue;
				}

				$current .= $line . "\n";
			}
			if ( ! empty( $version ) && ! empty( $current ) ) {
				$this->upgrade_notice[ $version ] = $this->sanitize_text( trim( $current ) );
			}
			unset( $this->sections['upgrade_notice'] );
		}

		// Markdownify!
		$this->sections       = array_map( array( $this, 'parse_markdown' ), $this->sections );
		$this->upgrade_notice = array_map( array( $this, 'parse_markdown' ), $this->upgrade_notice );

		if ( isset( $this->sections['screenshots'] ) ) {
			preg_match_all( '#<li>(.*?)</li>#is', $this->sections['screenshots'], $screenshots, PREG_SET_ORDER );
			if ( $screenshots ) {
				$i = 1; // Screenshots start from 1
				foreach ( $screenshots as $ss ) {
					$this->screenshots[ $i++ ] = $this->filter_text( $ss[1] );
				}
			}
			unset( $this->sections['screenshots'] );
		}

		// Filter the HTML
		$this->sections = array_map( array( $this, 'filter_text' ), $this->sections );

		return true;
	}

	protected function get_first_nonwhitespace( &$contents ) {
		while ( ( $line = array_shift( $contents ) ) !== null ) {
			$trimmed = trim( $line );
			if ( ! empty( $line ) ) {
				break;
			}
		}

		return $line;
	}

	protected function strip_newlines( $line ) {
		return rtrim( $line, "\r\n" );
	}

	protected function trim_length( $desc, $length = 150 ) {
		if ( function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) ) {
			if ( mb_strlen( $desc ) > $length ) {
				$desc = mb_substr( $desc, 0, $length );
			}
		} else {
			if ( strlen( $desc ) > $length ) {
				$desc = substr( $desc, 0, $length );
			}
		}

		return trim( $desc );
	}

	/**
	 * @access protected
	 *
	 * @param string $text
	 * @return string
	 */
	protected function filter_text( $text ) {
		$text = trim( $text );

		$allowed = array(
			'a'          => array(
				'href'  => true,
				'title' => true,
				'rel'   => true,
			),
			'blockquote' => array(
				'cite' => true
			),
			'br'         => true,
			'p'          => true,
			'code'       => true,
			'pre'        => true,
			'em'         => true,
			'strong'     => true,
			'ul'         => true,
			'ol'         => true,
			'li'         => true,
			'h3'         => true,
			'h4'         => true,
		);

		$text = balanceTags( $text );
		$text = make_clickable( $text );

		$text = wp_kses( $text, $allowed );

		// wpautop() will eventually replace all \n's with <br>s, and that isn't what we want.
		$text = preg_replace( "/(?<![> ])\n/", ' ', $text );

		$text = trim( $text );

		return $text;
	}

	/**
	 * @access protected
	 *
	 * @param string $text
	 * @return string
	 */
	protected function sanitize_text( $text ) { // not fancy
		$text = strip_tags( $text );
		$text = esc_html( $text );
		$text = trim( $text );

		return $text;
	}

	/**
	 * Sanitize proided contributors to valid WordPress users
	 *
	 * @param array $users Array of user_login's or user_nicename's.
	 * @return array Array of user_logins.
	 */
	protected function sanitize_contributors( $users ) {
		foreach ( $users as $i => $name ) {
			if ( get_user_by( 'login', $name ) ) {
				continue;
			} elseif ( false !== ( $user = get_user_by( 'slug', $name ) ) ) {
				// Overwrite the nicename with the user_login
				$users[ $i ] = $user->user_login;
			} else {
				// Unknown user, we'll skip these entirely to encourage correct readmes
				unset( $users[ $i ] );
			}
		}
		return $users;
	}

	protected function parse_markdown( $text ) {
		static $markdown = null;
		if ( ! class_exists( '\\Michelf\\MarkdownExtra' ) ) {
			// TODO: Autoloader?
			include __DIR__ . '/libs/michelf-php-markdown-1.6.0/Michelf/MarkdownExtra.inc.php';
		}
		if ( is_null( $markdown ) ) {
			$markdown = new \Michelf\MarkdownExtra();
		}

		$text = $this->code_trick( $text );
		$text = preg_replace( '/^[\s]*=[\s]+(.+?)[\s]+=/m', "\n" . '<h4>$1</h4>' . "\n", $text );
		$text = $markdown->transform( trim( $text ) );

		return trim( $text );
	}

	protected function code_trick( $text ) {
		// If doing markdown, first take any user formatted code blocks and turn them into backticks so that
		// markdown will preserve things like underscores in code blocks
		$text = preg_replace_callback( "!(<pre><code>|<code>)(.*?)(</code></pre>|</code>)!s", array( $this, 'code_trick_decodeit_cb' ), $text );
		$text = str_replace( array( "\r\n", "\r" ), "\n", $text );

		// Markdown can do inline code, we convert bbPress style block level code to Markdown style
		$text = preg_replace_callback( "!(^|\n)([ \t]*?)`(.*?)`!s", array( $this, 'code_trick_indent_cb' ), $text );

		return $text;
	}

	protected function code_trick_indent_cb( $matches ) {
		$text = $matches[3];
		$text = preg_replace( '|^|m', $matches[2] . '    ', $text );

		return $matches[1] . $text;
	}

	protected function code_trick_decodeit_cb( $matches ) {
		$text        = $matches[2];
		$trans_table = array_flip( get_html_translation_table( HTML_ENTITIES ) );
		$text        = strtr( $text, $trans_table );
		$text        = str_replace( '<br />', '', $text );
		$text        = str_replace( '&#38;', '&', $text );
		$text        = str_replace( '&#39;', "'", $text );

		if ( '<pre><code>' == $matches[1] ) {
			$text = "\n$text\n";
		}

		return "`$text`";
	}
}
