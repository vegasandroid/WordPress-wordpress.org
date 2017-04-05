<?php
/**
 * Template part for displaying posts.
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package WordPressdotorg\Plugin_Directory\Theme
 */

namespace WordPressdotorg\Plugin_Directory\Theme;
use WordPressdotorg\Plugin_Directory\Plugin_Directory;
use WordPressdotorg\Plugin_Directory\Template;
use WordPressdotorg\Plugin_Directory\Tools;
global $section, $section_slug, $section_content, $section_read_more;

$content = Plugin_Directory::instance()->split_post_content_into_pages( get_the_content() );

?><article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<?php echo Template::get_plugin_banner( get_post(), 'html' ); ?>

	<header class="plugin-header">
		<?php if ( time() - get_post_modified_time() > 2 * YEAR_IN_SECONDS ) : ?>
			<div class="plugin-notice notice notice-warning notice-alt">
				<p><?php _e( 'This plugin <strong>hasn&#146;t been updated in over 2 years</strong>. It may no longer be maintained or supported and may have compatibility issues when used with more recent versions of WordPress.', 'wporg-plugins' ); ?></p>
			</div><!-- .plugin-notice -->
		<?php endif; ?>
		<?php if ( 'publish' != get_post()->post_status ) :
				$notice_type = 'notice-error';
				switch ( get_post()->post_status ) {
					case 'draft':
					case 'pending':
						$message = __( 'This plugin is requested and not visible to the public yet. Please be patient as your plugin gets reviewed.', 'wporg-plugins' );
						$notice_type = 'notice-info';
						break;

					case 'approved':
						$message = __( 'This plugin is approved and awaiting data upload but not visible to the public yet. Once you make your first commit, the plugin will become public.', 'wporg-plugins' );
						$notice_type = 'notice-info';
						break;

					case 'rejected':
						$message = __( 'This plugin is rejected and is not visible to the public.', 'wporg-plugins' );
						break;

					case 'disabled':
						if ( current_user_can( 'plugin_approve' ) ) {
							$message = __( 'This plugin is disabled (closed, but actively serving updates) and is not visible to the public.', 'wporg-plugins' );
							break;
						}
						// fall through
					default:
					case 'closed':
						$message = __( 'This plugin is closed and is not visible to the public.', 'wporg-plugins' );
						break;
				}
			?>
			<div class="plugin-notice notice <?php echo esc_attr( $notice_type ); ?> notice-alt">
				<p><?php echo $message; ?></p>
			</div><!-- .plugin-notice -->
		<?php endif; ?>

		<div class="entry-thumbnail">
			<?php echo Template::get_plugin_icon( get_post(), 'html' ); ?>
		</div>

		<div class="plugin-actions">
			<?php
			if ( is_user_logged_in() ) :
				$url = Template::get_favorite_link();
				$is_favorited = Tools::favorited_plugin( $post );
				?>
				<div class="plugin-favorite">
					<a href="<?php echo esc_url( $url ); ?>" class="plugin-favorite-heart<?php echo $is_favorited ? ' favorited' : ''; ?>">
						<span class="screen-reader-text">
							<?php
								if ( $is_favorited ) {
									/* translators: %s: plugin name */
									printf( __( 'Unfavorite %s', 'wporg-plugins' ), get_the_title() );
								} else {
									/* translators: %s: plugin name */
									printf( __( 'Favorite %s', 'wporg-plugins' ), get_the_title() );
								}
							?>
						</span>
					</a>
					<script>
						jQuery( '.plugin-favorite-heart' )
							.on( 'click touchstart animationend', function() {
								jQuery( this ).toggleClass( 'is-animating' );
							} )
							.on( 'click', function() {
								jQuery( this ).toggleClass( 'favorited' );
							} );
					</script>
				</div>
			<?php endif; ?>

			<a class="plugin-download button download-button button-large" href="<?php echo esc_url( Template::download_link() ); ?>" itemprop="downloadUrl"><?php _e( 'Download', 'wporg-plugins' ); ?></a>
			<meta itemprop="softwareVersion" content="<?php echo esc_attr( get_post_meta( get_the_ID(), 'version', true ) ); ?>">
			<meta itemprop="fileFormat" content="application/zip">
		</div>

		<?php the_title( '<h1 class="plugin-title"><a href="' . esc_url( get_permalink() ) . '">', '</a></h1>' ); ?>

		<span class="byline"><?php
			$url = get_post_meta( get_the_ID(), 'header_author_uri', true );
			$author = strip_tags( get_post_meta( get_the_ID(), 'header_author', true ) ) ?: get_the_author();

			printf(
				_x( 'By %s', 'post author', 'wporg-plugins' ),
				'<span class="author vcard">' .
				( $url ? '<a class="url fn n" rel="nofollow" href="' . esc_url( $url ) . '">' : '' ) .
				esc_html( Template::encode( $author ) ) .
				( $url ? '</a>' : '' ) .
				'</span>'
			);
		?></span>
	</header><!-- .entry-header -->

	<div class="entry-content">
		<?php
		if ( get_query_var( 'plugin_advanced' ) ) :
			get_template_part( 'template-parts/section-advanced' );
		else:
			$plugin_sections = Template::get_plugin_sections();

			foreach ( array( 'description', 'screenshots', 'faq', 'reviews', 'changelog', 'developers' ) as $section_slug ) :
				if ( ! isset( $content[ $section_slug ] ) ) {
					continue;
				}

				$section_content = trim( apply_filters( 'the_content', $content[ $section_slug ], $section_slug ) );
				if ( empty( $section_content ) ) {
					continue;
				}

				$section = wp_list_filter( $plugin_sections, array( 'slug' => $section_slug ) );
				$section = array_pop( $section );

				$section_no_read_mores = array( 'screenshots', 'reviews' );
				// If the FAQ section is the newer `<dl>` form, no need to do read-more for it.
				if ( false !== stripos( $section_content, '<dl>' ) ) {
					$section_no_read_mores[] = 'faq';
				}

				$section_read_more = ! in_array( $section_slug, $section_no_read_mores );

				get_template_part( 'template-parts/section' );
			endforeach;
		endif; // plugin_advanced
		?>
	</div><!-- .entry-content -->

	<div class="entry-meta">
		<?php
		get_template_part( 'template-parts/plugin-sidebar', ( get_query_var( 'plugin_avanced' ) ? 'advanced' : '' ) );
		?>
	</div><!-- .entry-meta -->
</article><!-- #post-## -->
