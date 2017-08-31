<?php
//namespace romael;

if (!defined('DEFINITION_MESSAGE')) {
	define('DEFINITION_MESSAGE', 1);
}
if (!defined('DATA_MESSAGE')) {
	define('DATA_MESSAGE', 0);
}

/*
 * This is the number of seconds difference between GPX and Unix timestamps.
 * GPX timestamps are seconds since UTC 00:00:00 Dec 31 1989 (source GPX SDK)
 * Unix time is the number of seconds since UTC 00:00:00 Jan 01 1970
 */
if (!defined('GPX_UNIX_TS_DIFF')) {
	define('GPX_UNIX_TS_DIFF', 631065600);
}

class phpFileAnalysis
{
	public $data_mesgs = array();  // Used to store the data read from the file in associative arrays.
	
	public $dataCumul = array();
	
	private $pointOld=null;
	
	private $retraitPause = 0;
	
	private $timeVirtual;
	
	protected $file_contents;
	
	// PHP Constructor - called when an object of the class is instantiated.
	public function __construct($file_path, $options = null)
	{
		if (empty($file_path)) {
			throw new Exception('phpGPXFileAnalysis->__construct(): file_path is empty!');
		}
		if (!file_exists($file_path)) {
			throw new Exception('phpGPXFileAnalysis->__construct(): file \''.$file_path.'\' does not exist!');
		}
		$this->options = $options;
		if (isset($options['garmin_timestamps']) && $options['garmin_timestamps'] == true) {
			$this->garmin_timestamps = true;
		}
		
		$this->timeVirtual=time();
		if (!function_exists('simplexml_load_file')) {
			echo "simpleXML functions are available.<br />\n";
		}
		
		$this->file_contents = simplexml_load_file($file_path);
		if ($this->file_contents === false) {
			echo "ERROR DE load du fichier $file_path";
		}
		else {
		// 	Process the file contents.
			$this->readHeader();
			$this->readDataRecords();
		}

// 		         echo "<pre>";
// 		         print_r($this->data_mesgs);
// 		         echo "</pre>";
		         
		         
	}
	
	protected function readheader() {
	}
	
	public function makeFile() {
		
	}
	
	protected function readDataRecords()
	{
	
		// un record, une data, timestamp et valeur
		 
		$this->data_mesgs['session']['event']=(string)$this->file_contents->trk->name;
		 
		$this->dataCumul['distancePlat']=0;
		$this->dataCumul['distanceAscent']=0;
		$this->dataCumul['distanceDescent']=0;
		$this->dataCumul['distance']=0;
		$this->dataCumul['timePlat']=0;
		$this->dataCumul['timeAscent']=0;
		$this->dataCumul['timeDescent']=0;
		$this->dataCumul['timeFirst']=0;
		$this->dataCumul['timeLast']=0;
		
		$this->dataCumul['elePlus']=0;
		$this->dataCumul['eleMoins']=0;
		$this->dataCumul['totalTimeSec']=0;
		$this->dataCumul['totalTimeDelta']=0;
		
		
		$noTime=false;
	
		$this->data_mesgs['record']['altitude']=array();
		$this->data_mesgs['record']['altitudeD']=array();
		$this->data_mesgs['record']['altitude2']=array();
		$this->data_mesgs['record']['position_lat']=array();
		$this->data_mesgs['record']['position_long']=array();
		$this->data_mesgs['record']['position_latD']=array();
		$this->data_mesgs['record']['position_longD']=array();
		$this->data_mesgs['record']['speed']=array();
		$this->data_mesgs['record']['speedD']=array();
		$this->data_mesgs['record']['speed2']=array();
		$this->data_mesgs['record']['distance']=array();
		$this->data_mesgs['record']['distance1']=array();
		$this->data_mesgs['record']['heart_rate']=array();
		$this->data_mesgs['record']['cadence']=array();
		$this->data_mesgs['record']['temperature']=array();
		$this->data_mesgs['record']['time']=array();
		
		$this->data_mesgs['record']['delta_distance']=array();
		$this->data_mesgs['record']['delta_time']=array();
		
	}
	
