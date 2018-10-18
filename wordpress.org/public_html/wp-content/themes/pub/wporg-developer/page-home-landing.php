<?php
/**
 * The template for displaying the Code Reference landing page.
 *
 * Template Name: Home
 *
 * @package wporg-developer
 */

get_header(); ?>

	<div id="primary" class="content-area">

		<div class="home-landing">

			<div class="inner-wrap section">

				<div class="box box-code-ref">
					<h3 class="widget-title"><div class="dashicons dashicons-editor-code"></div><?php _e( 'Code Reference', 'wporg' ); ?></h3>
					<p class="widget-description"><?php _e( 'Looking for documentation for the codebase?', 'wporg' ); ?></p>
					<a href="<?php echo esc_url( home_url( '/reference' ) ); ?>" class="go"><?php _e( 'Visit the reference', 'wporg' ); ?></a>
				</div>

				<div class="box box-themes">
					<h3 class="widget-title"><div class="dashicons dashicons-admin-appearance"></div><?php _e( 'Themes', 'wporg' ); ?></h3>
					<p class="widget-description"><?php _e( 'Want to learn how to start theming WordPress?', 'wporg' ); ?></p>
					<a href="<?php echo esc_url( get_post_type_archive_link( 'theme-handbook' ) ); ?>" class="go"><?php _e( 'Develop Themes ', 'wporg' ); ?></a>
				</div>

				<div class="box box-plugins">
					<h3 class="widget-title"><div class="dashicons dashicons-admin-plugins"></div><?php _e( 'Plugins', 'wporg' ); ?></h3>
					<p class="widget-description"><?php _e( 'Ready to dive deep into the world of plugin authoring?', 'wporg' ); ?></p>
					<a href="<?php echo esc_url( get_post_type_archive_link( 'plugin-handbook' ) ); ?>" class="go"><?php _e( 'Develop Plugins ', 'wporg' ); ?></a>
				</div>

				<div class="box box-rest-api">
					<h3 class="widget-title"><div class="dashicons dashicons-controls-repeat"></div><?php _e( 'REST API', 'wporg' ); ?></h3>
					<p class="widget-description"><?php _e( 'Getting started on making WordPress applications?', 'wporg' ); ?></p>
					<a href="<?php echo esc_url( get_post_type_archive_link( 'rest-api-handbook' ) ); ?>" class="go"><?php _e( 'Make Applications ', 'wporg' ); ?></a>
				</div>

				<div class="box box-wp-cli">
					<h3 class="widget-title"><div class="dashicons dashicons-arrow-right-alt2"></div><?php _e( 'WP-CLI', 'wporg' ); ?></h3>
					<p class="widget-description"><?php _e( 'Want to accelerate your workflow managing WordPress?', 'wporg' ); ?></p>
					<a href="<?php echo esc_url( get_post_type_archive_link( 'command' ) ); ?>" class="go"><?php _e( 'Run Commands ', 'wporg' ); ?></a>
				</div>

			</div>

			<div class="search-guide inner-wrap section">

				<?php if ( is_active_sidebar( 'landing-footer-1') ) { ?>
					<?php dynamic_sidebar( 'landing-footer-1' ); ?>
				<?php } else { ?>
					<div class=" box"></div>
				<?php } ?>

				<div class="box">
					<h3 class="widget-title"><?php _e( 'Contribute', 'wporg' ); ?></h3>
					<ul class="unordered-list no-bullets">
						<li><a href="https://make.wordpress.org/" class="make-wp-link"><?php _e( 'Help Make WordPress', 'wporg' ); ?></a></li>
					</ul>
				</div>

				<?php if ( is_active_sidebar( 'landing-footer-2') ) { ?>
					<?php dynamic_sidebar( 'landing-footer-2' ); ?>
				<?php } else { ?>
					<div class=" box"></div>
				<?php } ?>

			</div>

		</div><!-- /home-landing -->
	</div><!-- #primary -->

<?php get_footer(); ?>

