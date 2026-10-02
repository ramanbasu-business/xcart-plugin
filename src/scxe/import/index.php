<?php

$queue = 0;
$verbose = 0;
if (isset($_GET['queue']) && $_GET['queue'] != "") {
    $queue = $_GET['queue'] == "1" ? 1 : 0;
}
if (isset($_GET['verbose']) && $_GET['verbose'] != "") {
    $verbose = $_GET['verbose'] == "1" ? 1 : 0;
}

if (isset($_GET['method']) && 'import' === $_GET['method'] || $_SERVER['REQUEST_METHOD'] === 'POST') {
    $xml = file_get_contents('php://input');
    _processPostedData($queue, $verbose, $xml);
}

if (isset($_GET['method']) && $_GET['method'] == 'testshipping') :
    
    $shippingMethod = \XLite\Core\Database::getRepo('XLite\Model\Shipping\Method')->findOneBy(
        array('method_id' => '63')
    );
    var_dump($shippingMethod->getName());
endif;

if (isset($_GET['method']) && $_GET['method'] == 'testproduct') {
    echo 'test product';
    $p = \XLite\Core\Database::getRepo('\XLite\Model\Product')->findOneBy(array('sku' => 'A2162'));
    if (!isset($p)) :
        echo 'product does not exist';
    else :
        echo $p->getName();
        echo $p->getPrice();
    endif;
}
if (isset($_GET['method']) && $_GET['method'] == 'test') {
    $globalHelper = new scxe_job();
    $data = $globalHelper->db_select('select * from job');
    //var_dump($data);
    foreach (\XLite\Core\Database::getRepo('XLite\Model\Attribute')->findAll() as $attribute) {
        //echo $attribute->getName() . ' ' . $attribute->getType() . '<br />';
    }

    $p = \XLite\Core\Database::getRepo('\XLite\Model\Product')->findOneBy(array('sku' => 'SP001'));

    if (false) :
        $ass = $p->getAttributeValues();
        foreach ($ass as $a) :
            echo($a->getId() . $a->getName());
        endforeach;
    endif;

    //$product->addVariantsAttributes($a);
    //$a->addVariantsProduct($product);
    $multiAttrs = $p->getVariantsAttributes();
    foreach ($multiAttrs as $a) :
        echo $a->getName() . ' ';
    endforeach;

    //exit;

    if (false) :
        $a = getMatrixAttribute($p, 'My Color');
        echo $a->getName(); //exit;
        _updateMatrixAttributeValues($p, $a);
        // prepareAttributeValues($ids)

        $a = getMatrixAttribute($p, 'My Size');
        echo $a->getName(); //exit;
    endif;

    doActionCreateVariants($p, array(67, 68));
}

if (isset($_GET['method']) && $_GET['method'] == 'testvariant') :
    echo 'test variant';
    $variantProcess = new scxe_variant();
    try {
        $variantProcess->test();
    } catch (Exception $ex) {
        $this->_printError($ex->getMessage());
    }

    unset($variantProcess);
endif;

function _processPostedData($queue, $verbose, $xml)
{
    $globalHelper = new scxe_job();
    $output = '';
    if ($verbose == 0) {
        header("Content-type: text/xml");
    }

    $feed_xml = simplexml_load_string($xml);
    $module = '';
    if (isset($feed_xml['module'])) {
        $module = $feed_xml['module'];
    }
    else {
        $module = '';
    }

    if ($module == '' || $module == 'invprice') {
        $datetime = date('dmyHis');
        $localfilename = "import-xml-" . $datetime . ".xml";
        $logfilename = "import-log-" . $datetime . ".xml";
    }
    elseif ($module == 'shipping') {
        $datetime = date('dmyHis');
        $localfilename = "shipping-" . $datetime . ".xml";
        $logfilename = "shipping-log-" . $datetime . ".xml";
    }

    /*
     * create xml file with posted data to extension front-end
     * "/files/scxe_log/xxx.log"
     */
    try {
        //echo $globalHelper->getLogFolderPath();
        $filePath = $globalHelper->getLogFolderPath() . $localfilename;
        $FileHandle = fopen($filePath, 'w'); // or die("can't open file");
        fwrite($FileHandle, $xml);
        fclose($FileHandle);
    } catch (Exception $ex) {
        $error_output = htmlentities($ex->getMessage());
        $globalHelper->printJobXmlError($error_output);
        return;
    }

    /*
     * Create new job
     */
    $globalHelper = new scxe_job();
    $newId = $globalHelper->insertJob(date("Y-m-d H:i:s"), null, $localfilename, 0, $logfilename, $module);
    if ($verbose == 1) {
        echo '[New ID# ' . $newId . ']';
    }

    if ($queue == 0) {
        $o = new scxe_import();
        $recordCount = 0;
        //$recordCount = count($feel_xml->children());
        if ($module == '') {
            foreach ($feed_xml->xpath("/products/product") as $child) {
                $recordCount++;
            }

            if ($recordCount <= 50) :
                $output = $o->importProducts($newId, $verbose);
            endif;
        } elseif ($module == 'invprice') {
            foreach ($feed_xml->xpath("/products/product") as $child) {
                $recordCount++;
            }

            if ($recordCount > 0 && $recordCount <= 500) :
                $output = $o->importProducts($newId, $verbose);
            endif;
        } elseif ($module == 'shipping') {
            foreach ($feed_xml->xpath("/orders/order") as $child) {
                $recordCount++;
            }

            if ($recordCount > 0 && $recordCount <= 500) :
                $output = $o->importProducts($newId, $verbose);
            endif;
        } elseif ($module == 'order_cancel') {
            
        }

        if ($verbose == 1) {
            echo 'record count = ' . $recordCount;
        }
    } //

    $job_row = $globalHelper->getJob($newId);
    //var_dump($job_row);
    //echo $job_row[0]['id'];

    if ($job_row) {
        $xml = $globalHelper->getJobXml2($job_row);
        scxe_utility::convertToXmlAndPrint($xml);
    }
    elseif ($output != "") {
        $globalHelper->printJobXmlError(htmlentities($output));
    }
}
