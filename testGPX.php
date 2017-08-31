<?php
require __DIR__ . '/src/phpGPXFileAnalysis.php';
require __DIR__ . '/src/phpTCXFileAnalysis.php';
require __DIR__ . '/src/tools.php';
require __DIR__ . '/libraries/PolylineEncoder.php'; // https://github.com/dyaaj/polyline-encoder
require __DIR__ . '/libraries/Line_DouglasPeucker.php'; // https://github.com/gregallensworth/PHP-Geometry

try {
	$pFFAGpx =new romael\phpGPXFileAnalysis("test_file/10min.gpx", $options);
// 	echo $pFFAGpx->saveToGPX();
	echo $pFFAGpx->saveToTCX();
	//	$pFFATcx =new romael\phpTCXFileAnalysis("test_file/10min.tcx", array ('decal'=>'440'));
} catch (Exception $e) {
	echo 'caught exception: '.$e->getMessage();
	die();
}
/*
$xmlGPX="<gpx creator='RomaeLGPs' version='1.1' xmlns='http://www.topografix.com/GPX/1/1' xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' xsi:schemaLocation='http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd http://www.garmin.com/xmlschemas/GpxExtensions/v3 http://www.garmin.com/xmlschemas/GpxExtensionsv3.xsd http://www.garmin.com/xmlschemas/TrackPointExtension/v1 http://www.garmin.com/xmlschemas/TrackPointExtensionv1.xsd http://www.garmin.com/xmlschemas/GpxExtensions/v3 http://www.garmin.com/xmlschemas/GpxExtensionsv3.xsd http://www.garmin.com/xmlschemas/TrackPointExtension/v1 http://www.garmin.com/xmlschemas/TrackPointExtensionv1.xsd http://www.garmin.com/xmlschemas/GpxExtensions/v3 http://www.garmin.com/xmlschemas/GpxExtensionsv3.xsd http://www.garmin.com/xmlschemas/TrackPointExtension/v1 http://www.garmin.com/xmlschemas/TrackPointExtensionv1.xsd'>";
$xmlGPX.="<metadata></metadata>";
$xmlGPX.="<trk>";
$xmlGPX.="<trkseg></trkseg>";	
$xmlGPX.="</trk>";
$xmlGPX.="</gpx>";

echo "<pre>";
$monXml = new SimpleXMLElement($xmlGPX);

$monXml->metadata->addChild("time","2017-01-26T11:20:05Z");

$monXml->trk->addChild('name',"Mon nom");

$trkptXml=$monXml->trk->trkseg->addChild("trkpt");
$trkptXml->addAttribute("lat","47.3035280");
$trkptXml->addAttribute("lon","-2.3929640");
$trkptXml->addChild("ele",26.5);
$trkptXml->addChild("time","2017-01-26T11:20:05Z");

$trkptXml=$monXml->trk->trkseg->addChild("trkpt");
$trkptXml->addAttribute("lat","47.3035280");
$trkptXml->addAttribute("lon","-2.3929640");
$trkptXml->addChild("ele",26.5);
$trkptXml->addChild("time","2017-01-26T11:20:05Z");


print_r($monXml);

/*<trkpt lat="47.3035280" lon="-2.3929640">
<ele>26.5</ele>
<time>2017-01-26T11:20:05Z</time>
</trkpt>*/
echo "---------------------------------------";
//print_r($monXml->asXMl());
//file_put_contents ( "/tmp/testMG.gpx",$monXml->asXMl()); 
echo "<pre>";
?>
<!-- <html>
<head>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
<script type="text/javascript" src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/js/bootstrap.min.js"></script>
<script type="text/javascript" src="js/tools.js"></script>
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/date.js"></script>
<script type="text/javascript" src="js/jquery.flot.js"></script>
<script type="text/javascript" src="js/jquery.flot.time.js"></script>
<script type="text/javascript" src="js/jquery.flot.crosshair.js"></script>

</head>
<body>
<pre>
<?php
echo "TCX : ".sizeof($pFFATcx->data_mesgs['record']['heart_rate'])."<br/>";
echo "GPX distance: ".sizeof($pFFAGpx->data_mesgs['record']['distance'])."<br/>";
    $valSVG=0;
    foreach (array_keys($pFFAGpx->data_mesgs['record']['distance']) as $keyTimestamps) {
    	if ($pFFATcx->data_mesgs['record']['heart_rate'][$keyTimestamps]) {
    		
    		$pFFAGpx->data_mesgs['record']['heart_rate'][$keyTimestamps]=$pFFATcx->data_mesgs['record']['heart_rate'][$keyTimestamps]+10;
    		
    		$valSVG=$pFFATcx->data_mesgs['record']['heart_rate'][$keyTimestamps]+10;
    	}
    	else {
    		$pFFAGpx->data_mesgs['record']['heart_rate'][$keyTimestamps]=$valSVG;
    		//$pFFAGpx->data_mesgs['record']['heart_rate'][$keyTimestamps]=0;
    	}
    }
    $pFFAGpx->data_mesgs['record']['heart_rate'] = convoluer(15, $pFFAGpx->data_mesgs['record']['heart_rate']);
    $pFFAGpx->data_mesgs['record']['heart_rate'] = convoluer(15, $pFFAGpx->data_mesgs['record']['heart_rate']);
    //print_r($pFFAGpx->data_mesgs['record']['heart_rate']);
    echo "GPX heart: ".sizeof($pFFAGpx->data_mesgs['record']['heart_rate'])."<br/>";
  ?>
</pre>



<div id='testMG' style="width:100%; height:50%; margin-bottom:8px"></div>
<script>	
graph('testMG', 'SALMON', false,  80, 160, ' bpm',[<?=makeSerieJS($pFFATcx->data_mesgs['record']['heart_rate'])?>],
		[<?=makeSerieJS($pFFAGpx->data_mesgs['record']['heart_rate'])?>], 'BLUE');			
</script>
</body>
</html>-->