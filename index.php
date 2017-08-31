<?php
//require 'src/phpFITFileAnalysis.php';
require 'src/phpGPXFileAnalysis.php';
require 'src/phpTCXFileAnalysis.php';
require 'src/tools.php';
require 'libraries/PolylineEncoder.php'; // https://github.com/dyaaj/polyline-encoder
require 'libraries/Line_DouglasPeucker.php'; // https://github.com/gregallensworth/PHP-Geometry

?>

<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Romael Combine FIT-GPX 2 GPX</title>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css">
<link href="//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css" rel="stylesheet">
<link rel="stylesheet" href="css/romael.css">
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
<script type="text/javascript" src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/js/bootstrap.min.js"></script>
<!-- <script type="text/javascript" src="js/jquery.flot.min.js"></script>-->
<script type="text/javascript" src="js/tools.js"></script>
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/date.js"></script>
<script type="text/javascript" src="js/jquery.flot.js"></script>
<script type="text/javascript" src="js/jquery.flot.time.js"></script>
<script type="text/javascript" src="js/jquery.flot.crosshair.js"></script>
<script type="text/javascript" >
function vueGraphCombine(id, minGraph, maxGraph, unite, data1, data2) {
	graph(id+'CombineG', 'SALMON', false,  minGraph, maxGraph, unite ,data1, data2, 'BLUE');			
	getElement(id+'Combine').style.visibility='visible';
}
function vueGraphConvoluer(id, minGraph, maxGraph, unite, data1, data2) {
	graph(id+'ConvoluerG', 'BLUE', false,  minGraph, maxGraph, unite ,data1, data2, 'RED');			
	getElement(id+'Convoluer').style.visibility='visible';
}

function basculeGraph(id) {
	var mode = getElement(id+"file1TrData").style.display;
	if (mode == 'none') {
		getElement(id+"file1TrData").style.display='table-row';
		getElement(id+"file2TrData").style.display='table-row';
		getElement(id+"Span").innerHTML='&nbsp;-&nbsp;';
	}
	else {
		getElement(id+"file1TrData").style.display='none';
		getElement(id+"file2TrData").style.display='none';
		getElement(id+"Span").innerHTML='&nbsp;+&nbsp;';
	}
}

function basculeBandeau(id) {
	var mode = getElement(id).style.display;
	if (mode == 'none') {
		getElement(id).style.display='table-row';
		getElement(id+"Span").innerHTML='&nbsp;-&nbsp;';
	}
	else {
		getElement(id).style.display='none';
		getElement(id+"Span").innerHTML='&nbsp;+&nbsp;';
	}
}


</script>
</head>
<body>
<?php
$fitFile="";
$index="";


// debut du chrnometrage de la pahse d'initialisation

list($usec, $sec) = explode(" ", microtime());
$chrono =((float)$usec + (float)$sec);

print_r($_POST);
echo "start";
//print_r($_FILES);
if (isset($_GET['reset'])) {
	setcookie("ROMAELfile1", "", 0, "/");
	unset($_COOKIE['ROMAELfile1']);
	setcookie("ROMAELfile2", "", 0, "/");
	unset($_COOKIE['ROMAELfile2']);
	session_unset();
}
	
if (isset($_POST['resetF1'])) {
 	setcookie("ROMAELfile1", "", 0, "/"); 
 	unset($_COOKIE['ROMAELfile1']);
 	setcookie("ROMAELfile2", "", 0, "/");
 	unset($_COOKIE['ROMAELfile2']);
 	session_unset();
 }
 
session_start();
 

if (!isset($_SESSION['options'])) {
	$_SESSION['options']['file1']=array();
	$_SESSION['options']['file2']=array();
}

/* lecture du fichier a loader */
if (isset($_POST['submitF1'])) {
	$index="file1";
}
else if (isset($_POST['submitF2'])) {
	$index="file2";
}
$tabFile = array ("file1" => "","file2" => "");
$decal = array ("file1" => "","file2" => "");

// init message
$txtretour="";
$classMsg="ok";


if (isset($index)) {
	$extension = pathinfo($_FILES[$index]['name'], PATHINFO_EXTENSION);
	print_r($_FILES[$index]);
	if (upload($index, $_SERVER['DOCUMENT_ROOT']."/romaelGPs/".strtolower($extension)."_files/".$_FILES[$index]['name'])) {
		$txtretour="-Chargement OK de ".$_FILES[$index]['name']. " pour $index<br/>";
		$classMsg="ok";
		$tabFile[$index]=$_FILES[$index]['name'];
		
		// stockage en cookies
		setcookie("ROMAEL".$index, $tabFile[$index], time() + (86400 * 30), "/"); // 86400 = 1 day
		$_COOKIE['ROMAEL'.$index]=$tabFile[$index];
	}
	else {
//		print_r(error_get_last());
// 		$txtretour="- Echec de ".$_FILES[$index]['name']." [".error_get_last()."]<br/>";
		$txtretour="- Echec de ".$_FILES[$index]['name']."<br/>";
		$classMsg="err";
	}
}

$tabOptions = array('decal','rDebut','rFin','convoStat','convoNew','sansPause') ;
foreach ($tabFile as $key => $file) {
	$formFile="";
	foreach ($tabOptions as $keyOption ) {
		if (isset($_POST[$keyOption.'_'.$key])) {
			$formFile=$key;
			$_SESSION['options'][$key][$keyOption]=$_POST[$keyOption.'_'.$key];
		}
		else if ($keyOption == 'convoStat') {
			$_SESSION['options'][$key][$keyOption]=15;
		}
		else if ($keyOption == 'convoNew') {
			$_SESSION['options'][$key][$keyOption]=15;
		}
		// on est sur le formulaire du fichier
// 		else if ($keyOption == 'sansPause' && $formFile != "") {
// 			unset($_SESSION['options'][$key][$keyOption]);
// 		}
	}
}
/*echo "<br/>COOKIES: ";
print_r($_COOKIE);
echo "----------------<br/> ";
*/
if (isset($_COOKIE['ROMAELfile1'])) {
	$tabFile["file1"]=$_COOKIE['ROMAELfile1'];
}
if (isset($_COOKIE['ROMAELfile2'])) {
	$tabFile["file2"]=$_COOKIE['ROMAELfile2'];
}

