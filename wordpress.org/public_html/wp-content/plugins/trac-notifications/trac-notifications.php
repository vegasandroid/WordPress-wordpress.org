<?php
/* Plugin Name: Trac Notifications
 * Description: Adds notifications endpoints for Trac, as well as notification and component management.
 * Author: Nacin
 * Version: 1.1
 */

class wporg_trac_notifications {

	protected $trac_subdomain;

	protected $tracs_supported = array( 'core', 'meta', 'themes', 'plugins' );
	protected $tracs_supported_extra = array( 'bbpress', 'buddypress', 'gsoc', 'glotpress' );

	function __construct() {
		$make_site = explode( '/', home_url( '' ) );
		$trac = $make_site[3];
		if ( $make_site[2] !== 'make.wordpress.org' || ! in_array( $trac, $this->tracs_supported ) ) {
			return;
		}
		if ( 'core' === $trac && isset( $_GET['trac'] ) && in_array( $_GET['trac'], $this->tracs_supported_extra ) ) {
			$trac = $_GET['trac'];
		}
		$this->set_trac( $trac );
		add_filter( 'allowed_http_origins', array( $this, 'filter_allowed_http_origins' ) );
		add_action( 'template_redirect', array( $this, 'action_template_redirect' ) );
		add_shortcode( 'trac-notifications', array( $this, 'notification_settings_page' ) );
		if ( 'core' === $trac ) {
			require __DIR__ . '/trac-components.php';
		}
	}

	function set_trac( $trac ) {
		$this->trac_subdomain = $trac;
		if ( function_exists( 'add_db_table' ) ) {
			$tables = array( 'ticket', '_ticket_subs', '_notifications', 'ticket_change', 'component', 'milestone', 'ticket_custom' );
			foreach ( $tables as $table ) {
				add_db_table( 'trac_' . $trac, $table );
			}
			$this->trac = $GLOBALS['wpdb'];
		}
	}

	function trac_url() {
		return 'https://' . $this->trac_subdomain . '.trac.wordpress.org';
	}

	function filter_allowed_http_origins( $origins ) {
		$origins[] = $this->trac_url();
		return $origins;
	}

	function ticket_link( $ticket ) {
		$status_res = $ticket->status;
		if ( $ticket->resolution ) {
			$status_res .= ': ' . $ticket->resolution;
		}
		return sprintf( '<a href="%s" class="%s ticket" title="%s">#%s</a>',
			$this->trac_url() . '/ticket/' . $ticket->id,
			$ticket->status,
			esc_attr( sprintf( "%s: %s (%s)", $ticket->type, $ticket->summary, $status_res ) ),
			$ticket->id );
	}

	function action_template_redirect() {
		if ( isset( $_POST['trac-ticket-sub'] ) ) {
			$this->trac_notifications_box_actions();
			exit;
		} elseif ( isset( $_POST['trac-ticket-subs'] ) ) {
			$this->trac_notifications_query_tickets();
			exit;
		} elseif ( isset( $_GET['trac-notifications'] ) ) {
			$this->trac_notifications_box_render();
			exit;
		}
	}

	function get_trac_ticket( $ticket_id ) {
		return $this->trac->get_row( $this->trac->prepare( "SELECT * FROM ticket WHERE id = %d", $ticket_id ) );
	}

	function get_trac_ticket_focuses( $ticket_id ) {
		return $this->trac->get_var( $this->trac->prepare( "SELECT value FROM ticket_custom WHERE ticket = %d AND name = 'focuses'", $ticket_id ) );
	}

	function get_trac_ticket_participants( $ticket_id ) {
		// Make sure we suppress CC-only comments that still exist in the database.
		// Do this by suppressing any 'cc' changes and also any empty comments (used by Trac for comment numbering).
		// Empty comments are also used for other property changes made without comment, but those changes will still be returned by this query.
		$ignore_cc = "field <> 'cc' AND NOT (field = 'comment' AND newvalue = '') AND";
		return $this->trac->get_col( $this->trac->prepare( "SELECT DISTINCT author FROM ticket_change WHERE $ignore_cc ticket = %d", $ticket_id ) );
	}

