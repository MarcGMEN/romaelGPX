<?php

$TAB_CONVO= array (3,7,9,11,13,15);

function upload($index,$destination,$maxsize=FALSE,$extensions=FALSE)
{
	//Test1: fichier correctement uploadé
	if (!isset($_FILES[$index]) OR $_FILES[$index]['error'] > 0) return FALSE;
	//Test2: taille limite
	if ($maxsize !== FALSE AND $_FILES[$index]['size'] > $maxsize) return FALSE;
	//Test3: extension
	$ext = substr(strrchr($_FILES[$index]['name'],'.'),1);
	if ($extensions !== FALSE AND !in_array($ext,$extensions)) return FALSE;
	//Déplacement
	
	#DEBUG echo "copie de ".$_FILES[$index]['tmp_name']." vers $destination";
	return move_uploaded_file($_FILES[$index]['tmp_name'],$destination);
}

function encodeTimeZone($dateFile, $latitude=0) {
	// Google Time Zone API
	$date = new DateTime('now', new DateTimeZone('UTC'));
	$date_s = $dateFile;
	$url_tz = 'https://maps.googleapis.com/maps/api/timezone/json?location='.$latitude.'&timestamp='.$date_s.'&key=AIzaSyDlPWKTvmHsZ-X6PGsBPAvo0nm1-WdwuYE';
	$result = file_get_contents($url_tz);
	$json_tz = json_decode($result);
	
	if ($json_tz->status == 'OK') {
		$date_s = $date_s + $json_tz->rawOffset + $json_tz->dstOffset;
	} else {
		$json_tz->timeZoneName = 'Error';
	}
	
	$date->setTimestamp($date_s);
	
	return $date;
}

function makeMap($position_lat, $position_long, $colorTrace) {
	$location = 'Unknow';
	$map_string="";
	
	$lat_long_combined = array();
	if ($position_lat) {
		foreach ($position_lat as $key => $value) {  // Assumes every lat has a corresponding long
			if ($position_lat[$key] != 0) {
				$lat_long_combined[] = array($position_lat[$key],$position_long[$key]);
			}
		}
	}
	if (sizeof($lat_long_combined) > 0) {
		$delta = 0.0001;
		do {
			$RDP_LatLng_coord = simplify_RDP($lat_long_combined, $delta);  // Simplify the array of coordinates using the Ramer-Douglas-Peucker algorithm.
			$delta += 0.0001;  // Rough accuracy somewhere between 4m and 12m depending where in the World coordinates are, source http://en.wikipedia.org/wiki/Decimal_degrees
		
			$polylineEncoder = new PolylineEncoder();  // Create an encoded string to pass as the path variable for the Google Static Maps API
			foreach ($RDP_LatLng_coord as $RDP) {
				$polylineEncoder->addPoint($RDP[0], $RDP[1]);
			}
			$map_encoded_polyline = $polylineEncoder->encodedString();
		
			$map_string = '&path=color:'.$colorTrace.'%7Cenc:'.$map_encoded_polyline;
		}
		while (strlen($map_string) > 1800);  // Google Map web service URL limit is 2048 characters. 1800 is arbitrary attempt to stay under 2048
	
		
		$LatLng_start = implode(',', $lat_long_combined[0]);
		$LatLng_finish = implode(',', $lat_long_combined[count($lat_long_combined)-1]);
	
		$map_string .= '&markers=color:red%7Clabel:F%7C'.$LatLng_finish.'&markers=color:green%7Clabel:S%7C'.$LatLng_start;
	
		// Google Geocoding API
		$url_coord = 'https://maps.googleapis.com/maps/api/geocode/json?latlng='.$LatLng_start.'&key=AIzaSyDlPWKTvmHsZ-X6PGsBPAvo0nm1-WdwuYE';
		$result = file_get_contents($url_coord);
		$json_coord = json_decode($result);
		if ($json_coord->status == 'OK') {
			foreach ($json_coord->results[0]->address_components as $addressPart) {
				if ((in_array('locality', $addressPart->types)) && (in_array('political', $addressPart->types))) {
					$city = $addressPart->long_name;
				} elseif ((in_array('administrative_area_level_1', $addressPart->types)) && (in_array('political', $addressPart->types))) {
					$state = $addressPart->short_name;
				} elseif ((in_array('country', $addressPart->types)) && (in_array('political', $addressPart->types))) {
					$country = $addressPart->long_name;
				}
			}
			$location = $city.', '.$state.', '.$country;
		}
	}
	return array( "map" => $map_string, "location" => $location);
}


function makeSerieJS($tab) {
	unset($tmp);
	$tmp = array();
	foreach ($tab as $key => $value) {
		$tmp[] = '['.$key.', '.$value.']';
	}
	return implode(', ', $tmp);
}