// print_r($tabFile);
foreach ($tabFile as $key => $file) {
	if ($file) {
		//$file = '/fit_files/mountain-biking.fit';
		//$file = '/fit_files/road-cycling.fit';
		$extension = pathinfo($file, PATHINFO_EXTENSION);
		try {
			if ($extension == "fit") {
				$fileFit = '/fit_files/'.$file;
				$options = array();
				// Just using the defaults so no need to provide
				//		'fix_data'	=> [],
				//		'units'		=> 'metric',
				//		'decal'		=> $decal[$key]
//				];
				$pFFA[$key] = new phpFITFileAnalysis(__DIR__ . $fileFit, $_SESSION['options'][$key]);
				
				$dateStart[$key]=encodeTimeZone($pFFA[$key]->data_mesgs['session']['start_time'],
				reset($pFFA[$key]->data_mesgs['record']['position_lat']).",".reset($pFFA[$key]->data_mesgs['record']['position_long']));
				
			}
			if ($extension == "gpx") {
				$fileFit = '/gpx_files/'.$file;
				$options = array(
						// Just using the defaults so no need to provide
						//		'fix_data'	=> [],
						//		'units'		=> 'metric',
								'decal'		=> $decal[$key]
				);
				$pFFA[$key] = new phpGPXFileAnalysis(__DIR__ . $fileFit, $_SESSION['options'][$key]);
				
				$dateStart[$key]=encodeTimeZone($pFFA[$key]->data_mesgs['session']['start_time'],
				reset($pFFA[$key]->data_mesgs['record']['position_lat']).",".reset($pFFA[$key]->data_mesgs['record']['position_long']));
			}
			if ($extension == "tcx") {
				$fileFit = '/tcx_files/'.$file;
				$options =  array(
						// Just using the defaults so no need to provide
						//		'fix_data'	=> [],
						//		'units'		=> 'metric',
								'decal'		=> $decal[$key]
						
				);
				$pFFA[$key] = new phpTCXFileAnalysis(__DIR__ . $fileFit, $_SESSION['options'][$key]);
					
				//print_r($pFFA[$key]);
// 				print_r($pFFA[$key]->data_mesgs['session']);
				$dateStart[$key]=encodeTimeZone($pFFA[$key]->data_mesgs['session']['start_time'],
						reset($pFFA[$key]->data_mesgs['record']['position_lat']).",".reset($pFFA[$key]->data_mesgs['record']['position_long']));
				
			}
			$txtretour.="-Load ok de $file<br/>";
			$classMsg="ok";
		} catch (Exception $e) {
			$txtretour='caught exception: '.$e->getMessage()."<br/>";
			$classMsg="err";
		}
	}
}


//echo "Fin load";

// definition des couleurs de graph
$colorGraph= array("file1" => 'salmon', "file2" => "blue");
	
// definition des labels, max, min ..... par fichiers.
foreach($tabFile as $indexFile => $fileTmp) {
	
	if ($pFFA[$indexFile]->data_mesgs['session']) {
	$tabData[$indexFile] = array (
		"distance" =>
			array ( "label" => "Distance/Temps",
					"min" => 0,
					"max" => $pFFA[$indexFile]->data_mesgs['session']['total_distance'],
					"avg" => (int)($pFFA[$indexFile]->data_mesgs['session']['total_distance']) / 2,
					"count" => count($pFFA[$indexFile]->data_mesgs['record']['distance']),
					"ascent" => $pFFA[$indexFile]->data_mesgs['session']['total_distance_ascent'],
					"descent" => $pFFA[$indexFile]->data_mesgs['session']['total_distance_descent'],
					"unite" => "km"
		),
		"speed" =>
			array ( "label" => "Vitesse/Temps",
					"min" => 0,
					"max" => $pFFA[$indexFile]->data_mesgs['session']['max_speed'],
					"avg" => $pFFA[$indexFile]->data_mesgs['session']['avg_speed'],
					"ascent" => $pFFA[$indexFile]->data_mesgs['session']['speed_ascent'],
					"descent" => $pFFA[$indexFile]->data_mesgs['session']['speed_descent'],
					"count" => count($pFFA[$indexFile]->data_mesgs['record']['speed']),
					"unite" => "km/h"
				),
		"speed2" =>
			array ( "label" => "Vitesse Convolu\E9 ".$_SESSION['options'][$indexFile]['convoStat'],
					"min" => 0,
					"max" => $pFFA[$indexFile]->data_mesgs['session']['max_speed2'],
					"avg" => $pFFA[$indexFile]->data_mesgs['session']['avg_speed2'],
					"ascent" => $pFFA[$indexFile]->data_mesgs['session']['speed_ascent2'],
					"descent" => $pFFA[$indexFile]->data_mesgs['session']['speed_descent2'],
					"count" => count($pFFA[$indexFile]->data_mesgs['record']['speed2']),
					"unite" => "km/h"
			),
		"heart_rate" =>
			array ( "label" => "Frequence Cardiaque",
					"min" => min($pFFA[$indexFile]->data_mesgs['record']['heart_rate']),
					"max" => $pFFA[$indexFile]->data_mesgs['session']['max_heart_rate'],
					"avg" => $pFFA[$indexFile]->data_mesgs['session']['avg_heart_rate'],
					"count" => count($pFFA[$indexFile]->data_mesgs['record']['heart_rate']),
					"unite" => "bpm"
			),
		"altitude" =>
			array ( "label" => "Altitude/Temps",
					"min" => min($pFFA[$indexFile]->data_mesgs['record']['altitude']),
					"max" => max($pFFA[$indexFile]->data_mesgs['record']['altitude']),
					"avg" => array_sum($pFFA[$indexFile]->data_mesgs['record']['altitude']) / count($pFFA[$indexFile]->data_mesgs['record']['altitude']),
					"ascent" => $pFFA[$indexFile]->data_mesgs['session']['total_ascent'],
					"count" => count($pFFA[$indexFile]->data_mesgs['record']['altitude']) ,
					"unite" => "m"
			),
		"altitudeD" =>
			array ( "label" => "Altitude/Distance",
					"min" => min($pFFA[$indexFile]->data_mesgs['record']['altitudeD']),
					"max" => max($pFFA[$indexFile]->data_mesgs['record']['altitudeD']),
					"avg" => array_sum($pFFA[$indexFile]->data_mesgs['record']['altitudeD']) / count($pFFA[$indexFile]->data_mesgs['record']['altitudeD']),
					"ascent" => $pFFA[$indexFile]->data_mesgs['session']['total_ascentD'],
					"count" => count($pFFA[$indexFile]->data_mesgs['record']['altitudeD']) ,
					"unite" => "m"
										),
		"altitude2" =>
			array ( "label" => "Altitude Convoluer ".$_SESSION['options'][$indexFile]['convoStat'],
					"min" => min($pFFA[$indexFile]->data_mesgs['record']['altitude2']),
					"max" => max($pFFA[$indexFile]->data_mesgs['record']['altitude2']),
					"avg" => array_sum($pFFA[$indexFile]->data_mesgs['record']['altitude2']) / count($pFFA[$indexFile]->data_mesgs['record']['altitude2']),
					"ascent" => $pFFA[$indexFile]->data_mesgs['session']['total_ascent2'],
					"count" => count($pFFA[$indexFile]->data_mesgs['record']['altitude2']) ,
					"unite" => "m"
			),
		"cadence" =>
			array ( "label" => "Cadence",
					"min" => min($pFFA[$indexFile]->data_mesgs['record']['cadence']),
					"max" => $pFFA[$indexFile]->data_mesgs['session']['max_cadence'],
					"avg" => $pFFA[$indexFile]->data_mesgs['session']['avg_cadence'],
					"count" => count($pFFA[$indexFile]->data_mesgs['record']['cadence']),
					"unite" => "tr/min"
			),
		"temperature" =>
			array ( "label" => "Temperature",
					"min" => min($pFFA[$indexFile]->data_mesgs['record']['temperature']),
					"max" => max($pFFA[$indexFile]->data_mesgs['record']['temperature']),
					"avg" => array_sum($pFFA[$indexFile]->data_mesgs['record']['temperature']) / count($pFFA[$indexFile]->data_mesgs['record']['temperature']),
					"count" => count($pFFA[$indexFile]->data_mesgs['record']['temperature']),
					"unite" => "\B0C"
			),
		"power" =>
			array ( "label" => "Puissance",
					"min" => min($pFFA[$indexFile]->data_mesgs['record']['power']),
					"max" => max($pFFA[$indexFile]->data_mesgs['record']['power']),
					"avg" => array_sum($pFFA[$indexFile]->data_mesgs['record']['power']) / count($pFFA[$indexFile]->data_mesgs['record']['power']),
					"count" => count($pFFA[$indexFile]->data_mesgs['record']['power']),
					"unite" => "watt"
			)
	);
	}
}

