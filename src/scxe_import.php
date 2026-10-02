<?php
/**
 * SellerCloud Export Plugin
 *
 * @category   X-Cart
 * @package    X-Cart
 * @subpackage Plugin
 * @author     Raman Basu <raman@sellercloud.com>
 * @copyright  Copyright (c) 2014 SellerCloud
 * @version    5.0
 * @link       http://www.x-cart.com/
 * @see        ____file_see____
 */
if (!defined('LC_INCLUDE_ADDITIONAL')) :
    define('LC_INCLUDE_ADDITIONAL', true);
endif;

require './top.inc.php';
$xcart_dir = LC_DIR_ROOT;

\Includes\Utils\ModulesManager::initModules();

if( !defined("SCXE_LOG")){
    define("SCXE_LOG", str_replace("\\", "/",  $xcart_dir . 'scxe' . LC_DS . 'log/'));
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

if ( isset($_GET['method']) && 'install' === $_GET['method']) {
    install();
    exit;
}

header('HTTP/1.1 200 OK');
header("Pragma: no-cache");
header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate');
header("Content-type: text/xml");

$encoder = new scxe_encoder();
$qtrStrArray = $encoder->authenticate();
if ($qtrStrArray == NULL)
    return;

include $_module_dir . 'index.php';

function install() {
    $jobObject=new scxe_job();
    $jobObject->installPlugin();
    echo 'SCXE plugin for SellerCloud has been installed successfully';
    unset($jobObject);
}

