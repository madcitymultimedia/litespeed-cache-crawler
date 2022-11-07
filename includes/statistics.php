<?php

defined('ABSPATH') || die;

require_once LS_CRAWLER_PLUGIN_PATH.'/includes/statistics-functions.php';

display_statistics_tab();

function display_statistics_tab(){

	if ( has_file_to_display() ) {
		
		display_file_stats();

	} else {

		display_overall_stats_tab();
		
	}
}

function display_overall_stats_tab(){

	?><h3>
		<?=__('Statistics','ls_crawler')?>
	</h3><?php

	$stats_files = get_stats_files();

	display_general_stats( $stats_files );
	
}

function display_file_stats(){

	$filename = get_filename_from_qs();

	if ( $filename === false ) {

		the_file_is_corrupted_message($filename);

	} else {

		?><h3>
		<?=__('Statistics from','ls_crawler')?><?php echo ' '.get_time_date_from_stats_filename( $filename );?>
		</h3><?php

		the_detailed_file_stats();
	}

}