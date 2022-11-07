<?php

defined('ABSPATH') || die;

//https://academy.datawrapper.de/article/225-what-you-can-do-with-our-api-and-how-to-use-it


function get_files_by_ext( $dir=LS_CRAWLER_STATS_FOLDER_PATH, $extention='log' ){

	$files=scandir( LS_CRAWLER_STATS_FOLDER_PATH , SCANDIR_SORT_ASCENDING);

	$extention = '.'.$extention;

	foreach ( $files as $filepath)  {

		$filename = basename( $filepath );

		if ( is_int( strpos($filename, $extention) ) ) $result[]=$filepath;

	}

	return $result??false;
}

function convert_slashes( $string ){

    return str_replace('\\','/', $string);
}

function get_stats_files(){
    
    $files= get_files_by_ext();

    foreach ( $files as $filename ){
        
        if( is_int( strpos(basename($filename),'_') ) ) $result[]=$filename;
    
    }

    return $result??false;
}


function display_stats_file( $stats_file ) {
	
	$stats_data = get_stats_data_from_file( $stats_file );

	$overall_stats_of_file = get_overall_stats_of_file( $stats_data );

	display_overall_stats_of_file( $overall_stats_of_file, $stats_file );

}

function get_stats_data_from_file( $stats_filename ){
	
	$stats_array = file( LS_CRAWLER_STATS_FOLDER_PATH.$stats_filename );

	foreach ( $stats_array as $url_stats) {

		if ( empty( $url_stats ) ) continue;

		$stats = json_decode( $url_stats, true );

		if ( is_stats_line( $stats ) ) {
						
			$result['url'][] = $stats['url'];

			$result['desktop'][] = get_stats_desktop_version( $stats ); 

			if ( isset($stats['cache_mobile']) ) $result['mobile'][] = get_stats_mobile_version( $stats ); 
				
		} else {
	
			$metadata_type = array_key_first($stats);

			$result['metadata'][$metadata_type] = reset($stats);
			
		}

	}

	return $result;
}

function get_stats_desktop_version( $stats ){

	$desktop_keys = array('time','cache','curl_error','response');

	foreach ( $desktop_keys as $key ){

		if ( isset($stats[$key]) ) $result[$key] = $stats[$key];
	}

	return $result??null;

}
function get_stats_mobile_version( $stats ){

	$mobile_keys_for_conversion = array(
		'time_mobile' => 'time',
		'cache_mobile' => 'cache',
		'curl_error_mobile'=>'curl_error_mobile'
	);

	foreach ( $mobile_keys_for_conversion as $mobile_key => $key ){

		if ( isset($stats[$mobile_key]) ) $result[$key] = $stats[$mobile_key];
	}

	return $result??null;

}

function get_overall_stats_of_file( $stats_data ){

	$result['metadata'] = $stats_data['metadata']??null;

	$result['timing'] = get_stats_timing( $stats_data );
	
	$result['cache'] = get_cache_status_count( $stats_data );

	$result['cache-status-percentile'] = get_cache_status_percentage( $result['cache'] );

	$http_error_count = get_http_error_count( $stats_data );

	if( $http_error_count ) $result['errors'] = $http_error_count;

	return $result??null;

}

function get_http_error_count( $stats_data ){

	foreach( get_versions_array() as $version ){
		
		$http_error_count = get_version_http_error_count( ($stats_data[$version]??[]) );
		
		if ( $http_error_count ) $result[$version] = $http_error_count;

	}

	return $result??['desktop'=>0];
}

function  get_version_http_error_count( $stats ){

	return count(array_column($stats,'curl_error'));
}

function is_non_cachable_url( $stats ){

	if ( empty($stats) ) return true;

	if ( isset($stats['curl_error']) || isset($stats['curl_error_mobile']) ) return true;

	if ( ($stats['cache']??'') == 'no-cache' ) return true;

	return false;

}

function has_url_error( $stats ){

	return isset($stats['curl_error']) || isset($stats['curl_error_mobile']);
}

function get_only_cachable_url_data( $stats_data ){

	foreach ($stats_data as $stats ){

		if ( is_non_cachable_url( $stats ) ) continue;

		$result[]=$stats;
	}

	return $result??[];
}

