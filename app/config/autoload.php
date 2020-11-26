<?php
defined('APP_PATH') || define('APP_PATH', realpath('.'));

/**
 * Auto Load file from Config
 *
 * @example
 * 	$autoload = [
 *		'Namespaces' => [
 *			'App\Controllers' => APP_DIR . '/controllers/',
 *			'App\Models' => APP_DIR . '/models/',
 *			'App\Security' => APP_DIR . '/security/',
 *			'App\Services' => APP_DIR . '/services/'
 *		],
 *		'Dirs' => [
 *
 *		]
 *	];
 */

$autoload = [
	'Namespaces' => [
		'App\Controllers'		=>	APP_PATH . '/controllers/',
		'App\Models'			=>	APP_PATH . '/models/',
		'App\Services'			=>	APP_PATH . '/services/',
		'App\Routes'			=>	APP_PATH . '/routes/',
		'App\Tasks' 			=>	APP_PATH . '/tasks/'
	],
	'Dirs' => [
		APP_PATH . '/tasks',
		APP_PATH . '/models',
		APP_PATH . '/controllers',
	]
];

return $autoload;
