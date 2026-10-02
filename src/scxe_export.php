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

require_once LC_DIR_ROOT . 'scxe/lib/class.scxe_tripledes.php';
require_once LC_DIR_ROOT . 'scxe/lib/class.scxe_encoder.php';
include_once LC_DIR_ROOT . 'scxe/lib/class.scxe_utility.php';
require_once LC_DIR_ROOT . 'scxe/lib/class.scxe_export.php';

if ('test' === $_GET['method']) {
    echo '<h1>TEST METHOD</h1>';
    //$remoteResource= \XLite\Core\RemoteResource\RemoteResourceFactory::getRemoteResourceByURL
    //("https://ce.cwa.sellercloud.com/images/products/251822.jpg");
    $remoteResource = \XLite\Core\RemoteResource\RemoteResourceFactory::getRemoteResourceByURL(
            "https://upload.wikimedia.org/wikipedia/en/a/a8/41_-_A_Portrait_of_My_Father.jpg");
    var_dump($remoteResource);
    var_dump($remoteResource->isAvailable());
    //order_test();
    exit();
}

function getDataCell($transaction, $name)
{
    $value = null;

    foreach ($transaction->getData() as $cell) {
        if ($cell->getName() == $name) {
            $value = $cell;
            break;
        }
    }

    // TODO: Consider situations if cells with same names have different access levels

    return $value;
}

function order_test()
{
    $order = \XLite\Core\Database::getRepo('XLite\Model\Order')->findOneByOrderNumber(29);
    if (isset($order) == false):
        echo 'invalid order';
        return;
    endif;
    echo $order->getOrderId();

    if ($order) {
        $transactions = $order->getPaymentTransactions();
        if (!empty($transactions)) {
            $transaction = $transactions[0];

            echo 'result from getPaymentTransactions() <br />';
            foreach ($transaction->getData() as $cell) {
                echo $cell->getName() . '=>' . $cell->getValue() . '<br/>';
            }

            echo 'result from getPaymentTransactionSums() <br />';
            $sums = $order->getPaymentTransactionSums();
            var_dump($sums);

            echo 'result from getEventData() <br />';
            $data = $transaction->getEventData();
            foreach ($data as $dataElement) {
                //$data[]
            }
            var_dump($data);

            //echo $data['txn_id'];
            //echo getDataCell($transaction, 'txn_id')->getValue();
            //echo $transactions[0]->getPaymentMethod()->getName();
            //echo $transactions[0]->getReadableStatus();
            //echo $transactions[0]->getPaymentMethod()->getProcessor();
            //$transactions[0]->getPaymentMethod()->getProcessor()->doTransaction($transactions[0],\XLite\Core\Request::getInstance()->transactionType);
        }
        else {
            echo 'tran is empty';
        }
    }

    $output = '';
    $result = $order->getTransactionIds();
    if (!$result || ($result && count($result) == 0)) {
        return 'no transaction ids';
    }

    foreach ($result as $p):
        $output .= '<orderpayment>';
        $output .= '<url>' . $p['url'] . '</url>';
        $output .= '<name>' . $p['name'] . '</name>';
        $output .= '<value>' . ($p['value'] == 'yes' ? '' : $p['value']) . '</value>';
        $output .= '</orderpayment>';
    endforeach;
    echo $output;
}

//scxe_utility::printHeader();
if (true) {
    header('HTTP/1.1 200 OK');
    header("Pragma: no-cache");
    header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate');
    header("Content-type: text/xml");
}

$encoder = new scxe_encoder();
$qtrStrArray = $encoder->authenticate();
if ($qtrStrArray == null) {
    return;
}

if (in_array($qtrStrArray['method'], array('invprice', 'products', 'categories', 'orderstatus', 'orders', 'shippinglist', 'cancelorder', 'test'))) {
    $start = !empty($qtrStrArray['start']) ? intval($qtrStrArray['start']) : 1;
    $limit = !empty($qtrStrArray['limit']) ? intval($qtrStrArray['limit']) : 10;
    $start--;

    $limit = max(min($limit, 10), 0);

    /* ADDED FOR V5.0 */
    $currentPageIndex = isset($qtrStrArray['currentpageindex']) ? (int) $qtrStrArray['currentpageindex'] : 1;
    $currentPageIndex--;
    $rowsPerPage = isset($qtrStrArray['rowsperpage']) ? $qtrStrArray['rowsperpage'] : 10;

    if ('invprice' === $qtrStrArray['method']) {
        $_product_sku = !empty($qtrStrArray['sku']) ? ($qtrStrArray['sku']) : "";
        $_export = new scxe_export();
        $_export->printInvPriceXml($currentPageIndex, $rowsPerPage, $_product_sku);
        $_export = null;
    } elseif ('products' === $qtrStrArray['method']) {
        $_product_name = !empty($qtrStrArray['name']) ? ($qtrStrArray['name']) : "";
        $_product_sku = !empty($qtrStrArray['sku']) ? ($qtrStrArray['sku']) : "";
        $_export = new scxe_export();
        $_export->printProductsXml($currentPageIndex, $rowsPerPage, $_product_name, $_product_sku);
        $_export = null;
    } elseif ('orderstatus' === $qtrStrArray['method']) {
        $_export = new scxe_export();
        $_list = $_export->getOrderStatusList();
        $_export->printOrderStatusXml($_list);
        $_export = null;

        //var_dump($_list);
        $_list = null;
        //var_dump( func_scxe_export_get_order_status() );
    } elseif ('orders' === $qtrStrArray['method']) {
        //http://www.localhost.com/xcart460goldplus/scxe_export.php?method=fullorders
        //http://www.localhost.com/xc5/scxe_export.php?method=orders&ignorelogin=1&from=2013-07-23&to=2013-07-23
        //http://www.localhost.com/xcart460goldplus/scxe_export.php?method=fullorders&orderid=12
        //http://www.localhost.com/xcart514/scxe_export.php?method=orders&from=2013-03-05&to=2014-03-06

        $_order_orderid = !empty($qtrStrArray['orderid']) ? ($qtrStrArray['orderid']) : "";
        $_order_from_date = !empty($qtrStrArray['from']) ? ($qtrStrArray['from']) : ""; //2013-06-26T07:45:56+02:00
        $_order_to_date = !empty($qtrStrArray['to']) ? ($qtrStrArray['to']) : ""; //2013-06-27T07:45:56+02:00

        $_export = new scxe_export();
        $_export->getOrdersFull($_order_orderid, $_order_from_date, $_order_to_date);
        $_export = null;
    } elseif ('shippinglist' === $qtrStrArray['method']) {
        $_export = new scxe_export();
        $_export->printShippingListXml();
        $_export = null;
    } elseif ('categories' === $qtrStrArray['method']) {
        $_export = new scxe_export();
        $_export->printCategoryListXml();
        unset($_export);
    }
}
