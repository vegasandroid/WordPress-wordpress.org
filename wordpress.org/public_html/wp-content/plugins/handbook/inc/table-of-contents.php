<?php
/**
 * Adds a table of contents to your pages based on h1, h2, h3 and h4 tags. Useful for documention-centric sites.
 *
 * @author Automattic, modifed by Nacin
 */
class WPorg_Handbook_TOC {
	protected $post_types = array();

	protected $styles = '<style> .toc-jump { text-align: right; font-size: 12px; } .page .toc-heading { margin-top: -50px; padding-top: 50px !important; }</style>';

	/**
	 * Arguments.
	 *
	 * @access protected
	 * @var array
	 */
	protected $args = array();

	/**
	 * Constructor.
	 *
	 * @access public
	 *
	 * @param array $post_types Post types.
	 * @param array $args {
	 *     Optional. Table of Contents arguments. Defualt emtpy array.
	 *
	 *     @type string $header_text Header text for the table. HTML-escaped on output.
	 * }
	 */
	public function __construct( $post_types, $args = array() ) {
		$this->post_types = (array) $post_types;
		add_action( 'template_redirect', array( $this, 'load_filters' ) );

		$this->args = (object) wp_parse_args( $args, array(
			'header_text' => __( 'Topics', 'wporg' ),
		) );
	}

	function load_filters() {
		$this->post_types = array_map( array( $this, 'append_suffix' ), $this->post_types );

		if ( is_singular( $this->post_types ) && ! is_embed() ) {
			add_filter( 'the_content', array( $this, 'add_toc' ) );
		}
	}

	function append_suffix( $t ) {
		if ( in_array( $t, array( 'handbook', 'page' ) ) ) {
			return $t;
		}

		return $t . '-handbook';
	}

	/**
	 * Converts given content to dynamically add the ToC.
	 *
	 * @access public
	 *
	 * @param string $content Content.
	 * @return string Modified content.
	 */
	public function add_toc( $content ) {
		if ( ! in_the_loop() ) {
			return $content;
		}

		$toc = '';

		$items = $this->get_tags( 'h([1-4])', $content );

		if ( count( $items ) < 2 ) {
			return $content;
		}

		for ( $i = 1; $i <= 4; $i++ )
			$content = $this->add_ids_and_jumpto_links( "h$i", $content );

		if ( ! apply_filters( 'handbook_display_toc', true ) ) {
			return $content;
		}

		if ( $items ) {
			$contents_header = 'h' . $items[0][2]; // Duplicate the first <h#> tag in the document.
			$toc .= $this->styles;
			$toc .= '<div class="table-of-contents">';
			$toc .= "<$contents_header>" . esc_html( $this->args->header_text ) . "</$contents_header><ul class=\"items\">";
			$last_item = false;
			$used_ids = [];

			foreach ( $items as $item ) {
				if ( $last_item ) {
					if ( $last_item < $item[2] )
						$toc .= "\n<ul>\n";
					elseif ( $last_item > $item[2] )
						$toc .= "\n</ul></li>\n";
					else
						$toc .= "</li>\n";
				}

				$last_item = $item[2];

				$id = sanitize_title_with_dashes( $item[3] );
				// Append unique suffix if anchor ID isn't unique.
				$count = 2;
				$orig_id = $id;
				while ( in_array( $id, $used_ids ) && $count < 50 ) {
					$id = $orig_id . '-' . $count;
					$count++;
				}
				$used_ids[] = $id;

				$toc .= '<li><a href="#' . esc_attr( $id  ) . '">' . $item[3]  . '</a>';
			}
			$toc .= "</ul>\n</div>\n";
		}

		return $toc . $content;
	}

	protected function add_ids_and_jumpto_links( $tag, $content ) {
		$items = $this->get_tags( $tag, $content );
		$first = true;
		$matches = array();
		$replacements = array();
		$used_ids = array();

		foreach ( $items as $item ) {
			$replacement = '';
			$matches[] = $item[0];
			$id = sanitize_title_with_dashes($item[2]);

			// Append unique suffix if anchor ID isn't unique.
			$count = 2;
			$orig_id = $id;
			while ( in_array( $id, $used_ids ) && $count < 50 ) {
				$id = $orig_id . '-' . $count;
				$count++;
			}
			$used_ids[] = $id;
		
			if ( ! $first ) {
				$replacement .= '<p class="toc-jump"><a href="#top">' . __( 'Top &uarr;', 'wporg' ) . '</a></p>';
			} else {
				$first = false;
			}
			$a11y_text      = sprintf( '<span class="screen-reader-text">%s</span>', $item[2] );
			$anchor         = sprintf( '<a href="#%1$s" class="anchor"><span aria-hidden="true">#</span>%2$s</a>', $id, $a11y_text );
			$replacement   .= sprintf( '<%1$s class="toc-heading" id="%2$s" tabindex="-1">%3$s %4$s</%1$s>', $tag, $id, $item[2], $anchor );
			$replacements[] = $replacement;
		}

		if ( $replacements ) {
			if ( count( array_unique( $matches ) ) !== count( $matches ) ) {
				foreach ( $matches as $i => $match ) {
					$content = preg_replace( '/' . preg_quote( $match, '/' ) . '/', $replacements[ $i ], $content, 1 );
				}
			} else {
				$content = str_replace( $matches, $replacements, $content );
			}
		}

		return $content;
	}

	private function get_tags( $tag, $content = '' ) {
		if ( empty( $content ) )
			$content = get_the_content();
		preg_match_all( "/(<{$tag}>)(.*)(<\/{$tag}>)/", $content, $matches, PREG_SET_ORDER );
		return $matches;
	}
}

