<?php

class WP_54_Credits extends WP_Credits {

	public function groups() {
		$groups = [
			'core-developers'         => [
				'name'    => 'Noteworthy Contributors',
				'type'    => 'titles',
				'shuffle' => false,
				'data'    => [
					'matt'              => [ 'Matt Mullenweg', 'Release Lead' ],
					'francina'          => [ 'Francesca Marano', 'Release Lead' ],
					'davidbaumwald'     => [ 'David Baumwald', 'Release Lead' ],
					'SergeyBiryukov'    => 'Sergey Biryukov',
					'audrasjb'          => 'Jean-Baptiste Audras',
					'jorgefilipecosta'  => 'Jorge Costa',
					'mapk'              => 'Mark Uraine',
					'marybaum'          => 'Mary Baum',
					'karmatosed'        => 'Tammie Lister',
					'ellatrix'          => 'Ella van Durpe',
					'youknowriad'       => 'Riad Benguella',
					'epiqueras'         => 'Enrique Piqueras',
					'aduth'             => 'Andrew Duthie',
					'gziolo'            => 'Grzegorz Ziółkowski',
					'azaozz'            => 'Andrew Ozz',
					'desrosj'           => 'Jonathan Desrosiers',
					'garrett-eclipse'   => 'Garrett Hyder',
					'johnbillion'       => 'John Blackbourn',
					'mkaz'              => 'Marcus Kazmierczak',
					'afercia'           => 'Andrea Fercia',
					'Joen'              => 'Joen Asmussen',
					'andraganescu'      => 'Andrei Draganescu',
					'retrofox'          => 'Damián Suárez',
					'talldanwp'         => 'Daniel Richards',
					'ianbelanger'       => 'Ian Belanger',
					'TimothyBlynJacobs' => 'Timothy Jacobs',
					'mcsf'              => 'Miguel Fonseca',
					'xkon'              => 'Konstantinos Xenos',
					'Clorith'           => 'Marius Jensen',
					'JeffPaul'          => 'Jeff Paul',
					'chanthaboune'      => 'Josepha Haden',
					'whyisjake'         => 'Jake Spurlock',
					'peterwilsoncc'     => 'Peter Wilson',
					'iandunn'           => 'Ian Dunn',
					'pbiron'            => 'Paul Biron',
					'afragen'           => 'Andy Fragen',
				],
			],
			'contributing-developers' => [
				'name'    => false,
				'type'    => 'titles',
				'shuffle' => true,
				'data'    => [
					'amykamala'         => 'Amy Kamala',
					'valentinbora'      => 'Valentin Bora',
					'melchoyce'         => 'Mel Choyce-Dwan',
					'ryelle'            => 'Kelly Dwan',
					'elmastudio'        => 'Ellen Bauer',
					'marktimemedia'     => 'Michelle Schulp',
					'Soean'             => 'Soren Wrede',
					'itsjonq'           => 'Jon Quach',
					'donmhico'          => 'Michael Panaga',
					'jrf'               => 'Juliette Reinders Folmer',
					'ramiy'             => 'Rami Yushuvaev',
					'get_dave'          => 'Dave Smith',
					'etoledom'          => 'Eduardo Toledo',
					'koke'              => 'Jorge Bernal',
					'mukesh27'          => 'Mukesh Panchal',
					'kadamwhite'        => 'K. Adam White',
					'sabernhardt'       => 'Stephen Bernhardt',
					'SergioEstevao'     => 'Sérgio Estêvão',
					'jbinda'            => 'Jakub Binda',
					'birgire'           => 'Birgir Erlendsson',
					'jeryj'             => 'Jerry Jones',
					'isabel_brison'     => 'Isabel Brison',
					'ocean90'           => 'Dominik Schilling',
					'Rarst'             => 'Andrey Savchenko',
					'marekdedic'        => 'Marek Dědič',
					'snapfractalpop'    => 'Matthew Kevins',
					'noisysocks'        => 'Robert Anderson',
					'zebulan'           => 'Zebulan Stanphill',
					'williampatton'     => 'William Patton',
					'dlh'               => 'David Herrera',
					'matveb'            => 'Matias Ventura',
					'richtabor'         => 'Rich Tabor',
					'nrqsnchz'          => 'Enrique Sánchez',
				],
			],
		];

		return $groups;
	}