	function addData($data) {
		$ldist=0;
		$noTime=false;
		
		if (!$this->options['timeDebut']) {
			// on recupere la premiere date
			$this->options['timeDebut']=strtotime($data['Time']);
		}
			
		// on ignore le temps du debut
		if (isset($this->options['rDebut']) ) {
			$this->options['timeDebut']+=$this->options['rDebut'];
			$this->options['rDebut']=0;
		}
		
		
		// temps de vue
		if ($this->options['rFin'] > 0) {
			$this->options['timeFin']=$this->options['timeDebut']+$this->options['rFin'];
		}else { 
			$this->options['timeFin']=strtotime($data['Time']);
		}
		
		
		//echo strtotime($data['Time'])." >= ".$this->options['timeDebut']."<br/>";
	//echo strtotime($data['Time'])." <= ".$this->options['timeFin']."<br/>";
		
		if (strtotime($data['Time']) >= $this->options['timeDebut'] && 
				strtotime($data['Time']) <= $this->options['timeFin']) {
			//echo "OK pour affichage";
		// si pas de temps on part de 0 unix (01/01/1970) avec plus 10sec part point
		if (!$data['Time']) {
			$noTime=true;
			$data['Time']=date('Y-m-d H:i:s',$this->timeVirtual);
			$this->timeVirtual+=10;

		}
		if ($this->options['decal']) {
			$data['Time']=date('Y-m-d H:i:s',strtotime($data['Time'])+$this->options['decal']);
		}
		
		
		if ($this->pointOld) {
			// calcul de la distance km
			if ($data['Dist']) {
				$this->dataCumul['distance']=$data['Dist'];
    			$ldist=$data['Dist']-$this->pointOld['Dist'];
    		}
    		else {
    			$ldist=(double)calculDistance((double)$this->pointOld['Lat'],(double)$this->pointOld['Long'],(double)$data['Lat'],(double)$data['Long']);
    			$this->dataCumul['distance']+=(double)$ldist;
//     			echo  $ldist." + ".$this->dataCumul['distance']." -- ";
			}
			
			// ecart de temps en secondes
			$deltaTime=strtotime($data['Time'])-strtotime($this->pointOld['Time']);
			// si pause  > 10sec et retrait pause, on retire la durée pour le time
			if ($this->options['sansPause'] > 0 && $deltaTime >= $this->options['sansPause']) {
					//echo "pause de : ".$deltaTime."  ";
					$this->retraitPause+=$deltaTime+1;
					$deltaTime=1;
			}
			$timeMili=strtotime($data['Time'])-$this->retraitPause;
			//echo "dataTime : ".$timeMili."  ";
			//$data['Time']=gmdate("Y-m-d\TH:i:s\Z",$timeMili);

			//echo "dataTime : ".$data['Time']." => ".strtotime($data['Time']);
					
			$this->data_mesgs['record']['delta_distance']+=array($timeMili => $ldist);
			$this->data_mesgs['record']['distance']+=array($timeMili => $this->dataCumul['distance']);
				
			if (!$noTime) {
				
				// en heure =
				$deltaTimeH=(double)$deltaTime/3600;
				//echo $data['Time']." - ".$this->pointOld['Time']." = ".$deltaTime."[".$deltaTimeH."]<br/>";
				if ($ldist > 0.005) {
					$this->dataCumul['totalTimeSec']+=$deltaTime;
				}
				// vitesse = km/temps
				$vitesse=(double)$ldist/$deltaTimeH;
				//echo ($ldist*1000)."/".$deltaTimeH." = ".number_format($vitesse,2)."<br/>";
				$this->data_mesgs['record']['speed']+=array($timeMili => $vitesse);
				$this->data_mesgs['record']['speedD']+=array((double)$this->dataCumul['distance']*1000 => $vitesse);
				$this->data_mesgs['record']['delta_time']+=array($timeMili => $deltaTime);
				
			}
			else {
				$this->data_mesgs['record']['speed']+=array($timeMili => 0);
				$this->data_mesgs['record']['speedD']+=array((double)$this->dataCumul['distance']*1000 => 0);
				$this->data_mesgs['record']['delta_time']+=array($timeMili => $deltaTime);
			}

		}
		else {
			$timeMili=strtotime($data['Time']);
			$this->data_mesgs['record']['delta_distance']=array($timeMili=> 0);
			$this->data_mesgs['record']['distance']=array($timeMili=> 0);
			if (!$noTime) {
				$this->data_mesgs['record']['speed']=array($timeMili=> 0);
				$this->data_mesgs['record']['speedD']=array(0=> 0);
				$this->data_mesgs['record']['delta_time']+=array($timeMili => 0);
			}
	
			$this->dataCumul['timeFirst']=$timeMili;
		}
		
		if (abs($data['Ele']) > 7000 ) {
			$data['Ele']=$pointOld['Ele'];
		}
		if ($data['Heart']) {
			$this->data_mesgs['record']['heart_rate']+=array($timeMili=> (double)$data['Heart']);
		}
		if ($data['Cadence']) {
			$this->data_mesgs['record']['cadence']+=array($timeMili=> (int)$data['Cadence']);
		}
		$this->data_mesgs['record']['altitude']+=array($timeMili=> (double)$data['Ele']);
		$this->data_mesgs['record']['altitudeD']+=array((double)$this->dataCumul['distance']*1000=> (double)$data['Ele']);
		
		$this->data_mesgs['record']['time']+=array($timeMili=> 1);
		
		if ($data['Lat'] != 0) {
			$this->data_mesgs['record']['position_lat']+=array($timeMili=> (double)$data['Lat']);
			$this->data_mesgs['record']['position_long']+=array($timeMili=> (double)$data['Long']);

			$this->data_mesgs['record']['position_latD']+=array((double)$this->dataCumul['distance']*1000=> (double)$data['Lat']);
			$this->data_mesgs['record']['position_longD']+=array((double)$this->dataCumul['distance']*1000=> (double)$data['Long']);
		}
		
		$this->pointOld=$data;
		$this->dataCumul['timeLast']=$timeMili;
		}
	}
	
