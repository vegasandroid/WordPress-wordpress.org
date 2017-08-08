<?php

class DevHub_CLI {

	private static $commands_manifest = 'https://raw.githubusercontent.com/wp-cli/handbook/master/bin/commands-manifest.json';
	private static $meta_key = 'wporg_cli_markdown_source';
	private static $supported_post_types = array( 'command' );
	private static $posts_per_page = 350;

	public static function init() {
		add_action( 'init', array( __CLASS__, 'action_init_register_cron_jobs' ) );
		add_action( 'init', array( __CLASS__, 'action_init_register_post_types' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'action_pre_get_posts' ) );
		add_action( 'devhub_cli_manifest_import', array( __CLASS__, 'action_devhub_cli_manifest_import' ) );
		add_action( 'devhub_cli_markdown_import', array( __CLASS__, 'action_devhub_cli_markdown_import' ) );
		add_filter( 'the_content', array( __CLASS__, 'filter_the_content' ) );
	}

	public static function action_init_register_cron_jobs() {
		if ( ! wp_next_scheduled( 'devhub_cli_manifest_import' ) ) {
			wp_schedule_event( time(), 'twicedaily', 'devhub_cli_manifest_import' );
		}
		if ( ! wp_next_scheduled( 'devhub_cli_markdown_import' ) ) {
			wp_schedule_event( time(), 'twicedaily', 'devhub_cli_markdown_import' );
		}
	}

	public static function action_init_register_post_types() {
		$supports = array(
			'comments',
			'custom-fields',
			'editor',
			'excerpt',
			'revisions',
			'title',
		);
		register_post_type( 'command', array(
			'has_archive' => 'cli/commands',
			'label'       => __( 'Commands', 'wporg' ),
			'labels'      => array(
				'name'               => __( 'Commands', 'wporg' ),
				'singular_name'      => __( 'Command', 'wporg' ),
				'all_items'          => __( 'Commands', 'wporg' ),
				'new_item'           => __( 'New Command', 'wporg' ),
				'add_new'            => __( 'Add New', 'wporg' ),
				'add_new_item'       => __( 'Add New Command', 'wporg' ),
				'edit_item'          => __( 'Edit Command', 'wporg' ),
				'view_item'          => __( 'View Command', 'wporg' ),
				'search_items'       => __( 'Search Commands', 'wporg' ),
				'not_found'          => __( 'No Commands found', 'wporg' ),
				'not_found_in_trash' => __( 'No Commands found in trash', 'wporg' ),
				'parent_item_colon'  => __( 'Parent Command', 'wporg' ),
				'menu_name'          => __( 'Commands', 'wporg' ),
			),
			'public'      => true,
			'hierarchical'=> true,
			'rewrite'     => array(
				'feeds'      => false,
				'slug'       => 'cli/commands',
				'with_front' => false,
			),
			'supports'    => $supports,
		) );
	}

	public static function action_pre_get_posts( $query ) {
		if ( $query->is_main_query() && $query->is_post_type_archive( 'command' ) ) {
			$query->set( 'post_parent', 0 );
			$query->set( 'orderby', 'title' );
			$query->set( 'order', 'ASC' );
			$query->set( 'posts_per_page', 250 );
		}
	}

