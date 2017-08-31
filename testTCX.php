<?php
require 'src/phpTCXFileAnalysis.php';
require 'src/tools.php';
try {
	$phpTXC= new romael\phpTCXFileAnalysis("tcx_files/testmg.tcx", $options);
	echo $phpTXC->saveToTCX();
	
} catch (Exception $e) {
	echo 'caught exception: '.$e->getMessage();
	die();
}
?>