	function makeSession() {
		
		$totalTimeDelta=$this->dataCumul['timeLast']-$this->dataCumul['timeFirst'];
		 
		//echo "sum delta = ".$totalTimeSec. "; delta first-last= ".$totalTimeDelta."<br/>";
		//echo $distanceAscent."+ ; ".$distancePlat." 0 ; ".$distanceDescent." -<br/>";
		$this->data_mesgs['session']['start_time']=$this->dataCumul['timeFirst'];
		$this->data_mesgs['session']['end_time']=$this->dataCumul['timeLast'];
		$this->data_mesgs['session']['total_distance']=$this->dataCumul['distance'];
		$this->data_mesgs['session']['total_elapsed_time']=$this->dataCumul['totalTimeSec'];
		
		$dataCumulEle = $this->makeDataElevation($this->data_mesgs['record']['altitude']);
		$this->data_mesgs['session']['time_ascent']=$dataCumulEle['timeAscent'];
		$this->data_mesgs['session']['time_descent']=$dataCumulEle['timeDescent'];
		$this->data_mesgs['session']['total_distance_ascent']=$dataCumulEle['distanceAscent'];
		$this->data_mesgs['session']['total_distance_descent']=$dataCumulEle['distanceDescent'];
		$this->data_mesgs['session']['total_ascent']=$dataCumulEle['elePlus'];
		$this->data_mesgs['session']['total_descent']=$dataCumulEle['eleMoins'];
		

		// elevation avec une convoluation
		$this->data_mesgs['record']['altitude2'] = convoluer($this->options['convoStat'],$this->data_mesgs['record']['altitude'],true);
		$dataCumulEle = $this->makeDataElevation($this->data_mesgs['record']['altitude2']);
		$this->data_mesgs['session']['time_ascent2']=$dataCumulEle['timeAscent'];
		$this->data_mesgs['session']['time_descent2']=$dataCumulEle['timeDescent'];
		$this->data_mesgs['session']['total_distance_ascent2']=$dataCumulEle['distanceAscent'];
		$this->data_mesgs['session']['total_distance_descent2']=$dataCumulEle['distanceDescent'];
		$this->data_mesgs['session']['total_ascent2']=$dataCumulEle['elePlus'];
		$this->data_mesgs['session']['total_descent2']=$dataCumulEle['eleMoins'];

		$this->data_mesgs['record']['altitudeD'] = convoluer($this->options['convoStat'],$this->data_mesgs['record']['altitudeD'],true);
		$dataCumulEleD = $this->makeDataElevationD($this->data_mesgs['record']['altitudeD']);
		$this->data_mesgs['session']['total_distance_ascentD']=$dataCumulEleD['distanceAscent'];
		$this->data_mesgs['session']['total_distance_descentD']=$dataCumulEleD['distanceDescent'];
		$this->data_mesgs['session']['total_ascentD']=$dataCumulEleD['elePlus'];
		$this->data_mesgs['session']['total_descentD']=$dataCumulEleD['eleMoins'];
		
		
		$this->data_mesgs['session']['avg_speed']=array_sum($this->data_mesgs['record']['speed']) / count($this->data_mesgs['record']['speed']);
		$this->data_mesgs['session']['max_speed']=max($this->data_mesgs['record']['speed']);
		 
		$this->data_mesgs['session']['speed_ascent']=(double)$this->data_mesgs['session']['total_distance_ascent']/($this->data_mesgs['session']['time_ascent']/3600);
		$this->data_mesgs['session']['speed_descent']=(double)$this->data_mesgs['session']['total_distance_descent']/($this->data_mesgs['session']['time_descent']/3600);
		
		// speed avec une convoluation
		$this->data_mesgs['record']['speed2']= convoluer($this->options['convoStat'],$this->data_mesgs['record']['speed'],true);
		$this->data_mesgs['session']['avg_speed2']=array_sum($this->data_mesgs['record']['speed2']) / count($this->data_mesgs['record']['speed2']);
		$this->data_mesgs['session']['max_speed2']=max($this->data_mesgs['record']['speed2']);
		
		$this->data_mesgs['session']['speed_ascent2']=(double)$this->data_mesgs['session']['total_distance_ascent2']/($this->data_mesgs['session']['time_ascent2']/3600);
		$this->data_mesgs['session']['speed_descent2']=(double)$this->data_mesgs['session']['total_distance_descent2']/($this->data_mesgs['session']['time_descent2']/3600);
		
		$this->data_mesgs['session']['avg_cadence']=number_format(array_sum($this->data_mesgs['record']['cadence']) / count($this->data_mesgs['record']['cadence']),2);
		$this->data_mesgs['session']['max_cadence']=max($this->data_mesgs['record']['cadence']);
		
		$this->data_mesgs['session']['avg_heart_rate']=number_format(array_sum($this->data_mesgs['record']['heart_rate']) / count($this->data_mesgs['record']['heart_rate']),2);
		$this->data_mesgs['session']['max_heart_rate']=max($this->data_mesgs['record']['heart_rate']);
		 
// 		print_r($this->data_mesgs['session']);
	}
	
