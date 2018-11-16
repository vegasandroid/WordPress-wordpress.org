<?php
/**
 * Template for displaying Images (post formats)
 */
?>
		<h2 class="entry-title"><a href="<?php the_permalink(); ?>" title="<?php printf( esc_attr__( 'Permalink to %s', 'twentyten' ), the_title_attribute( 'echo=0' ) ); ?>" rel="bookmark"><?php the_title(); ?></a></h2>

		<div class="entry-meta">
			Posted by <?php the_author_posts_link(); ?> on <a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>"><?php the_date(); ?></a> with <?php comments_popup_link( 'No replies yet', '1 reply', '% replies', 'comments-link', 'Comments are off for this post');?>
		</div><!-- .entry-meta -->

		<?php echo get_avatar( get_the_author_meta('ID'), 60 ); ?>


<?php if ( is_archive() || is_search() ) : // Only display excerpts for archives and search. ?>
		<div class="entry-summary">
			<?php the_excerpt(); ?>
		</div><!-- .entry-summary -->
<?php else : ?>
		<div class="entry-content">
			<?php the_content( __( 'Continue reading <span class="meta-nav">&rarr;</span>', 'twentyten' ) ); ?>
			<?php wp_link_pages( array( 'before' => '<div class="page-link">' . __( 'Pages:', 'twentyten' ), 'after' => '</div>' ) ); ?>

			<div class="entry-utility">
				<?php if ( count( get_the_category() ) ) : ?>
					<span class="cat-links">
						<?php printf(
							wp_kses_post( __( '<span class="%1$s">Categories</span> %2$s', 'twentyten' ) ),
							'entry-utility-prep entry-utility-prep-cat-links',
							wp_kses_post( get_the_category_list( ', ' ) )
						); ?>
					</span>
					<span class="meta-sep">|</span>
				<?php endif; ?>
				<?php
					$tags_list = get_the_tag_list( '', ', ' );
					if ( $tags_list ):
				?>
					<span class="tag-links">
						<?php printf(
							wp_kses_post( __( '<span class="%1$s">Tags</span> %2$s', 'twentyten' ) ),
							'entry-utility-prep entry-utility-prep-tag-links',
							wp_kses_post( $tags_list )
						); ?>
					</span>
				<?php endif; ?>
				<?php edit_post_link( __( 'Edit', 'twentyten' ), '<span class="edit-link">', '</span>' ); ?>
			</div><!-- .entry-utility -->

		</div><!-- .entry-content -->
<?php endif; ?>