	function get_trac_ticket_subscriptions( $ticket_id ) {
		$by_status = array( 'blocked' => array(), 'starred' => array() );
		$subscriptions = $this->trac->get_results( $this->trac->prepare( "SELECT username, status FROM _ticket_subs WHERE ticket = %s", $ticket_id ) );
		foreach ( $subscriptions as $subscription ) {
			$by_status[ $subscription->status ? 'starred' : 'blocked' ][] = $subscription->username;
		}
		return $by_status;
	}

	function get_trac_focuses() {
		return array( 'accessibility', 'administration', 'docs', 'javascript', 'multisite', 'performance', 'rtl', 'template', 'ui' );
	}

	function make_components_tree( $components ) {
		$tree = array();
		$subcomponents = array(
			'Comments' => array( 'Pings/Trackbacks' ),
			'Editor' => array( 'Autosave', 'Press This', 'Quick/Bulk Edit', 'TinyMCE' ),
			'Formatting' => array( 'Charset', 'Shortcodes' ),
			'Media' => array( 'Embeds', 'Gallery', 'Upload' ),
			'Permalinks' => array( 'Canonical', 'Rewrite Rules' ),
			'Posts, Post Types' => array( 'Post Formats', 'Post Thumbnails', 'Revisions' ),
			'Themes' => array( 'Appearance', 'Widgets', 'Menus' ),
			'Users' => array( 'Role/Capability', 'Login and Registration' )
		);
		foreach ( $components as $component ) {
			if ( isset( $tree[ $component ] ) && false === $tree[ $component ] ) {
				continue;
			} elseif ( isset( $subcomponents[ $component ] ) ) {
				$tree[ $component ] = $subcomponents[ $component ];
				foreach ( $subcomponents[ $component ] as $subcomponent ) {
					$tree[ $subcomponent ] = false;
				}
			} else {
				$tree[ $component ] = true;
			}
		}
		$tree = array_filter( $tree );
		return $tree;
	}

	function get_trac_components() {
		return $this->trac->get_col( "SELECT name FROM component WHERE name <> 'WordPress.org site' ORDER BY name ASC" );
	}