function get_stats_timing( $stats_data ) {

	$result['desktop']=[
		'min'	=>	'-',
		'max'	=> '-',
		'avg'	=> '-'
	];

	foreach( get_versions_array() as $version ){

		if ( empty( $stats_data[$version] ) ) continue;
		
		$cachable_data = get_only_cachable_url_data($stats_data[$version]);

		if ( empty($cachable_data) ) continue;

		$time_array = array_column( $cachable_data, 'time' );
		
		// deb( $time_array, __FUNCTION__.' '.__LINE__);
		
		
		$result[$version]['min']=min($time_array)??'-';

		$result[$version]['max']=max($time_array)??'-';
		
		$avg_time  = (int) round(array_sum( $time_array )/count( $time_array ),0);
		
		if ( !empty( $avg_time)) $result[$version]['avg'] = $avg_time;
		
	}

// deb( $result, __FUNCTION__.' '.__LINE__);


	return $result;
}

function get_cache_status_percentage( $cache_data ){

	foreach( get_versions_array() as $version ){

		if ( empty($cache_data[$version]) ) continue;

		$every_cache_status_count = array_sum(array_values($cache_data[$version]));

		foreach( $cache_data[$version] as $cache_status => $status_count ){

			$result[$version][$cache_status] =  $status_count === 0 ? 0 : (int)(( $status_count/$every_cache_status_count )*100);
			
						
		}

	}

	return $result ?? ['desktop'=>0];
}

function get_cache_status_count( $stats_array ){

	foreach( get_versions_array() as $version ){

		if ( empty($stats_array[$version]) ) continue;
		
		foreach ($stats_array[$version] as $stats ){

			if ( has_url_error( $stats ) ) continue;

			$cache_status = ltrim($stats['cache'],'t-');
			
			if ( empty($cache_status) ) continue; 

			$cache_count[$version][$cache_status] = isset($cache_count[$version][$cache_status]) ?
			
						++$cache_count[$version][$cache_status] : (int) 1;
		}
	}
	
	return $cache_count??false;
}

function get_stats_min_value( $stats_time, $result_min ){

	return $stats_time < $result_min ?  $stats_time : $result_min;

}

function get_stats_max_value( $stats_time, $result_max ){

	return $stats_time > $result_max ?  $stats_time : $result_max;

}

function get_stats_avg_value($stats_time, $result_avg){

	if ( $result_avg === 0 ) return $stats_time;

	return (int) ( ( $result_avg + $stats_time )/2);

}

function is_stats_line($stats_array) {

	return !empty($stats_array['url']);
}

function get_versions_array(){

	return ['desktop','mobile'];
}

function get_default_overall_values( $has_mobile_data ){

    $result = array (
        'min' 		=> 99999,
        'max' 		=> 0,
        'avg'		=> 0,
		'no_cache'	=> 0,
		'curl_error'=> 0,
		'cache_count'=>[]
        );
    
	if  ( $has_mobile_data ) $result+= array (	
        
        'min_mobile' => 99999,
        'max_mobile' => 0,
        'avg_mobile' => 0,
		'curl_error'=> 0
        );

		return $result;

}

function get_display_data_attribute(){

    return 	array( 
        
					'min' 		=> 'time',
					'max' 		=> 'time',
					'avg' 		=> 'time-score',
					'hit'		=> '%',
					'no-cache'	=> '%'

	);

}

function display_overall_stats_of_file( $overall_stats_of_file, $stats_file ){
//zxc
	// define('IS_CRAWLER_MOBILE', isset($stats['timing']['mobile']) );
	
	the_stats_table_line( $overall_stats_of_file, $stats_file );

}

function get_time_date_from_stats_filename( $stats_file ){

	$datetime_array= explode('_', basename( $stats_file , '.log') );

	$date= $datetime_array[0];

	$time = str_replace('-',':',$datetime_array[1]);

	return $date.' at '.$time;
}

function get_value_markup( $value, $data_attribute ){

	if ( $value === false ) return '';

	if (is_array($value)) return 'array';
	
	if ( $value === '--' ) return '-';

	if ( $data_attribute == '%' ) return $value.' %';
	
	if ( $data_attribute == 'time' ) return get_time_value_with_time_unit($value);

	if ( $data_attribute == 'time-score' ) return get_time_unit_markup( $value );

	return $value;
}