	public function props() {
		return [
			'0v3rth3d4wn',
			'123host',
			'1naveengiri',
			'aandrewdixon',
			'abhijitrakas',
			'abrightclearweb',
			'acosmin',
			'adamboro',
			'adamsilverstein',
			'addiestavlo',
			'adnanlimdi',
			'aduth',
			'afercia',
			'afragen',
			'aftabmuni',
			'agawish',
			'akibjorklund',
			'akshayar',
			'alexandreb3',
			'alexholsgrove',
			'alexischenal',
			'alextran',
			'alishankhan',
			'aliveic',
			'aljullu',
			'allancole',
			'allendav',
			'alpipego',
			'alshakero',
			'amirs17',
			'amolv',
			'anantajitjg',
			'andizer',
			'andraganescu',
			'andreaitm',
			'andrewserong',
			'ankitmaru',
			'anlino',
			'antpb',
			'apeatling',
			'apedog',
			'apermo',
			'apieschel',
			'aravindajith',
			'archon810',
			'arenddeboer',
			'aristath',
			'ashokrd2013',
			'assassinateur',
			'atachibana',
			'ataurr',
			'ate-up-with-motor',
			'audrasjb',
			'autotutorial',
			'ayeshrajans',
			'azaozz',
			'b-07',
			'backups',
			'bahia0019',
			'bamadesigner',
			'bartczyz',
			'benedictsinger',
			'bengreeley',
			'bfintal',
			'bibliofille',
			'bilgilabs',
			'birgire',
			'boga86',
			'bookdude13',
			'boonebgorges',
			'bordoni',
			'bph',
			'brentswisher',
			'bwmarkle',
			'cafenoirdesign',
			'casiepa',
			'celloexpressions',
			'ceyhun0',
			'chanthaboune',
			'chetan200891',
			'chinteshprajapati',
			'chipsnyder',
			'christianamohr',
			'chrisvanpatten',
			'cklosows',
			'clayisland',
			'clorith',
			'coffee2code',
			'collet',
			'copons',
			'coreymckrill',
			'costasovo',
			'crdunst',
			'cvoell',
			'cybr',
			'danielbachhuber',
			'danieltj',
			'daniloercoli',
			'darrenlambert',
			'dartiss',
			'daveslaughter',
			'davewp196',
			'davidbaumwald',
			'davidbinda',
			'davidshq',
			'dd32',
			'dekervit',
			'delowardev',
			'denisco',
			'derweili',
			'desaiuditd',
			'desrosj',
			'dhavalkasvala',
			'dhurlburtusa',
			'diddledan',
			'dilipbheda',
			'dingo_d',
			'dinhtungdu',
			'dipeshkakadiya',
			'disillusia',
			'djp424',
			'dkarfa',
			'dlh',
			'dominic_ks',
			'donmhico',
			'dontdream',
			'dotancohen',
			'dphiffer',
			'dragosh635',
			'drewapicture',
			'dryanpress',
			'dshanske',
			'dufresnesteven',
			'earnjam',
			'eatingrules',
			'eclare',
			'eclev91',
			'eden159',
			'ediamin',
			'efarem',
			'ellatrix',
			'elmastudio',
			'epiqueras',
			'equin0x80',
			'erikkroes',
			'estelaris',
			'etoledom',
			'fabiankaegy',
			'fabifott',
			'fahimmurshed',
			'faisal03',
			'felipeelia',
			'felipeloureirosantos',
			'fernandovbsouza',
			'fervillz',
			'fgiannar',
			'fierevere',
			'finchps',
			'flaviozavan',
			'flixos90',
			'fotisps',
			'francina',
			'galbaras',
			'garrett-eclipse',
			'garyj',
			'gdragon',
			'georgestephanis',
			'geriux',
			'get_dave',
			'gh640',
			'girishpanchal',
			'glebkema',
			'grafruessel',
			'grapplerulrich',
			'gregrickaby',
			'grzegorzjanoszka',
			'guddu1315',
			'gwwar',
			'gziolo',
			'hamedmoodi',
			'hampzter',
			'happiryu',
			'hareesh-pillai',
			'harry-milatz',
			'hazdiego',
			'hedgefield',
			'helgatheviking',
			'henryholtgeerts',
			'hinjiriyo',
			'hometowntrailers',
			'hypest',
			'i3anaan',
			'iaaxpage',
			'ianatkins',
			'ianbelanger',
			'iandunn',
			'ianmjones',
			'ideaboxcreations',
			'iihglobal',
			'imani3011',
			'imath',
			'intimez',
			'ipstenu',
			'isabel_brison',
			'ispreview',
			'itowhid06',
			'itsjonq',
			'ixkaito',
			'jameskoster',
			'jameslnewell',
			'jankimoradiya',
			'jarretc',
			'jaydeep23290',
			'jbinda',
			'jblz',
			'jdy68',
			'jean-david',
			'jeherve',
			'jeichorn',
			'jenilk',
			'jepperask',
			'jeremyclarke',
			'jeremyfelt',
			'jeroenrotty',
			'jeryj',
			'jffng',
			'jg-visual',
			'jipmoors',
			'jnylen0',
			'joedolson',
			'joehoyle',
			'joemcgill',
			'joen',
			'johnbillion',
			'johnjamesjacoby',
			'johnwatkins0',
			'jon81',
			'jonoaldersonwp',
			'jonsurrell',
			'joonasvanhatapio',
			'joostdevalk',
			'jorbin',
			'jorgefilipecosta',
			'joshuawold',
			'joyously',
			'jqz',
			'jrf',
			'jsnajdr',
			'juanfra',
			'juliankimmig',
			'juliobox',
			'jurgen',
			'justdaiv',
			'justinahinon',
			'kadamwhite',
			'kaggdesign',
			'kalpshit',
			'karmatosed',
			'kasparsd',
			'kennithnichol',
			'ketuchetan',
			'khag7',
			'kharisblank',
			'khushbu19',
			'killerbishop',
			'kingkool68',
			'kinjaldalwadi',
			'kitchin',
			'kjellr',
			'kkarpieszuk',
			'klopez8',
			'knutsp',
			'koke',
			'kokkieh',
			'kraftbj',
			'krynes',
			'kubiq',
			'kyliesabra',
			'la-geek',
			'lakenh',
			'larrach',
			'leandroalonso',
			'leogermani',
			'leprincenoir',
			'lgrev01',
			'linuxologos',
			'lisota',
			'littlebigthing',
			'ljasinskipl',
			'looswebstudio',
			'lorenzof',
			'luisherranz',
			'luisrivera',
			'lukaswaudentio',
			'lukecavanagh',
			'luminuu',
			'm-e-h',
			'maciejmackowiak',
			'macmanx',
			'mahesh901122',
			'man4toman',
			'manikmist09',
			'manzoorwanijk',
			'mapk',
			'marcelo2605',
			'marcio-zebedeu',
			'marcoz',
			'marekdedic',
			'marius84',
			'markjaquith',
			'marktimemedia',
			'marybaum',
			'mat-lipe',
			'matstars',
			'mattchowning',
			'matthias-reuter',
			'mattkeys',
			'mattnyeus',
			'matveb',
			'mauteri',
			'maxme',
			'mayanksonawat',
			'mbrailer',
			'mcsf',
			'mehidi258',
			'melchoyce',
			'mensmaximus',
			'michael-arestad',
			'michaelecklund',
			'mickaelperrin',
			'miette49',
			'mihdan',
			'miinasikk',
			'mikehansenme',
			'mikejdent',
			'mikeschinkel',
			'mikeschroder',
			'mimitips',
			'mircoraffinetti',
			'miss_jwo',
			'mista-flo',
			'miyauchi',
			'mjnewman',
			'mkaz',
			'mlbrgl',
			'mmarzeotti',
			'mmtr86',
			'morganestes',
			'mppfeiffer',
			'mryoga',
			'msaari',
			'mt8.biz',
			'mte90',
			'mujuonly',
			'mukesh27',
			'mukto90',
			'murgroland',
			'musamamasood',
			'nacin',
			'nagoke',
			'nekomajin',
			'nerrad',
			'netweb',
			'nextscripts',
			'nfmohit',
			'nickdaugherty',
			'nickylimjj',
			'nicole2292',
			'nielslange',
			'nikhilgupte',
			'nilamacharya',
			'noahtallen',
			'noisysocks',
			'nosolosw',
			'noyle',
			'nrqsnchz',
			'nsubugak',
			'nsundberg',
			'nukaga',
			'oakesjosh',
			'obenland',
			'ocean90',
			'oldenburg',
			'otto42',
			'ottok',
			'ov3rfly',
			'paaljoachim',
			'pagewidth',
			'paragoninitiativeenterprises',
			'paranoia1906',
			'passoniate',
			'paulschreiber',
			'pbearne',
			'pbiron',
			'pcarvalho',
			'pedromendonca',
			'pento',
			'perrywagle',
			'peterwilsoncc',
			'philipmjackson',
			'phpbits',
			'pierlo',
			'pierrelannoy',
			'pikamander2',
			'pixelverbieger',
			'poena',
			'prashantvatsh',
			'pratik-jain',
			'presskopp',
			'priyankabehera155',
			'quicoto',
			'raamdev',
			'ragnarokatz',
			'rahe',
			'ramiy',
			'raoulunger',
			'rarst',
			'razamalik',
			'rconde',
			'rcutmore',
			'remcotolsma',
			'rephotsirch',
			'retrofox',
			'rheinardkorf',
			'richtabor',
			'rimadoshi',
			'rinkuyadav999',
			'rixeo',
			'rmccue',
			'rob006',
			'roytanck',
			'rryyaanndd',
			'ryelle',
			'ryokuhi',
			'sabernhardt',
			'sablednah',
			'sainthkh',
			'samuelfernandez',
			'santilinwp',
			'sathyapulse',
			'schlessera',
			'scruffian',
			'scvleon',
			'sebastianpisula',
			'sebastienserre',
			'seedsca',
			'sergeybiryukov',
			'sergioestevao',
			'sergiomdgomes',
			'sgastard',
			'sgoen',
			'sgr33n',
			'shaampk1',
			'shahariaazam',
			'shaikhaezaz80',
			'shariqkhan2012',
			'sheparddw',
			'shital-patel',
			'shizumi',
			'simison',
			'simonjanin',
			'sinatrateam',
			'sirreal',
			'skithund',
			'skorasaurus',
			'skypressatx',
			'smallprogrammers',
			'smerriman',
			'snapfractalpop',
			'sncoker',
			'soean',
			'socalchristina',
			'spacedmonkey',
			'spaceshipone',
			'spenserhale',
			'sproutchris',
			'squarecandy',
			'starvoters1',
			'steelwagstaff',
			'steevithak',
			'steffanhalv',
			'stevegrunwell',
			'stevenlinx',
			'stiofansisland',
			'stroona',
			'studiotwee',
			'subrataemfluence',
			'subratamal',
			'superdav42',
			'swapnild',
			'swissspidy',
			'takeshifurusato',
			'talldanwp',
			'tanvirul',
			'tbschen',
			'tdlewis77',
			'tellyworth',
			'thamaraiselvam',
			'thefarlilacfield',
			'themezee',
			'timhavinga',
			'timon33',
			'timothyblynjacobs',
			'tivus',
			'tjnowell',
			'tkama',
			'tmanoilov',
			'tmatsuur',
			'tobifjellner',
			'tollmanz',
			'tomgreer',
			'tommix',
			'toro_unit',
			'torres126',
			'tristangemus',
			'tristanleboss',
			'tsuyoring',
			'upadalavipul',
			'utsav72640',
			'vadimnicolai',
			'vaishalipanchal',
			'valentinbora',
			'varunshanbhag',
			'veminom',
			'veraxus',
			'vinita29',
			'vinoth06',
			'viper007bond',
			'viralsampat',
			'virgodesign',
			'vortfu',
			'vsamoletov',
			'waleedt93',
			'webmandesign',
			'websupporter',
			'welcher',
			'westonruter',
			'whyisjake',
			'williampatton',
			'wodarekly',
			'wonderboymusic',
			'wpamitkumar',
			'wpgurudev',
			'wpkuf',
			'wptoolsdev',
			'xedinunknown-1',
			'xendo',
			'xknown',
			'xkon',
			'yale01',
			'yordansoares',
			'youknowriad',
			'zachflauaus',
			'zaffarn',
			'zanderz',
			'zebulan',
			'zodiac1978',
			'zsusag',
		];
	}

