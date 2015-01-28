<?php

namespace Dotorg\Slack\Announce;

// Required function: get_whitelist()
// Optional function: get_avatar()

/**
 * Returns a whitelist of users by channel.
 *
 * The array keys are the channel name (omit #) and the
 * values are an array of users.
 */
function get_whitelist() {
	return array(
		'cli' => array(
			'danielbachhuber',
		),
		'core' => array(
			'johnbillion',
			'drew',
		),
		'polyglots' => array(
			'petya',
		),
		'wptv' => array(
			'jerrysarcastic',
		),
	);
}
