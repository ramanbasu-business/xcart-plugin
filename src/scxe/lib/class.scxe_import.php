<?php

class scxe_import
{
    private $globalHelper;
    
    public function importProducts($jobId, $verbose)
    {
        $this->globalHelper =new scxe_job();

        $job_row = $this->globalHelper->getJob($jobId);
        if ($job_row == null) {
            return "job# " . $jobId . " not found";
        }
        //var_dump($job_row);
        $localfilename = $this->globalHelper->getLogFolderPath() . (string) $job_row['localfilename'];
        $logfilename = $this->globalHelper->getLogFolderPath() . (string) $job_row['logfilename'];
        $feed_xml = simplexml_load_file($localfilename);
        
        if ($feed_xml == null) {
            $this->globalHelper->updateJobErrored($jobId);
            if ($verbose == 1) {
                echo "job# " . $jobId . ", can not load localfilename: " . $localfilename . " Job status:error, rejected";
            }
            return "job# " . $jobId . ", can not load localfilename: " . $localfilename . " Job status:error, rejected";
        }
    
        $module = '';
        if (isset($feed_xml['module'])) {
            $module =(string) $feed_xml['module'];
        }
    
        try {
            $this->globalHelper->updateJobSubmitted($jobId);
            
            $o = new scxe_importer($logfilename, $verbose);
            if ($module == '') {
                //$o->setupConfigAttributes($feed_xml);
                //$o->importSimple($feed_xml);
                //$o->importConfig($feed_xml);
                //$o->importRelatedProducts($feed_xml);
                //$o->importImages($feed_xml);
                $o->importProducts($feed_xml);
            } elseif ($module == 'invprice') {
                $o->importMini($feed_xml);
            } elseif ($module == 'shipping') {
                $o->importShipping($feed_xml);
            } elseif ($module == 'order_cancel') {
                $o->importCancelOrder($feed_xml);
            }

            $o = null;
            
            $this->globalHelper->updateJobCompleted($jobId);
            if ($verbose == 1) {
                echo "job# " . $jobId . " updated as completed" ;
            }
            return ""; // executed successfully
        } catch (Exception $ex) {
            $this->globalHelper->updateJobErrored($jobId);
            if ($verbose == 1) {
                echo "job# " . $jobId . ", Job status:error " . $ex->getMessage();
            }
            return $ex->getMessage();
        }
    }
    
    
}