	public function makeDataElevation($dataEle) {
		$dataCumulEle=array();
		$oldEle=null;
		$first=true;
		foreach ($dataEle as $keyTime => $valEle) {
			$ldist=$this->data_mesgs['record']['delta_distance'][$keyTime];
			$deltaTime=$this->data_mesgs['record']['delta_time'][$keyTime];
			if (!$first) {
				// delta entre les deux altitudes
				$deltaEle=(double)$valEle-(double)$oldEle;
				//Seuil de prise en compte des ecarts
// 				echo "$deltaEle = ".(double)$valEle." - ".(double)$oldEle." " ;
				$seuilEle = 0;
				// on grimpe
				if ($deltaEle > $seuilEle )  {
// 					echo "monte ";
					$dataCumulEle['timeAscent']+=$deltaTime;
					$dataCumulEle['distanceAscent']+=(double)$ldist;
					$dataCumulEle['elePlus']+=$deltaEle;
				}
				// 		on descend
				else if ($deltaEle < -$seuilEle ) {
// 					echo "descend ";
						$dataCumulEle['timeDescent']+=$deltaTime;
					$dataCumulEle['distanceDescent']+=(double)$ldist;
					$dataCumulEle['eleMoins']+=abs($deltaEle);
				}
				//c'est plat'
				else {
// 					echo "plat ";
						$dataCumulEle['timePlat']+=$deltaTime;
					$dataCumulEle['distancePlat']+=(double)$ldist;
				}
			}
			else {
				$dataCumulEle['timeAscent']=0;
				$dataCumulEle['distanceAscent']=0;
				$dataCumulEle['elePlus']=0;
				$dataCumulEle['timeDescent']=0;
				$dataCumulEle['distanceDescent']=0;
				$dataCumulEle['eleMoins']=0;
				$dataCumulEle['timePlat']=0;;
				$dataCumulEle['distancePlat']=0;
				
				$first=false;
				
			}
			$oldEle=$valEle;
		}
		return $dataCumulEle;
	}
	