function get_time_unit_markup( $value, $td=false ){

	if ($value === false ) return '';

	$value_score = get_value_score( $value ); 

	$value_class = 'value-'.str_replace(' ', '-', $value_score );

	$value_with_time_unit = get_time_value_with_time_unit($value);

	$value_score_markup = ' <span class="'.$value_class.'">'.$value_score.'</span>';

	if ( $td ) $value_score_markup = '<td>'.$value_score_markup.'</td>';

	return $value_with_time_unit.$value_score_markup;

}

function get_time_value_with_time_unit( $value ){
	
	if ($value === false ) return '';

	// if ( $value===0 ) return '>1 ms';

	if ($value < 1000 ) return $value.' ms';

	return round(($value/1000),2) .' s';

}

function get_value_score( $value ){

	if( $value > 3000 ) return 'very bad';

	if( $value > 1500 ) return 'bad';
	
	if( $value > 700 ) return 'quite bad';

	if( $value > 500 ) return 'OK';

	if( $value > 150 ) return 'good';

	if( $value > 80 ) return 'very good';

	return 'excelent';
}



function the_stats_table_line( $stats, $filename){
		
	?>
	<tr>
		<td class="stat-name">
			<a href="?page=ls_cache_crawler&tab=stats&file=<?=$filename?>">
				<?=get_time_date_from_stats_filename( $filename ).' '?>
			</a>
		</td>
		<?php 
		the_overall_inline_stats( $stats );
		?>
	</tr>
	<?php

}

function the_overall_inline_stats( $stats_array ){

	defined('IS_CRAWLER_MOBILE') || define('IS_CRAWLER_MOBILE', has_stats_mobile_data($stats_array) );

	$stats_to_display = get_stats_to_display($stats_array);

	$attributes = get_display_data_attribute();

	// deb($attributes,'attr');

	foreach( get_versions_array() as $version ){

		foreach ( $stats_to_display[$version] as $data_key => $value ){

			$data_attribute = $attributes[$data_key]??false;

			if ( $value === '--' && !IS_CRAWLER_MOBILE ) continue;
			?>
			<td class="stat-value">
				<?=get_value_markup($value, $data_attribute)?>
			</td>
			<?php
		}
	}

}

function get_stats_to_display($stats_array){

	$result['desktop']['min'] = $stats_array['timing']['desktop']['min']??0;

	$result['desktop']['max'] = $stats_array['timing']['desktop']['max']??0;

	$result['desktop']['avg'] = $stats_array['timing']['desktop']['avg']??0;
	
	$result['desktop']['hit'] = $stats_array['cache-status-percentile']['desktop']['hit']??0;

	$result['desktop']['no-cache'] = $stats_array['cache-status-percentile']['desktop']['no-cache']??'-';

	$result['desktop']['errors'] = $stats_array['errors']['desktop']??'-';

	$result['mobile']['avg'] = $stats_array['timing']['mobile']['avg']??'--';

	$result['mobile']['hit'] = $stats_array['cache-status-percentile']['mobile']['hit']??'--';

	return $result;

}

// function the_stats_line_loop( $stats_array ){

	
// }



// function body_stats_table_timing( $stats_array, $version ){

// 	$attributes=get_display_data_attribute();

// 	the_stats_line_loop($stats_array['timing'][$version])


// }

if (0){
	?>
	<td class="stat-value">
		<?=$timing['min']?>
	</td>
	<td class="stat-value">
		<?=$timing['max']?>
	</td>
	<td class="stat-value">
		<?=$timing['avg']?>
	</td>
	<td class="stat-value">
		<?=$cache?>
	</td>

	<?php
}


// $result='';

// $conversion_array = get_display_data_attribute();

// $excluded_data = ['min_mobile','max_mobile','avg_mobile'];

// // cache_hit_percentage_mobile is only displayed

// foreach ($stats_array as $key => $value){

// 	if (in_array($key,$excluded_data) || !isset($conversion_array[$key]['unit']) ) continue;
	
// 	$unit = $conversion_array[$key]['unit'];

// 	$result.= get_value_markup( $value, $unit ); 
// }

// return $result;



