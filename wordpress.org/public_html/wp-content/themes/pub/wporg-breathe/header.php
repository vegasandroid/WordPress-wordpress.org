<?php
$GLOBALS['pagetitle'] = wp_get_document_title();
require WPORGPATH . 'header.php';
?>
<header id="masthead" class="site-header" role="banner">
	<a href="#" id="secondary-toggle" onclick="return false;"><strong><?php _e( 'Menu' ); ?></strong></a>
	<div class="site-branding">
		<?php if ( is_front_page() && is_home() ) : ?>
			<h1 class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></h1>
		<?php else : ?>
			<p class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></p>
		<?php endif; ?>
	</div>

	<nav id="site-navigation" class="navigation-main clear" role="navigation">
		<div class="screen-reader-text skip-link"><a href="#content" title="<?php _e( 'Skip to content', 'p2-breathe' ); ?>"><?php _e( 'Skip to content', 'p2-breathe' ); ?></a></div>

		<?php wp_nav_menu( array( 'theme_location' => 'primary', 'fallback_cb' => false, 'depth' => 1 ) ); ?>
	</nav><!-- .navigation-main -->
</header><!-- .site-header -->

<?php do_action( 'wporg_breathe_after_header' ); ?>

<div id="page" class="hfeed site">
	<?php do_action( 'before' ); ?>

	<div id="main" class="site-main clear">