	public function makeDataElevationD($dataEle) {
		$dataCumulEle=array();
		$oldEle=null;
		$first=true;
		foreach ($dataEle as $keyDist => $valEle) {
			$ldist=$keyDist;
			if (!$first) {
				// delta entre les deux altitudes
				$deltaEle=(double)$valEle-(double)$oldEle;
				//Seuil de prise en compte des ecarts
				$seuilEle = 0;
				// on grimpe
				if ($deltaEle > $seuilEle )  {
					$dataCumulEle['distanceAscent']+=(double)$ldist;
					$dataCumulEle['elePlus']+=$deltaEle;
				}
				// 		on descend
				else if ($deltaEle < -$seuilEle ) {
					$dataCumulEle['distanceDescent']+=(double)$ldist;
					$dataCumulEle['eleMoins']+=abs($deltaEle);
				}
				//c'est plat'
				else {
					$dataCumulEle['timePlat']+=$deltaTime;
					$dataCumulEle['distancePlat']+=(double)$ldist;
				}
			}
			else {
				$dataCumulEle['distanceAscent']=0;
				$dataCumulEle['elePlus']=0;
				$dataCumulEle['distanceDescent']=0;
				$dataCumulEle['eleMoins']=0;
				$dataCumulEle['distancePlat']=0;
				
				$first=false;
				
			}
			$oldEle=$valEle;
		}
		return $dataCumulEle;
	}
	
	
	
	public function calculVitesse() {
		foreach ($this->data_mesgs['record']['distance_delta'] as $time => $ldist) { 
			// deplacement de plus de 5m.
			// 	en heure =
			$deltaTimeH=(double)$this->data_mesgs['record']['delta_time'][$key]/3600;
		//		echo $data['Time']." - ".$this->pointOld['Time']." = ".$deltaTime."[".$deltaTimeH."]<br/>";
	
			// vitesse = km/temps
			$vitesse=(double)$ldist/$deltaTimeH;
	//		echo ($ldist*1000)."/".$deltaTimeH." = ".number_format($vitesse,2)."<br/>";
			$this->data_mesgs['record']['speed']+=array($key => $vitesse);
			$this->data_mesgs['record']['speed1']+=array((double)$this->dataCumul['distance']*1000 => $vitesse);
		}
	}
		
	/**
	 * Short-hand access to commonly used enumerated data.
	 */
	public function manufacturer()
	{
		return $this->data_mesgs['file_id']['manufacturer'];
	}
	public function product()
	{
		return $this->data_mesgs['file_id']['product'];
	}
	public function sport()
	{
		return $this->data_mesgs['file_id']['sport'];
	}
	
