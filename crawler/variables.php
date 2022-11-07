<?php

if ( !defined( 'LS_CARWLER_RUN' ) ) exit;

require_once (__DIR__ . '/variables-functions.php');

define ('CRAWLER_USER_AGENT', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.88 Safari/537.36 LsCrawler' );

define('SITE_BASE_URL', get_base_url() );

define('SITEMAP_URL', SITE_BASE_URL.'/sitemap_index.xml' );

define('LS_CRAWLER_STATS_FOLDER_PATH', get_crawler_LS_CRAWLER_STATS_FOLDER_PATH_path() );

define('SITEMAP_CACHE_FILEPATH', LS_CRAWLER_STATS_FOLDER_PATH.'sitemap-cache.log' );

define('SITEMAP_CACHE_TTL', 86400 );

define('CRAWLER_SETTINGS', get_settings_from_file() );

define('CRAWLER_RUN_INI', LS_CRAWLER_STATS_FOLDER_PATH.'run.ini' );

define('CRAWLER_URL_IP_SOURCE_IS_PUBLIC', false );

define('CRAWLER_MANUAL_START_TTL', 30 ); //seconds

define('ORPHANED_DATA_TTL', 3660); //seconds

define('STATS_TTL', 30 ); //days

ini_set('max_execution_time', 3600 ); //increase PHP timeout



//TODO throw error when settings empty

// vd( get_urls_from_sitemaps() );

// define('SITE_SITEMAP_URL', get_sitemap_url() );

// $base_url = get_base_url();

// $LS_CRAWLER_STATS_FOLDER_PATH_path = get_crawler_LS_CRAWLER_STATS_FOLDER_PATH_path();

// $crawler_settings = get_settings_from_file( 'settings.ini' );

// vd(CRAWLER_SETTINGS);die;
// CRAWLER_SETTINGS["bypass-qs"]=> string(0) ""
// CRAWLER_SETTINGS["second-qs"]=> string(0) ""
// CRAWLER_SETTINGS["mobile"]=> string(1) "1"
// CRAWLER_SETTINGS["whitelist-ip"]=> string(0) ""
// CRAWLER_SETTINGS["excluded-keyword"]=> string(0) ""
// CRAWLER_SETTINGS["secondary-sitemap"]=> string(0) ""
// CRAWLER_SETTINGS["max-server-load"]=> string(0) ""


// $sitmap_position = get_sitemap_position();

// vd( is_authorised_crawler_run_from_backend( $authorization_filename = 'auth.ini' ));



define('HEADER_CACHE_DETECTION_STRINGS',

    array (

        'cdn-hit' => 	array(	'cf-cache-status: hit',
								'x-sucuri-cache: hit',
								'x-qc-cache: hit'
        ),

        'hit' => 		array (	    'x-litespeed-cache: hit',
									'x-cache-status: hit',
									'x-proxy-cache: hit',
									'x-cache: hit',
									'x-kinsta-cache: hit',
									'x-runcloud-cache: hit'
        ),

        'miss' =>		array (	    'x-litespeed-cache: miss',
									'x-proxy-cache: miss',
									'x-runcloud-cache: miss'
        ),
        
        'no-cache' => 	array (	    'x-litespeed-cache-control: no-cache',
									'x-runcloud-cache: bypass'
							)

    )
);