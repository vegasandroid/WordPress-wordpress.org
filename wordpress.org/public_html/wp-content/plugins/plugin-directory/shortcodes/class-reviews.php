<?php
namespace WordPressdotorg\Plugin_Directory\Shortcodes;
use WordPressdotorg\Plugin_Directory\Template;
use WordPressdotorg\Plugin_Directory\Tools;

/**
 * The [wporg-plugins-reviews] shortcode handler to display plugin reviews.
 *
 * @package WordPressdotorg\Plugin_Directory\Shortcodes
 */
class Reviews {

	/**
	 * @return string
	 */
	static function display() {
		$reviews = Tools::get_plugin_reviews( get_post()->post_name );
		$ratings = get_post_meta( get_the_ID(), 'ratings', true ) ?: array();
		$review_count = array_sum( $ratings );

		if ( empty( $reviews ) ) {
			return '';
		}

		ob_start();
		?>

		<div class="plugin-reviews">
			<?php
		foreach ( $reviews as $review ) :
				$reviewer = get_user_by( 'id', $review->post_author );
				if ( ! $reviewer ) :
					continue;
				endif;
				?>
				<article class="plugin-review">
					<div class="review-avatar">
						<?php echo get_avatar( $reviewer->ID, 60 ); ?>
					</div><div class="review">
						<header>
							<?php if ( !empty( $review->ID ) ) : ?>
								<h3 class="review-title"><a class="url" href="<?php echo esc_url( add_query_arg( array( 'p' => $review->ID ), 'https://wordpress.org/support/plugin/' ) ); ?>"><?php echo $review->post_title; ?></a></h3>
							<?php else: ?>
								<h3 class="review-title"><?php echo $review->post_title; ?></h3>
							<?php endif; ?>
							<?php echo Template::dashicons_stars( $review->post_rating ); ?>
							<span class="review-author author vcard"><a class="url fn n" href="<?php echo esc_url( get_author_posts_url( $reviewer->ID ) ); ?>"><?php echo Template::encode( $reviewer->display_name ); ?></a></span>
						</header>
						<p class="review-content"><?php echo $review->post_content; ?></p>
					</div>
				</article>
			<?php endforeach; ?>
		</div>

		<a class="reviews-link" href="<?php echo esc_url( 'https://wordpress.org/support/plugin/' . get_post()->post_name . '/reviews/' ); ?>">
			<?php printf( __( 'Read all %s reviews', 'wporg-plugins' ), number_format_i18n( $review_count ) ); ?>
		</a>

		<?php
		return ob_get_clean();
	}
}
