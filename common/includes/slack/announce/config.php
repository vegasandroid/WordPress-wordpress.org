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
	$wordcamp_central = array(
		'andreamiddleton',
		'camikaos',
		'chanthaboune',
		'coreymckrill',
		'courtneypk',
		'hlashbrooke',
		'iandunn',
		'rocio',
	);

	return array(
		'accessibility' => array(
			'afercia',
			'joedolson',
			'rianrietveld',
		),
		'bbpress' => array(
			'jjj',
			'netweb',
		),
		'buddypress' => array(
			'boone',
			'djpaul',
			'jjj',
		),
		'cli' => array(
			'danielbachhuber',
			'schlessera',
		),
		'community-events' => $wordcamp_central,
		'community-team' => $wordcamp_central,
		'core' => array(
			'adamsilverstein',
			'desrosj',
			'drew',
			'flixos90',
			'jeffpaul',
			'johnbillion',
			'mike',
			'obenland',
			'ocean90',
			'sergey',
			'wonderboymusic',
			'welcher',
		),
		'core-bootstrap' => array(
			'schlessera',
		),
		'core-comments' => array(
			'rachelbaker',
		),
		'core-committers' => get_committers(),
		'core-customize' => array(
			'celloexpressions',
			'jeffpaul',
			'ocean90',
			'valendesigns',
			'voldemortensen',
			'westonruter',
		),
		'core-docs' => array(
			'drew',
			'kenshino',
			'omarreiss',
		),
		'core-editor' => array(
			'azaozz',
			'iseulde',
		),
		'core-fields' => array(
			'sc0ttkclark',
		),
		'core-flow' => array(
			'boren',
		),
		'core-http' => array(
			'eric',
			'tollmanz',
		),
		'core-i18n' => array(
			'ocean90',
			'sergey',
		),
		'core-js' => array(
			'adamsilverstein',
			'aduth',
			'rmccue',
		),
		'core-media' => array(
			'desrosj',
			'joemcgill',
			'mike',
			'ocean90',
		),
		'core-multisite' => array(
			'flixos90',
			'jeremyfelt',
		),
		'core-passwords' => array(
			'georgestephanis',
			'valendesigns',
		),
		'core-php' => array(
			'flixos90',
			'schlessera',
		),
		'core-restapi' => array(
			'danielbachhuber',
			'joehoyle',
			'kadamwhite',
			'krogsgard',
			'rachelbaker',
			'rmccue',
		),
		'core-themes' => array(
			'davidakennedy',
			'iamtakashi',
			'karmatosed',
			'laurelfulford',
			'melchoyce',
		),
		'design' => array(
			'hugobaeta',
			'karmatosed',
			'melchoyce',
		),
		'design-dashicons' => array(
			'empireoflight',
		),
		'docs' => array(
			'drew',
			'hlashbrooke',
			'kenshino',
			'lizkaraffa',
			'zzap',
		),
		'feature-notifications' => array(
			'johnbillion',
		),
		'feature-shinyupdates' => array(
			'obenland',
			'adamsilverstein',
			'michaelarestad',
			'swissspidy',
		),
		'forums' => array(
			'clorith',
			'ipstenu',
			'jan_dembowski',
			'macmanx',
		),
		'glotpress' => array(
			'ocean90',
			'gregross',
		),
		'hosting-community' => array(
			'mike',
		),
		'marketing' => array(
			'sararosso',
			'gidgey',
		),
		'meta' => array(
			'obenland',
			'tellyworth',
		),
		'meta-devhub' => array(
			'coffee2code',
			'drew',
		),
		'meta-i18n' => array(
			'ocean90',
		),
		'meta-wordcamp' => $wordcamp_central,
		'polyglots' => array(
			'casiepa',
			'coachbirgit',
			'deconf',
			'nao',
			'ocean90',
			'petya',
			'sergey',
			'tobifjellner',
		),
		'polyglots-events' => array(
			'casiepa',
			'coachbirgit',
			'deconf',
			'nao',
			'ocean90',
			'petya',
			'sergey',
			'tobifjellner',
		),
		'themereview' => array(
			'cais',
			'chipbennett',
			'emiluzelac',
			'grapplerulrich',
			'greenshady',
			'jcastaneda',
			'karmatosed',
		),
		'training' => array(
			'courtneydawn',
			'liljimmi',
			'bethsoderberg',
			'courtneyengle',
			'chanthaboune',
			'melindahelt',
			'juliekuehl',
		),
		'wceu-volunteers' => array(
			'lanche86',
			'lucasartoni',
			'paolo',
			'savione',
		),
		'wcus' => array(
			'alx',
			'camikaos',
		),
		'wcus-contributor-day' => array(
			'alx',
			'camikaos',
			'drew',
		),
		'wcus-speakers' => array(
			'alx',
			'camikaos',
			'liljimmi',
			'williamsba',
		),
		'wcus-summit' => array(
			'liljimmi',
		),
		'wcus-volunteers' => array(
			'alx',
			'camikaos',
			'ingridmiller',
			'liamdempsey',
			'bishop',
		),
		'wptv' => array(
			'jerrysarcastic',
			'roseapplemedia',
		),
	);
}

function get_committers() {
	return array(
		'dd32', 'nacin', 'mark', 'helen', 'azaozz', 'westi',
		'nb', 'sergey', 'ocean90', 'wonderboymusic', 'drew', 'johnbillion',
		'jorbin', 'boone', 'jeremyfelt', 'pento', 'obenland', 'iseulde',
		'westonruter', 'afercia', 'karmatosed', 'rmccue', 'michaelarestad',
		'swissspidy', 'rachelbaker', 'joehoyle', 'melchoyce', 'eric', 'mike',
		'peterwilsoncc', 'joemcgill', 'davidakennedy', 'adamsilverstein',
		'jnylen', 'flixos90'
	);
}