//qwe
function display_general_stats( $stats_files, $cache=true ){

	if ( empty($stats_files) ) display_no_stats_found();
	
	$overall_stats = get_all_stats_data_sumary( $stats_files,  $cache );

	define('IS_CRAWLER_MOBILE', has_stats_mobile_data($overall_stats) );

	?>
	<div class="stats-box">
		<table class="stats-table">
			<tbody>
				<tr>
					<?php the_stats_table_header();?>
				</tr>
				<?php

				foreach (array_reverse($overall_stats ) as $filename => $stats_of_file ){
						
					the_stats_table_line( $stats_of_file, $filename);

				}
				?>
			</tbody>
		</table>
	</div>
	<?php

}

function the_stats_table_header(){

	?>
	<td class="stat-name">Statistics from</td>
	<td class="stat-value">Min time</td>
	<td class="stat-value">Max time</td>
	<td class="stat-value">Avg time</td>
	<td class="stat-value">Cache hit</td>
	<td class="stat-value">No cache</td>
	<td class="stat-value">Errors</td>
	<?php
	
	if(!IS_CRAWLER_MOBILE) return;
	
	?>
	<td class="stat-value">Mobile avg</td>
	<td class="stat-value">Mobile cache hit</td>
	<?php
		
}

function display_no_stats_found(){

	echo 'No statistics been made';

}

function get_all_stats_data_sumary( $stats_files,  $cache ){

	$cached_stats = $cache ?  get_stat_data_from_cache() : [];

	// deb( count($cached_stats), 'cache count' );
	
	foreach ( $stats_files as $filename ){
		
		$stats[ $filename ] = $cached_stats[$filename] ?? get_file_stats_summary( $filename );

	}
	
	// deb( count($cached_stats), 'total stats count' );

	if ( count($cached_stats) !== count($stats) && $cache === true ) store_stats_summary_in_cache($stats);

	return $stats??[];
}

function get_stat_data_from_cache(){

	return  get_transient( 'ls_crawler_stats' ) ?: [];
}

function get_file_stats_summary( $filename ){

	return get_overall_stats_of_file( get_stats_data_from_file( $filename ) );
}

function store_stats_summary_in_cache( $stats ){
	
	//15778800 seconds is 6 months
	set_transient( 'ls_crawler_stats', $stats, 15778800 ); 
	
}

function has_file_to_display(){

	return !empty($_GET['file']);
}

function has_stats_mobile_data($overall_stats){

	return in_array( true, array_map( function($stats){ return isset($stats['timing']['mobile']);}, $overall_stats ) );
}

function get_filename_from_qs(){

	$filename = basename( str_replace('\\','/', $_GET['file'] ) );

	return file_exists( LS_CRAWLER_STATS_FOLDER_PATH.$filename ) ? $filename : false;
}

function the_detailed_file_stats(){

	$filename = get_filename_from_qs();

	$stats_summary = get_file_stats_summary( $filename );

	if ( !$stats_summary ) return the_file_is_corrupted_message( $filename );

	display_overall_file_stats($stats_summary);

	// continue here
	// display_urls_per_category( $filename );


}

function display_urls_per_category( $filename ){

	$category = get_stats_display_category_from_qs();

	foreach( file( LS_CRAWLER_STATS_FOLDER_PATH.$filename ) as $stats ){

		if ( empty( $url_stats ) ) continue;

		$stats = json_decode( $url_stats, true );

		if ( !isset( $stats['url'] ) ) continue;

		if ( is_in_current_category( $stats, $category ) ) $result[] =$result_for_category;
	}

	print_r($result??'EMPTY');
}

//continue here
function is_in_current_category( $stats, $category ){

	// if ( $category='error' &&   )

	return false;
}

function get_stats_display_category_from_qs(){

	return in_array( ($_GET['cat']??''), get_display_categories_names() ) ? $_GET['cat'] : 'none';
}

function get_display_categories_names(){

	return [
			'error',
			'very-bad',
			'very',
			'no-cache',
			'all'
		];
}

function display_overall_file_stats($stats_summary){
	
	
	// deb($stats_summary, __FUNCTION__.' '.__LINE__);
	?>
	<table class="stats-table">
		<tbody>
			
	<?php
		foreach( get_overall_data_display_array($stats_summary) as $display_line ){

			if( empty( $display_line[1] ) ) continue;

			echo '<tr><td class="stat-name">'.$display_line[0].'</td><td class="stat-value">'.$display_line[1].'</td></tr>';
		}
	?>
				
			</tbody>
	</table>
	<?php


}

