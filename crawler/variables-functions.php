<?php

if ( !defined( 'LS_CARWLER_RUN' ) ) exit;


function get_base_url() {

	$base_url = get_http_protocol().'://'.$_SERVER['HTTP_HOST'];

    return rtrim($base_url,"/");

/**
 * @param output https://domain.com
 * no trailing slash
 **/
}

function get_http_protocol(){

	if ( !isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != "on" ) return 'http';

	return 'https';

}


function get_crawler_LS_CRAWLER_STATS_FOLDER_PATH_path(){

	return dirname(__DIR__,3).'/uploads/ls-crawler-stats/';
	
}

function get_settings_from_file( ){
	
	return json_decode(file_get_contents(LS_CRAWLER_STATS_FOLDER_PATH.'settings.ini'), true);
	
}


function is_authorised_crawler_run_from_backend( $authorization_filename ){

	$authorization_file_path = LS_CRAWLER_STATS_FOLDER_PATH.$authorization_filename;

	if (!file_exists( $authorization_file_path ) ) return false;
  
	$adamin_backend_authorization_timeout = 20;

	return has_file_ttl( $authorization_file_path, $adamin_backend_authorization_timeout);

}

function has_file_ttl( $filepath, $ttl ){

	return get_file_age( $filepath ) <= $ttl;
}

function get_file_age( $filepath ){

	return time() - filemtime( $filepath );

}

function get_urls_from_sitemaps(){

	$time_measure_start = microtime(true);

	$sitemap_url = empty(CRAWLER_SETTINGS["secondary-sitemap"]) ? SITEMAP_URL : SITE_BASE_URL.CRAWLER_SETTINGS["secondary-sitemap"];

	$sitemap_data = get_xml_content( $sitemap_url );

	$sitemap_subpages_links = get_xml_links( $sitemap_data );

	$result = array();
	
	foreach ( $sitemap_subpages_links as $url ) {

		$result = array_merge( $result, get_xml_links_from_url( $url ) );

	}

	$result = array_unique(filter_excluded_keywords( $result ));

	$time_measure_end = microtime(true); 

	$time_measure_result = get_time_measure_result( $time_measure_end, $time_measure_start );

	$time_in_seconds = round( $time_measure_result/1000, 2 );
	
	log_to_file( 'Total '.count($result).' links found in '.$time_in_seconds.' s.');

	return $result;
}



function filter_excluded_keywords( $urls ) {

	if ( empty( CRAWLER_SETTINGS["excluded-keyword"] ) ) return $urls; 
	
	$exluded_keywords = explode(',', CRAWLER_SETTINGS["excluded-keyword"] );

	foreach ( $urls as $url ) {

		if ( has_excluded_keyword( $url, $exluded_keywords ) ) continue;

		$result[]=$url;
	}

	return $result; 
	
}

function has_excluded_keyword( $url, $exluded_keywords ) {

	foreach ( $exluded_keywords as $keyword ){

		if ( is_int(strpos( $url , $keyword)) ) return true;
	}

	return false;
}

function get_xml_links_from_url( $url ) {

	return get_xml_links( get_xml_content($url) );
}




function get_links_to_crawl(){

	if ( has_valid_sitemap_cache() ){
		
		return get_links_from_sitemap_cache();
	}

	$links_to_crawl = (array) get_urls_from_sitemaps();
		
	if ( empty($links_to_crawl) || count($links_to_crawl) === 0 ) {

		log_to_file('No links to crawl been found');

		return array();
	}

	create_sitemap_cache( $links_to_crawl );

	return $links_to_crawl;
}

function create_sitemap_cache( $links_to_crawl ){

	$links_to_write = implode( PHP_EOL, $links_to_crawl );

	$write_success = is_int( file_put_contents( SITEMAP_CACHE_FILEPATH, $links_to_write ) );

	$log_message = $write_success ? 'Sitemap cache has been written' : 'Error writing sitemap cache file';
	
	log_to_file($log_message);

}

function get_links_from_sitemap_cache(){
	
	$sitemap_cache_data = file_get_contents(SITEMAP_CACHE_FILEPATH);

	$links_to_crawl = explode(PHP_EOL, $sitemap_cache_data );

	return $links_to_crawl;
}

function get_remaining_links_to_crawl($links_to_crawl, $crawler_start_position){
	
	return array_slice( $links_to_crawl, $crawler_start_position, count($links_to_crawl) );
}

function has_valid_sitemap_cache() {

	if ( !file_exists( SITEMAP_CACHE_FILEPATH ) || filesize(SITEMAP_CACHE_FILEPATH)<10 ) return false;

	$is_sitemap_cache_within_time_limit = get_file_age(SITEMAP_CACHE_FILEPATH) <= SITEMAP_CACHE_TTL ? true : false;
	
	return $is_sitemap_cache_within_time_limit;

}

function get_request_ip(){

	$request_header_ip_array = array (

		'HTTP_CF_CONNECTING_IPV6',

		'HTTP_CF_CONNECTING_IP',

		'HTTP_X_REAL_IP',

		'REMOTE_ADDR'

	);

	foreach ( $request_header_ip_array as $header_name ) {

		if ( !empty( $_SERVER[$header_name]) ) return $_SERVER[$header_name];
	}

	return false;
}


function is_crawler_iniciation_authorised( $request_ip ){

	if ( is_authorised_from_back_end() ) return true;

	return in_array( $request_ip, get_whitelisted_ips() );

}

function is_authorised_from_back_end(){

	$authorization_file = LS_CRAWLER_STATS_FOLDER_PATH.'auth.ini';

	if ( !file_exists( $authorization_file) ) return false;

	$age_of_authorization_file = time()-filemtime( $authorization_file );

	$token = file_get_contents( $authorization_file );

	unlink( $authorization_file );

	if ( !is_within_the_time_limit($age_of_authorization_file) ) return false;


	if ( $token !== ( $_GET['token'] ?? false ) ) return false;
	
	log_to_file('Authorised from back-end');

	return true;
	

}

function is_within_the_time_limit($age_of_authorization_file){

	return $age_of_authorization_file <= CRAWLER_MANUAL_START_TTL;
}

function get_whitelisted_ips(){

	return array(

		//localhost
		'127.0.0.1'

		// Easycron IPs
		,'198.27.83.222'
		,'2607:5300:60:24de::'
		,'198.27.81.205'
		,'2607:5300:60:22cd::'
		,'198.27.81.189'
		,'2607:5300:60:22bd::'
		,'192.99.36.110'
		,'2607:5300:60:4b6e::'
		,'192.99.21.124'
		,'2607:5300:60:467c::'
		,'167.114.64.88'
		,'2607:5300:60:6558::'
		,'167.114.64.21'
		,'2607:5300:60:6515::'
		,'2001:41d0:800:2503::'
		// the end Easycron IPs

		//server's IP
		, $_SERVER['SERVER_ADDR']

		//whitelisted IP in plugin's settings
		, CRAWLER_SETTINGS["whitelist-ip"]

	);

}
