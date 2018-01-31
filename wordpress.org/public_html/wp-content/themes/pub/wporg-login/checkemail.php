<?php
/**
 * The post-resetpassword Template
 *
 * @package wporg-login
 */

get_header();
?>

<p class="center singleline"><?php _e( 'Check your email for a confirmation link.', 'wporg' ); ?></p>

<p id="nav">
	<a href="/"><?php _e( '&larr; Back to login', 'wporg' ); ?></a>
</p>

<?php get_footer(); ?>