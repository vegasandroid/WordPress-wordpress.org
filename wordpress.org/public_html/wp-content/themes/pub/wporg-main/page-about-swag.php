<?php
/**
 * Template Name: Swag
 *
 * Page template for displaying the Swag page.
 *
 * @package WordPressdotorg\MainTheme
 */

namespace WordPressdotorg\MainTheme;

if ( false === stristr( home_url(), 'test' ) && ! isset( $_GET['test'] ) ) {
	return get_template_part( 'page' );
}

$GLOBALS['menu_items'] = [
	'about/philosophy'   => __( 'Philosophy', 'wporg' ),
	'about/etiquette'    => __( 'Etiquette', 'wporg' ),
	'about/swag'         => __( 'Swag', 'wporg' ),
	'about/logos'        => __( 'Graphics &amp; Logos', 'wporg' ),
	'about/testimonials' => __( 'Testimonials', 'wporg' ),
];

// Prevent Jetpack from looking for a non-existent featured image.
add_filter( 'jetpack_images_pre_get_images', function() {
	return new \WP_Error();
} );

// See inc/page-meta-descriptions.php for the meta description for this page.

get_header( 'child-page' );
the_post();
?>

	<main id="main" class="site-main col-12" role="main">

		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<header class="entry-header">
				<h1 class="entry-title col-8"><?php esc_html_e( 'Swag', 'wporg' ); ?></h1>
			</header><!-- .entry-header -->

			<div class="entry-content row">
				<section class="col-8">
					<p>
						<a class="alignright" href="https://mercantile.wordpress.org">
							<img width="132" height="177" src="https://s.w.org/images/home/swag_col-1.jpg?1" alt="<?php esc_attr_e( 'WordPress Swag', 'wporg' ); ?>" />
						</a>
						<?php
						/* translators: Link to swag store */
						printf( wp_kses_post( __( 'Whether you&#8217;re a seasoned WordPress fanatic or just getting warmed up, wear your WordPress love with pride. The official <a href="%s">WordPress Swag Store</a> sells shirts and hoodies in a variety of designs and colors, printed on stock from socially responsible companies.', 'wporg' ) ), esc_url( 'https://mercantile.wordpress.org' ) );
						?>
					</p>
					<p><?php esc_html_e( 'The swag store also rotates other products through the lineup on a regular basis.', 'wporg' ); ?></p>
					<p><?php esc_html_e( 'The proceeds from these sales help offset the cost of providing free swag (buttons, stickers, etc.) to WordCamps and WordPress meetups around the world.', 'wporg' ); ?></p>
					<p>
						<?php
						/* translators: Link to swag store */
						printf( wp_kses_post( __( 'So show the love and spread the word &mdash; get your <a href="%s">WordPress swag</a> today.', 'wporg' ) ), esc_url( 'https://mercantile.wordpress.org' ) );
						?>
					</p>

				</section>

			</div><!-- .entry-content -->

		</article><!-- #post-## -->

	</main><!-- #main -->

<?php
get_footer();