	public static function action_devhub_cli_manifest_import() {
		$response = wp_remote_get( self::$commands_manifest );
		if ( is_wp_error( $response ) ) {
			return $response;
		} elseif ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return new WP_Error( 'invalid-http-code', 'Markdown source returned non-200 http code.' );
		}
		$manifest = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! $manifest ) {
			return new WP_Error( 'invalid-manifest', 'Manifest did not unfurl properly.' );;
		}
		// Fetch all handbook posts for comparison
		$q = new WP_Query( array(
			'post_type'      => self::$supported_post_types,
			'post_status'    => 'publish',
			'posts_per_page' => self::$posts_per_page,
		) );
		$existing = array();
		foreach( $q->posts as $post ) {
			$cmd_path = rtrim( str_replace( home_url( 'cli/commands/' ), '', get_permalink( $post->ID ) ), '/' );
			$existing[ $cmd_path ] = array(
				'post_id'   => $post->ID,
				'cmd_path'  => $cmd_path,
			);
		}
		$created = 0;
		foreach( $manifest as $doc ) {
			// Already exists
			$existing_doc = wp_filter_object_list( $existing, array( 'cmd_path' => $doc['cmd_path'] ) );
			if ( $existing_doc ) {
				$existing_doc = array_shift( $existing_doc );
				if ( ! empty( $doc['repo_url'] ) ) {
					update_post_meta( $existing_doc['post_id'], 'repo_url', esc_url_raw( $doc['repo_url'] ) );
				}
				continue;
			}
			if ( self::process_manifest_doc( $doc, $existing, $manifest ) ) {
				$created++;
			}
		}
		if ( class_exists( 'WP_CLI' ) ) {
			\WP_CLI::success( "Successfully created {$created} handbook pages." );
		}
	}

	private static function process_manifest_doc( $doc, &$existing, $manifest ) {
		$post_parent = null;
		if ( ! empty( $doc['parent'] ) ) {
			// Find the parent in the existing set
			$parents = wp_filter_object_list( $existing, array( 'cmd_path' => $doc['parent'] ) );
			if ( empty( $parents ) ) {
				if ( ! self::process_manifest_doc( $manifest[ $doc['parent'] ], $existing, $manifest ) ) {
					return;
				}
				$parents = wp_filter_object_list( $existing, array( 'cmd_path' => $doc['parent'] ) );
			}
			if ( ! empty( $parents ) ) {
				$parent = array_shift( $parents );
				$post_parent = $parent['post_id'];
			}
		}
		$post = self::create_post_from_manifest_doc( $doc, $post_parent );
		if ( $post ) {
			$cmd_path = rtrim( str_replace( home_url( 'cli/commands/' ), '', get_permalink( $post->ID ) ), '/' );
			if ( ! empty( $doc['repo_url'] ) ) {
				update_post_meta( $post->ID, 'repo_url', esc_url_raw( $doc['repo_url'] ) );
			}
			$existing[ $cmd_path ] = array(
				'post_id'   => $post->ID,
				'cmd_path'  => $cmd_path,
			);
			return true;
		}
		return false;
	}

	public static function action_devhub_cli_markdown_import() {
		$q = new WP_Query( array(
			'post_type'      => self::$supported_post_types,
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'posts_per_page' => self::$posts_per_page,
		) );
		$ids = $q->posts;
		$success = 0;
		foreach( $ids as $id ) {
			$ret = self::update_post_from_markdown_source( $id );
			if ( class_exists( 'WP_CLI' ) ) {
				if ( is_wp_error( $ret ) ) {
					\WP_CLI::warning( $ret->get_error_message() );
				} else {
					\WP_CLI::log( "Updated {$id} from markdown source" );
					$success++;
				}
			}
		}
		if ( class_exists( 'WP_CLI' ) ) {
			$total = count( $ids );
			\WP_CLI::success( "Successfully updated {$success} of {$total} CLI command pages." );
		}
	}

	/**
	 * Create a new handbook page from the manifest document
	 */
	private static function create_post_from_manifest_doc( $doc, $post_parent = null ) {
		$post_data = array(
			'post_type'   => 'command',
			'post_status' => 'publish',
			'post_parent' => $post_parent,
			'post_title'  => sanitize_text_field( wp_slash( $doc['title'] ) ),
			'post_name'   => sanitize_title_with_dashes( $doc['slug'] ),
		);
		$post_id = wp_insert_post( $post_data );
		if ( ! $post_id ) {
			return false;
		}
		if ( class_exists( 'WP_CLI' ) ) {
			\WP_CLI::log( "Created post {$post_id} for {$doc['title']}." );
		}
		update_post_meta( $post_id, self::$meta_key, esc_url_raw( $doc['markdown_source'] ) );
		return get_post( $post_id );
	}

	/**
	 * Update a post from its Markdown source
	 */
	private static function update_post_from_markdown_source( $post_id ) {
		$markdown_source = self::get_markdown_source( $post_id );
		if ( is_wp_error( $markdown_source ) ) {
			return $markdown_source;
		}
		if ( ! function_exists( 'jetpack_require_lib' ) ) {
			return new WP_Error( 'missing-jetpack-require-lib', 'jetpack_require_lib() is missing on system.' );
		}

		// Transform GitHub repo HTML pages into their raw equivalents
		$markdown_source = preg_replace( '#https?://github\.com/([^/]+/[^/]+)/blob/(.+)#', 'https://raw.githubusercontent.com/$1/$2', $markdown_source );
		$markdown_source = add_query_arg( 'v', time(), $markdown_source );
		$response = wp_remote_get( $markdown_source );
		if ( is_wp_error( $response ) ) {
			return $response;
		} elseif ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return new WP_Error( 'invalid-http-code', 'Markdown source returned non-200 http code.' );
		}

		$markdown = wp_remote_retrieve_body( $response );
		// Strip YAML doc from the header
		$markdown = preg_replace( '#^---(.+)---#Us', '', $markdown );

		$title = null;
		if ( preg_match( '/^#\s(.+)/', $markdown, $matches ) ) {
			$title = $matches[1];
			$markdown = preg_replace( '/^#\swp\s(.+)/', '', $markdown );
		}
		$markdown = trim( $markdown );

		// Steal the first sentence as the excerpt
		$excerpt = '';
		if ( preg_match( '/^(.+)/', $markdown, $matches ) ) {
			$excerpt = $matches[1];
			$markdown = preg_replace( '/^(.+)/', '', $markdown );
		}

		// Transform to HTML and save the post
		jetpack_require_lib( 'markdown' );
		$parser = new \WPCom_GHF_Markdown_Parser;
		$html = $parser->transform( $markdown );
		$post_data = array(
			'ID'           => $post_id,
			'post_content' => wp_filter_post_kses( wp_slash( $html ) ),
			'post_excerpt' => sanitize_text_field( wp_slash( $excerpt ) ),
		);
		if ( ! is_null( $title ) ) {
			$post_data['post_title'] = sanitize_text_field( wp_slash( $title ) );
		}
		wp_update_post( $post_data );
		return true;
	}

	/**
	 * Retrieve the markdown source URL for a given post.
	 */
	public static function get_markdown_source( $post_id ) {
		$markdown_source = get_post_meta( $post_id, self::$meta_key, true );
		if ( ! $markdown_source ) {
			return new WP_Error( 'missing-markdown-source', 'Markdown source is missing for post.' );
		}

		return $markdown_source;
	}

	/**
	 * Filter the content of command pages
	 */
	public static function filter_the_content( $content ) {
		if ( 'command' !== get_post_type() || ! is_singular() ) {
			return $content;
		}
		// Transform emdash back to triple-dashes
		$content = str_replace( '&#045;&#8211;', '&#045;&#045;&#045;', $content );

		$children = get_children( array(
			'post_parent'    => get_the_ID(),
			'post_type'      => 'command',
			'posts_per_page' => 250,
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );
		// Append subcommands if they exist
		if ( $children ) {
			ob_start();
			?>
			<h3>SUBCOMMANDS</h3>
			<table>
				<thead>
				<tr>
					<th>Name</th>
					<th>Description</th>
				</tr>
				</thead>
				<tbody>
					<?php foreach( $children as $child ) : ?>
						<tr>
							<td><a href="<?php echo apply_filters( 'the_permalink', get_permalink( $child->ID ) ); ?>"><?php echo apply_filters( 'the_title', $child->post_title ); ?></a></td>
							<td><?php echo apply_filters( 'the_excerpt', $child->post_excerpt ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php
			$subcommands = ob_get_clean();
			$content .= PHP_EOL . $subcommands;
		}

		$contributing_bits = array();
		$repo_url = get_post_meta( get_the_ID(), 'repo_url', true );
		$cmd_slug = str_replace( 'wp ', '', get_the_title() );
		$open_issues = 'https://github.com/issues?q=label%3A' . urlencode( 'command:' . str_replace( ' ', '-', $cmd_slug ) ) . '+sort%3Aupdated-desc+org%3Awp-cli+is%3Aopen';
		$closed_issues = 'https://github.com/issues?q=label%3A' . urlencode( 'command:' . str_replace( ' ', '-', $cmd_slug ) ) . '+sort%3Aupdated-desc+org%3Awp-cli+is%3Aclosed';
		ob_start();
		?>
		<div class="github-tracker">
			<?php if ( $repo_url ) : ?>
				<a href="<?php echo esc_url( $repo_url ); ?>"><img src="https://make.wordpress.org/cli/wp-content/plugins/wporg-cli/assets/images/github-mark.svg" class="icon-github" /></a>
				<a href="<?php echo esc_url( rtrim( $repo_url, '/' ) . '/issues/new' ); ?>" class="button">Create New Issue</a>
			<?php endif; ?>
			<div class="btn-group">
				<a href="<?php echo esc_url( $open_issues ); ?>" class="button">View Open Issues</a>
				<a href="<?php echo esc_url( $closed_issues ); ?>" class="button">View Closed Issues</a>
			</div>
		</div>
		<?php
		$issues = ob_get_clean();
		$contributing_bits[] = $issues;
		$contributing_bits[] = '<p>Command documentation is regenerated at every release. To add or update an example, please submit a pull request against the corresponding part of the codebase.</p>';
		$contributing_bits[] = '<p>See <a href="https://make.wordpress.org/cli/handbook/contributing/">contributing guidelines</a> for full details on contributing.</p>';

		$contributing = '<h3>CONTRIBUTING</h3>' . PHP_EOL . implode( PHP_EOL, $contributing_bits );
		$content .= $contributing;

		// Add 'Quick Links' across the top
		$items = self::get_tags( 'h([1-4])', $content );
		if ( count( $items ) > 1 ) {
			for ( $i = 1; $i <= 4; $i++ ) {
				$content = self::add_ids_and_jumpto_links( "h$i", $content );
			}
			$quick_links = '<p class="quick-links"><small>Quick Links: ';
			foreach( $items as $item ) {
				$quick_links .= '<a href="#' . sanitize_title_with_dashes( $item[3] )  . '">' . ucwords( strtolower( $item[3] ) ) . '</a> | ';
			}
			$quick_links = rtrim( $quick_links, ' |' ) . '</small></p>';
			$content = $quick_links . PHP_EOL . PHP_EOL . $content;
		}

		// Include the excerpt in the main content well
		$excerpt = get_the_excerpt();
		if ( $excerpt ) {
			$content = '<p class="excerpt">' . $excerpt . '</p>' . PHP_EOL . $content;
		}

		return $content;
	}

	protected static function add_ids_and_jumpto_links( $tag, $content ) {
		$items = self::get_tags( $tag, $content );
		$first = true;
		$matches = array();
		$replacements = array();

		foreach ( $items as $item ) {
			$replacement = '';
			$matches[] = $item[0];
			$id = sanitize_title_with_dashes($item[2]);

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
			$content = str_replace( $matches, $replacements, $content );
		}

		return $content;
	}

	private static function get_tags( $tag, $content ) {
		preg_match_all( "/(<{$tag}>)(.*)(<\/{$tag}>)/", $content, $matches, PREG_SET_ORDER );
		return $matches;
	}

}

DevHub_CLI::init();

