<?php

namespace Dotorg\API\Events;

/**
 * Main entry point
 */
function main() {
	global $cache_group, $cache_life;

	bootstrap();
	wp_cache_init();

	// The test suite just needs the functions defined and doesn't want any headers or output
	if ( defined( 'RUNNING_TESTS' ) && RUNNING_TESTS ) {
		return;
	}

	$cache_group   = 'events';
	$cache_life    = 12 * 60 * 60;
	$ttl           = 12 * 60 * 60; // Time the client should cache the document.
	$location_args = parse_request();
	$location      = get_location( $location_args );
	$response      = build_response( $location );

	send_response( $response, $ttl );
}

/**
 * Include dependencies
 */
function bootstrap() {
	$base_dir = dirname( dirname(__DIR__ ) );

	require( $base_dir . '/init.php' );
	require( $base_dir . '/includes/hyperdb/bb-10-hyper-db.php' );
	include( $base_dir . '/includes/object-cache.php' );
	include( $base_dir . '/includes/wp-json-encode.php' );
}

/**
 * Parse and normalize the client's request
 *
 * @return array
 */
function parse_request() {
	$location_args = array();

	// If a precise location is known, use a GET request. The values here should come from the `location` key of the result of a POST request.
	if ( isset( $_GET['latitude'] ) ) {
		$location_args['latitude'] = $_GET['latitude'];
		$location_args['longitude'] = $_GET['longitude'];
	}

	if ( isset( $_GET['country'] ) ) {
		$location_args['country'] = $_GET['country'];
	}

	// If a precise location is not known, create a POST request with a bunch of data which can be used to determine a precise location for future GET requests.
	if ( isset( $_POST['location_data'] ) ) {
		$location_args = $_POST['location_data'];
	}

	// Simplified parameters for lookup by location (city) name, with optional timezone and locale params for extra context.
	if ( isset( $_REQUEST['location'] ) ) {
		$location_args['location_name'] = trim( $_REQUEST['location'] );
		$location_args['location_name'] = str_replace( ',', '', $location_args['location_name'] );
	}

	if ( isset( $_REQUEST['timezone'] ) && ! isset( $location_args['timezone'] ) ) {
		$location_args['timezone'] = $_REQUEST['timezone'];
	}

	if ( isset( $_REQUEST['locale'] ) && ! isset( $location_args['locale'] ) ) {
		$location_args['locale'] = $_REQUEST['locale'];
	}

	if ( isset( $_REQUEST['ip'] ) && ! isset( $location_args['ip'] ) ) {
		$location_args['ip'] = $_REQUEST['ip'];
	}

	return $location_args;
}

/**
 * Build the API's response to the client's request
 *
 * @param array $location
 *
 * @return array
 */
function build_response( $location ) {
	if ( false === $location ) {
		// No location was determined for the request. Bail with an error.
		$events = array();
		$error = 'no_location_available';
	} else {
		$event_args = array();

		if ( isset( $_REQUEST['number'] ) ) {
			$event_args['number'] = $_REQUEST['number'];
		}

		if ( ! empty( $location['latitude'] ) ) {
			$event_args['nearby'] = array(
				'latitude' => $location['latitude'],
				'longitude' => $location['longitude'],
			);
		}

		if ( ! empty( $location['country'] ) ) {
			$event_args['country'] = $location['country'];
		}

		$events = get_events( $event_args );
	}

	return compact( 'error', 'location', 'events', 'ttl' );
}

/**
 * Send the API's response to the client's request
 *
 * @param array $response
 * @param int   $ttl
 */
function send_response( $response, $ttl ) {
	header( 'Expires: ' . gmdate( 'r', time() + $ttl ) );
	header( 'Access-Control-Allow-Origin: *' );
	header( 'Content-Type: application/json; charset=UTF-8' );

	echo wp_json_encode( $response );
}

/**
 * Guess the location based on a city inside the given input
 *
 * @param string $location_name
 * @param string $timezone
 * @param string $country_code
 *
 * @return false|object false on failure; an object on success
 */
