<?php
namespace WordPressdotorg\Make\Breathe;

function styles() {
	wp_dequeue_style('breathe-style');
	wp_enqueue_style( 'p2-breathe', get_template_directory_uri() . '/style.css' );

	// Cacheing hack
	wp_enqueue_style( 'wporg-breathe', get_stylesheet_uri(), array( 'p2-breathe' ), '20160729' );
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\styles', 11 );

function inline_scripts() {
	?>
	<script type="text/javascript">
		var el = document.getElementById( 'make-welcome-hide' );
		if ( el ) {
			el.addEventListener( 'click', function( e ) {
				document.cookie = el.dataset.cookie + '=' + el.dataset.hash + '; expires=Fri, 31 Dec 9999 23:59:59 GMT';
				jQuery( '.make-welcome' ).slideUp();
			} );
		}
	</script>
	<?php
}
add_action( 'wp_footer', __NAMESPACE__ . '\inline_scripts' );

function welcome_box() {
	$welcome = get_page_by_path( 'welcome' );
	$cookie  = 'welcome-' . get_current_blog_id();
	$hash    = isset( $_COOKIE[ $cookie ] ) ? $_COOKIE[ $cookie ] : '';
	$content_hash = $welcome ? md5( $welcome->post_content ) : '';

	if ( $welcome && ( empty( $hash ) || $content_hash !== $hash ) ) :
		$columns = preg_split( '|<hr\s*/?>|', $welcome->post_content );
		if ( count( $columns ) === 2 ) {
			$welcome->post_content = "<div class='content-area'>\n\n{$columns[0]}</div><div class='widget-area'>\n\n{$columns[1]}</div>";
		}
		setup_postdata( $welcome );

		// Disable Jetpack sharing buttons
		add_filter( 'sharing_show', '__return_false' );
	?>
	<div class="make-welcome">
		<div class="entry-meta">
			<?php edit_post_link( __( 'Edit', 'o2' ), '', '', $welcome->ID ); ?>
			<button type="button" id="make-welcome-hide" class="toggle dashicons dashicons-no" data-hash="<?php echo $content_hash; ?>" data-cookie="<?php echo $cookie; ?>" title="<?php esc_attr_e( 'Hide this message', 'p2-breathe' ); ?>"></button>
		</div>
		<div class="entry-content clear">
			<?php the_content(); ?>
		</div>
	</div>
	<?php
		remove_filter( 'sharing_show', '__return_false' );
		wp_reset_postdata();
	endif;
}
add_action( 'wporg_breathe_after_header', __NAMESPACE__ . '\welcome_box' );
