<?php

/**
 * SellerCloud Export Plugin
 *
 * @category   X-Cart
 * @package    X-Cart
 * @subpackage Plugin
 * @author     Raman Basu <ramanbasu.business@gmail.com>
 * @version    5.0
 * @link       http://www.x-cart.com/
 * @see        ____file_see____
 */
require './top.inc.php';
$xcart_dir = LC_DIR_ROOT;

if (!defined(SCXE_LOG)) {
    define(SCXE_LOG, str_replace("\\", "/", $xcart_dir . 'scxe' . LC_DS . 'log/'));
}
$_module_dir = $xcart_dir . LC_DS . 'scxe' . LC_DS . 'import/';


require_once LC_DIR_ROOT . 'scxe/lib/class.scxe_tripledes.php';
require_once LC_DIR_ROOT . 'scxe/lib/class.scxe_encoder.php';
include_once LC_DIR_ROOT . 'scxe/lib/class.scxe_utility.php';

/* Import specific libraries */
require_once $xcart_dir . 'scxe/lib/class.scxe_job.php';
require_once $xcart_dir . 'scxe/lib/class.scxe_xmllog.php';
require_once $xcart_dir . 'scxe/lib/KLogger.php';

if ('getjob' === $_GET['method']) :
    scxe_utility::printHeader();

    $encoder = new scxe_encoder();
    $qtrStrArray = $encoder->authenticate();
    if ($qtrStrArray == NULL)
        return;

    $newId = !empty($qtrStrArray['id']) ? intval($qtrStrArray['id']) : 0;
    
    $jobObject = new scxe_job();
    $job_row = $jobObject->getJob($newId);
    $xml = $jobObject->getJobXml2($job_row);
    scxe_utility::convertToXmlAndPrint($xml);
    $jobObject = NULL;
endif;

