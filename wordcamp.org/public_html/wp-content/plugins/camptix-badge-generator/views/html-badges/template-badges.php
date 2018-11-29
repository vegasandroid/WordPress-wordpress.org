<?php

namespace CampTix\Badge_Generator\HTML;
use WP_Post;

defined( 'WPINC' ) || die();

/**
 * @global string  $template
 * @var    array   $attendees
 * @var    WP_Post $attendee
 */

/*
 * template-loader.php includes this file in the global scope, which is ugly. So, include this again from a
 * function, so that we get a nice, clean, local scope.
 */
if ( isset( $template ) && __FILE__ === $template ) {
	render_badges_template();
	return;
}

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
	<title><?php esc_html_e( 'CampTix HTML Badges', 'wordcamporg' ); ?></title>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<?php wp_head(); ?>
</head>

<body>
	<?php

	if ( empty( $attendees ) ) :

		esc_html_e( 'No attendees were found. Please try again once tickets have been purchased.', 'wordcamporg' );

	else :

		foreach ( $attendees as $attendee ) : ?>
			<article class="attendee <?php echo esc_attr( $attendee->css_classes ); ?>">
				<section class="badge badge-back">
					<?php require( __DIR__ . '/template-part-badge-contents.php' ); ?>
				</section>

				<section class="badge badge-front">
					<div class="holepunch">&#9421;</div>

					<?php require( __DIR__ . '/template-part-badge-contents.php' ); ?>
				</section>

				<!-- These are arbitrary elements that you can use for any purpose -->
				<div class="attendee-design-element-1"></div>
				<div class="attendee-design-element-2"></div>
				<div class="attendee-design-element-3"></div>
				<div class="attendee-design-element-4"></div>
				<div class="attendee-design-element-5"></div>
			</article>
		<?php endforeach;

	endif;

	wp_footer();

	?>
</body>
</html>
