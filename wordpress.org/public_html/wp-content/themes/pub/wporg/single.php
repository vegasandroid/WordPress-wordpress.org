<?php
/**
 * The template for displaying all single posts.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package WordPressdotorg\Theme
 */

namespace WordPressdotorg\Theme;

get_header( 'page' ); ?>

	<main id="main" class="site-main" role="main">

		<?php
			while ( have_posts() ) :
				the_post();

				get_template_part( 'template-parts/content', 'single' );

				// If comments are open or we have at least one comment, load up the comment template.
				if ( comments_open() || get_comments_number() ) :
					comments_template();
				endif;

				// Previous/next post navigation.
				the_post_navigation( array(
					'next_text' => '<span class="meta-nav" aria-hidden="true">' . __( 'Next', 'wporg' ) . '</span> ' .
					               '<span class="screen-reader-text">' . __( 'Next post:', 'wporg' ) . '</span> ' .
					               '<span class="post-title">%title</span>',
					'prev_text' => '<span class="meta-nav" aria-hidden="true">' . __( 'Previous', 'wporg' ) . '</span> ' .
					               '<span class="screen-reader-text">' . __( 'Previous post:', 'wporg' ) . '</span> ' .
					               '<span class="post-title">%title</span>',
				) );
			endwhile; // End of the loop.
		?>

	</main><!-- #main -->

<?php
get_footer();
