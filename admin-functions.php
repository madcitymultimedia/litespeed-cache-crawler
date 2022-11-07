<?php

defined( 'ABSPATH' ) || exit;

define ('LS_CRAWLER_PLUGIN_PATH', __DIR__.'/');

define ('LS_CRAWLER_URL', plugin_dir_url(__FILE__).'crawler/' );

define ( 'LS_CRAWLER_STATS_FOLDER_PATH', trailingslashit( wp_get_upload_dir()["basedir"] ).'ls-crawler-stats/' );

define ( 'LS_CRAWLER_STATS_FOLDER_URL', trailingslashit( wp_get_upload_dir()["baseurl"] ).'ls-crawler-stats/' );


function set_manual_authorization() {
	
	file_put_contents( LS_CRAWLER_STATS_FOLDER_PATH.'auth.ini','' );
}


function is_save_settings(){

	return  ( $_POST['page'] ?? false ) == 'ls_cache_crawler' &&  $_POST['tab'] == 'options';
}

function get_settings_from_file($setting_file){

	return json_decode(file_get_contents( $setting_file ), true);
}

function get_crawler_settings( $setting_file ) {
	  	
	$settings_to_save = get_setting_to_save( $setting_file );

	if ( empty($settings_to_save) ) return get_settings_from_file($setting_file);

	save_crawler_settings( $setting_file, $settings_to_save );

	return $settings_to_save;
}

function save_crawler_settings( $setting_file, $settings_to_save ){

	file_put_contents( $setting_file, json_encode($settings_to_save) );
}

if ( ! function_exists( 'the_form_checked' )){
	function the_form_checked ($value=false) {
	  
	  if ($value == '1' )  echo 'checked';
	  
	}
}

if ( ! function_exists( 'the_form_value' )){
	function the_form_value($value=false) {

	  if ( !empty($value) or $value == '0' ) echo 'value="'.$value.'"';
	  
	}
}

function get_default_settings_array(){

	return array(	

			'bypass-qs' => '',
			'second-qs' => '',
			'mobile' => '',
			'whitelist-ip' => '',
			'excluded-keyword' => '',
			'secondary-sitemap' => '',
			'max-server-load' => ''
		);
}


function get_setting_to_save( $setting_file ) {
	
	if ( !file_exists($setting_file) ) return get_default_settings_array();

	if ( !is_save_settings() )	return false;

	$settings = $_POST;

	unset($settings['page'],$settings['options']);

	$settings['mobile'] = $settings['mobile']??'';

	$settings['secondary-sitemap'] = remove_domain_from_url( $settings['secondary-sitemap'] );

	return $settings;
}

function remove_domain_from_url( $url ){

	if ( empty($url) ) return '';

	$url_array = parse_url($url);

	if ( empty ( $url_array['query'] ) ) return $url_array['path'];
	
	return $url_array['path'].'?'.$url_array['query'];

}


if ( ! function_exists( 'get_log_link' )){
	function get_log_link($data,$upload_stats_dir){

	  $data_array = explode(" ", $data);

	  $filename = date("Y-m-d", strtotime(str_replace( '/','-',$data_array[1]))).'_';

	  $filename .= str_replace (':','-',$data_array[2]).'.log';

	  if (file_exists($upload_stats_dir.$filename)) {
		// echo'yes';
		return $filename;
	  }
	  // echo'no';
	  return false;
	}

}


function the_overall_stats( $active_log, $upload_stats_dir ) {
	
	if ( !empty($active_log) ) {

		if (file_exists($upload_stats_dir.$active_log)) {

			echo '<hr>'.nl2br(file_get_contents($upload_stats_dir.$active_log));
		
		} else {

			echo '<hr> File '.$active_log.' not found.';
		
		}

	}
}


/**
 * return: bool
 * Has folder been successfully created?
 **/

function create_stats_folder($setting_file) {

	if ( file_exists(LS_CRAWLER_STATS_FOLDER_PATH) ) return true;


	if( !is_stats_foler_created() ) return display_admin_notice_stat_folder_creation_error();

	save_crawler_settings( $setting_file , get_default_settings_array() );

	file_put_contents( LS_CRAWLER_STATS_FOLDER_PATH.'index.php', '<?php'.PHP_EOL.'//silence is golden'.PHP_EOL.'?>' );

	return true;
}

function is_stats_foler_created(){

	return mkdir( LS_CRAWLER_STATS_FOLDER_PATH , 0755, true);
}

function display_admin_notice_stat_folder_creation_error(){

	add_settings_error(
        'ls_crawler_error',
        'folder_creation',
        __('Statistics and settings folder could not be created. Folder /wp-content/uploads/ must be writable.','ls_crawler'),
        'error'
    );

	return false;
}

function php_timeout_warning() {
	
	$php_timeout = ini_get('max_execution_time');
	
	if ( $php_timeout<10 ) echo '<br>PHP timout is a bit low: '.$php_timeout.'seconds. Recommended at least 30 seconds<br><br>';

}

function the_start_stop_button(){

	if ( is_crawler_running() ){

		body_running_crawler();
	
	} else {

		set_manual_authorization();

		the_start_button();

	}
}