	public function saveToTCX() {
	
		//HEAD
		$xmlTCX="<TrainingCenterDatabase xsi:schemaLocation='http://www.garmin.com/xmlschemas/TrainingCenterDatabase/v2 http://www.garmin.com/xmlschemas/TrainingCenterDatabasev2.xsd' ".
				//http://www.garmin.com/xmlschemas/ActivityExtension/v1 http://www.garmin.com/xmlschemas/ActivityExtensionv1.xsd ".
				"xmlns='http://www.garmin.com/xmlschemas/TrainingCenterDatabase/v2' ".
				"xmlns:extv2='http://www.garmin.com/xmlschemas/ActivityExtension/v2' ".
				"xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' ".
				">";
		$xmlTCX.="<Activities>".
				"<Activity Sport='Biking'>".
				"<Id></Id>".
				"<Lap><Track></Track></Lap>".
				/*"<Creator xsi:type='Device_t'>".
				"<Name>Garmin Epix</Name>".
				"<UnitId>12345678</UnitId>".
				"<ProductID>65535</ProductID>".
				"<Version>".
				"<VersionMajor>1</VersionMajor>".
				"<VersionMinor>1</VersionMinor>".
				"</Version>".
				"</Creator>".*/
				"</Activity>".
				"</Activities>";
		/*$xmlTCX.="<Author  xsi:type='Application_t'>".
				"<Name>RomaelGPs</Name>".
				"<Build>".
				"<Version>".
				"<VersionMajor>1</VersionMajor>".
				"<VersionMinor>01</VersionMinor>".
				"<BuildMajor>0</BuildMajor>".
				"<BuildMinor>0</BuildMinor>".
				"</Version>".
				"<Type>Release</Type>".
				"<Time>Jan 01 2017, 15:15:15</Time>".
				"</Build>".
				"<LangID>FR</LangID>".
				"<PartNumber>XXX-XXXXX-XX</PartNumber>".
				"</Author>";*/
		$xmlTCX.="</TrainingCenterDatabase>";
		try {
	
			$monXml = new SimpleXMLElement($xmlTCX);
	
			// 	time de creation du GPX 2017-01-26T11:20:05Z
			$monXml->Activities->Activity->Lap->addAttribute("StartTime",gmdate("Y-m-d\TH:i:s\Z",$this->data_mesgs['file_id']['time_created']));
	
			$monXml->Activities->Activity->Id=$this->data_mesgs['session']['event'];
			
			$trackXml= $monXml->Activities->Activity->Lap->Track;
			foreach ($this->data_mesgs['record']['distance'] as $timeStamp => $val) {
				$trkptXml=$trackXml->addChild("Trackpoint");
				$trkptXml->addChild("Time",gmdate("Y-m-d\TH:i:s\Z",$timeStamp));
				
				if ($this->data_mesgs['record']['position_lat'][$timeStamp]) {
					$positionXml = $trkptXml->addChild("Position");
					$positionXml->addchild("LatitudeDegrees",$this->data_mesgs['record']['position_lat'][$timeStamp]);
					$positionXml->addchild("LongitudeDegrees",$this->data_mesgs['record']['position_long'][$timeStamp]);
				}
				
				$trkptXml->addChild("AltitudeMeters",$this->data_mesgs['record']['altitude'][$timeStamp]);
				
				if ($this->data_mesgs['record']['distance'][$timeStamp] && $this->data_mesgs['record']['distance'][$timeStamp] != "") {
// 					$trkptXml->addChild("DistanceMeters",number_format($this->data_mesgs['record']['distance'][$timeStamp]*1000,2, '.', ''));
				}
				
				if (isset($this->data_mesgs['record']['heart_rate']) && isset($this->data_mesgs['record']['heart_rate'][$timeStamp])
						&& $this->data_mesgs['record']['heart_rate'][$timeStamp] != "") {
// 					$heartXml = $trkptXml->addChild("HeartRateBpm");
// 					$heartXml->addAttribute("xmlns:xsi:type","HeartRateInBeatsPerMinute_t");
// 					$heartXml->addChild("Value",$this->data_mesgs['record']['heart_rate'][$timeStamp]);
				}
				
				if (isset($this->data_mesgs['record']['cadence']) && isset($this->data_mesgs['record']['cadence'][$timeStamp])
						&& $this->data_mesgs['record']['cadence'][$timeStamp] != "") {
				$trkptXml->addChild("Cadence",$this->data_mesgs['record']['cadence'][$timeStamp]);
				}
				
			/*	if (isset($this->data_mesgs['record']['speed']) && isset($this->data_mesgs['record']['speed'][$timeStamp])
						&& $this->data_mesgs['record']['speed'][$timeStamp] != "") {
  							$extXML = $trkptXml->addChild("Extensions");
// 							$extv2TPXXML = $extXML->addChild("xmlns:extv2:TPX");
  							$extv2TPXXML = $extXML->addChild("TPX");
 							//$extv2TPXXML->addChild("xmlns:extv2:Speed",$this->data_mesgs['record']['cadence'][$timeStamp]);
  							$extv2TPXXML->addChild("Speed",$this->data_mesgs['record']['speed'][$timeStamp]);
						}
				*/
				/*
				 <DistanceMeters>0</DistanceMeters>
				 <HeartRateBpm xsi:type="HeartRateInBeatsPerMinute_t"><Value>90</Value></HeartRateBpm>
				 <Cadence>72</Cadence>
				 <Extensions><extv2:TPX><extv2:Speed>5.102777777777778</extv2:Speed></extv2:TPX></Extensions>
				 </Trackpoint>
				 */
			}
			$monXml->asXMl($_SERVER['DOCUMENT_ROOT']."/tmp/RomaelGPs.tcx");
	
			return "/tmp/RomaelGPs.tcx";
			 
		} catch (Exception $e) {
			print_r($e);
			return 'ERREUR makeFile TCX: '.$e->getMessage();
		}
	}
	
