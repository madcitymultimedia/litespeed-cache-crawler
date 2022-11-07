<?php

if ( !defined( 'ABSPATH' ) && !defined( 'LS_CARWLER_RUN' ) ) exit;

require_once( 'variables.php' );

require_once('crawler-functions.php');

/**
 * $result = get_url_data('http://test2.local/', $arg=array('timeout' => 120));
 * $args defualt
 * array(3) {
 * ["mobile"]=> false
 * ["return_page_content"]=> false
 * ["timeout"]=> int(10000) }
 */

function get_url_data( $url, $args_input = array() ){
	
	if ( !defined('CURL_RUN') && is_local_request() ) the_heat_up_curl_request();

	$args = add_essential_arguments( $args_input );

	$ch = get_curl_init_lsc( $url, $args );

	if ( $args['mobile'] ) {
		
		$mobile_stats = get_curl_data_lsc( $ch, $url, $args );
	}

	$args['mobile'] = false;

	$desktop_stats = get_curl_data_lsc( $ch, $url, $args );
	
	curl_close($ch);

	$result = isset($mobile_stats) ? array_merge( $desktop_stats, $mobile_stats ) : $desktop_stats;

	return $result; 

/* output
array(6)
{
["url"]=> string(19) "http://test2.local/"
["response"]=> int(200)
["time"]=> int(241)
["cache"]=> string(6) "t-miss"
["page_content"] => string of retrived page content
["curl_error"] => int if error occurs, this will be set with the value
If mobile user agent is active

["time_mobile"]=> int(194)
["cache_mobile"]=> string(6) "t-miss"
["curl_error_mobile"] => int same as ["curl_error"] when mobile user agent request is made
}
*/

}

function add_essential_arguments( $args ){
	
	$args_essentials =	array(	'mobile' => is_mobile_crawl_active( $args ),
								'return_page_content' => false,
								'timeout' => 10000,
								'query_string' => get_settings_qs(),
								'follow_redirect' =>false
							);

	foreach ($args_essentials as $key => $value) {

		$args[$key] = $args[$key] ?? $value;
	
	}

	return $args; 

}

function get_curl_init_lsc( $url, $args ){

	$args['crawled_url']= get_url_with_settings_qs( $url, $args['query_string'] );
	
	$ch = curl_init();

	if ( CRAWLER_URL_IP_SOURCE_IS_PUBLIC ){

		set_curl_settings_for_public_dns( $ch, $args);

	} else {
		
		set_curl_settings_for_local_dns( $ch, $args );

	}

	return $ch;
}

function set_curl_settings_for_local_dns( $ch, $args ){
	
	$url_with_ip = replace_domain_with_ip_lsc( $args['crawled_url'] );

	$header_domain = array('Host: '.$domain = $_SERVER['HTTP_HOST'] );

	curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );

	curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
	
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	
	curl_setopt( $ch, CURLOPT_HEADER, true );
	
	curl_setopt( $ch, CURLOPT_FAILONERROR, true );
	
	curl_setopt( $ch, CURLOPT_URL, $url_with_ip );
	
	curl_setopt( $ch, CURLOPT_HTTPHEADER, $header_domain );
	
	curl_setopt( $ch, CURLOPT_TIMEOUT_MS, $args['timeout'] );
	
	curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, $args['follow_redirect'] ); 

}

function set_curl_settings_for_public_dns( $ch, $args ){

	//Get headers
	curl_setopt( $ch, CURLOPT_HEADER, true );
	//Optionally avoid validating SSL
	curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, get_http_ssl_status() );

	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	
	curl_setopt( $ch, CURLOPT_TIMEOUT_MS, $args['timeout'] );

	curl_setopt( $ch, CURLOPT_URL, $args['crawled_url'] );

	curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, $args['follow_redirect'] );

}

function replace_domain_with_ip_lsc( $link_to_crawl ){

	$server_ip = $_SERVER['SERVER_ADDR'];

	$domain = $_SERVER['HTTP_HOST'];

	return str_replace( $domain, $server_ip, $link_to_crawl );
}

function get_url_with_settings_qs( $url, $qs ){

	if ( $qs === false ) return $url;

 	return add_query_string_to_url( $url, $qs );
}

function get_settings_qs(){

	return array( 
		CRAWLER_SETTINGS["bypass-qs"], 
		CRAWLER_SETTINGS["second-qs"]
	);
}

