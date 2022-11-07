<?php

defined( 'ABSPATH' ) || exit;

if ( uri_has_string('plugins.php') && ! function_exists( 'add_action_links_ls_crawler' ) ){

	function add_action_links_ls_crawler ( $actions ) {

		$mylinks = array(
			'<a href="' . admin_url( 'options-general.php?page=ls_cache_crawler' ) . '">Settings</a>',
		);

		$actions = array_merge( $mylinks, $actions );

		return $actions;
	}

add_filter( 'plugin_action_links_' .basename(__DIR__).'/litespeed-cache-crawler.php', 'add_action_links_ls_crawler' );

}

function ls_crawler_admin_menu() {

	add_submenu_page('options-general.php',
	//add_menu_page( 
		'LiteSpeed Cache Crawler', 
		'LiteSpeed Cache Crawler', 
		'administrator', 
		'ls_cache_crawler', 
		'ls_crawler_admin_page'
		);

	// if( is_plugin_active( 'litespeed-cache/litespeed-cache.php' ) ) { 
	//   add_menu_page('LiteSpeed Cache', 'LiteSpeed Cache', 'manage_options', 'lscache-settings') ;

	//   add_submenu_page('lscache-settings',
	//       'LiteSpeed Cache Crawler', 
	//       'LiteSpeed Cache Crawler', 
	//       'administrator', 
	//       'ls_cache_crawler', 
	//       'ls_crawler_admin_page', 
	//       'dashicons-controls-repeat' 
	//      );
	// }else {

	//   }

}
add_action('admin_menu', 'ls_crawler_admin_menu');


function is_plugin_menu_lsc(){

	return  
			
			( $_GET['page'] ?? '') == 'ls_cache_crawler' &&
	
			uri_has_string('options-general.php');
}


function uri_has_string( $string ){

	return is_int(strpos( $_SERVER["REQUEST_URI"], $string ));

}