	public function saveToGPX() {
		 
		//HEAD
		$xmlGPX="<gpx creator='RomaelGPs' version='1.1' xmlns='http://www.topografix.com/GPX/1/1' xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' xsi:schemaLocation='http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd http://www.garmin.com/xmlschemas/GpxExtensions/v3 http://www.garmin.com/xmlschemas/GpxExtensionsv3.xsd http://www.garmin.com/xmlschemas/TrackPointExtension/v1 http://www.garmin.com/xmlschemas/TrackPointExtensionv1.xsd http://www.garmin.com/xmlschemas/GpxExtensions/v3 http://www.garmin.com/xmlschemas/GpxExtensionsv3.xsd http://www.garmin.com/xmlschemas/TrackPointExtension/v1 http://www.garmin.com/xmlschemas/TrackPointExtensionv1.xsd http://www.garmin.com/xmlschemas/GpxExtensions/v3 http://www.garmin.com/xmlschemas/GpxExtensionsv3.xsd http://www.garmin.com/xmlschemas/TrackPointExtension/v1 http://www.garmin.com/xmlschemas/TrackPointExtensionv1.xsd'>";
		$xmlGPX.="<metadata></metadata>";
		$xmlGPX.="<trk>";
		$xmlGPX.="<trkseg></trkseg>";
		$xmlGPX.="</trk>";
		$xmlGPX.="</gpx>";
		try {
	
			$monXml = new SimpleXMLElement($xmlGPX);
	
			// 	time de creation du GPX 2017-01-26T11:20:05Z
			$monXml->metadata->addChild("time",gmdate("Y-m-d\TH:i:s\Z",$this->data_mesgs['file_id']['time_created']));
	
			$monXml->trk->addChild('name',$this->data_mesgs['session']['event']);
	
			foreach ($this->data_mesgs['record']['distance'] as $timeStamp => $val) {
				$trkptXml=$monXml->trk->trkseg->addChild("trkpt");
				$trkptXml->addAttribute("lat",$this->data_mesgs['record']['position_lat'][$timeStamp]);
				$trkptXml->addAttribute("lon",$this->data_mesgs['record']['position_long'][$timeStamp]);
				$trkptXml->addChild("ele",$this->data_mesgs['record']['altitude'][$timeStamp]);
				$trkptXml->addChild("time",gmdate("Y-m-d\TH:i:s\Z",$timeStamp));
			}
			 
			$monXml->asXMl("/tmp/RomaelGPs.gpx");
	
			return "/tmp/RomaelGPs.gpx";
			 
		} catch (Exception $e) {
			return 'ERREUR makeFile GPX: '.$e->getMessage();
		}
	}
}
