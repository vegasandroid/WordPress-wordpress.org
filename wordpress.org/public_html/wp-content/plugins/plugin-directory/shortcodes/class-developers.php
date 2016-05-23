<?php
namespace WordPressdotorg\Plugin_Directory\Shortcodes;
use WordPressdotorg\Plugin_Directory\Tools;

/**
 * The [wporg-plugins-developers] shortcode handler to display developer information.
 *
 * @package WordPressdotorg\Plugin_Directory\Shortcodes
 */
class Developers {

	/**
	 * @return string
	 */
	static function display() {
		$post       = get_post();
		$committers = Tools::get_plugin_committers( $post->post_name );

		$output = '<ul class="plugin-developers">';
		foreach ( $committers as $committer_slug ) {
			$committer = get_user_by( 'slug', $committer_slug );

			$output .= '<li>' . get_avatar( $committer->ID, 32 ) . $committer->display_name . '</li>';
		}
		$output .= '</ul>';

		return $output .
			'<h5>' . __( 'Browse the code', 'wporg-plugins' ) . '</h5>' .
			'<ul>' .
				'<li>' .
					'<a href="' . esc_url( "https://plugins.trac.wordpress.org/log/{$post->post_name}/" ) . '" rel="nofollow">' . __( 'Development Log', 'wporg-plugins' ) . '</a>' . "\n" .
					'<a href="' . esc_url( "https://plugins.trac.wordpress.org/log/{$post->post_name}/?limit=100&mode=stop_on_copy&format=rss" ) . '" rel="nofollow"><img src="//s.w.org/style/images/feedicon.png" /></a>' .
				'</li>' .
				'<li><a href="' . esc_url( "https://plugins.svn.wordpress.org/{$post->post_name}/" ) . '" rel="nofollow">' . __( 'Subversion Repository', 'wporg-plugins' ) . '</a></li>' .
				'<li><a href="' . esc_url( "https://plugins.trac.wordpress.org/browser/{$post->post_name}/" ) . '" rel="nofollow">' . __( 'Browse in Trac', 'wporg-plugins' ) . '</a></li>' .
				'<li><a href="' . esc_url( "https://translate.wordpress.org/projects/wp-plugins/{$post->post_name}/" ) . '" rel="nofollow">' . __( 'Translation Contributors', 'wporg-plugins' ) . '</a></li>' .
			'</ul>';
	}
}
