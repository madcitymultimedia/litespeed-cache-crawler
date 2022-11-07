<?php

if ( !defined('DEBUG_CRAWLER') ) define ('DEBUG_CRAWLER', false );

// define ('DEBUG_TMP_FILE', tempnam(sys_get_temp_dir(), 'ls-crawler-debug.tmp') );

// define ('DEBUG_TMP_FILE', __DIR__.'/crawler/ls-crawler-debug.tmp' );

defined('DEBUG_LOG_FILE') || define ('DEBUG_LOG_FILE', __DIR__.'/debug'.date("Y-m-d_h-i-sa").'.log' );

store_php_errors();

create_temp_log_file();

function log_the_end( $ending_note='' ) {

	if ( ! DEBUG_CRAWLER ) return;
	
	write_to_file( 'Ended '.$ending_note );
}

function create_temp_log_file() {
	
	if( DEBUG_CRAWLER !== true ) return;

	if ( file_exists( DEBUG_LOG_FILE ) ) return;

	$initial_record = date(	"Y-m-d_H:i:s").
							' '.
							get_current_time_milliseconds().
							'ms Started Request microtime: '.
							$_SERVER['REQUEST_TIME_FLOAT'].
							PHP_EOL.PHP_EOL;

	$result = @file_put_contents( DEBUG_LOG_FILE, $initial_record );
	
	if ( $result === false ) error_log('[LS Crawler] Unable to create debug.log file');
}

function get_current_time_milliseconds(){

	return (string) round( explode(' ', microtime() )[0] * 1000,0 );

}

function write_to_file( $message='') {

	$message_to_write = ( empty($message) && $message !=='0' ? '[empty]': $message);

	$current_time = date("H:i:s").' '.get_current_time_milliseconds().'ms';

	$string_to_write = $current_time.' '.$message_to_write.PHP_EOL.PHP_EOL;

	$result = @file_put_contents( DEBUG_LOG_FILE, $string_to_write, FILE_APPEND );
	
	if ( $result === false ) error_log('[LS Crawler]Unable to write to debug.log file');

}

function log_to_file($string='', $completed = false ) {

	if ( DEBUG_CRAWLER === false ) return;

	write_to_file( $string );

	if ( $completed ) log_the_end();

}

function store_php_errors(){

	ini_set("log_errors", true ); 

	ini_set('error_log', __DIR__.'/error.log' );
}