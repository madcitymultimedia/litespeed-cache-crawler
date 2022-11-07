<?php

if ( !defined( 'LS_CARWLER_RUN' ) ) exit;

/**
 * Useless request, just to use once curl to get first request time reading correct
 */
function the_heat_up_curl_request(){

	define('CURL_RUN', true );

	get_url_data( SITEMAP_URL, $args=[ 'mobile' => false, 'timeout' => 120 ] );

}

function end_crawling( $orphaned = false ) {

	unlink( CRAWLER_RUN_INI );
	
	end_temp_and_log_files( $orphaned );

}

function end_temp_and_log_files( $orphaned=false ){

	rename_temp_to_log_files( $orphaned );

	$log_massage = $orphaned ? 'unfinished' : '';
	
	log_the_end($log_massage);

}

function restart_crawler(){
	
	$url_to_restart = SITE_BASE_URL.parse_url($_SERVER['REQUEST_URI'])['path'];

	$restart_query_string = array(
		CRAWLER_SETTINGS["bypass-qs"],
		'local'
	);

	$args=array(	'timeout' => 300,
					'query_string'=> $restart_query_string, 
					'return_page_content' => false,
					'mobile'=>false );

	get_url_data( $url_to_restart , $args );

}



function create_run_ini( $links_count_all ){

	file_put_contents( CRAWLER_RUN_INI , '0-'.$links_count_all );
}

function get_crawler_position(){

	if ( !file_exists( CRAWLER_RUN_INI ) ) return 0;
	
	return explode( '-', file_get_contents( CRAWLER_RUN_INI ))[0];

}

function store_crawler_position($current_crawler_postion,$links_count_all){

	file_put_contents( CRAWLER_RUN_INI , $current_crawler_postion.'-'.$links_count_all );
}

function get_cache_status( $url_data, $time_measure_result ){
	
	// if( rand(0,1) ) return 'no-cache';

	$url_data = strtolower( get_only_header( $url_data ) );

	$header_detected_status = get_header_detected_status( $url_data );

	if ( $header_detected_status ) return $header_detected_status;

	$result = 't-'; //time based / assumed result as there is no cache status information available in the header

	$time_based_assumed_result_threshold = 150; // in ms

	$result .= $time_based_assumed_result_threshold > $time_measure_result ? 'hit' : 'miss';

	return $result;

}

function get_header_detected_status( $url_data ) {

	foreach ( HEADER_CACHE_DETECTION_STRINGS as $detection_type => $detection_string_array ) {

		if ( has_detection_string( $detection_string_array, $url_data ) ) return $detection_type;
	}

	return false;
}

function has_detection_string( $detection_string_array, $url_data ) {

	foreach ( $detection_string_array as $detection_string ) {

		if ( strpos( $url_data, strtolower($detection_string)) ) return true;

	}

	return false;

}

function get_only_header( $html ){

	$result ='';

	foreach( explode("\n",$html) as $line ){

		if (strlen($line) < 2 ) break;
		
		$result .=$line;
	}

	return $result;
}

function get_xml_links ($html) {

	$result = get_links_from_aioseo_xml_format($html);

	if ( empty( $result[0] ) ) $result =  get_links_from_yoast_xml_format($html);

	return $result;

}

function get_links_from_aioseo_xml_format($html){

	preg_match_all('/<loc>.+?(http.+?)\]/', $html, $matches); 

	return $matches[1];
}

function get_links_from_yoast_xml_format($html){

	preg_match_all('/<loc>(.+?)<\/loc>/', $html, $matches); 

	return $matches[1];
}

function get_xml_content( $url ) {

	if ( empty( $url) ) return '';

	$data = get_url_data( 
		
			$url, 
			$arg = array(
				'return_page_content' => true,
				'query_string'=> CRAWLER_SETTINGS["bypass-qs"],
				'follow_redirect' =>true
			)

	);

	return $data["page_content"] ?? '';
}

function add_query_string_to_url( $url, $qs ){

	if ( !is_array( $qs) ) $qs = array( $qs );

	foreach ( $qs as $qs_value ) {

		if ( is_qs_empty( $qs_value) ) continue;

		$parsed_url = parse_url($url);

		$separator = isset( $parsed_url['query'] ) ? '&' : '?';

		$url .= $separator . $qs_value;

	}

	return $url; 

}

