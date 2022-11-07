<?php

defined( 'ABSPATH' ) || exit;

/*Version 2021-07-22
//error_reporting(E_ALL);

// require_once(trailingslashit(__DIR__).'crawler/clean.php');

/**
 * Admin back-end
*/

require_once __DIR__.'/admin-functions.php';

load_plugin_css();


function display_admin_header(){

	?> 
	<h2>
		<?PHP _e('LiteSpeed cache crawler','ls_crawler');?>
	</h2>
	<h4>
		<i>
			<?PHP _e('Inspired by LiteSpeed, for service of everyone','ls_crawler');?>
		</i>
	</h4>
	<?php 
}

function display_tab_nav( ){
	
	?>
	<div class="my-tabs">
		<h2 class="nav-tab-wrapper">
			<?php display_tab_links(); ?>
		</h2>
	</div>
	<?php 
}

function display_tab_links(){

	$tabs = get_tabs_arr();

	$active_tab = get_active_tab();

	foreach ($tabs as $slug => $tab_arr){

		$active_tab_class = $slug == $active_tab ? ' nav-tab-active' : '';

		$tab_qs = $slug == 'home' ? '':'&tab='.$slug;

		?>
		<a href="?page=ls_cache_crawler<?=$tab_qs?>" class="nav-tab<?=$active_tab_class?>">
			<?=$tab_arr['title']?>
		</a>
		<?php
	}
}


function get_active_tab(){

	if ( empty($_GET[ 'tab' ]) ) return 'home';

	$tab_slugs=array_keys(get_tabs_arr());

	return in_array($_GET[ 'tab' ], $tab_slugs) ? $_GET['tab']:'home';
}

/**
 * @return array [slug]=>['title']=>(string),['require']=>(string)
 */

function get_tabs_arr(){

	return [
		
		'home'		=> [ 
				
			'title' 	=> __('Home','ls_crawler'),
			'require'	=> 'home.php',
		],
		'stats'		=> [

			'title' 	=> __('Statistics','ls_crawler'),
			'require'	=> 'statistics.php',
		],
		
		'options'	=> [

			'title' 	=> __('Options','ls_crawler'),
			'require'	=> 'options.php',
		]
	
	];

}

function get_tab_require_filepath(){

	return LS_CRAWLER_PLUGIN_PATH.'includes/'.get_tabs_arr()[get_active_tab()]['require'];

}

function ls_crawler_admin_page() {

	$setting_file = LS_CRAWLER_STATS_FOLDER_PATH.'settings.ini';

	$stats_folder_creation_sucess = create_stats_folder( $setting_file );

	display_admin_header();

	settings_errors();

	if ( !$stats_folder_creation_sucess ) return;

	$crawler_settings = get_crawler_settings( $setting_file );
	
	display_tab_nav( );
	
	require get_tab_require_filepath();

}