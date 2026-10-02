<?php
require('../include.php' );
getjob();

    function getjob() {
	
	$scoc_encoder=new scoc_encoder();
	$util = new scoc_utility();
	
	$util->printHeader();
	$qtrStrArray = $scoc_encoder->authenticate(TRUE, FALSE);
	if ($qtrStrArray == NULL) {
	    return;
	}

	$newId = !empty($qtrStrArray['id']) ? intval($qtrStrArray['id']) : 0;

	$scoc_lib=new scoc_lib();
	$job_row = $scoc_lib->getJob($newId);
	//var_dump($job_row);
	
	$xml = $scoc_lib->getJobXml2($job_row);
	$util->convertToXmlAndPrint($xml);
	unset($xml, $util);
    }