function body_running_crawler(){

	if ( has_stopping_crawler_initialised() ) {
		
		the_stop_process();

	} else {

		the_running_crawler_progress();

		the_stop_button();

	}

}

function has_stopping_crawler_initialised(){
	
	return isset($_GET['stop']);
}

function the_start_button(){

	$token = uniqid();

	store_manual_run_token($token);

	$manual_start_crawler_qs = '?token='.$token;

	$manual_start_crawler_qs .= empty($crawler_settings['bypass-qs']) ? '' : '&'.$crawler_settings['bypass-qs'];

	$manual_start_crawler_url =  LS_CRAWLER_URL.$manual_start_crawler_qs;

	?>
	<br>
	<a href="<?=$manual_start_crawler_url?>" target="_blank">
		<button class="button button-primary" type="submit" id="submit">Run crawler now</button>
	</a>
	<br><br>
	<?php

}

function store_manual_run_token($token){
	
	$auth_file = LS_CRAWLER_STATS_FOLDER_PATH.'auth.ini';

	file_put_contents( $auth_file, $token);
}

function the_stop_process(){
	
	if ( is_crawler_active() ) {

		echo 'Stopping crawler in progress, refresh page in few seconds.';

		file_put_contents( LS_CRAWLER_STATS_FOLDER_PATH.'stop.ini','' );

	} else {

		define( 'LS_CARWLER_RUN', false );

		require_once( __DIR__.'/crawler/crawler-functions.php');

		rename_temp_to_log_files( $orphaned = true );
		
		unlink(LS_CRAWLER_STATS_FOLDER_PATH.'run.ini');
	}

	_e( 'Crawler being stopped.', 'ls-crawler' );

}


function is_crawler_active(){

	sleep(3);
	
	return has_file_ttl( LS_CRAWLER_STATS_FOLDER_PATH.'run.ini', 3 );
}

function has_file_ttl( $filepath, $ttl ){

	return get_file_age( $filepath ) <= $ttl;
}

function get_file_age( $filepath ){

	return time() - filemtime( $filepath );

}

function the_stop_button(){
	
	$stop_active_crawler_url = admin_url( 'options-general.php?page=ls_cache_crawler&stop' );
	
	?>
	<br>
	<a href="<?=$stop_active_crawler_url?>">
		<button class="button button-primary" type="submit" id="submit_stop">Stop crawler</button>
	</a>
	<br>
	<?php
}

function the_running_crawler_progress(){

	$crawler_position = explode('-',file_get_contents(LS_CRAWLER_STATS_FOLDER_PATH.'run.ini'));

	if ( empty(reset($crawler_position)) ) return;

	$current_position = (int) $crawler_position[0];

	$end_position = (int) ($crawler_position[1]??0);

	$percentile_done = $end_position != 0 ? round(($current_position/$end_position)*100) :'0';

	?>
Crawler is now running<br><br>
Current progress:<br>
Position: <?=$current_position?> of <?=$end_position?> (<?=$percentile_done?>% done)<br>
<?php

}


function is_crawler_running(){

	return file_exists( LS_CRAWLER_STATS_FOLDER_PATH.'run.ini' );

}





















// function get_url_stats_array( $stats_array ){/*legacy*/

// 	$stats_bits_array = explode(';', $stats_array);

// 	$result = array();

// 	foreach ( $stats_bits_array as $stats_unit ) {

// 		if ( is_empty_stats_unit( $stats_unit ) ) continue;

// 		$pre_result = get_finnal_stats_array( $stats_unit );

// 		if ( !empty($pre_result) ) $result = array_merge( $pre_result, $result);
		
// 	}

// 	return $result;
// }

// function is_empty_stats_unit( $stats_unit ){

// 	return !is_int( strpos( $stats_unit, '|') );

// }

// function get_finnal_stats_array( $stats_unit ){

// 	$finnal_stats_array = explode('|', $stats_unit );

// 	if ( is_invalid_stat_name( $finnal_stats_array ) ) return array();

// 	$result[$finnal_stats_array[0]] = $finnal_stats_array[1];

// 	return $result;
// }

// function is_invalid_stat_name( $finnal_stats_array ){

// 	$stat_name_lenght = strlen( $finnal_stats_array[0] );

// 	return  $stat_name_lenght<3 ? true : false;
// }

function get_latest_crawl_stats_file(){

	$stats_files = scandir( LS_CRAWLER_STATS_FOLDER_PATH , SCANDIR_SORT_DESCENDING);

	foreach ($stats_files as $file ) if ( is_log_file($file)  ) return $file;

	return false;

}

function is_log_file($file){


	return is_int( strpos($file,'_') ) && is_int( strpos($file,'.log') );

}


function load_plugin_css(){

	add_action('admin_print_styles', 'enqueue_carwler_plugin_styles');
}

function enqueue_carwler_plugin_styles(){

    wp_register_style( 'ls_crawler_admin_css', trailingslashit(plugin_dir_url( __FILE__ )).'style.css', false, '1.0.0' );
    wp_enqueue_style( 'ls_crawler_admin_css' );
}

function get_current_server_load_lsc() {
	
	if (!is_callable('sys_getloadavg') ) return 'N\A';

	return (int) is_array( sys_getloadavg() ) ? sys_getloadavg()[0] : sys_getloadavg();

}
