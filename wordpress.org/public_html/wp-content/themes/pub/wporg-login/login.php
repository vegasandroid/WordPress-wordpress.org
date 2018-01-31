<?php
/**
 * The login form Template
 *
 * @package wporg-login
 */

get_header();
?>
<p class="intro"><?php _e( 'Log in to your WordPress.org account to contribute to WordPress, get help in the support forum, or rate and review themes and plugins.', 'wporg' ); ?></p>

<?php wp_login_form(); ?>

<p id="nav">
	<a href="/lostpassword/" title="<?php _e( 'Password Lost and Found', 'wporg' ); ?>"><?php _e( 'Lost password?', 'wporg' ); ?></a> &nbsp; • &nbsp;
	<a href="<?php echo esc_url( wp_registration_url() ); ?>" title="<?php _e( 'Create an account', 'wporg' ); ?>"><?php _e( 'Create an account', 'wporg' ); ?></a>
</p>

<script type="text/javascript">
setTimeout( function() {
	try {
		d = document.getElementById( 'user_login' );
		d.focus();
		d.select();
	} catch( e ){}
}, 200 );
</script>


<?php get_footer(); ?>
