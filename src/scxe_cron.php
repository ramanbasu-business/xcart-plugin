<?php
/**
 * Legacy X-Cart cron job runner
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

if( !defined(SCXE_LOG)){
    define(SCXE_LOG, str_replace("\\", "/",  $xcart_dir . 'scxe' . LC_DS . 'log/'));
}
$_module_dir  = $xcart_dir . LC_DS . 'scxe' . LC_DS . 'import/';


require_once LC_DIR_ROOT . 'scxe/lib/class.scxe_tripledes.php';
require_once LC_DIR_ROOT . 'scxe/lib/class.scxe_encoder.php';
include_once LC_DIR_ROOT . 'scxe/lib/class.scxe_utility.php';

/* Import specific libraries */
require_once $xcart_dir . 'scxe/lib/class.scxe_job.php';
require_once $xcart_dir . 'scxe/lib/class.scxe_xmllog.php';
require_once $xcart_dir . 'scxe/lib/KLogger.php';
include_once $xcart_dir . 'scxe/lib/class.scxe_variant.php';
include_once $xcart_dir . 'scxe/lib/class.scxe_import.php';
require_once $xcart_dir . 'scxe/lib/class.scxe_importer.php';

$id = 0; // default value for $id
$verbose = 1; // default mode

if (isset($_GET['id']) && $_GET['id'] != "") {
    //cron.php?id=XXX
    $id = (int) $_GET["id"];
}
$globalHelper = new scxe_job();


if ($id != 0) {
    $job_row = $globalHelper->getJob($id);
} else {
    $job_row = $globalHelper->getOneSubmittedJob(NULL);
}
//var_dump($job_row);
if ($job_row && count($job_row)) {
    $id = $job_row["id"];
    $module = $job_row["module"];

    if ($id > 0) {
	$o = new scxe_import();
	echo "<h2>cron job $id started ........</h2>";
	if ($module == '' || $module == 'invprice') {
	    $output = $o->importProducts($id, $verbose);
	} else if ($module == 'shipping') {
	    $output = $o->importProducts($id, $verbose);
	}
	$o = NULL;

	if ($verbose)
	    echo $output;
	echo "<h2>cron job finished............</h2>";
    }
}else {
    echo "<h2>No submitted job is found</h2>";
}
exit;
