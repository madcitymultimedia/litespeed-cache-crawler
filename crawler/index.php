<?php

define('LS_CARWLER_RUN', true );

define( 'ABSPATH', dirname(__DIR__,4).'/');

define('HAS_TOKEN', isset($_GET['token'] ) );

if ( file_exists( ABSPATH.'wp-content/mu-plugins/0a-deb-functions.php' ) ){
	@include ABSPATH.'wp-debug/wp-debug.php';

	@include ABSPATH.'wp-content/mu-plugins/0a-deb-functions.php';

	//TODO server addr different to header ip
}
	
require __DIR__.'/debug.php';

require_once __DIR__.'/variables.php';

the_html_header();

iniciate_crawler();


function iniciate_crawler(){

	$request_ip = get_request_ip();

	$crawler=[
				'run' => false,
				'display-message' => 'Unauthorised IP: '.$request_ip,
				'log-message' => 'Unauthorised IP: '.$request_ip
			];

	if ( is_crawler_iniciation_authorised( $request_ip ) ) {

		$crawler=[
					'run' => true,
					'display-message' => 'Started',
					'log-message' => ''
		];
	}

	if ( is_another_instance_running() ) {

		$crawler=[
					'run' => false,
					'display-message' => 'Crawler haven\'t finish previous run.',
					'log-message' => 'Stopped, another instance running.'
		];

	}

	if (!empty( $crawler['display-message'] ) ) echo $crawler['display-message'];

	if (!empty( $crawler['log-message'] ) ) log_to_file( $crawler['log-message'], $crawler['run'] );

	if( $crawler['run' ] === true ) require __DIR__.'/crawler.php';

	die;
}

function is_another_instance_running(){

	if ( !file_exists(CRAWLER_RUN_INI) ) return false;

	sleep(3);
	
	return has_file_ttl( CRAWLER_RUN_INI, 3 );
}

the_html_footer();

function the_html_header(){

	header ('Content-Type: text/html; charset=UTF-8');

	header('cache-control: no-cache, must-revalidate, max-age=0');

	header('x-litespeed-cache-control: no-cache');

	if ( !HAS_TOKEN ) return;
	?>
	<!DOCTYPE html>
	<html>
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title></title>
	</head>
	<body style="font-family: system-ui;background: #555;color: #eee;white-space: break-spaces;">
	<?php 
}

function the_html_footer(){

	if ( !HAS_TOKEN ) return;

	?>
	</body>
	</html>
	<?php
	die();
}