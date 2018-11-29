<?php

namespace CampTix\Badge_Generator\InDesign;
defined( 'WPINC' ) || die();

/**
 * @var string $html_customizer_url
 */

?>

<h2>
	<?php esc_html_e( 'InDesign Badges', 'wordcamporg' ); ?>
</h2>

<p>
	<?php esc_html_e(
		"The process for building InDesign badges hasn't been automated yet, so it requires a developer to run a script. That script will create a CSV file and will download Gravatar images for all attendees. Once that's done, a designer can take those files into InDesign and use the Data Merge feature to create personalized badges for each attendee.",
		'wordcamporg'
	); ?>
</p>

<p>
	<?php printf(
		wp_kses_post( __(
			'Full instructions are <a href="%1$s">available in the WordCamp Organizer Handbook</a>. If you\'d prefer an easier way, <a href="%2$s">the HTML/CSS method</a> is much more automated at this time.',
			'wordcamporg'
		) ),
		'https://make.wordpress.org/community/handbook/wordcamp-organizer-handbook/first-steps/helpful-documents-and-templates/create-wordcamp-badges-with-gravatars/',
		esc_url( $html_customizer_url )
	); ?>
</p>

<p>
	<?php printf(
		wp_kses_post( __( 'If you\'d like to help automate the InDesign process, you can contribute to <a href="%s">Meta ticket #262</a>.', 'wordcamporg' ) ),
		'https://meta.trac.wordpress.org/ticket/262'
	); ?>
</p>