function guess_location_from_city( $location_name, $timezone, $country_code ) {
	$guess = guess_location_from_geonames( $location_name, $timezone, $country_code );
	$location_word_count = str_word_count( $location_name );
	$location_name_parts = explode( ' ', $location_name );

	/*
	 * Multi-word queries may contain cities, regions, and countries, so try to extract just the city
	 */
	if ( ! $guess && $location_word_count >= 2 ) {
		// Catch input like "Portland Maine"
		$guess = guess_location_from_geonames( $location_name_parts[0], $timezone, $country_code );
	}

	if ( ! $guess && $location_word_count >= 3 ) {
		// Catch input like "Sao Paulo Brazil"
		$city_name = sprintf( '%s %s', $location_name_parts[0], $location_name_parts[1] );
		$guess     = guess_location_from_geonames( $city_name, $timezone, $country_code );
	}

	// Normalize all errors to boolean false for consistency
	if ( empty ( $guess ) ) {
		$guess = false;
	}

	return $guess;
}

function guess_location_from_geonames( $location_name, $timezone, $country ) {
	global $wpdb;
	// Look for a location that matches the name.
	// The FIELD() orderings give preference to rows that match the country and/or timezone, without excluding rows that don't match.
	// And we sort by population desc, assuming that the biggest matching location is the most likely one.

	// Strip quotes from the search query and enclose it in double quotes, to force an exact literal search
	$location_name = '"' . strtr( $location_name, [ '"' => '', "'" => '' ] ) . '"';
	$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM geoname WHERE MATCH(name,asciiname,alternatenames) AGAINST(%s IN BOOLEAN MODE) ORDER BY FIELD(%s, country) DESC, FIELD(%s, timezone) DESC, population DESC LIMIT 1", $location_name, $country, $timezone ) );

	return $row;
}

/**
 * Determine a location for the given IPv4 address
 *
 * @todo - Add support for IPv6 addresses. Otherwise, this will quickly lose effectiveness. As of March 2017, IPv6
 *         adoption is at 16% globally and rising relatively fast. Some countries are as high as 30%.
 *         See https://www.google.com/intl/en/ipv6/statistics.html#tab=ipv6-adoption for current stats.
 *
 * @param string $dotted_ip
 *
 * @return null|object `null` on failure; an object on success
 */
function guess_location_from_ip( $dotted_ip ) {
	global $wpdb;

	$long_ip = ip2long( $dotted_ip );
	if ( $long_ip === false )
		return;

	$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM ip2location WHERE ip_to >= %d ORDER BY ip_to ASC LIMIT 1", $long_ip ) );
	// Unknown location:
	if ( ! $row || '-' == $row->country_short ) {
		return;
	}

	return $row;
}

function get_location( $args = array() ) {
	global $cache_life, $cache_group;

	$cache_key = 'get_location:' . md5( serialize( $args ) );
	$location  = wp_cache_get( $cache_key, $cache_group );

	if ( false !== $location ) {
		return $location;
	}

	// For a country request, no lat/long are returned.
	if ( isset( $args['country'] ) ) {
		$location = array(
			'country' => $args['country'],
		);
	}

	$country_code = null;
	if ( ! $location && ( isset( $args['locale'] ) && preg_match( '/^[a-z]+[-_]([a-z]+)$/i', $args['locale'], $match ) ) ) {
		$country_code = $match[1];
	}

	// Location was provided by the user:
	if ( ! $location && isset( $args['location_name'] ) ) {
		$guess = guess_location_from_city( $args['location_name'], $args['timezone'] ?? '', $country_code  );

		if ( $guess ) {
			$location = array(
				'description' => $guess->name,
				'latitude' => $guess->latitude,
				'longitude' => $guess->longitude,
				'country' => $guess->country,
			);
		} else {
			$guess = guess_location_from_country( $args['location_name'] );

			if ( ! $location && $guess ) {
				$location = array(
					'country' => $guess,
				);
			}
		}
	}

	// IP:
	if ( ! $location && isset( $args['ip'] ) ) {
		$guess = guess_location_from_ip( $args['ip'] );

		if ( $guess ) {
			$location = array(
				'description' => $guess->ip_city,
				'latitude' => $guess->ip_latitude,
				'longitude' => $guess->ip_longitude,
				'country' => $guess->country_short,
			);
		}
	}

	if (
		! $location && (
			! empty( $args['latitude'] )  && is_numeric( $args['latitude'] ) &&
			! empty( $args['longitude'] ) && is_numeric( $args['longitude'] )
		)
	) {
		$city = get_city_from_coordinates( $args['latitude'], $args['longitude'] );

		$location = array(
			'description' => $city ? $city : "{$args['latitude']}, {$args['longitude']}",
			'latitude'  => $args['latitude'],
			'longitude' => $args['longitude']
		);
	}

	if ( ! $location ) {
		if ( isset( $args['location_name'] ) || isset( $args['ip'] ) || ! empty( $args['latitude'] ) || ! empty( $args['longitude'] ) ) {
			// If any of these are specified, and no localitity was guessed based on the above checks, bail with no location.
			$location = false;
		} else {
			// No specific location details.
			$location = array();
		}
	}

	wp_cache_set( $cache_key, $location, $cache_group, $cache_life );
	return $location;
}