	public function external_libraries() {
		return [
			[ 'Babel Polyfill', 'https://babeljs.io/docs/en/babel-polyfill' ],
			[ 'Backbone.js', 'http://backbonejs.org/' ],
			[ 'Class POP3', 'https://squirrelmail.org/' ],
			[ 'clipboard.js', 'https://clipboardjs.com/' ],
			[ 'Closest', 'https://github.com/jonathantneal/closest' ],
			[ 'CodeMirror', 'https://codemirror.net/' ],
			[ 'Color Animations', 'https://plugins.jquery.com/color/' ],
			[ 'getID3()', 'http://getid3.sourceforge.net/' ],
			[ 'FormData', 'https://github.com/jimmywarting/FormData' ],
			[ 'Horde Text Diff', 'https://pear.horde.org/' ],
			[ 'hoverIntent', 'http://cherne.net/brian/resources/jquery.hoverIntent.html' ],
			[ 'imgAreaSelect', 'http://odyniec.net/projects/imgareaselect/' ],
			[ 'Iris', 'https://github.com/Automattic/Iris' ],
			[ 'jQuery', 'https://jquery.com/' ],
			[ 'jQuery UI', 'https://jqueryui.com/' ],
			[ 'jQuery Hotkeys', 'https://github.com/tzuryby/jquery.hotkeys' ],
			[ 'jQuery serializeObject', 'http://benalman.com/projects/jquery-misc-plugins/' ],
			[ 'jQuery.query', 'https://plugins.jquery.com/query-object/' ],
			[ 'jQuery.suggest', 'https://github.com/pvulgaris/jquery.suggest' ],
			[ 'jQuery UI Touch Punch', 'http://touchpunch.furf.com/' ],
			[ 'json2', 'https://github.com/douglascrockford/JSON-js' ],
			[ 'Lodash', 'https://lodash.com/' ],
			[ 'Masonry', 'http://masonry.desandro.com/' ],
			[ 'MediaElement.js', 'http://mediaelementjs.com/' ],
			[ 'Moment', 'http://momentjs.com/' ],
			[ 'PclZip', 'http://www.phpconcept.net/pclzip/' ],
			[ 'PemFTP', 'https://www.phpclasses.org/package/1743-PHP-FTP-client-in-pure-PHP.html' ],
			[ 'phpass', 'http://www.openwall.com/phpass/' ],
			[ 'PHPMailer', 'https://github.com/PHPMailer/PHPMailer' ],
			[ 'Plupload', 'http://www.plupload.com/' ],
			[ 'random_compat', 'https://github.com/paragonie/random_compat' ],
			[ 'React', 'https://reactjs.org/' ],
			[ 'Redux', 'https://redux.js.org/' ],
			[ 'Requests', 'http://requests.ryanmccue.info/' ],
			[ 'SimplePie', 'http://simplepie.org/' ],
			[ 'The Incutio XML-RPC Library', 'https://code.google.com/archive/p/php-ixr/' ],
			[ 'Thickbox', 'http://codylindley.com/thickbox/' ],
			[ 'TinyMCE', 'https://www.tinymce.com/' ],
			[ 'Twemoji', 'https://github.com/twitter/twemoji' ],
			[ 'Underscore.js', 'http://underscorejs.org/' ],
			[ 'whatwg-fetch', 'https://github.com/github/fetch' ],
			[ 'zxcvbn', 'https://github.com/dropbox/zxcvbn' ],
		];
	}
}

