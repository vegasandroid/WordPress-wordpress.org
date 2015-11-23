<?php

namespace Dotorg\Slack\Trac\Tracs;
use Dotorg\Slack\Trac\Trac;

class Core extends Trac {
	protected $name = 'WordPress';
	protected $primary_channel  = '#core';
	protected $commits_channel  = '#core-commits';
	protected $tickets_channel  = '#core-newtickets';
	protected $firehose_channel = '#core-firehose';

	protected $primary_channel_ticket_format = 'title';

	/**
	 * File paths that cause commits to be piped to particular channels.
	 * Start regex matches with # as your delimiter.
	 */
	protected $commit_path_filters = array(
		'wp-content/themes'       => array( '#core-themes' => true, '#core' => false ),
		'customize'               => '#core-customize',
		'editor-expand.js'        => '#core-editor',
		'wp-admin/css/edit.css'   => '#core-editor',
		'wp-admin/css/editor.css' => '#core-editor',
		'press-this.php'          => '#core-pressthis',
	);

	/**
	 * Components or focuses that cause new tickets to be piped to particular channels.
	 */
	protected $ticket_component_filters = array(
		'Bundled Theme' => array( '#core-themes' => true, '#core' => false ),
		'Customize'     => '#core-customize',
		'Press This'    => '#core-pressthis',
	);
}

class Meta extends Trac {
	protected $name = 'WordPress.org Meta';
	protected $primary_channel  = '#meta';
	protected $commits_channel  = '#meta-commits';
	protected $tickets_channel  = '$meta-newtickets';
	protected $firehose_channel = '#meta-firehose';

	protected $bypass_primary_channel_for_commit_filter_matches = true;
	protected $bypass_primary_channel_for_ticket_filter_matches = true;

	protected $commit_path_filters = array(
		'translate.wordpress.org/'              => '#meta-i18n',
		'global.wordpress.org/'                 => '#meta-i18n',
		'plugins/wporg-gp-'                     => '#meta-i18n',
		'translations'                          => '#meta-i18n',
		'developer-reference/'                  => '#meta-devhub',
		'wporg-developer/'                      => '#meta-devhub',
		'trac.wordpress.org/'                   => '#meta-tracdev',
		'svn.wordpress.org/'                    => '#meta-tracdev',
		'wordpress.org/public_html/style/trac/' => '#meta-tracdev',
		'trac-notifications/'                   => '#meta-tracdev',
	);

	protected $ticket_component_filters = array(
		'International Forums'          => '#meta-i18n',
		'International Sites (Rosetta)' => '#meta-i18n',
		'translate.wordpress.org'       => '#meta-i18n',
		'developer.wordpress.org'       => '#meta-devhub',
		'Trac'                          => '#meta-tracdev',
	);
}

class bbPress extends Trac {
	protected $primary_channel  = '#bbpress';
	protected $commits_channel  = '#bbpress-commits';
	protected $tickets_channel  = '#bbpress-newtickets';
	protected $firehose_channel = '#bbpress-firehose';

	protected $primary_channel_ticket_format = 'title';

	protected $commit_path_filters = array(
		'branches/1.' => '#meta',
	);

	protected $color = '#080';
	protected $icon  = ':bbpress:';
}

class BuddyPress extends Trac {
	protected $primary_channel  = '#buddypress';
	protected $commits_channel  = '#buddypress-commits';
	protected $tickets_channel  = '#buddypress-newtickets';
	protected $firehose_channel = '#buddypress-firehose';

	protected $primary_channel_ticket_format = 'title';

	protected $color = '#d84800';
	protected $icon  = ':buddypress:';
}

class Dotorg extends Trac {
	protected $name = 'Private Dotorg';
	protected $public = false;
	protected $primary_channel  = 'dotorg';
	protected $firehose_channel = 'dotorg';
}

class Deploy extends Trac {
	protected $public = false;
	protected $tickets = false;

	protected $primary_channel  = 'dotorg';
	protected $firehose_channel = 'dotorg';
}

class GlotPress extends Trac {
	protected $primary_channel = '#glotpress';
	protected $firehose_channel = '#glotpress-firehose';
}

class Build extends Trac {
	protected $name = 'WordPress Build';
	protected $tickets = false;
}

class BackPress extends Trac {
	protected $commits_channel = '#meta';
}

class SupportPress extends Trac {
}

class Design extends Trac {
	protected $commit_template = 'https://core.trac.wordpress.org/changeset/design/%s';
	protected $commit_info_template = 'https://core.trac.wordpress.org/log/%s?rev=%s&format=changelog&limit=1&verbose=on';
}

class Plugins extends Trac {
}

class Themes extends Trac {
}

class i18n extends Trac {
	protected $name = 'WordPress i18n';
	protected $tickets = false;
}

class Unit_Tests extends Trac {
	protected $dormant = true;
	protected $slug = 'unit-tests';
	protected $name = 'Unit Tests (Old)';
}

class MU extends Trac {
	protected $dormant = true;
	protected $name = 'WordPress MU';
}

class OpenAtd extends Trac {
	protected $dormant = true;
	protected $name = 'After the Deadline';
}

class Code extends Trac {
	protected $dormant = true;
	protected $name = 'Code Repo';
}

class GSoC extends Trac {
	protected $dormant = true;
}

class Security extends Trac {
	protected $public = false;
	protected $commits = false;
}

class WordCamp extends Trac {
	protected $name = 'Private WordCamp.org';
	protected $public = false;
}