if ($tabData['file1']) {
	foreach($tabData['file1'] as $indexData => $dataTmp) {
		if (!$_SESSION['tabSortie'][$indexData]) {
			$_SESSION['tabSortie'][$indexData]="on";
		}
		if (!$_SESSION['tabChoix'][$indexData]) {
			$_SESSION['tabChoix'][$indexData]="off";
		}
		else {
			if ($_SESSION['tabSortie'][$indexData]=="off") {
				unset($pFFA['file2']->data_mesgs['record'][$indexData]);
			}
		}
	}
}

if ($_POST['vue']) {
	if ($_POST['choix'.$_POST['vue']]=="on") {
		$_SESSION['tabChoix'][$_POST['vue']]="on";
	}
	else {
		$_SESSION['tabChoix'][$_POST['vue']]="off";
	}
	if ($_POST['sortie'.$_POST['vue']]=="on") {
		$_SESSION['tabSortie'][$_POST['vue']]="on";
	}
	else {
		$_SESSION['tabSortie'][$_POST['vue']]="off";
	}
}

// 
if (isset($pFFA['file1']) && isset($pFFA['file2'])) {
	foreach($tabData['file1'] as $indexData => $dataTmp) {
		if ($_SESSION['tabSortie'][$indexData]=="on") {
		// si pas de presence de file2 pour ces donn\E9es ou choix de 1
		if (($pFFA['file1']->data_mesgs['record'][$indexData] && !$pFFA['file2']->data_mesgs['record'][$indexData]) ||
			$pFFA['file1']->data_mesgs['record'][$indexData] && $_SESSION['tabChoix'][$indexData] == "on" )  {
			$txtretour.="-Cr&eacute;ation de ".$tabData['file2'][$indexData]['label']." pour le file2<br/>";
		
			$tabData['file2'][$indexData]=$tabData['file1'][$indexData];
			$tabData['file2'][$indexData]['label'].=" NEW ".$_SESSION['options']['file2']['convoNew'];
			$tabData['file2'][$indexData]['new']=1;
			$valSVG=0;
			foreach (array_keys($pFFA['file2']->data_mesgs['record']['distance']) as $keyTimestamps) {
			
// 			echo "$keyTimestamps (".(string)$pFFA['file1']->data_mesgs['record'][$indexData][$keyTimestamps].")";
				if ((string)$pFFA['file1']->data_mesgs['record'][$indexData][$keyTimestamps]) {
					$pFFA['file2']->data_mesgs['record'][$indexData][$keyTimestamps]=$pFFA['file1']->data_mesgs['record'][$indexData][$keyTimestamps];
					$valSVG=$pFFA['file1']->data_mesgs['record'][$indexData][$keyTimestamps];
// 				echo " -- [".$indexData."]ok avec 0 pour file2 ".gmdate("H:i:s",$keyTimestamps)." avec val file1 ".gmdate("H:i:s",$keyTimestamps)." = ".$valSVG."<br/>";
				}
				else {
					$timePlusBase=4;
					$valok=false;
					for ($timePlus=1; $timePlus<=$timePlusBase; $timePlus++){
					
// 					echo "search ".$keyTimestamps+$timePlus." sur file1 [".$pFFA['file1']->data_mesgs['record'][$indexData][$keyTimestamps+$timePlus]."]";
							
						if ((string)$pFFA['file1']->data_mesgs['record'][$indexData][$keyTimestamps-$timePlus] != "" ) {
							$pFFA['file2']->data_mesgs['record'][$indexData][$keyTimestamps]=$pFFA['file1']->data_mesgs['record'][$indexData][$keyTimestamps-$timePlus];
							$valSVG=$pFFA['file1']->data_mesgs['record'][$indexData][$keyTimestamps-$timePlus];
							$valok=true;
// 							echo " -- [".$indexData."]ok avec $timePlus pour file2 ".gmdate("H:i:s",$keyTimestamps)." avec val file1 ".gmdate("H:i:s",$keyTimestamps+$timePlus)." = ".$valSVG."<br/>";
							break;
						}
						else if ((string)$pFFA['file1']->data_mesgs['record'][$indexData][$keyTimestamps+$timePlus] != "" ) {
							$pFFA['file2']->data_mesgs['record'][$indexData][$keyTimestamps]=$pFFA['file1']->data_mesgs['record'][$indexData][$keyTimestamps+$timePlus];
							$valSVG=$pFFA['file1']->data_mesgs['record'][$indexData][$keyTimestamps+$timePlus];
							$valok=true;
							// 							echo " -- [".$indexData."]ok avec $timePlus pour file2 ".gmdate("H:i:s",$keyTimestamps)." avec val file1 ".gmdate("H:i:s",$keyTimestamps+$timePlus)." = ".$valSVG."<br/>";
							break;
						}
						
					}
					
					if (!$valok) {
						$pFFA['file2']->data_mesgs['record'][$indexData][$keyTimestamps]=$valSVG;
// 						echo " -- [".$indexData."]ok avec SVG pour file2 ".gmdate("H:i:s",$keyTimestamps)." avec val file1 ".gmdate("H:i:s",$keyTimestamps)." = ".$valSVG."<br/>";
					}	
				}
			}
	 		$pFFA['file2']->data_mesgs['record'][$indexData] = convoluer($_SESSION['options']['file2']['convoNew'], $pFFA['file2']->data_mesgs['record'][$indexData],true);
		//$pFFA['file2']->data_mesgs['record'][$indexData] = convoluer(15, $pFFA['file2']->data_mesgs['record'][$indexData]);
		
		
			$tabData['file2'][$indexData]["min"] = min($pFFA[$indexFile]->data_mesgs['record'][$indexData]);
			$tabData['file2'][$indexData]["max"] = max($pFFA[$indexFile]->data_mesgs['record'][$indexData]);
			$tabData['file2'][$indexData]["avg"] = array_sum($pFFA[$indexFile]->data_mesgs['record'][$indexData]) / count($pFFA[$indexFile]->data_mesgs['record'][$indexData]);
			$tabData['file2'][$indexData]["count"] = count($pFFA[$indexFile]->data_mesgs['record'][$indexData]);
		
//		$pFFA['file2']->data_mesgs['record'][$indexData]=$pFFA['file1']->data_mesgs['record'][$indexData];
		// construction de l'equivalent en file2
		// avec indexData+"New"
		}
		}
	}
}