function get_http_ssl_status(){

	return get_http_protocol()==='https';

}

function is_mobile_crawl_active( $args ){

	return $args['mobile'] ?? CRAWLER_SETTINGS["mobile"] ?? false;

}

function get_curl_data_lsc( $ch, $url , $args ){

	pause_based_on_server_load_lsc();
	
	set_curl_user_agent( $ch, $args );

	$time_measure_start = microtime(true);

	$page_content = curl_exec($ch); 

	$time_measure_end = microtime(true); 

	$httpcode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

	$time_measure_result = get_time_measure_result( $time_measure_end, $time_measure_start );

	$cache_status = get_cache_status( $page_content, $time_measure_result );

	if ( $args['mobile'] ) {

		$result = array( 	'time_mobile' => $time_measure_result,
							'cache_mobile' => $cache_status
							 );

		if ( curl_errno($ch) ) $result['curl_error_mobile'] = curl_errno($ch);

	} else {

		$result = array( 	'url' => $url,
							'response' => $httpcode,
							'time' => $time_measure_result,
							'cache' => $cache_status
							 );
		
		// if( rand(0,1) ) $result['curl_error']='123';

		if ( curl_errno($ch) ) $result['curl_error'] = curl_errno($ch);

		if ( $args['return_page_content'] ) $result['page_content'] = $page_content;
	}
	
	log_curl_data( $url, $page_content, $time_measure_start,$time_measure_end,
	 	$args,$httpcode, $result );
	
	return $result; 
}

function get_time_measure_result( $time_measure_end, $time_measure_start ){

 return (int) (round( $time_measure_end - $time_measure_start, 4 )*1000 );
}

function set_curl_user_agent( $ch, $args ){

	$mobile_suffix = $args['mobile'] ? ' Mobile' : '';

	curl_setopt( $ch, CURLOPT_USERAGENT, CRAWLER_USER_AGENT.$mobile_suffix );
}

function log_curl_data(	$url, $page_content,
						$time_measure_start,$time_measure_end,
	 					$args,$httpcode, $result ){

	if( !DEBUG_CRAWLER ) return;
	
	$mobile_suffix = $args['mobile'] ? ' Mobile' : '';

	$curl_error_message = isset($result['curl_error']) ? 'Curl error:'.$result['curl_error'] :'';

	$curl_error_message .= isset($result['curl_error_mobile']) ? PHP_EOL.'Curl mobile error:'.$result['curl_error_mobile'] :'';

	$curl_error_message = empty($curl_error_message) ? '' : PHP_EOL.PHP_EOL.$curl_error_message;

	ob_start();
?>

Url: <?=$url.$mobile_suffix?>

Response: <?=$httpcode?>

TTFB: <?=get_time_measurements_units($time_measure_start,$time_measure_end)?>

<?php the_header_data_markup( $page_content ); ?>

<?=$curl_error_message?>

*****************************************************************************
<?php

	$output = ob_get_contents();

	ob_end_clean();

	log_to_file ($output);

}


function the_header_data_markup( $page_content ){

	$header_data = get_only_header($page_content);

	if ( empty( $header_data ) ) return;

	?>
Header:
------------------

<?=$header_data?>

------------------	
	<?php

}
function get_time_measurements_units( $start_time_measure, $end_time_measure ){

	$elapsed_time = round( $end_time_measure - $start_time_measure, 3)*1000; 

	$time_unit =' ms';

	if ( round( $elapsed_time,0 ) == 0 ) {

		$elapsed_time = round($end_time_measure - $start_time_measure,8)*1000000;

		$time_unit =' μs';
	}


	return $elapsed_time.$time_unit;

}


function pause_based_on_server_load_lsc() {
		
	$max_server_load = (int) CRAWLER_SETTINGS["max-server-load"];

	if ( $max_server_load ==='0' || is_windows_server_lsc() ) return;

	$current_server_load = get_current_server_load_lsc();

	if ( $current_server_load > $max_server_load  ) {

		usleep(800000);

	} else {

		usleep(100000);

	}
}

function is_windows_server_lsc(){

	return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

}

function get_current_server_load_lsc() {
	
	//high value used if not available measurement
	if (!is_callable('sys_getloadavg') ) return 99;

	return (int) is_array( sys_getloadavg() ) ? sys_getloadavg()[0] : sys_getloadavg();

}