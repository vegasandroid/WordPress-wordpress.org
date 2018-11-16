<?php
/**
 * Category archives template
 */
?>
<?php get_header(); ?>

	<div id="container" class="group">
		<div id="content" role="main" class="group">

			<h1 class="page-title">
				<?php printf(
					wp_kses_post( __( 'Category Archives: %s', 'twentyten' ) ),
					'<span>' . single_cat_title( '', false ) . '</span>'
				); ?>
			</h1>

			<?php

			$category_description = category_description();

			if ( ! empty( $category_description ) ) {
				echo '<div class="archive-meta">' . wp_kses_post( $category_description ) . '</div>';
			}

			?>

			<?php get_search_form(); ?>

			<?php get_template_part( 'navigation-above' ); ?>

			<?php while ( have_posts() ) :
				the_post(); ?>

					<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

						<?php get_template_part( 'content', get_post_format() ); ?>

					</div><!-- #post-## -->

			<?php endwhile; // End the loop. Whew. ?>

			<?php get_template_part( 'navigation-below' ); ?>

		</div><!-- #content -->

	</div><!-- #container -->

	<?php get_sidebar(); ?>

<?php get_footer(); ?>