//print_r($pFFA['file1']->data_mesgs['record']['position_longD']);
// print_r($pFFA['file2']->data_mesgs['record']['distance']);
if (isset($pFFA['file1']) && isset($pFFA['file2'])) {
	// si pas de presence de file2 pour ces donn\E9es ou choix de 1
	if (($pFFA['file1']->data_mesgs['record']['position_latD'] && !$pFFA['file2']->data_mesgs['record']['position_latD'])) {
		$txtretour.="-Cr&eacute;ation d'une trace pour le file2<br/>";
		$newCart=false;
		foreach ($pFFA['file2']->data_mesgs['record']['distance'] as $keyTimestamps => $valDist) {
				
			$valDist*=1000;
//  			echo "-$keyTimestamps (".($valDist).")";
			if ((string)$pFFA['file1']->data_mesgs['record']['position_latD'][$valDist]) {
				$pFFA['file2']->data_mesgs['record']['position_lat'][$keyTimestamps]=$pFFA['file1']->data_mesgs['record']['position_latD'][$valDist];
				$pFFA['file2']->data_mesgs['record']['position_long'][$keyTimestamps]=$pFFA['file1']->data_mesgs['record']['position_longD'][$valDist];
//  				echo "new coor Direct avec + $valDist  => ".$pFFA['file1']->data_mesgs['record']['position_longD'][$valDist]." => ".$pFFA['file2']->data_mesgs['record']['position_long'][$keyTimestamps];
				$newCart=true;
			}
			else {
				$timePlusBase=100;
				$valok=false;
				for ($timePlus=0; $timePlus<=$timePlusBase; $timePlus+=1){

					//echo "search ".$keyTimestamps+$timePlus." sur file1 [".$pFFA['file1']->data_mesgs['record']['position_latD'][$valDist+$timePlus]."]";

					if ((string)$pFFA['file1']->data_mesgs['record']['position_latD'][$valDist-$timePlus] != "" ) {
						$pFFA['file2']->data_mesgs['record']['position_lat'][$keyTimestamps]=$pFFA['file1']->data_mesgs['record']['position_latD'][$valDist-$timePlus];
						$pFFA['file2']->data_mesgs['record']['position_long'][$keyTimestamps]=$pFFA['file1']->data_mesgs['record']['position_longD'][$valDist-$timePlus];
						$valok=true;
//  						echo "new coor moins  avec - ".($valDist-$timePlus)."  => ".$pFFA['file1']->data_mesgs['record']['position_longD'][$valDist-$timePlus]." => ".$pFFA['file2']->data_mesgs['record']['position_long'][$keyTimestamps];
						// 							echo " -- [".$indexData."]ok avec $timePlus pour file2 ".gmdate("H:i:s",$keyTimestamps)." avec val file1 ".gmdate("H:i:s",$keyTimestamps+$timePlus)." = ".$valSVG."<br/>";
						break;
					}
					else if ((string)$pFFA['file1']->data_mesgs['record']['position_latD'][$valDist+$timePlus] != "" ) {
						$pFFA['file2']->data_mesgs['record']['position_lat'][$keyTimestamps]=$pFFA['file1']->data_mesgs['record']['position_latD'][$valDist+$timePlus];
						$pFFA['file2']->data_mesgs['record']['position_long'][$keyTimestamps]=$pFFA['file1']->data_mesgs['record']['position_longD'][$valDist+$timePlus];
						$valok=true;
//  						echo "new coor plsu avec + ".($valDist+$timePlus)." => ".$pFFA['file2']->data_mesgs['record']['position_long'][$keyTimestamps];
						break;
					}
				}
				if ($valok) {
					$newCart=true;
				}
				else {
					//echo "Pas trouv\E9 pour une distance de $valDist";
					$pFFA['file2']->data_mesgs['record']['position_lat'][$keyTimestamps]=$latOld;
					$pFFA['file2']->data_mesgs['record']['position_long'][$keyTimestamps]=$longOld;
				}
				$latOld=$pFFA['file2']->data_mesgs['record']['position_lat'][$keyTimestamps];
				$longOld=$pFFA['file2']->data_mesgs['record']['position_long'][$keyTimestamps];
			}
// 			echo "<br/>";
		}
		
 		$pointNew=sizeof($pFFA['file2']->data_mesgs['record']['position_long']);
 		$pointbase=sizeof($pFFA['file1']->data_mesgs['record']['position_long']);
 		$nbpointVoulu=sizeof($pFFA['file2']->data_mesgs['record']['distance']);
 		$txtretour.="- Nb points de la nvlle trace  : $pointNew [ ".($pointNew-$nbpointVoulu)." ] / $pointbase<br/>";
		
	}
}

