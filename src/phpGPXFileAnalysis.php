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
 * This is the number of seconds difference between GPX and Unix timestamps.
 * GPX timestamps are seconds since UTC 00:00:00 Dec 31 1989 (source GPX SDK)
 * Unix time is the number of seconds since UTC 00:00:00 Jan 01 1970
 */
if (!defined('GPX_UNIX_TS_DIFF')) {
    define('GPX_UNIX_TS_DIFF', 631065600);
}

class phpGPXFileAnalysis extends phpFileAnalysis
{
     // PHP Constructor - called when an object of the class is instantiated.
    public function __construct($file_path, $options = null)
    {
    	parent::__construct($file_path, $options);
    	
    }
    
    /**
     * D00001275 Flexible & Interoperable Data Transfer (GPX) Protocol Rev 1.7.pdf
     * Table 3-1. Byte Description of File Header
     */
    protected function readHeader()
    {
    	// attibution de l'entete dans file_id
    	$this->data_mesgs['file_id']['type']="GPX";
    	$this->data_mesgs['file_id']['manufacturer']=(string) $this->file_contents['creator'];
    	$this->data_mesgs['file_id']['number']=(string) $this->file_contents['version'];
    	$datebrut=(string)$this->file_contents->metadata->time;
    	$this->data_mesgs['file_id']['time_created']=strtotime( $datebrut);
    	
//     	print_r($this->file_contents);
    }
    
    /**
     * Reads the remainder of $this->file_contents and store the data in the $this->data_mesgs array.
     */
    protected function readDataRecords()
    {
    	parent::readDataRecords();
    	
    	foreach ($this->file_contents->trk->trkseg->trkpt as $theSeg) {

    		$data['Time']=$theSeg->time;
    		$data['Ele']=$theSeg->ele;
    		$data['Dist']=0;
    		$data['Cadence']=0;
    		$data['Heart']=0;
    		$data['Lat']=$theSeg['lat'];
    		$data['Long']=$theSeg['lon'];
    		
    		
			$this->addData($data); 
			
    	}
    	$this->makeSession();
//      	 echo "<pre>";
//      	print_r($this->data_mesgs['session']);
//      	//print_r($this->data_mesgs['record']['altitude']);
//      	echo "</pre>";

    }
    
   
	
}