/**
 * Guess the location based on a country identifier inside the given input
 *
 * This isn't perfect because some of the country names in the database are in a format that regular
 * people wouldn't type -- e.g., "Venezuela, Bolvarian Republic Of" -- but this will still match a
 * majority of them.
 *
 * Currently, this only works with English names because that's the only data we have.
 *
 * @param string $location_name
 *
 * @return false|string false on failure; a country code on success
 */
function guess_location_from_country( $location_name ) {
	// Check if they entered only the country name, e.g. "Germany" or "New Zealand"
	$country_code = get_country_code_from_name( $location_name );
	$location_word_count = str_word_count( $location_name );
	$location_name_parts = explode( ' ', $location_name );

	// Check if they entered only the country code, e.g., "GB"
	if ( ! $country_code ) {
		$valid_country_codes = get_valid_country_codes();

		if ( in_array( $location_name, $valid_country_codes, true ) ) {
			$country_code = $location_name;
		}
	}

	/*
	 * Multi-word queries may contain cities, regions, and countries, so try to extract just the country
	 */
	if ( ! $country_code && $location_word_count >= 2 ) {
		// Catch input like "Vancouver Canada"
		$country_id   = $location_name_parts[ $location_word_count - 1 ];
		$country_code = get_country_code_from_name( $country_id );

		// Catch input like "London GB"
		if ( ! $country_code ) {
			if ( in_array( $country_id, $valid_country_codes, true ) ) {
				$country_code = $country_id;
			}
		}
	}

	if ( ! $country_code && $location_word_count >= 3 ) {
		// Catch input like "Santiago De Los Caballeros, Dominican Republic"
		$country_name = sprintf(
			'%s %s',
			$location_name_parts[ $location_word_count - 2 ],
			$location_name_parts[ $location_word_count - 1 ]
		);
		$country_code = get_country_code_from_name( $country_name );
	}

	if ( ! $country_code && $location_word_count >= 4 ) {
		// Catch input like "Kaga-Bandoro, Central African Republic"
		$country_name = sprintf(
			'%s %s %s',
			$location_name_parts[ $location_word_count - 3 ],
			$location_name_parts[ $location_word_count - 2 ],
			$location_name_parts[ $location_word_count - 1 ]
		);
		$country_code = get_country_code_from_name( $country_name );
	}

	return $country_code;
}

/**
 * Get a list of valid country codes
 *
 * @return array
 */
function get_valid_country_codes() {
	global $wpdb;

	return $wpdb->get_col( "SELECT DISTINCT country FROM geoname" );
}

/**
 * Get the country code that corresponds to the given country name
 *
 * @param string $country_name
 *
 * @return false|string false on failure; a country code on success
 */
function get_country_code_from_name( $country_name ) {
	global $wpdb;

	$country_code = $wpdb->get_var( $wpdb->prepare( "
		SELECT country_short
		FROM ip2location
		WHERE country_long = %s
		LIMIT 1",
		$country_name
	) );


	// Convert all errors to boolean false for consistency
	if ( empty( $country_code ) ) {
		$country_code = false;
	}

	return $country_code;
}

