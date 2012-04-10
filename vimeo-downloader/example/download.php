#!/opt/local/bin/php
<?php
/*
 * Usage:
 * php download.php 37119711 38247700 7674096
 */

$base = realpath(dirname(__FILE__) . '/..');
include $base . '/vimeo-downloader.php';

$downloader = new vimeoDownloader;

foreach($argv as $arg){
		
	if(is_numeric($arg)){
		$downloader->download($arg);
	}
}

?>
