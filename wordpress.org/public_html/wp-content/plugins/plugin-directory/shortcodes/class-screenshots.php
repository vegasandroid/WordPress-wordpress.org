<?php
namespace WordPressdotorg\Plugin_Directory\Shortcodes;
use WordPressdotorg\Plugin_Directory\Template;

/**
 * The [wporg-plugins-screenshots] shortcode handler to display a plugins screenshots.
 *
 * @package WordPressdotorg_Plugin_Directory
 */
class Screenshots {
	static function display() {
		$plugin = get_post();

		// All indexed from 1
		$screenshot_descriptions = get_post_meta( $plugin->ID, 'screenshots', true );
		$assets_screenshots = get_post_meta( $plugin->ID, 'assets_screenshots', true );

		$output = '';
		foreach ( $screenshot_descriptions as $index => $description ) {
			// Find the image that corresponds with the text.
			// The image numbers are stored within the 'resolution' key.
			$found = false;
			foreach ( $assets_screenshots as $image ) {
				if ( $index == $image['resolution'] ) {
					$found = true;
					break;
				}
			}
			if ( ! $found ) {
				continue;
			}

			$url = Template::get_asset_url( $plugin->post_name, $image );

			$output .= sprintf(
				'<li>
					<a href="%1$s" rel="nofollow">
						<img class="screenshot" src="%1$s">
					</a>
					<p>%2$s</p>
				</li>',
				$url,
				$description
			);
		}

		return '<ol class="screenshots">' . $output . '</ol>';

	}
}