/**
 * Get the name of the city that's closest to the given coordinates
 *
 * @todo - This can probably be optimized by SELECT'ing from a derived table of the closest rows, instead of the
 *         entire table, similar to the technique described at
 *         http://www.techfounder.net/2009/02/02/selecting-closest-values-in-mysql/
 *         There's only 140k rows in the table, though, so this is performant for now.
 *
 * @param float $latitude
 * @param float $longitude
 *
 * @return false|string
 */
function get_city_from_coordinates( $latitude, $longitude ) {
	global $wpdb;

	$results = $wpdb->get_col( $wpdb->prepare( "
		SELECT
			name,
			ABS( %f - latitude  ) AS latitude_distance,
			ABS( %f - longitude ) AS longitude_distance
		FROM geoname
		HAVING
			latitude_distance  < 0.3 AND    -- 0.3 degrees is about 30 miles
			longitude_distance < 0.3
		ORDER by latitude_distance ASC, longitude_distance ASC
		LIMIT 1",
		$latitude,
		$longitude
	) );

	return isset( $results[0] ) ? $results[0] : false;
}

function get_events( $args = array() ) {
	global $wpdb, $cache_life, $cache_group;

	// Sort to ensure consistent cache keys.
	ksort( $args );

	// number should be between 0 and 100, with a default of 10.
	$args['number'] = $args['number'] ?? 10;
	$args['number'] = max( 0, min( $args['number'], 100 ) );

	$cache_key = 'events:' . md5( serialize( $args ) );
	if ( false !== ( $data = wp_cache_get( $cache_key, $cache_group ) ) ) {
		return $data;
	}

	$wheres = array();
	if ( !empty( $args['type'] ) && in_array( $args['type'], array( 'meetup', 'wordcamp' ) ) ) {
		$wheres[] = '`type` = %s';
		$sql_values[] = $args['type'];
	}

	// If we want nearby events, create a WHERE based on a bounded box of lat/long co-ordinates.
	if ( !empty( $args['nearby'] ) ) {
		// Distances in kilometers
		$event_distances = array(
			'meetup' => 100,
			'wordcamp' => 400,
		);
		$nearby_where = array();
		$nearby_vals = '';
		foreach ( $event_distances as $type => $distance ) {
			if ( !empty( $args['type'] ) && $type != $args['type'] ) {
				continue;
			}
			$bounded_box = get_bounded_coordinates( $args['nearby']['latitude'], $args['nearby']['longitude'], $distance );
			$nearby_where[] = '( `type` = %s AND `latitude` BETWEEN %f AND %f AND `longitude` BETWEEN %f AND %f )';
			$sql_values[] = $type;			
			$sql_values[] = $bounded_box['latitude']['min'];
			$sql_values[] = $bounded_box['latitude']['max'];
			$sql_values[] = $bounded_box['longitude']['min'];
			$sql_values[] = $bounded_box['longitude']['max'];
		}
		// Build the nearby where as a OR as different event types have different distances.
		$wheres[] = '(' . implode( ' OR ', $nearby_where ) . ')';
	}

	// Allow queries for limiting to specific countries.
	if ( !empty( $args['country'] ) && preg_match( '![a-z]{2}!i', $args['country'] ) ) {
		$wheres[] = '`country` = %s';
		$sql_values[] = $args['country'];
	}

	// Just show upcoming events
	$wheres[] = '`date_utc` >= %s';
	// Dates are in local-time not UTC, so the API output will contain events that have already happened in some parts of the world.
	// TODO update this when the UTC dates are stored.
	$sql_values[] = gmdate( 'Y-m-d', time() - ( 24 * 60 * 60 ) );

	// Limit 
	if ( isset( $args['number'] ) ) {
		$sql_limits = 'LIMIT %d';
		$sql_values[] = $args['number'];
	}

	$sql_where = $sql_limit = '';
	if ( $wheres ) {
		$sql_where = 'WHERE ' . implode( ' AND ', $wheres );
	}

	$raw_events = $wpdb->get_results( $wpdb->prepare(
		"SELECT
			`type`, `title`, `url`,
			`meetup`, `meetup_url`,
			`date_utc`, `date_utc_offset`,
			`location`, `country`, `latitude`, `longitude`
		FROM `wporg_events`
		$sql_where
		ORDER BY `date_utc` ASC
		$sql_limits",
		$sql_values
	) );

	$events = array();
	foreach ( $raw_events as $event ) {
		$events[] = array(
			'type'  => $event->type,
			'title' => $event->title,
			'url'   => $event->url,
			'meetup' => $event->meetup,
			'meetup_url' => $event->meetup_url,
			'date' => $event->date_utc, // TODO: DB stores a local date, not UTC.
			'location' => array(
				'location' => $event->location,
				'country' => $event->country,
				'latitude' => (float) $event->latitude,
				'longitude' => (float) $event->longitude,
			)
		);
	}

	wp_cache_set( $cache_key, $events, $cache_group, $cache_life );
	return $events;	
}