function get_overall_data_display_array($stats_summary){

	return [

		0 => [	
				__('Total crawler run duration:','ls_crawler'),
				get_crawl_run_duration($stats_summary['metadata'])
			],

		1 => [
				__('Server load on the start','ls_crawler'),
				$stats_summary['metadata']['start_data']['server-load']??'-'
			],
		2 => [
				__('Server load on the end','ls_crawler'),
				$stats_summary['metadata']['finnal_data']['server-load']??'-'
			],
		3 => [
				__('Min time','ls_crawler'),
				get_display_stats_time($stats_summary['timing']['desktop']['min'])
				
			
			],
		4 => [
				__('Averge time','ls_crawler'),
				get_evaluated_display_stats_time($stats_summary['timing']['desktop']['avg']??false)
			
			],
		5 => [
				__('Max time','ls_crawler'),
				get_display_stats_time($stats_summary['timing']['desktop']['max'])
			
			],
		6 => [
				__('Min time Mobile','ls_crawler'),
				get_display_stats_time($stats_summary['timing']['mobile']['min']??false)
			
			],
		7 => [
				__('Averge time Mobile','ls_crawler'),
				get_evaluated_display_stats_time($stats_summary['timing']['mobile']['avg']??false)
			
			],
		8 => [
				__('Max time Mobile','ls_crawler'),
				get_display_stats_time($stats_summary['timing']['mobile']['max']??false)
			
			],
		9 => [
				__('Cache hit','ls_crawler'),
				get_value_markup( $stats_summary['cache-status-percentile']['desktop']['hit']??false , '%')
			],
		10 => [
				__('No-cache','ls_crawler'),
				get_value_markup( $stats_summary['cache-status-percentile']['desktop']['no-cache']??false , '%')
			],
		11 => [
				__('Cache hit Mobile','ls_crawler'),
				get_value_markup( $stats_summary['cache-status-percentile']['mobile']['hit']??false, '%')
			],
		12 => [
				__('No-cache','ls_crawler'),
				get_value_markup( $stats_summary['cache-status-percentile']['mobile']['no-cache']??false , '%')
			],
		13 => [
				__('Errors','ls_crawler'),
				$stats_summary['errors']['desktop']??false
			],
		14 => [
				__('Errors Mobile','ls_crawler'),
				$stats_summary['errors']['mobile']??false
			],
		15 => [
				__('Total URLs crawled','ls_crawler'),
				$stats_summary['metadata']['finnal_data']['total-links-count']??false
			],

	];

}

function get_display_stats_time($number){

	if($number === '-' ) return '-';

	return get_time_value_with_time_unit($number);
}

function get_evaluated_display_stats_time($number, $td=true ){

	if( $number === '-' ) return '-';

	return get_time_unit_markup($number, $td );
}


function get_crawl_run_duration($stats_summary){

	return gmdate("H\h i\m s\s", get_run_lenght($stats_summary) );

}

function get_run_lenght($stats_summary){
	
	return (int) $stats_summary['finnal_data']['end-time'] - $stats_summary['start_data']['start-time'];

}

// function get_details_stats_raw( $filename ){

// 	if ( !file_exists(LS_CRAWLER_STATS_FOLDER_PATH.$filename) ) return false;

// 	return get_file_stats_summary( $filename );
// 	// $stats_raw = explode("\n", file_get_contents( LS_CRAWLER_STATS_FOLDER_PATH.$filename ) );

// 	// return get_sorted_stat_array( $stats_raw );
// }

// function get_sorted_stat_array( $stats_raw ){

// 	foreach( $stats_raw as $stats){

// 		$stats_data = json_decode( $stats, true );

// 		if ( empty( $stats_data ) ) continue;

// 		if( is_stats_line($stats_data) ) {

// 			$stats_array['stats'][] = $stats_data;

// 		} else {
			
// 			$metadata_type = array_key_first($stats_data);

// 			$stats_array['metadata'][$metadata_type] = reset( $stats_data);
			
// 		}
		
// 	}
	
// 	return $stats_array??false;

// }

function the_file_is_corrupted_message($filename){

	?>
	<h4>
		<?=__('Error reading file, It may be  is corruped.','ls_crawler')?>
	</h4>
	<?php
}