<?php

/**
 * The front page of the site.
 *
 * @package WPBBP
 */

get_header();?>

	<main id="main" class="site-main home-page" role="main">
		<section class="quick-intro">
			<div class="shapes">
				<a class="parallelogram workshop-ideas dashicons-before dashicons-slides" href="/workshops/">
					<p>
						<strong><?php _e( 'Workshops', 'wporg-forums' ); ?></strong>
						<?php
						_e( 'Workshops are a great way to get hands-on with WordPress. Here are some workshops for you to level up your WordPress skills.', 'wporg-learn' );
						?>
						<u><?php _e( 'Browse Workshops' ); ?></u>
					</p>
				</a>
				<a class="parallelogram lesson-plans dashicons-before dashicons-clipboard" href="/lesson-plans/">
					<p>
						<strong><?php _e( 'Lesson Plans', 'wporg-forums' ); ?></strong>
						<?php
							_e( 'Are you teaching WordPress to others? These lesson plans are designed to guide and inspire you to deliver great content.', 'wporg-learn' );
						?>
						<u><?php _e( 'See the Lesson Plans' ); ?></u>
					</p>
				</a>
			</div>
		</section>

		<section class="about-training">
			<div class="getin">
				<div class="graphic"><span class="dashicons dashicons-welcome-learn-more"></span></div>
				<div>
					<h2 class="h3"><?php _e( 'Get Involved' ); ?></h2>
					<p><?php _e( 'Want to grow WordPress? As part of Training Team you could help others learn WordPress accross the world.' ); ?></p>
					<a href="https://make.wordpress.org/training/handbook/"><?php _e( 'Learn About The Training Team' ); ?></a>
				</div>
			</div>
		</section>

		<hr>
		<section>
			<div class="row align-middle between section-heading">
				<h2 class="h4 section-heading_title"><?php esc_html_e( 'Recent Workshops', 'wporg-learn' ); ?></h2>
				<a class="section-heading_link" href="/workshops"><?php esc_html_e( 'View All »', 'wporg-learn' ); ?></a>
			</div>

			<?php
				$args = array(
					'posts_per_page' => '3',
				);
				set_query_var( 'video-grid-options', $args );
				get_template_part( 'template-parts/component', 'video-grid' );
			?>
		</section>
		<hr>

		<section class="lesson-lists clearfix">
			<div class="col">
				<h2 class="screen-reader-text"><?php _e( 'Lesson Plans' ); ?></h2>
				<div class="lesson-item">
					<h3 class="h4 title"><?php _e( 'User-oriented Lesson Plans' ); ?></h3>
					<p><?php _e( 'User lessons are targeted towards end-users, those who actually publish content.' ); ?></p>
					<a class="viewmore" href="#"><?php _e( 'View the lesson plans' ); ?></a>
				</div>
				<div class="lesson-item">
					<h3 class="h4 title"><?php _e( 'Theme-oriented Lesson Plans' ); ?></h3>
					<p><?php _e( 'Theme lessons are targeted towards entry-level developers, those who actually write code.' ); ?></p>
					<a class="viewmore" href="#"><?php _e( 'View the lesson plans' ); ?></a>
				</div>
				<div class="lesson-item">
					<h3 class="h4 title"><?php _e( 'Plugin-oriented Lesson Plans' ); ?></h3>
					<p><?php _e( 'Plugin lessons are targeted towards entry-level developers, those who actually write code.' ); ?></p>
					<a class="viewmore" href="#"><?php _e( 'View the lesson plans' ); ?></a>
				</div>
			</div>

			<div class="col">
				<h2 class="screen-reader-text"><?php _e( 'Workshop Ideas' ); ?></h2>
				<div class="lesson-item">
					<h3 class="h4 title"><?php _e( 'Half-day Workshop Ideas' ); ?></h3>
					<p><?php _e( 'Concepts and activities for workshops that are only a few hours long.' ); ?></p>
					<a class="viewmore" href="#"><?php _e( 'View the Workshop Ideas' ); ?></a>
				</div>
				<div class="lesson-item">
					<h3 class="h4 title"><?php _e( 'Full-day Workshop Ideas' ); ?></h3>
					<p><?php _e( 'Concepts and activities for workshops that fill the whole day.' ); ?></p>
					<a class="viewmore" href="#"><?php _e( 'View the Workshop Ideas' ); ?></a>
				</div>
				<div class="lesson-item">
					<h3 class="h4 title"><?php _e( 'Multi-day Workshop Ideas' ); ?></h3>
					<p><?php _e( 'Concepts and activities for workshops that span the course of 2 or more days.' ); ?></p>
					<a class="viewmore" href="#"><?php _e( 'View the Workshop Ideas' ); ?></a>
				</div>
			</div>

			<div class="col">
				<div class="lesson-item">
					<h2 class="h4 title"><?php _e( 'Want to Help More People Speak at Meetups and WordCamps?' ); ?></h2>
					<p><?php _e( 'WordPress is a built on a community where diversity and inclusion are key to growth. Meetups and WordCamps are the best way to teach others about WordPress on a local level and these resources can help diversify the speakers at these events.' ); ?></p>
					<a class="viewmore" href="#"><?php _e( 'View the Speaker Diversity Lesson plans' ); ?></a>
				</div>
				<div class="lesson-item">
					<h2 class="h4 title"><?php _e( 'Helpful Links' ); ?></h2>
					<a class="viewmore" href="#"><?php _e( 'WordCamp Central' ); ?></a> <br />
					<a class="viewmore" href="#"><?php _e( 'WordPress Meetups' ); ?></a>
				</div>
			</div>
		</section>

		<hr>

		<?php wporg_submit_idea_cta(); ?>

		<?php if ( ! is_active_sidebar( 'front-page-blocks' ) ) : ?>
			<?php //get_template_part( 'template-parts/bbpress', 'front' ); ?>
		<?php else : ?>
			<div class="three-up helphub-front-page">
				<?php dynamic_sidebar( 'front-page-blocks' ); ?>
			</div>

			<hr>

			<div id="helphub-forum-link" class="text-center">
				<h3><?php esc_html_e( 'Support Forums', 'wporg-forums' ); ?></h3>

				<p>
					<span>
						<?php esc_html_e( 'Can\'t find what you\'re looking for? Find out if others share your experience.', 'wporg-forums' ); ?>
					</span>

					<br>

					<a href="<?php echo esc_url( site_url( '/forums/' ) ); ?>"><?php esc_html_e( 'Check out our support forums', 'wporg-forums' ); ?></a>
				</p>
			</div>
		<?php endif; ?>

	</main>

<?php
get_footer();