/**
 * Create a bounded latitude/longitude box of x KM around specific coordinates.
 *
 * @param float $lat            The latitude of the location.
 * @param float $lon            The longitude of the location.
 * @param int   $distance_in_km The distance of the bounded box, in KM.
 * @return array of bounded box.
 */
function get_bounded_coordinates( $lat, $lon, $distance_in_km = 50 ) {
	// Based on http://janmatuschek.de/LatitudeLongitudeBoundingCoordinates

	$angular_distance = $distance_in_km / 6371; // 6371 = radius of the earth in KM.
	$lat = deg2rad( $lat );
	$lon = deg2rad( $lon );

	$earth_min_lat = -1.5707963267949; // = deg2rad(  -90 ) = -PI/2
	$earth_max_lat =  1.5707963267949; // = deg2rad(   90 ) =  PI/2
	$earth_min_lon = -3.1415926535898; // = deg2rad( -180 ) = -PI
	$earth_max_lon =  3.1415926535898; // = deg2rad(  180 ) =  PI

	$minimum_lat = $lat - $angular_distance;
	$maximum_lat = $lat + $angular_distance;
	$minimum_lon = $maximum_lon = 0;

	// Ensure that we're not within a pole-area of the world, weirdness will ensure.
	if ( $minimum_lat > $earth_min_lat && $maximum_lat < $earth_max_lat ) {

		$lon_delta = asin( sin( $angular_distance ) / cos( $lat ) );

		$minimum_lon = $lon - $lon_delta;
		if ( $minimum_lon < $earth_min_lon ) {
			$minimum_lon += 2 * pi();
		}

		$maximum_lon = $lon + $lon_delta;
		if ( $maximum_lon > $earth_max_lon ) {
			$maximum_lon -= 2 * pi();
		}

	} else {
		// Use a much simpler range in polar regions.
		$minimum_lat = max( $minimum_lat, $earth_min_lat );
		$maximum_lat = min( $maximum_lat, $earth_max_lat );
		$minimum_lon = $earth_min_lon;
		$maximum_lon = $earth_max_lon;
	}

	return array(
		'latitude' => array(
			'min' => rad2deg( $minimum_lat ),
			'max' => rad2deg( $maximum_lat )
		),
		'longitude' => array(
			'min' => rad2deg( $minimum_lon ),
			'max' => rad2deg( $maximum_lon )
		)
	);
}

main();

/*
CREATE TABLE `wporg_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(32) NOT NULL DEFAULT '',
  `source_id` varchar(32) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `url` text NOT NULL,
  `description` longtext,
  `attendees` int(11) unsigned DEFAULT NULL,
  `meetup` varchar(255) DEFAULT NULL,
  `meetup_url` text,
  `date_utc` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `location` text,
  `latitude` double NOT NULL,
  `longitude` double NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_source_id` (`type`,`source_id`),
  KEY `latitude` (`latitude`),
  KEY `longitude` (`longitude`),
  KEY `date` (`date_utc`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=latin1;
*/
