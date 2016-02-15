<?php
/**
 * Static page template.
 *
 * @package P2
 */
?>
<?php get_header(); ?>

<div class="handbook-name"><span><a href="<?php echo esc_url( get_post_type_archive_link( 'handbook' ) ); ?>"><?php echo esc_html( WPorg_Handbook::get_name() ); ?></a></span></div>

<!-- Also called on in footer but will not display the second time. -->
<?php get_sidebar( get_post_type() ); ?> 

<div class="sleeve_main">

	<div id="main">
		<h2 class="handbook-page-title"><?php the_title(); ?></h2>
		<?php if ( have_posts() ) : ?>

			<ul id="postlist">
			<?php while ( have_posts() ) : the_post(); ?>
				<?php get_template_part( 'entry', 'handbook' ); ?>
			<?php endwhile; ?>
			</ul>

			<?php \WPorg_Handbook_Navigation::navigate_via_menu( 'Table of Contents' ); ?>

		<?php endif; ?>

	</div> <!-- main -->

</div> <!-- sleeve -->

<?php get_footer(); ?>