// si demande sauvegarde
if ($_POST["SaveTCX"]) {?>
	<script>
		alert('<?=$pFFA[$key]->saveToTCX()?>');
	  window.open('<?=$pFFA[$key]->saveToTCX()?>',
                  '',
                  '');
      </script> 
<?php }
//   echo "<pre>";
//   print_r($_SESSION);
//   echo "</pre>";
//  	echo "<pre>";
	//print_r($pFFA['file1']->data_mesgs['session']);
// 	print_r($pFFA['file1']);
// 	print_r($pFFA['file2']->data_mesgs['record']['altitudeD']);
//  	echo "</pre>";
	
$chronoFin=microtime();
list($usec, $sec) = explode(" ", microtime());
$chronoFin=((float)$usec + (float)$sec);

$txtretour.="<b>-- Calcul en ".number_format(($chronoFin-$chrono),4)." sec --</b><br/>";

?>
<header >
<table border=0  width=99% id="bandeau">
	<tr>
		<td width=40% valign=top>
			<table border=0 width=100%>
				<tr>
					<td width=100%> 
			    		<div class="panel-default">
      						<div class="panel-heading">
        						<h3 class="panel-title"><i class="fa fa-file-code-o"> Fichier 1</i></h3>
			    			</div>
      						<div class="panel-body text-center">
	       					<?php if (!$tabFile['file1']) {?>
      							<form method="post" action="index.php" enctype="multipart/form-data">
      							<input type="hidden" name="MAX_FILE_SIZE" value="2048576" />
      								<table>
      									<tr>
      										<td>Recherche fichier 1 : </td>
      										<td><input type="file" name="file1" /></td>
       										<td><input type="submit" name="submitF1" value="Load" /></td>
       									</tr>
       								</table>
    	   						</form>
	       					<?php } else {?>
      								<h3 class="panel-title" ><?=$tabFile["file1"]?></h3>
	       					<?php }?>
	       					</div>
		    			</div>
					</td>
				</tr>
			</table>
		</td>
		<td width=20% align="center" style="background-color: GREY">
			<form method="post" action="index.php" >
	    		<input type="submit" value="Reset" name="resetF1" >
	    		<span  style="background-color:WHITE; cursor:pointer;" onclick="basculeBandeau('BandeauData')" id="BandeauSpan">&nbsp;-&nbsp;</span>
	       	</form>
		</td>
		<td width=40% valign=top>
			<?php if (isset($pFFA['file1'])) { ?>
			<table border=0 width=100% class="bandeau">
				<tr>
					<td width=100%> 
						<div class="panel-default">
      						<div class="panel-heading">
       							<form method="post" action="index.php" >
        						<h3 class="panel-title"><i class="fa fa-file-code-o"> Fichier 2</i>
       						<?php if (isset($pFFA['file2'])) { ?>
      								<span style="text-align: right;">
      									<input type="hidden" value="Go" name="SaveTCX" />
      									<input type="button" value="Save as TCX"  onclick="this.form.submit()" />
      								</span>
       						<?php }?>
      							</h3>
      							</form>
      						</div>
						    <div class="panel-body text-center">
	       					<?php if (!$tabFile['file2']) {?>
      							<form method="post" action="index.php" enctype="multipart/form-data">
      								<input type="hidden" name="MAX_FILE_SIZE" value="2048576" />
      								<table border=0>
      									<tr>
      										<td>Recherche fichier 2 : </td>
											<td><input type="file" name="file2" /> </td>
       										<td><input type="submit" name="submitF2" value="Load" /> </td>
       									</tr>
       								</table>
    	   						</form>
	       					<?php } else {?>
	       						<h3 class="panel-title" ><?=$tabFile['file2']?></h3>
	       					<?php }?>
    						</div>
						</div>
					</td>
				</tr>
			</table>
			<?php } else {?>
			<?php }?>
		</td>
	</tr>
	<tr id="BandeauData">
		<?php foreach($tabFile as $indexFile => $fileTmp) {?>
		<td valign=top>
				<?php if (isset($pFFA[$indexFile])) {?>
				<form method="post" action="index.php">
  				<table width=100% border=0 cellspacing=10 >
  					<tr>
  						<td class="titRow">D&eacute;calage (sec) : </td>
  						<td class="dataRow">&nbsp;
  							<input type='text' name=decal_<?=$indexFile?> value='<?=$_SESSION['options'][$indexFile]['decal']?>' size=3 maxlength=10 onchange='this.form.submit()'>
  						</td>
  						<td class="titRow">Tps pause : </td>
  						<td class="dataRow">&nbsp;
  							<input type='text' name=sansPause_<?=$indexFile?> 
  							value='<?=$_SESSION['options'][$indexFile]['sansPause']?>' onchange='this.form.submit()' size=2 maxlength=3 />
  							
  						</td>
        			</tr>
  					<tr>
  						<td class="titRow">Racourcir d&eacute;but (sec) : </td>
  						<td class="dataRow">&nbsp;
  							<input type='text' name=rDebut_<?=$indexFile?> value='<?=$_SESSION['options'][$indexFile]['rDebut']?>' size=3 maxlength=5 onchange='this.form.submit()'>
  						</td>
  						<td class="titRow">Tps d'apercu (sec) : </td>
  						<td class="dataRow">&nbsp;
  							<input type='text' name=rFin_<?=$indexFile?> value='<?=$_SESSION['options'][$indexFile]['rFin']?>' size=3 maxlength=5 onchange='this.form.submit()'>
  						</td>
  					</tr>
  					<tr>
  						<td class="titRow">Convolution stat : </td>
  						<td class="dataRow">&nbsp;
  							<select name="convoStat_<?=$indexFile?>" onchange='this.form.submit()'>
  								<?php foreach ($TAB_CONVO as $val) {?>
  									<option <?php if ($val==$_SESSION['options'][$indexFile]['convoStat']) { echo 'selected'; }?>
  									><?=$val?></option>
  								<?php }?>
        					</select>
						</td>        						
  						<td class="titRow">Convolution new : </td>
  						<td class="dataRow">&nbsp;
  							<select name="convoNew_<?=$indexFile?>" onchange='this.form.submit()'>
  								<?php foreach ($TAB_CONVO as $val) {?>
  									<option <?php if ($val==$_SESSION['options'][$indexFile]['convoNew']) { echo 'selected'; }?>
  									><?=$val?></option>
  								<?php }?>
        					</select>
        				</td>	
        			</tr>
  					<tr>
  						<td class="titRow">Appareil : </td>
  						<td class="dataRow">&nbsp;
  							<?php echo $pFFA[$indexFile]->manufacturer() . ' ' . $pFFA[$indexFile]->product(); ?>
  						</td>
  						<td class="titRow">Sport : </td>
  						<td class="dataRow">&nbsp;
  							<?=$pFFA[$indexFile]->sport(); ?>
     					</td>
    				</tr>
    				<tr >
  						<td class="titRow">Enregistr&eacute; : </td>
  						<td class="dataRow">&nbsp;
        					<?=$dateStart[$indexFile]->format('d/m/Y @ H:i');?>
        				</td>
        				<td class="titRow">Dur&eacute;e : </td>
  						<td class="dataRow">&nbsp;
  						<?//$pFFA[$indexFile]->data_mesgs['session']['total_elapsed_time']/sizeof($pFFA[$indexFile]->data_mesgs['record']['distance'])?>
  							<?php echo gmdate('H:i:s', $pFFA[$indexFile]->data_mesgs['session']['total_elapsed_time']); ?>
  						</td>
  					</tr>
  					<tr>
        				<td class="titRow">Dur&eacute;e + : </td>
  						<td class="dataRow">&nbsp;
  							<?php echo gmdate('H:i:s', $pFFA[$indexFile]->data_mesgs['session']['time_ascent']); ?>
        					[<?php echo gmdate('H:i:s', $pFFA[$indexFile]->data_mesgs['session']['time_ascent2']); ?>]
        				</td>
        				<td class="titRow">Dur&eacute;e - : </td>
  						<td class="dataRow">&nbsp;
  							<?php echo gmdate('H:i:s', $pFFA[$indexFile]->data_mesgs['session']['time_descent']); ?>
        					[<?php echo gmdate('H:i:s', $pFFA[$indexFile]->data_mesgs['session']['time_descent2']); ?>]
        				</td>
        			</tr>
        			<tr>
        				<td class="titRow">Distance : </td>
  						<td class="dataRow">&nbsp;
  							<?php echo number_format($pFFA[$indexFile]->data_mesgs['session']['total_distance'],3); ?> km
      					</td>
    				</tr>
    			</table>
    			</form>
  				<?php }?>
  		</td>
  		<?php if ($indexFile == "file1") {?>
  		<td style="background-color: GREY">
  			<div class="panel panel-default <?=$classMsg?>">
				<?=$txtretour?>
			</div>
  		</td>
		<?php } ?>
  		<?php }?>
  	</tr>
  	
  	<tr>
  		<?php foreach($tabFile as $indexFile => $fileTmp) {?>
  		
  		<?php if ($indexFile == "file2") {?>
  		<td align="center" style="background-color: GREY">
  			<?php if (isset($pFFA["file2"])) {
  				$delta=$pFFA['file1']->data_mesgs['session']['start_time'] - $pFFA['file2']->data_mesgs['session']['start_time'];
  				if (abs($delta) > (3600*24)) { 
  					echo "<div class='error'>Trace avec un ecart de plus de ".abs((int)($delta/(3600*24)))." jours</div>";
  				}
  				else {
  					echo "Delta = ".$delta." sec";
  				}
  			}?>
  		</td>
		<?php } ?>
		<td valign=top align="center">
				<?php if (isset($pFFA[$indexFile])) {?>
  					d&eacute;but &agrave; <?=date("H:i:s",$pFFA[$indexFile]->data_mesgs['session']['start_time']) ?>
  					<!-- (<?$pFFA[$indexFile]->data_mesgs['session']['start_time'] ?>)-->
    				fin &agrave; <?=date("H:i:s",$pFFA[$indexFile]->data_mesgs['session']['end_time']) ?>
    				<!-- (<?=$pFFA[$indexFile]->data_mesgs['session']['end_time'] ?>)-->
	  			<?php }?>
  		</td>
  		<?php }?>
  	</tr>
  	</table>
  </header>
  
  <br/><br/>
  	<table width=99% border=0>
  	<tr>
  	<!--  for sur les donn\E9es a afficher-->
	<!-- distancen speed, heart_race, altitude, cadence, temperature -->
	<?php
	foreach($tabFile as $indexFile => $fileTmp) {
		if ($indexFile == "file1") {?>
		<td width=54% valign=top>
		<?php } else {?>
		<td width=46% valign=top>
		<?php }?>
		
		<table width=100% border=0 style="cellpadding:0; cellspacing:0">
		<?php if ($tabData[$indexFile]) {?>
		<?php foreach($tabData[$indexFile] as $indexData => $dataStat) { ?>
			<tr >
				<td >
					<?php if ($indexData == 'altitude2' || $indexData == 'speed2') {?>
			 		<div style="position: absolute;width:99%; height: 500px; align:center; z-index: 100; visibility: hidden; background-color: LIGHTGREEN" 
						class="panel panel-default panel-heading" id="<?=$indexData?>Convoluer">
						<h2 class="panel-title">Vue des graphs <?=$dataStat['label']?> & normal </h2>
			 		<input type=button value="Fermer" onclick="getElement('<?=$indexData?>Convoluer').style.visibility='hidden';" />
			 		<div id="<?=$indexData?>ConvoluerG" style="width:100%; height:400px; margin-bottom:8px"></div>
			 		<?php }?>	
					</div> 
					
					<div class="panel panel-default">
						<div class="panel-heading">
							<h2 class="panel-title">
								<?=$dataStat['label']?>
							</h2>
							<?php if ($indexData == 'altitude2' || $indexData == 'speed2') {?>
  							<?php if ($indexData == 'altitude2' ) {$dataG2 = 'altitude';}?>
  							<?php if ($indexData == 'speed2' ) { $dataG2 = 'speed';}?>
  							<input type="button" value="Vue normal/convolue" 
  								onclick="vueGraphConvoluer('<?=$indexData?>',<?=$minGraph?>, <?=$maxGraph?>,' <?=$dataStat['unite']?>' ,
													[<?php echo makeSerieJS($pFFA[$indexFile]->data_mesgs['record'][$dataG2])?>],
													[<?php echo makeSerieJS($pFFA[$indexFile]->data_mesgs['record'][$indexData])?>])"
							/>
							<?php }?>
						</div>
					</div>
				</td>
				<?php if ($indexFile == "file1") {?>
  					<td  align="center" style="background-color: GREY">
						<span  style="background-color:WHITE; cursor:pointer;" onclick="basculeGraph('<?=$indexData?>')" id="<?=$indexData?>Span">&nbsp;-&nbsp;</span>
	  					Choix
	  					<br/>
  					</td>
  				<?php }?>
			</tr>
			<tr id="<?=$indexData.$indexFile?>TrData">
				<?php if ($indexFile == "file1") {?>
 				<td width=85% valign=top height=200px>
				<div style="position: absolute;width:99%; height: 500px; align:center; z-index: 100; visibility: hidden;background-color: LIGHTGREY" 
						class="panel panel-default panel-heading" id="<?=$indexData?>Combine">
					<h2 class="panel-title">Vue comparaison file1-file2  de <?=$dataStat['label']?> </h2>
					<input type=button value="Fermer" onclick="getElement('<?=$indexData?>Combine').style.visibility='hidden';" />
			 		<div id="<?=$indexData?>CombineG" style="width:100%; height:400px; margin-bottom:8px"></div>
			 		
				</div> 
				<?php } else {?>
				<td width=100% valign=top height=200px>
				<?php }?>
  				<?php if (isset($pFFA[$indexFile]->data_mesgs['record'][$indexData])
  						&& sizeof($pFFA[$indexFile]->data_mesgs['record'][$indexData]) > 0) {
  					
  					if ($dataStat['min'] < 0) {
  						$minGraph=(double)$dataStat['min']*1.2;
  					} else {
  						$minGraph=(double)$dataStat['min']*0.9;
  					}
  			
  					$maxGraph=$dataStat['max']*(1.1);
//   					echo "Graph : $minGraph - $maxGraph";
//   					echo "Graph : ".$dataStat['min']." - ".$dataStat['max'];
  					?>
  					<table width=100% class=" panel panel-default ">
  						<tr>
  							<td width=25%>Nb Point : <?=number_format($dataStat['count'],0)?></td>
  							<td width=25%>Min : <?=number_format($dataStat['min'],2)?></td>
  							<td width=25%>Max : <?=number_format($dataStat['max'],2)?></td>
  							<td width=25%>Moyenne : <?=number_format($dataStat['avg'],2)?></td>
  						</tr>
  						<?php if ($indexData == 'altitude' || $indexData == 'altitude2' || $indexData == 'altitudeD') {?>
  						<tr>
  							<td colspan=2">Ascension : <?=number_format($dataStat['ascent'],2)?></td>
  						</tr>
  						<?php }?>
  						<?php if ($indexData == 'distance' || $indexData == 'speed' || $indexData == 'speed2') {?>
  						<tr>
  							<td colspan=2">Ascension : <?=number_format($dataStat['ascent'],2)?></td>
  							<td colspan=2">Descente : <?=number_format($dataStat['descent'],2)?></td>
  						</tr>
  						<?php }?>
  					</table>
		  			<div class="row">
    					<div class="col-md-6">
      						<dl class="dl-horizontal">
      							<?php //if ($_SESSION['tabSortie'][$indexData]=="on" || $indexFile =='file1') {?>
      							<div id="<?=$indexData.$indexFile?>" style="width:800px; height:100px; margin-bottom:8px"></div>
      							<?php if ($dataStat['new']) {$colorG= 'GREEN'; } else { $colorG=$colorGraph[$indexFile];}?>
    								<script type="text/javascript">
										graph('<?=$indexData.$indexFile?>', '<?=$colorG?>', true,  <?=$minGraph?>, <?=$maxGraph?>,' <?=$dataStat['unite']?>' ,
													[<?php echo makeSerieJS($pFFA[$indexFile]->data_mesgs['record'][$indexData])?>]);
									</script>
  	  							<?php //} ?> 
      						</dl>
    					</div>
  					</div>
		  		<?php }?> 
		  		&nbsp;
  				</td>
  				<?php if ($indexFile == "file1") {?>
  					<td width=15% align="center" style="background-color: GREY">
  					<form action="index.php" method="POST">
  						<input type='hidden' value="<?=$indexData?>" name="vue" />
  					<?php if ($indexData == "speed2" || $indexData == "altitude2" || $indexData == 'altitudeD' ) {
  							if ($pFFA['file1']->data_mesgs['record'][$indexData] &&
  								$pFFA['file2']->data_mesgs['record'][$indexData])  {?>
  								<input type="button" value="Vue combine" 
  									onclick="vueGraphCombine('<?=$indexData?>',<?=$minGraph?>, <?=$maxGraph?>,' <?=$dataStat['unite']?>' ,
  										[<?php echo makeSerieJS($pFFA['file1']->data_mesgs['record'][$indexData])?>],
  										[<?php echo makeSerieJS($pFFA['file2']->data_mesgs['record'][$indexData])?>])"
  								/>
  							<?php }
  					}
  					else { if ($pFFA['file1']->data_mesgs['record'][$indexData] &&
  							   $pFFA['file2']->data_mesgs['record'][$indexData])  {?>
  						Prendre file1 <input type="checkBox" name="choix<?=$indexData?>" onclick='this.form.submit()'
  								<?php if ($_SESSION['tabChoix'][$indexData]=="on" || $tabData['file2'][$indexData]['new']) { echo checked ; } ?> />
  						<br/>
  						Pr&eacute;sent en sortie <input type="checkBox" name="sortie<?=$indexData?>" onclick="this.form.submit()"
  	  							<?php if ($_SESSION['tabSortie'][$indexData]=="on") { echo checked ; } ?> />
  	  							
  	  							
  	  							
  	  							
  						<br/>
  						<input type="button" value="Vue combine" 
  							onclick="vueGraphCombine('<?=$indexData?>',<?=$minGraph?>, <?=$maxGraph?>,' <?=$dataStat['unite']?>' ,
													[<?php echo makeSerieJS($pFFA['file1']->data_mesgs['record'][$indexData])?>],
													[<?php echo makeSerieJS($pFFA['file2']->data_mesgs['record'][$indexData])?>])"
						/>
  						
  					<?php } else if ($pFFA['file1']->data_mesgs['record'][$indexData] ) { ?>
  						Pr&eacute;sent en sortie <input type="checkBox" name="sortie<?=$indexData?>" onclick="this.form.submit()"
  	  							<?php if ($_SESSION['tabSortie'][$indexData]=="on") { echo checked ; } ?> />
  	  							
  					<?php } else if ($pFFA['file2']->data_mesgs['record'][$indexData] ) { ?>
  						Pr&eacute;sent en sortie <input type="checkBox" name="sortie<?=$indexData?>" onclick="this.form.submit()"
  	  							<?php if ($_SESSION['tabSortie'][$indexData]=="on") { echo checked ; } ?> />
  	  							
  					<?php } else  { ?>
  					<?php }
  					}?>
  					
  					
  					</form>
  					</td>
  				<?php }?>
  			</tr>
			<?php } // fin if data?>
			<?php } // fin foreach des data?>
  		</table>
  		</td>
		<?php } // fin foreach des files?>
		
		
		<!-- a la fin du chargement on regarde si n doit montrer les graphs en fonction de file1-->
		<?php
		if ($tabData[$indexFile]) {
		foreach($tabData[$indexFile] as $indexData => $dataStat) { ?>
			<?php if (!isset($pFFA["file1"]->data_mesgs['record'][$indexData])
  						|| sizeof($pFFA["file1"]->data_mesgs['record'][$indexData]) == 0) {?>
  					<script type="text/javascript">basculeGraph('<?=$indexData?>');</script>
  			<?php }?>
  		<?php }?>
  		<?php }?>
		
	</tr>
  	<tr>
  		<?php	
  		$colorMap = array("file1" => 'red', "file2" => "blue");
  		
  		if ($newCart) {
  			$colorMap['file2']="green";
  		}
  		
  		foreach($tabFile as $indexFile => $fileTmp) {
  			if (sizeof($pFFA[$indexFile]->data_mesgs['record']['position_lat']) < 10) {
  				$tabMapInfo[$indexFile] = makeMap($pFFA[$indexFile]->data_mesgs['record']['position_latD'],$pFFA[$indexFile]->data_mesgs['record']['position_longD'],$colorMap[$indexFile]);
  			}
  			else {
  				$tabMapInfo[$indexFile] = makeMap($pFFA[$indexFile]->data_mesgs['record']['position_lat'],$pFFA[$indexFile]->data_mesgs['record']['position_long'],$colorMap[$indexFile]);
  			}
  		}
  		foreach($tabFile as $indexFile => $fileTmp) { ?>
  		<td>
  			<table width=100% border=0 style="cellpadding:0; cellspacing:0">
  				<tr>
					<?php if ($indexFile == "file1") {?>
					<td width=85% valign=top height=200px>
					<?php if ($tabMapInfo["file1"] && $tabMapInfo["file2"])  {?>
  						<div style="position: absolute;width:99%; height: 900px; align:center; z-index: 100; visibility: hidden;" 
							class="panel panel-default panel-heading" id="mapCombine">
							
				 			<input type=button value="Fermer" onclick="getElement('mapCombine').style.visibility='hidden';" />
				 			<center>
				 			<img src="https://maps.googleapis.com/maps/api/staticmap?size=640x640&scale=2&key=AIzaSyDlPWKTvmHsZ-X6PGsBPAvo0nm1-WdwuYE<?php echo $tabMapInfo['file1']['map']; ?><?php echo $tabMapInfo['file2']['map']; ?>" alt="Google map" border="0">
				 			</center>
						</div>
					<?php }?>
					<?php } else {?>
					<td width=100% valign=top height=200px>
					<?php }?>
    	   				<div class="panel-body">
            				<div id="gmap" style="padding-bottom:20px; text-align:center;">
           	  					<strong>Google Geocoding API: </strong><?php echo $tabMapInfo[$indexFile]['location']; ?><br>
              					<img src="https://maps.googleapis.com/maps/api/staticmap?size=640x640&key=AIzaSyDlPWKTvmHsZ-X6PGsBPAvo0nm1-WdwuYE<?php echo $tabMapInfo[$indexFile]['map']; ?>" alt="Google map" border="0">
            				</div>
          				</div>
	   				</td>
    				<?php if ($indexFile == "file1") {?>
  					<td width=15% align="center" style="background-color: GREY">
						<?php if ($tabMapInfo[$indexFile] && $tabMapInfo[$indexFile])  {?>
							<input type="button" value="Vue combine" 
  								onclick="getElement('mapCombine').style.visibility='visible';"/>
						<?php }?>
    				</td>
  					<?php }?>
  				</tr>
  			</table>
  		</td>
    	<?php }?>
    </tr>
</table>
</body>
</html>

<?php session_write_close();?>
