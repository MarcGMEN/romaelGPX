<?php
//namespace romael;

require_once 'src/phpFileAnalysis.php';

if (!defined('DEFINITION_MESSAGE')) {
    define('DEFINITION_MESSAGE', 1);
}
if (!defined('DATA_MESSAGE')) {
    define('DATA_MESSAGE', 0);
}

/*
 * This is the number of seconds difference between TCX and Unix timestamps.
 * TCX timestamps are seconds since UTC 00:00:00 Dec 31 1989 (source TCX SDK)
 * Unix time is the number of seconds since UTC 00:00:00 Jan 01 1970
 */
if (!defined('TCX_UNIX_TS_DIFF')) {
    define('TCX_UNIX_TS_DIFF', 631065600);
}

class phpTCXFileAnalysis extends phpFileAnalysis
{
    
     // PHP Constructor - called when an object of the class is instantiated.
    public function __construct($file_path, $options = null)
    {
    	parent::__construct($file_path, $options);
     }
    
    /**
     * D00001275 Flexible & Interoperable Data Transfer (TCX) Protocol Rev 1.7.pdf
     * Table 3-1. Byte Description of File Header
     */
     protected function readHeader()
    {
    	$this->data_mesgs['file_id']['type']="TCX";
    	$this->data_mesgs['file_id']['manufacturer']=(string)$this->file_contents->Activities->Activity->Creator->Name;
    	$this->data_mesgs['file_id']['number']=(string)$this->file_contents->Author->Build->Version->VersionMajor.".".
    		(string)$this->file_contents->Author->Build->Version->VersionMinor;
    	$datebrut=(string)$this->file_contents->Activities->Activity->Lap['StartTime'];
    	$this->data_mesgs['file_id']['time_created']=strtotime( $datebrut);
    	$this->data_mesgs['session']['sport']=(string)$this->file_contents->Activities->Activity['Sport'];
    	   
    }
    
    /**
     * Reads the remainder of $this->file_contents and store the data in the $this->data_mesgs array.
     */
    protected function readDataRecords()
    {
    	parent::readDataRecords();

    	foreach ($this->file_contents->Activities->Activity->Lap->Track->Trackpoint as $theSeg) {
    	
    		$data['Time']=$theSeg->Time;
    		$data['Ele']=$theSeg->AltitudeMeters;
    		$data['Dist']=$theSeg->DistanceMeters/1000;
    		$data['Cadence']=$theSeg->Cadence;
    		$data['Heart']=$theSeg->HeartRateBpm->Value;
    		$data['Lat']=$theSeg->Position->LatitudeDegrees;
    		$data['Long']=$theSeg->Position->LongitudeDegrees;
    	
    	
    		$this->addData($data);
    			
    	}
    	$this->makeSession();
    
    }
    
    
}
