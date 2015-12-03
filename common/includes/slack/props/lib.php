<?php

namespace Dotorg\Slack\Props;
use Dotorg\Slack\Send;

function show_error( $user ) {
	echo "Please use `/props SLACK_USERNAME MESSAGE` to give props.\n";
}

function run( $data, $force_test = false ) {
	$sender = $data['user_name'];

	if ( $data['command'] !== '/props' ) {
		echo "???\n";
		return;
	}

	if ( empty( $data['text'] ) ) {
		show_error( $sender );
		return;
	}

	list( $receiver, $message ) = @preg_split( '/\s+/', trim( $data['text'] ), 2 );

	$receiver = ltrim( $receiver, '@' );

	if ( ! strlen( $receiver ) || ! strlen( $message ) ) {
		show_error( $sender );
		return;
	}

	// TODO: Add WordPress.org username to $text if different than Slack username.
	$text = sprintf( "Props to @%s: %s", $receiver, $message );

	$send = new Send( \Dotorg\Slack\Send\WEBHOOK );
	$send->set_username( $sender );
	$send->set_text( $text );

	$get_avatar = __NAMESPACE__ . '\\' . 'get_avatar';

	if ( function_exists( $get_avatar ) ) {
		$send->set_icon( call_user_func( $get_avatar, $sender, $data['user_id'], $data['team_id'] ) );
	}
	
	if ( $force_test ) {
		$send->testing( true );
	}

	$send->send( '#props' );

	printf( "Your props to @%s have been sent.\n", $receiver );
}