function is_qs_empty( $qs ){

	if ( $qs === '0' ) return false;

	return empty( $qs );
}


function store_stats_to_file( $link_stats ){

	$current_crawls_stats_file = get_stats_file();
	
	$serialized_stats = serialize_crawler_stats($link_stats);

	file_put_contents( $current_crawls_stats_file, $serialized_stats.PHP_EOL, FILE_APPEND );

}

function serialize_crawler_stats( $link_stats ){

	if ( empty($link_stats['url']) ) return json_encode( $link_stats );

	//remove URL base to make stats file shorter and save the disk space
	$link_stats['url'] = remove_base_url( $link_stats['url'] ); 

	return json_encode( $link_stats );
}

function remove_base_url( $url ){

	return str_replace( SITE_BASE_URL, '', $url);
}

function get_stats_file(){

	$stats_file = get_list_of_temp_files()[0]??false;

	if ( empty($stats_file) ) {
		
		remove_old_stats_files();

		$stats_file = get_new_temp_file();
	}

	return $stats_file ;

}

function get_new_temp_file (){

//	rename_temp_to_log_files();

	$temp_stats_file = LS_CRAWLER_STATS_FOLDER_PATH.date("Y-m-d_H-i-s").'.temp';

	$start_data = [ 'start_data' => [

					'start-time' => microtime(true),
					'server-load' => get_current_server_load_lsc()

					]

				];

	file_put_contents( $temp_stats_file, json_encode($start_data).PHP_EOL );

	return $temp_stats_file;
}

function rename_temp_to_log_files( $orphaned = true ) {

	$temp_files = get_list_of_temp_files();

	if ( empty($temp_files) ) return;

	$file_suffix = $orphaned ? '-unfinished' : '';

	foreach ($temp_files as $file ) {

		$file_with_log_extension = str_replace('.temp', $file_suffix.'.log', $file);

		rename ($file, $file_with_log_extension);

	}

}

function get_list_of_temp_files( ){

	foreach (new RecursiveIteratorIterator( new RecursiveDirectoryIterator(LS_CRAWLER_STATS_FOLDER_PATH) ) as $filename) {
	
		if ( $filename->getExtension() == 'temp' ) $result[] = strval($filename);
	
	}

	return $result??false;
}

function pause_based_on_server_load() {
		
	$max_server_load = (int) CRAWLER_SETTINGS["max-server-load"];

	if ( $max_server_load ==='0' || is_windows_server() ) return;

	$current_server_load = get_current_server_load();

	if ( $current_server_load > $max_server_load  ) {

		usleep(800000);

	} else {

		usleep(100000);

	}
}

function is_windows_server(){

	return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

}

function get_current_server_load() {

	return (int) is_array( sys_getloadavg() ) ? sys_getloadavg()[0] : sys_getloadavg();

}

function is_local_request(){

	return $_SERVER['SERVER_ADDR'] == get_request_ip() && isset($_GET['local']);
}

function clear_orphaned_data(){

	$run_ini_exists = file_exists( CRAWLER_RUN_INI );
	
	$run_ini_has_ttl = $run_ini_exists ? 
		
				has_file_ttl( CRAWLER_RUN_INI, ORPHANED_DATA_TTL ) :
				false;
	
	if ( $run_ini_exists && $run_ini_has_ttl ) return;

	if ( $run_ini_exists && !$run_ini_has_ttl ) unlink(CRAWLER_RUN_INI);

	rename_temp_to_log_files();

}

function is_log_or_temp_file($filename){

		if ( !is_int( strpos($filename,'_') ) ) return false;

		return is_int( strpos($filename,'.temp') ) || is_int( strpos($filename,'.log') );
}

function remove_old_stats_files(){
	
	$files_in_stats_folder = scandir( LS_CRAWLER_STATS_FOLDER_PATH , SCANDIR_SORT_ASCENDING );

	foreach ( $files_in_stats_folder as $filename ) {

		if ( !is_log_or_temp_file($filename) ) continue;
		
		if ( has_file_ttl( LS_CRAWLER_STATS_FOLDER_PATH.$filename, STATS_TTL * 86400 ) ) break;
		
		unlink( LS_CRAWLER_STATS_FOLDER_PATH.$filename );
		
	}

}