function calculDistance($lat1, $lon1, $lat2, $lon2, $precis=false) {
	$rayonTerre = 6366;
	
	$lat1 = deg2rad($lat1);
	$lon1 = deg2rad($lon1);
	$lat2 = deg2rad($lat2);
	$lon2 = deg2rad($lon2);
	
	//echo "CD [$lat1-$lon1 -> $lat2-$lon2]";

	if ($lat1 == $lat2 && $lon1 == $lon2) {
		$dp=0;
	}
	else {
		if ($precis) {
		/**
	 	* Formule précise
		* d=2*asin(sqrt((sin((lat1-lat2)/2))^2 + cos(lat1)*cos(lat2)*(sin((lon1-lon2)/2))^2) )
	 	*/
			$dp= 2 * asin(
				sqrt(pow( sin(($lat1-$lat2)/2) , 2) + cos($lat1)*cos($lat2)* pow( sin(($lon1-$lon2)/2) , 2)	) );
		}
		else {
		/**
	 	* Formule simple
	 	* d=acos(sin(lat1)*sin(lat2)+cos(lat1)*cos(lat2)*cos(lon1-lon2))
	 	*/
			$dp= acos(sin($lat1)*sin($lat2)+cos($lat1)*cos($lat2)*cos($lon1-$lon2));
		}
	}
	
	$distanceKm = $dp * $rayonTerre;
	return $distanceKm;
}

function convoluer($Nbr_Pond,$tabInVrai, $withZero=false) {

	foreach ($tabInVrai as $key => $val) {
		$tabIn[]=$val;
		$tabKey[]=$key;
	}
	$tabOut  = $tabIn;
	
	$ponderation = array (
			5 =>array ( -2 => -3, -1 => 12 , 0 => 17 , 1 => 12, 2  => -3),
			7 => array ( -3 => -2, -2 => 3, -1 => 6 , 0 => 7 , 1 => 6, 2  => 3, 3 => -2),
			9 => array ( -4 => -21 ,-3 => 14, -2 => 39, -1 => 54 , 0 => 59 , 1 => 54, 2  => 39, 3 => 14, 4 => -21),
			11 => array ( -5=> -36 , -4 => 9 ,-3 => 44, -2 => 69, -1 => 84 , 0 => 89 , 1 => 84, 2  => 69, 3 => 44, 4 => 9, 5=> -36),
			13 => array ( -6=>-11 , -5=> 0 , -4 => 9 ,-3 => 16, -2 => 21, -1 => 24 , 0 => 25 , 1 => 24, 2  => 21, 3 => 16, 4 => 9, 5=> 0, 6=> -11),
	15 => array ( -7 => -78, -6=>-13 , -5=> 42 , -4 => 87 ,-3 => 122, -2 => 147, -1 => 162 , 0 => 167 , 1 => 162, 2  => 147, 3 => 122, 4 => 87, 5=> 42, 6=> -13, 7=>-78)
	);
	
	$Lim_Sup = (int)(sizeof($tabIn) - ($Nbr_Pond - 1) / 2);
	$Lim_Inf = (int)($Nbr_Pond / 2);

//	echo "lim inf $Lim_Inf ; Lim Sup $Lim_Sup ;<br/>";

	// avant la limInf
	For ($index=0; $index < $Lim_Inf; $index++) {
		$Som = 0;
		$norme=0;
		For ($J = 0; $J < sizeof($ponderation[$Nbr_Pond])-$Lim_Inf ; $J++) {
			$Som += $tabIn[$index + $J] * $ponderation[$Nbr_Pond][$J-$index];
// 			echo "premier tabIn[".($index+$J)."] * ponderation[".$Nbr_Pond."][".($J-$index)."]";
			 $norme+=$ponderation[$Nbr_Pond][$J];
		}
		$tabOut[$index] = $tabIn[$index];
//		$tabOut[$index] = $Som / $norme;
 //		echo "som[".$index."] (".$tabIn[$index]."): $Som => ".$tabOut[$index]." <br/>";
 		
	}

	// milieu
	For ($index=$Lim_Inf; $index <= $Lim_Sup; $index++) {
   		$Som = 0;
   		$norme=0;
   		if ($withZero && $tabIn[$index]<1) {
   			$tabOut[$index] = $tabIn[$index];
   		}
   		else {
			For ($J = -$Lim_Inf; $J < sizeof($ponderation[$Nbr_Pond])-$Lim_Inf ; $J++) {
		      	$Som += $tabIn[$index + $J] * $ponderation[$Nbr_Pond][$J];
	      		$norme+=$ponderation[$Nbr_Pond][$J];
			}
			$tabOut[$index] = $Som / $norme;
   		}
	}

	// fin de tableau
	For ($index=$Lim_Sup+1; $index < sizeof($tabIn); $index++) {
		$Som = 0;
		$norme=0;
		For ($J = -$Lim_Inf; $J < sizeof($tabIn)-$index ; $J++) {
			$Som += $tabIn[$index + $J] * $ponderation[$Nbr_Pond][$J];
// 			echo "premier tabIn[".($index+$J)."] * ponderation[".$Nbr_Pond."][".($J)."]";
			$norme+=$ponderation[$Nbr_Pond][$J];
		}
		//$tabOut[$index] = $Som / $norme;
		$tabOut[$index] = $tabIn[$index];
		// 		echo "som[".$index."] (".$tabIn[$index]."): $Som => ".$tabOut[$index]." <br/>";
	}
	
	foreach ($tabOut as $key => $val) {
		$tabOutVrai[$tabKey[$key]]=$val;
	}
	
	return $tabOutVrai;
}