	function get_trac_milestones() {
		// Only show 3.8+, when this feature was launched.
		return $this->trac->get_results( "SELECT name, completed FROM milestone
			WHERE name NOT IN ('WordPress.org', '3.5.3', '3.6.2', '3.7.2') AND (completed = 0 OR completed >= 1386864000000000)
			ORDER BY (completed = 0) DESC, name DESC", OBJECT_K );
	}

	function get_trac_notifications_for_user( $username ) {
		$rows = $this->trac->get_results( $this->trac->prepare( "SELECT type, value FROM _notifications WHERE username = %s ORDER BY type ASC, value ASC", $username ) );
		$notifications = array( 'component' => array(), 'milestone' => array(), 'focus' => array(), 'newticket' => array() );

		foreach ( $rows as $row ) {
			$notifications[ $row->type ][ $row->value ] = true;
		}
		$notifications['newticket'] = ! empty( $notifications['newticket']['1'] );

		return $notifications;
	}

	function get_trac_ticket_subscription_status_for_user( $ticket_id, $username ) {
		$status = $this->trac->get_var( $this->trac->prepare( "SELECT status FROM _ticket_subs WHERE username = %s AND ticket = %s", $username, $ticket_id ) );
		if ( null !== $status ) {
			$status = (int) $status;
		}
		return $status;
	}

	function get_trac_ticket_subscriptions_for_user( $username ) {
		return $this->trac->get_col( $this->trac->prepare( "SELECT ticket FROM _ticket_subs WHERE username = %s AND status = 1", $username ) );
	}

	function trac_notifications_box_actions() {
		send_origin_headers();

		if ( ! is_user_logged_in() ) {
			wp_send_json_error();
		}

		$username = wp_get_current_user()->user_login;

		$ticket_id = absint( $_POST['trac-ticket-sub'] );
		if ( ! $ticket_id ) {
			wp_send_json_error();
		}

		$ticket = $this->get_trac_ticket( $ticket_id );
		if ( ! $ticket ) {
			wp_send_json_error();
		}

		$action = $_POST['action'];
		if ( ! $action ) {
			wp_send_json_error();
		}

		switch ( $action ) {
			case 'subscribe' :
			case 'block' :
				$status = $action === 'subscribe' ? 1 : 0;
				$this->trac->delete( '_ticket_subs', array( 'username' => $username, 'ticket' => $ticket_id ) );
				$result = $this->trac->insert( '_ticket_subs', array( 'username' => $username, 'ticket' => $ticket_id, 'status' => $status ) );
				break;

			case 'unsubscribe' :
			case 'unblock' :
				$status = $action === 'unsubscribe' ? 1 : 0;
				$result = $this->trac->delete( '_ticket_subs', array( 'username' => $username, 'ticket' => $ticket_id, 'status' => $status ) );
				break;
		}

		if ( $result ) {
			wp_send_json_success();
		}

		wp_send_json_error();
	}

	function trac_notifications_query_tickets() {
		send_origin_headers();

		if ( ! is_user_logged_in() ) {
			exit;
		}
		$username = wp_get_current_user()->user_login;

		$queried_tickets = (array) $_POST['tickets'];
		if ( count( $queried_tickets ) > 100 ) {
			wp_send_json_error();
		}

		$subscribed_tickets = $this->get_trac_ticket_subscriptions_for_user( $username );
		if ( ! is_array( $subscribed_tickets ) ) {
			wp_send_json_error();
		}
		$tickets = array_intersect( $queried_tickets, $subscribed_tickets );
		$tickets = array_map( 'intval', array_values( $tickets ) );
		wp_send_json_success( array( 'tickets' => $tickets ) );
	}

	function trac_notifications_box_render() {
		send_origin_headers();

		if ( ! is_user_logged_in() ) {
			exit;
		}
		$username = wp_get_current_user()->user_login;

		$ticket_id = absint( $_GET['trac-notifications'] );
		if ( ! $ticket_id ) {
			exit;
		}
		$ticket = $this->get_trac_ticket( $ticket_id );
		if ( ! $ticket ) {
			exit;
		}

		$focuses = explode( ', ', $this->get_trac_ticket_focuses( $ticket_id ) );

		$notifications = $this->get_trac_notifications_for_user( $username );

		$ticket_sub = $this->get_trac_ticket_subscription_status_for_user( $ticket_id, $username );

		$ticket_subscriptions = $this->get_trac_ticket_subscriptions( $ticket_id );
		$stars = $ticket_subscriptions['starred'];
		$star_count = count( $stars );

		$participants = $this->get_trac_ticket_participants( $ticket_id );

		$unblocked_participants = array_diff( $participants, $ticket_subscriptions['blocked'] );
		$all_receiving_notifications = array_unique( array_merge( $stars, $unblocked_participants ) );
		natcasesort( $all_receiving_notifications );

		$reasons = array();

		if ( $username == $ticket->reporter ) {
			$reasons['reporter'] = 'you reported this ticket';
		}
		if ( $username == $ticket->owner ) {
			$reasons['owner'] = 'you own this ticket';
		}
		if ( in_array( $username, $participants ) ) {
			$reasons['participant'] = 'you have commented';
		}

		$intersected_focuses = array();
		foreach ( $focuses as $focus ) {
			if ( ! empty( $notifications['focus'][ $focus ] ) ) {
				$intersected_focuses[] = $focus;
			}
		}
		if ( $intersected_focuses ) {
			if ( count( $intersected_focuses ) === 1 ) {
				$reasons['focus'] = sprintf( 'you subscribe to the %s focus', $intersected_focuses[0] );
			} else {
				$reasons['focus'] = 'you subscribe to the ' . wp_sprintf( '%l focuses', $intersected_focuses );
			}
		}

		if ( ! empty( $notifications['component'][ $ticket->component ] ) ) {
			$reasons['component'] = sprintf( 'you subscribe to the %s component', $ticket->component );
		}
		if ( ! empty( $notifiations['milestone'][ $ticket->milestone ] ) ) {
			$reasons['milestone'] = sprintf( 'you subscribe to the %s milestone', $ticket->milestone );
		}

		if ( 1 === $ticket_sub ) {
			$class = 'subscribed';
		} else {
			if ( null === $ticket_sub && $reasons ) {
				$class = 'block';
			} elseif ( 0 === $ticket_sub ) {
				$class = 'blocked';
			} else {
				$class = '';
			}
		}
		if ( $reasons ) {
			$class .= ' receiving';
		}

		if ( $star_count === 0 || $star_count === 1 ) {
			$class .= ' count-' . $star_count;
		}
		if ( ! empty( $_COOKIE['wp_trac_ngrid'] ) ) {
			$class .= ' show-usernames';
		}

		ob_start();
		?>
	<div id="notifications" class="<?php echo $class; ?>">
		<fieldset>
			<legend>Notifications</legend>
				<p class="star-this-ticket">
					<a href="#" class="button button-large watching-ticket"><span class="dashicons dashicons-star-filled"></span> Watching ticket</a>
					<a href="#" class="button button-large watch-this-ticket"><span class="dashicons dashicons-star-empty"></span> Watch this ticket</a>
					<span class="num-stars"><span class="count"><?php echo $star_count; ?></span> <span class="count-1">star</span> <span class="count-many">stars</span></span>
					<div class="star-list">
				<?php
					natcasesort( $stars ); foreach ( $stars as $follower ) :
					// foreach ( $all_receiving_notifications as $follower ) :
						if ( $username === $follower ) {
							continue;
						}
						$follower = esc_attr( $follower );
						$class = ''; // in_array( $follower, $stars, true ) ? ' class="star"' : '';
					?>
						<a<?php echo $class; ?> title="<?php echo $follower; ?>" href="//profiles.wordpress.org/<?php echo $follower; ?>">
							<?php echo get_avatar( get_user_by( 'login', $follower )->user_email, 36 ); ?>
							<span class="username"><?php echo $follower; ?></span>
						</a>
					<?php endforeach; ?>
					<a title="you" class="star-you" href="//profiles.wordpress.org/<?php echo esc_attr( $username ); ?>">
						<?php echo get_avatar( wp_get_current_user()->user_email, 36 ); ?>
						<span class="username"><?php echo $username; ?></span>
					</a>
					</div>
				</p>
				<p class="receiving-notifications">You are receiving notifications.</p>
			<?php if ( $reasons ) : ?>
				<p class="receiving-notifications-because">You are receiving notifications because <?php echo current( $reasons ); ?>. <a href="#" class="button button-small block-notifications">Block notifications</a></p>
			<?php endif ?>
				<p class="not-receiving-notifications">You do not receive notifications because you have blocked this ticket. <a href="#" class="button button-small unblock-notifications">Unblock</a></p>
				<span class="preferences"><span class="grid-toggle"><a href="#" class="grid dashicons dashicons-screenoptions"></a> <a href="#" class="names dashicons dashicons-exerpt-view dashicons-excerpt-view"></a></span> <a href="<?php echo home_url( 'notifications/' ); ?>">Preferences</a></span>
		</fieldset>
	</div>
	<?php
		$this->ticket_notes( $ticket, $username );
		wp_send_json_success( array( 'notifications-box' => ob_get_clean() ) );
		exit;
	}

	function ticket_notes( $ticket, $username ) {
		if ( $username == $ticket->reporter ) {
			return;
		}

		$previous_tickets = $this->trac->get_results( $this->trac->prepare( "SELECT id, summary, type, status, resolution
			FROM ticket WHERE reporter = %s AND id <= %d LIMIT 5", $ticket->reporter, $ticket->id ) );

		if ( count( $previous_tickets ) >= 5 ) {
			return;
		}	

		if ( 1 == count( $previous_tickets ) ) {
			$previous_comments = $this->trac->get_var( $this->trac->prepare( "SELECT ticket FROM ticket_change
				WHERE field = 'comment' AND author = %s AND ticket <> %d LIMIT 1", $ticket->reporter, $ticket->id ) );

			$output = '<strong>Make sure ' . $ticket->reporter . ' receives a warm welcome.</strong><br/>';

			if ( $previous_comments ) {
				$output .= 'They&#8217;ve commented before, but it&#8127;s their first bug report!';
			} else {
				$output .= 'It&#8127;s their first bug report!';
			}
		} else {
			$mapping = array( 2 => 'second', 3 => 'third', 4 => 'fourth' );

			$output = '<strong>This is only ' . $ticket->reporter . '&#8217;s ' . $mapping[ count( $previous_tickets ) ] . ' ticket!</strong><br/>Previously:';

				foreach ( $previous_tickets as $t ) {
					if ( $t->id != $ticket->id ) {
						$output .= ' ' . $this->ticket_link( $t );
					}
				}
				$output .= '.';
		}

		echo '<p class="ticket-note note-new-reporter">';
		echo '<img width="36" height="36" src="//wordpress.org/grav-redirect.php?user=' . esc_attr( $ticket->reporter ) . '&amp;s=36" /> ';
		echo '<span class="note">' . $output . '</span>';
		echo '<span class="dashicons dashicons-welcome-learn-more"></span>';
	}

	function notification_settings_page() {
		if ( ! is_user_logged_in() ) {
			return 'Please <a href="//wordpress.org/support/bb-login.php">log in</a> to save your notification preferences.';
		}

		ob_start();
		$components = $this->get_trac_components();
		$milestones = $this->get_trac_milestones();
		$focuses = $this->get_trac_focuses();

		$username = wp_get_current_user()->user_login;
		$notifications = $this->get_trac_notifications_for_user( $username );

		if ( $_POST && isset( $_POST['trac-nonce'] ) ) {
			check_admin_referer( 'save-trac-notifications', 'trac-nonce' );

			foreach ( array( 'milestone', 'component', 'focus' ) as $type ) {
				foreach ( $_POST['notifications'][ $type ] as $value => $on ) {
					if ( empty( $notifications[ $type ][ $value ] ) ) {
						$this->trac->insert( '_notifications', compact( 'username', 'type', 'value' ) );
						$notifications[ $type ][ $value ] = true;
					}
				}

				foreach ( $notifications[ $type ] as $value => $on ) {
					if ( empty( $_POST['notifications'][ $type ][ $value ] ) ) {
						$this->trac->delete( '_notifications', compact( 'username', 'type', 'value' ) );
						unset( $notifications[ $type ][ $value ] );
					}
				}
			}
			if ( empty( $_POST['notifications']['newticket'] ) && ! empty( $notifications['newticket'] ) ) {
					$this->trac->delete( '_notifications', array( 'username' => $username, 'type' => 'newticket' ) );
			} elseif ( ! empty( $_POST['notifications']['newticket'] ) && empty( $notifications['newticket'] ) ) {
				$this->trac->insert( '_notifications', array( 'username' => $username, 'type' => 'newticket', 'value' => '1' ) );
			}
		}
		?>

		<style>
		#focuses, #components, #milestones, p.save-changes {
			clear: both;
		}
		#milestones, p.save-changes {
			padding-top: 1em;
		}
		#focuses li {
			display: inline-block !important;
			list-style: none;
			min-width: 15%;
			margin-right: 30px;
		}
		#components > ul {
			float: left;
			width: 24%;
			margin: 0 0 0 1% !important;
			margin: 0;
			padding: 0;
		}
		#components > ul > li {
			list-style: none;
		}
		#milestones > ul > li {
			float: left;
			width: 25%;
			list-style: none;
		}	
		.completed-milestone {
			display: none !important;
		}
		.completed-milestone.checked,
		#milestones.show-completed-milestones .completed-milestone {
			display: list-item !important;
		}
		</style>
		<script>
		jQuery(document).ready( function($) {
			$('#show-completed').on('click', 'a', function() {
				$('#show-completed').hide();
				$('#milestones').addClass( 'show-completed-milestones' );
				return false;
			});
			$('p.select-all').on('click', 'a', function() {
				$('#components').find('input[type=checkbox]').prop('checked', $(this).data('action') === 'select-all');
				return false;
			});
		});
		</script>
		<?php
		echo '<form method="post" action="">';
		wp_nonce_field( 'save-trac-notifications', 'trac-nonce', false );
		echo '<h3>New Tickets</h3>';
		$checked = checked( $notifications['newticket'], true, false );
		echo '<ul><li style="list-style:none"><label><input type="checkbox" ' . $checked . 'name="notifications[newticket]" /> Receive all new ticket notifications.</label><br /><em>To receive comments to a ticket, you will need to star it, unless it matches one of your other preferences below.</em></li></ul>';
		echo '<div id="focuses">';
		echo '<h3>Focuses</h3>';
		echo '<ul>';
		foreach ( $focuses as $focus ) {
			$checked = checked( ! empty( $notifications['focus'][ $focus ] ), true, false );
			echo '<li><label><input type="checkbox" ' . $checked . 'name="notifications[focus][' . esc_attr( $focus ) . ']" /> ' . $focus . '</label></li>';
		}
		echo '</ul>';
		echo '</div>';
		echo '<div id="components">';
		echo '<h3>Components</h3>';
		echo '<p class="select-all"><a href="#" data-action="select-all">select all</a> &bull; <a href="#" data-action="clear-all">clear all</a></p>';
		echo "<ul>\n";
		$components_tree = $this->make_components_tree( $components );
		$breakpoints = array( 'Export', 'Media', 'Script Loader' );
		foreach ( $components_tree as $component => $subcomponents ) {
			if ( in_array( $component, $breakpoints ) ) {
				echo '</ul><ul>';
			}
			$checked = checked( ! empty( $notifications['component'][ $component ] ), true, false );
			echo '<li><label><input type="checkbox" ' . $checked . 'name="notifications[component][' . esc_attr( $component ) . ']" /> ' . $component . "</label>\n";
			if ( is_array( $subcomponents ) ) {
				echo "<ul>\n";
				foreach ( $subcomponents as $subcomponent ) {
					$checked = checked( ! empty( $notifications['component'][ $subcomponent ] ), true, false );
					echo '<li><label><input type="checkbox" ' . $checked . 'name="notifications[component][' . esc_attr( $subcomponent ) . ']" /> ' . $subcomponent . "</label></li>\n";
				}
				echo "</ul>\n";
			}
			echo "</li>\n";
		}
		echo '</ul>';
		echo '</div>';
		echo '<div id="milestones">';
		echo '<h3>Milestones</h3>';
		echo '<ul>';
		foreach ( $milestones as $milestone ) {
			$checked = checked( ! empty( $notifications['milestone'][ $milestone->name ] ), true, false );
			$class = '';
			if ( ! empty( $milestone->completed ) ) {
				$class = 'completed-milestone';
				if ( $checked ) {
					$class .= ' checked';
				}
				$class = ' class="' . $class . '"';
			}
			echo  '<li' . $class . '><label><input type="checkbox" ' . $checked . 'name="notifications[milestone][' . esc_attr( $milestone->name ) . ']" /> ' . $milestone->name . '</label></li>';
		}
		echo '<li id="show-completed"><a href="#">Show recently completed&hellip;</a></li>';
		echo '</ul>';
		echo '</div>';
		echo '<p class="save-changes"><input type="submit" value="Save Changes" /></p>';
		echo '</form>';
		return ob_get_clean();
	}
}
$wporg_trac_notifications = new wporg_trac_notifications;

