<?php

namespace WordCamp\RemoteCSS;
defined( 'WPINC' ) || die();

/**
 * @var string $fonts_tool_url
 * @var string $media_library_url
 */

?>

<ul>
	<li>
		<?php printf(
			wp_kses_data( __( 'We recommend <a href="%s">setting up a local development environment that mirrors WordCamp.org</a>.', 'wordcamporg' ) ),
			'https://make.wordpress.org/community/handbook/wordcamp-organizer-handbook/first-steps/web-presence/contributing-to-wordcamp-org/setting-up-a-local-wordcamp-org-sandbox/'
		); ?>
	</li>

	<li>
		<?php echo wp_kses_data( __(
			"Don't use post IDs as selectors, because they can change between your development environment and production. Instead, use the slug; e.g. <code>body.post-slug-call-for-volunteers</code>, or <code>body.wcb_speaker-slug-sergey-biryukov</code>. Just make sure that you update your CSS if you rename a post.",
			'wordcamporg'
		) ); ?>
	</li>

	<li>
		<?php printf(
			wp_kses_data( __( 'Use <a href="%s">the Fonts tool</a> to embed your web fonts.', 'wordcamporg' ) ),
			esc_url( $fonts_tool_url )
		); ?>
	</li>

	<li>
		<?php printf(
			wp_kses_data( __(
				'Upload your images to <a href="%s">the Media Library</a> rather than hosting them on 3rd party servers. That way, visitors will avoid an extra DNS request, and you won\'t have to worry about them going offline if there\'s a problem with the external server.',
				'wordcamporg'
			) ),
			esc_url( $media_library_url )
		); ?>
	</li>

	<li>
		<?php esc_html_e(
			"This tool plays nicely with the Core/Jetpack editor, and it's possible to use both. If you do, the rules in the Core/Jetpack editor will take precedence.",
			'wordcamporg'
		); ?>
	</li>
</ul>
