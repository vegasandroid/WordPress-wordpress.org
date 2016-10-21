<?php
namespace WordPressdotorg\Plugin_Directory\Widgets;
use WordPressdotorg\Plugin_Directory\Template;

/**
 * A Widget to display contributor information about a plugin.
 *
 * @package WordPressdotorg\Plugin_Directory\Widgets
 */
class Contributors extends \WP_Widget {

	/**
	 * Meta constructor.
	 */
	public function __construct() {
		parent::__construct( 'plugin_contributors', __( 'Plugin Contributors', 'wporg-plugins' ), array(
			'classname'   => 'plugin-contributors',
			'description' => __( 'Displays contributor information.', 'wporg-plugins' ),
		) );
	}

	/**
	 * Outputs the content of the widget.
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		$post = get_post();

		$contributors = (array) wp_list_pluck( (array)get_the_terms( $post, 'plugin_contributors' ), 'name' );
		$contributors = array_map( function( $user_nicename ) {
			return get_user_by( 'slug', $user_nicename );
		}, $contributors );

		echo $args['before_widget'];
		?>
		<style>
			<?php // TODO: Yes, these need to be moved into the CSS somewhere. ?>
			ul.contributors-list {
				list-style: none;
				margin: 0;
				font-size: 0.9em;
			}
			ul.contributors-list li {
				padding-bottom: 0.5em;
			}
		</style>
		<h3><?php _e( 'Contributors', 'wporg-plugins' ); ?></h3>
		<p class="widget-subnav"><small><a href="#"><?php _e( 'View the capabilities these users have', 'wporg-plugins' ); ?></a></small></p>

		<ul id="contributors-list" class="contributors-list read-more" aria-expanded="false">
		<?php foreach ( $contributors as $contributor ) {
			echo '<li>' . get_avatar( $contributor->ID, 32 ) . '<a href="' . esc_url( 'https://profiles.wordpress.org/' . $contributor->user_nicename ) . '">' . Template::encode( $contributor->display_name ) . '</a></li>';
		} ?>
		</ul>
		<button type="button" class="button-link section-toggle" aria-controls="contributors-list"><?php _e( 'View more', 'wporg-plugins' ); ?></button>

		<?php
		echo $args['after_widget'];
	}
}
