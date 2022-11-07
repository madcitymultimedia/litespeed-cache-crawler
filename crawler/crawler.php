<?php

if ( !defined( 'LS_CARWLER_RUN' ) ) exit;

require_once('crawler-functions.php');

require_once('curl.php');

if ( is_local_request() ) {

	clear_orphaned_data();

	run_crawler();

} else {
	
	restart_crawler();
}

die;


function run_crawler (){

	extract( get_crawler_data() );

	log_to_file('Start position '.$crawler_position);

	$crawler_end_position = crawler_loop( $links_to_crawl, $crawler_position, $links_count_all);

	if ( $crawler_end_position >= $links_count_all ) {
		
		$finnal_data = [ 'finnal_data' => [

							'end-time' => microtime(true),
							'total-links-count' => $links_count_all,
							'server-load' => get_current_server_load_lsc()

							]

		];

		store_stats_to_file( $finnal_data );
	
		end_crawling();
	

	} else {

		// echo 'Restart position '.$crawler_end_position ;

		log_to_file('Restart position '.$crawler_end_position);

		restart_crawler();
	}

}

function get_crawler_data(){

	$links_to_crawl_all = get_links_to_crawl();

	$result['links_count_all'] = count( $links_to_crawl_all );

	$result['crawler_position'] = (int)get_crawler_position();

	if ( $result['crawler_position'] === 0 ) create_run_ini( $result['links_count_all'] );

	$result['links_to_crawl'] = get_remaining_links_to_crawl( $links_to_crawl_all, $result['crawler_position']);
	
	return $result;

}

function crawler_loop( $links_to_crawl, $crawler_position, $links_count_all ){

	foreach( $links_to_crawl as $key => $link_to_crawl ){

		$link_stats = get_url_data( $link_to_crawl );

		store_stats_to_file( $link_stats );
		
		store_crawler_position( ++$crawler_position, $links_count_all );

		if ( has_php_reached_timeout() ) break;
		
		if ( has_stop_signal() ) {

			log_to_file('Manually stopped at position: '.$crawler_position);

			//makes it as it already has crawled all the website
			$crawler_position = $links_count_all;
			
			unlink(LS_CRAWLER_STATS_FOLDER_PATH.'stop.ini');

			break;
		}
	}

	return $crawler_position;
}

function has_stop_signal(){

	return file_exists( LS_CRAWLER_STATS_FOLDER_PATH.'stop.ini' );
}

function has_php_reached_timeout(){
	
	$php_timeout = ini_get('max_execution_time') - 4;
	
	// $php_timeout = 1;

	$start_time = (float)$_SERVER['REQUEST_TIME_FLOAT'];

	return $php_timeout < (round(microtime(true) - $start_time));

}