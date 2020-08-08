
<?php
/**
 * Template part for displaying the featured workshop.
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package WPBBP
 */

$args = array( 'category_name' => 'Featured', 'posts_per_page' => '1' );

$featured_workshop = wporg_get_workshops( $args );
?>

<div class="featured-workshop">
	<div class="featured-workshop_video"><?php echo the_post_thumbnail( 'full' ); ?></div>
	<div class="featured-workshop_content">
		<?php while ( $featured_workshop->have_posts() ) : 
				$featured_workshop->the_post();	
		?>
		<a class="featured-workshop_title" href="<?php echo esc_url( get_the_permalink() ); ?>"><?php echo the_title() ?></a>
		<div class="row">
			<div class="col-8">	
				<p>With WordPress moving more and more into the world of blocks, knowing how to build your own blocks has become valuable knowledge. However, if you are a plugin or theme developer, you might not be sure where to start. This workshop will serve as a guide to building your first block.</p>
			</div>
			<div class="col-4 featured-workshop_content_author">
				<?php get_template_part( 'template-parts/component', 'author' ); ?>
			</div>
		</div>
		<?php endwhile; ?>
	</div>
</div>