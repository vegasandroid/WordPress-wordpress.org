<?php

namespace WordCamp\RemoteCSS;
defined( 'WPINC' ) || die();

?>

<p>
	<?php esc_html_e(
		"You don't have to manually synchronize the local file every time you make a change to the remote file; instead, you can setup a webhook to trigger synchronization automatically.",
		'wordcamporg'
	); ?>
</p>

<h2><?php esc_html_e( 'Setup', 'wordcamporg' ); ?></h2>

<p>
	<?php esc_html_e( "The details will vary depending on your server, but let's use GitHub as an example.", 'wordcamporg' ); ?>
</p>

<ol>
	<li>
		<?php printf(
			wp_kses_data( __( 'Follow <a href="%s">GitHub\'s instructions for creating a webhook</a>.', 'wordcamporg' ) ),
			'https://developer.github.com/webhooks/creating/'
		); ?>
	</li>

	<li>
		<?php printf(
			wp_kses_post( __( 'For the <code>Payload URL</code>, enter <code>%s</code>.', 'wordcamporg' ) ),
			esc_url( $webhook_payload_url )
		); ?>
	</li>

	<li><?php esc_html_e( 'For the rest of the options, you can accept the default values.', 'wordcamporg' ); ?></li>
</ol>

<p>
	<?php esc_html_e(
		"If you're not using GitHub, your process will be different, but at the end of the day all you need to do is setup something to open an HTTP request to the payload URL above whenever your file changes.",
		'wordcamporg'
	); ?>
</p>

<h2><?php esc_html_e( 'Testing &amp; Troubleshooting', 'wordcamporg' ); ?></h2>

<p>
	<?php esc_html_e(
		'To test if the synchronization is working, make a change to the file, commit it, push it to GitHub, and then check the site to see if that change is active.',
		'wordcamporg'
	); ?>
</p>

<p>
	<?php echo wp_kses_data( __(
		"If your change isn't active on WordCamp.org, edit the webhook and scroll down to the <strong>Recent Deliveries</strong> section, then open the latest delivery and look at the <strong>Response</strong> tab for any errors.",
		'wordcamporg'
	) ); ?>
</p>

<p>
	<?php printf(
		// translators: %s: WordPress Slack URL.
		wp_kses_post( __(
			'If that doesn\'t help solve the problem, you can ask for help in the <code>#meta-wordcamp</code> channel on <a href="%s">Slack</a>.',
			'wordcamporg'
		) ),
		'https://chat.wordpress.org'
	); ?>
</p>
