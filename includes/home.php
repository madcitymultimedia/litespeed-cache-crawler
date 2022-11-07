<?php

defined( 'ABSPATH' ) || exit;



?>	 
<div class="data-container">
<?php
  
  php_timeout_warning();

  the_start_stop_button();

  if ( !isset( $_GET['stop'] ) ) the_latest_stats();

?>
</div>
<?php


function the_latest_stats(){

	?>
	<hr>
	<h3><?=__('Latest crawl statistics','ls_carwler')?></h3>
	<?php

	$latest_crawl_stats_file = get_latest_crawl_stats_file();

	if ( empty($latest_crawl_stats_file) ) {

		echo 'Crawler haven\'t ran yet';

	} else {

		require_once LS_CRAWLER_PLUGIN_PATH.'/includes/statistics-functions.php';
		
		display_general_stats( (array) $latest_crawl_stats_file, $cache=false );

	}
	
}
