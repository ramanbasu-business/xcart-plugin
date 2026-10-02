<?php

/* * **********************************************************
 *
 * *********************************************************** */

//require_once "KLogger.php";
/* * **********************************************************************
  class to handle product import from given XML file stored in Web server.
 * **********************************************************************
 * global $var_dirs;
 * $var_dirs['log']
 */

class scxe_importer {

    private $defaultAttributeSetId = -1;
    public $_defAttrSetName = 'Default';
    private $verbose = 0;
    private $xmllog;
    private $log;
    // version 10.8
    private $_reqdAttrs = array();
    private $globalHelper;

    public function __construct($logfilename, $_verbose)
    {
        $this->globalHelper = new scxe_job();
        $this->verbose = $_verbose;
        $this->log = new KLogger(
                $this->globalHelper->getLogFolderPath() . "import-log-" . date('dmy') . ".txt"
                , KLogger::INFO
        );
        //$this->log = new scxe_job();
        $this->xmllog = new scxe_xmllog($logfilename);
        //var_dump($logfilename);

        foreach ($GLOBALS as $key => $val) {
            global $$key;
        }
    }

    public function __destruct()
    {
        unset($this->xmllog);
    }

    public function getModule($feed_xml)
    {
        if (isset($feed_xml['module'])) {
            return $feed_xml['module'];
        }
        return '';

        // foreach ($feed_xml->xpath("/products/product") as $child)
        // {
        // $nodes = $child->children();
        // $nodeCount = count($nodes);
        // echo $nodeCount;
        // $cnt_product++;
        // }
    }

    public function importProducts($feed_xml)
    {
        $this->_print("---------------------------------------");
        $this->_print("-------- import process starts --------");
        $this->_print("---------------------------------------");

        foreach ($feed_xml->product as $productXML) :
            $_productid = 0;
            $action = "";
            $sku = (string) $productXML->productcode;

            $this->_print("======== importing SKU '$sku' starts ========");
            if (empty($sku)) {
                $this->_print($sku . ' product not found.');
                $this->xmllog->log($sku, "error", "product not found");
                continue;
            }

            if (isset($productData)) {
                unset($productData);
            }
            $productData = array();
            $productData['sku'] = (string) $productXML->productcode;
            $productData['name'] = (string) $productXML->product;
            $productData['price'] = abs(doubleval((double) $productXML->price));
            $productData['weight'] = isset($productXML->weight) ? abs(doubleval((double) $productXML->weight)) : 0;

            $p = \XLite\Core\Database::getRepo('\XLite\Model\Product')->findOneBy(array('sku' => $sku));
            //var_dump($p->getName());
            if (!isset($p)) {
                $action = "A";
                $this->_print($sku . ' adding new product.');

                $p = new \XLite\Model\Product();
                $p->setSku($sku);
                $p->setName($productData['name']);
                $p->setInventoryEnabled(true);

                $p->update(true);
                $_productid = $p->getProductId();
                $this->_print($p->getSku() . " added");
                $this->_print("New product id '$_productid'");
            }
            else {
                $action = "M";
                $_productid = $p->getProductId();
                $this->_print("Existing product id '$_productid'");
            }


            $p->map($productData);
            $p->setBriefDescription(isset($productXML->descr) ? (string) $productXML->descr : '');
            $p->setDescription(isset($productXML->fulldescr) ? (string) $productXML->fulldescr : '');
            $p->setShippable(true);
            if ($p->getShippable()):
                if (isset($productXML->separate_box)):
                    $p->setUseSeparateBox((int) $productXML->separate_box == 1 ? true : false);
                endif;
                if (isset($productXML->items_per_box)):
                    $p->setItemsPerBox((int) $productXML->items_per_box);
                endif;
                if (isset($productXML->length)):
                    $p->setBoxLength((double) $productXML->length);
                endif;
                if (isset($productXML->width)):
                    $p->setBoxWidth((double) $productXML->width);
                endif;
                if (isset($productXML->height)):
                    $p->setBoxWidth((double) $productXML->height);
                endif;
            endif;

            if (isset($productXML->forsale)):
                $p->setEnabled((string) $productXML->forsale === 'Y' ? true : false);
            endif;

            if (isset($productXML->title_tag)):
                $p->setMetaTitle((string) $productXML->title_tag);
            endif;
            if (isset($productXML->meta_description)):
                $p->setMetaDesc((string) $productXML->meta_description);
            endif;
            if (isset($productXML->meta_keywords)):
                $p->setMetaTags((string) $productXML->meta_keywords);
            endif;
            if (isset($productXML->clean_url)):
                $p->setCleanURL((string) $productXML->clean_url);
            endif;

            $this->_print($sku . " inventory enabled: " . $p->getInventoryEnabled());

            if (isset($productXML->avail)):
                if ($p->getInventoryEnabled() == true):
                    //$inventory = $p->getInventory();
                    //$inventory->setAmount((int) $productXML->avail);
                    //$p->setInventory($inventory);
                    //$inventory->setProduct($p);

                    $p->setAmount((int) $productXML->avail);
                endif;

            endif;
            if (isset($productXML->low_avail_limit)):
                $p->setLowLimitEnabled(true);
                $p->setLowLimitAmount((int) $productXML->low_avail_limit);
            endif;

            $p->setTaxClass($this->getTaxClass($p, $productXML));
            $this->updateSale($p, $productXML);
            $this->updateCategories($p, $productXML);
            $this->updateImages($p, $productXML);
            $this->updateAdditionalAttributes($p, $productXML);
            $p->update(true);

            $variantProcess = new scxe_variant();
            try {
                $variantProcess->MatrixEntryPoint($productXML);
            } catch (Exception $ex) {
                $this->_printError($ex->getMessage());
            }

            unset($variantProcess);

            $this->_print($sku . " updated.");
            $this->xmllog->log($sku, "success", "");
        endforeach;
    }

    protected function updateSale($p, $productXML)
    {
        if (isset($productXML->on_sale)) :
            $on_sale = (string) $productXML->on_sale;
            $value = isset($productXML->list_price) ? floatval($productXML->list_price) : null;

            if ($on_sale == 'Y' && $value > 0) {
                $p->setParticipateSale(true);
                $p->setSalePriceValue($value);
                $p->setDiscountType(
                        strpos($value, '%') > 0 ? \XLite\Model\Product::SALE_DISCOUNT_TYPE_PERCENT : \XLite\Model\Product::SALE_DISCOUNT_TYPE_PRICE
                );
            }
            else {
                $p->setParticipateSale(false);
            }
        endif;
    }

    protected function getTaxClass($product, $productXML)
    {
        if (!isset($productXML->tax)) {
            return 0;
        }
        $taxClassName = (string) $productXML->tax;
        try {
            $tc = \XLite\Core\Database::getRepo('XLite\Model\TaxClass')->findOneByName($taxClassName, true);
            if ($tc <= 0) {
                return null;
            }

            $taxclassObject = \XLite\Core\Database::getRepo('\XLite\Model\TaxClass')->find($tc);
            if ($taxclassObject && $taxclassObject->getId()) {
                return $taxclassObject;
            }
            else {
                return null;
            }
        } catch (Exception $ex) {
            $this->_print($ex->getMessage());
        }
    }

    // V5.0
    protected function updateCategories(\XLite\Model\Product $product, $productXML)
    {
        if (!isset($productXML->categories)) {
            return;
        }
        if (count($productXML->categories->categoryid) < 1) {
            return;
        }

        //var_dump($newProduct->getCategoryProducts()->toArray());
        if ($product->getCategoryProducts()) {
            \XLite\Core\Database::getRepo('\XLite\Model\CategoryProducts')->deleteInBatch(
                    $product->getCategoryProducts()->toArray()
            );
            $product->getCategoryProducts()->clear();
        }

        //if($product->getCategoryProducts()) $product->getCategoryProducts()->clear();
        $this->_print("Setting up categories");
        // Import categories
        foreach ($productXML->categories->categoryid as $p_categoryID):
            $categoryId = (int) $p_categoryID;
            $category = \XLite\Core\Database::getRepo('\XLite\Model\Category')->find($categoryId);
            if (!isset($category)) {
                //echo 'no cat';
                continue;
            }

            $link = new \XLite\Model\CategoryProducts;
            $link->setProduct($product);
            $link->setCategory($category);
            $product->addCategoryProducts($link);
            \XLite\Core\Database::getEM()->persist($link);

            $this->_print("Category $categoryId created");
        endforeach;
    }

    // v5.0
    protected function updateImages(\XLite\Model\Product $product, $productXML)
    {
        // gallery_image ignored, considered image
        if (!isset($productXML->image)) {
            return;
        }
        $this->_print($product->getSku() . ' saving images');

        // Delete existing images for product
        $count = $product->countImages();
        if ($count > 0) :
            while (count($product->getImages()) > 0) {
                $image = $product->getImages()->last();
                \XLite\Core\Database::getRepo('XLite\Model\Image\Product\Image')->delete($image, false);
                $product->getImages()->removeElement($image);
            }
        endif;


        foreach ($productXML->image as $path) :
            $path = (string) $path;
            $this->_print($product->getSku() . ' saving gallery image ' . $path);
            $image = new \XLite\Model\Image\Product\Image();

            if (1 < count(parse_url($path))) :
                $success = $image->loadFromURL($path, true);
                if ($success) :
                    $image->setProduct($product);
                    $product->getImages()->add($image);
                    \XLite\Core\Database::getEM()->persist($image);

                    $this->_print($product->getSku() . ' image ' . $path . $path . ' saved');
                else :
                    $this->_print($product->getSku() . ' image ' . $path . ' NOT saved');
                endif;
            endif;
        endforeach;
    }

    public function updateAdditionalAttributes(\XLite\Model\Product $product, $productXML)
    {
        if (!isset($productXML->additional_attributes)) {
            return;
        }

        $this->_print("-------- save additional attributes starts --------");

        foreach ($productXML->additional_attributes->associativeentity as $associativeentity) :
            //$service_name = (string)$associativeentity->service_name;
            $field = (string) $associativeentity->field;
            $service_name = $field;
            $value = (string) $associativeentity->value;

            if (!empty($service_name)) :
                $variantProcess = new scxe_variant();
                try {
                    if ($variantProcess->updateAttributeWithSingleValue($product, $service_name, $value)) {
                        $this->_print("Additional attribute: Created new for service_name='$service_name', field='$field'");
                    }
                    else {
                        $this->_print("Additional attribute: failed for service_name='$service_name', field='$field'");
                    }
                } catch (Exception $ex) {
                    $this->_printError($ex->getMessage());
                }

                unset($variantProcess);

            endif;
        endforeach;

        $this->_print("-------- save additional attributes ends --------");
    }

    public function importMini($feed_xml)
    {
        //foreach ($GLOBALS as $key => $val) {
        //    global $$key;
        //}
        $variant_repo = \XLite\Core\Database::getRepo('\XLite\Module\XC\ProductVariants\Model\ProductVariant');

        $this->_print('Mini-updating price/inventory from XML');

        $updated = 0;

        foreach ($feed_xml->product as $productXML) {
            $sku = '';
            $price = null;
            $qty = 0;
            $variantObject = null;
            $p = null;
            $isVariant = false;

            try {
                $sku = trim((string) $productXML->productcode);

                if (empty($sku)) :
                    $this->_print($sku . ' productid not found.');
                    $this->xmllog->log($sku, "error", "productid not found");
                    continue;
                endif;

                $price = isset($productXML->price) ? str_replace(',', '', (string) $productXML->price) : null;
                $qty = isset($productXML->qty) ? (int) $productXML->qty : null;
                //$membershipid = isset($productXML->membershipid) ? (int) $productXML->membershipid : 0;

                $this->_print('SKU: ' . $sku . ' price: ' . $price . ' qty: ' . $qty);

                $p = \XLite\Core\Database::getRepo('\XLite\Model\Product')->findOneBy(array('sku' => $sku));
                if (!isset($p)) {
                    if ($variant_repo):
                        //$variantObject = $variant_repo->findOneBySku($sku);
                        $variantObject = $variant_repo->findOneBy(array('sku' => $sku));
                        
                        if (!$variantObject) {
                            $this->xmllog->log($sku, "error", "product SKU " . $sku . " not found");
                            continue;
                        }

                        $isVariant = true;
                    endif;
                }


                $str = $sku;

                $this->_print($sku . " inventory enabled: " . $p->getInventoryEnabled());
                $this->_print("updating price...");

                if (isset($productXML->price)) :
                    if (isset($p)) :
                        //$productData = array();
                        //$productData['price'] = abs(doubleval((double) $productXML->price));
                        //$p->map($productData);
                        $p->setPrice(abs(doubleval((double) $price)));
                    elseif ($isVariant === true) :
                        $variantObject->setPrice($price);
                        $variantObject->setDefaultPrice(false);
                    endif;
                endif;

                if (isset($productXML->qty)) :
                    if (isset($p)) :
                        
                        if ($p->getInventory() == null) :
                            $p->setAmount($qty);
                        else :
                            $inventory = $p->getInventory();
                            $inventory->setAmount($qty);
                            $p->setInventory($inventory);
                            $inventory->setProduct($p);
                        endif;
                        
                    endif;

                    if ($isVariant === true):
                        $variantObject->setAmount($qty);
                        $variantObject->setDefaultAmount(false);
                    endif;
                endif;

                if (isset($p)) :
                    $p->update(true);
                elseif ($isVariant === true) :
                    \XLite\Core\Database::getEM()->persist($variantObject);

                //$variantObject->update(true);
                //$p = $variantObject->getProduct();
                //$variantObject->setProduct($p);
                //$p->updateQuickData();
                //$variantObject->getProduct()->checkVariants();
                //var_dump($variantObject->getAmount());
                endif;
                //var_dump($qty); exit;


                $this->_print($sku . ': updated ');
                $this->xmllog->log($sku, "success", "");
            } catch (Exception $ex) {
                $this->_print($ex->getMessage());
                $this->xmllog->log($sku, "error", $ex->getMessage());
                continue;
            }
        } // each product in loop

        \XLite\Core\Database::getEM()->flush();
        //\XLite\Core\Database::getEM()->clear();
    }

    private function _print($s)
    {
        if ($this->verbose == 1) {
            echo '<li style="color:darkgreen;font-family:serif,tahoma,verdana,arial;font-size:0.85em">' . $s . '</li>';
        }
        $this->log->LogInfo($s);
    }

    private function _printError($s)
    {
        if ($this->verbose == 1) {
            echo '<li style="color:red;font-family:serif,tahoma,verdana,arial;font-size:0.85em">' . $s . '</li>';
        }
        $this->log->LogError($s);
    }

    protected function skusToIds($userData, $configurable)
    {
        $configurableIds = array();
        foreach ($this->userCSVDataAsArray($userData) as $oneSku) {
            $a_sku = $configurable->getIdBySku(trim($oneSku));
            if ($a_sku && $a_sku > 0) {
                $configurableIds[] = $a_sku;
            }
            //var_dump($a_sku);
        }

        return $configurableIds;
    }

    protected function skusToProducts($userData)
    {
        $ps = array();
        foreach ($this->userCSVDataAsArray($userData) as $oneSku) {
            //echo '$oneSku='. $oneSku;
        }
        //var_dump($products);
        return $ps;
    }

    protected function userCSVDataAsArray($data)
    {
        //return explode( ',', str_replace( " ", "", $data ) );
        return explode(',', trim($data));
    }

    public function importShipping($feed_xml)
    {
        foreach ($GLOBALS as $key => $val) {
            global $$key;
        }

        $this->_print('Import order tracking');

        foreach ($feed_xml->order as $orderXML) {
            $order = null;
            $orderModel = \XLite\Core\Database::getRepo('XLite\Model\Order');
            $actualOrderId = 0;

            //try {
            $orderId = (int) $orderXML->orderid;
            $carrier_code = isset($orderXML->carrier_code) ? (string) $orderXML->carrier_code : "";
            $carrier_subcode = isset($orderXML->carrier_subcode) ? (string) $orderXML->carrier_subcode : "";
            $new_tracking_number = (string) $orderXML->number;
            $shipment_date = (isset($orderXML->shipment_date) && strlen((string) $orderXML->shipment_date) > 0)
                    ? (string) $orderXML->shipment_date : null; //date("Y-m-d H:i:s");
            
            $order = $orderModel->findOneByOrderNumber($orderId); // findOneByOrderNumber

            if (!$order) :
                $this->_print($orderId . ': not found ');
                $this->xmllog->log($orderId, "error", "");
                continue;
            endif;
            
            
            $this->_print($orderId . ' found. starting tracking import ');
            $this->_print($orderId . ': Finding Shipping method Id from carrier code: ' . $carrier_code
                . " And subcode:" . $carrier_subcode);

            $trackingObject = null;

            // -------------------------------------------------------------
            // UPDATE SHIPPING ID/ METHOD ID TO ORDER
            $model = \XLite\Core\Database::getRepo('XLite\Model\Shipping\Method');
            $qb = $model->createQueryBuilder();
            
            /**
             * commented in 2.5.4.0. Just finding against code or name for subcode
            $qb
                ->select('m.method_id')
                ->andWhere('translations.name = :name')
                ->andWhere('m.processor = :processor')
                ->setParameter('name', $carrier_subcode)
                ->setParameter('processor', $carrier_code);
            **/
            $qb
                ->select('m.method_id')
                ->andWhere('translations.name = :name')
                ->setParameter('name', $carrier_subcode)
                ->orWhere('m.code = :code')
                ->setParameter('code', $carrier_subcode);
            
            
            $methodId = $qb->getSingleScalarResult();
            if ($methodId) :
                $this->_print($orderId . ': Shipping method id found ' . $methodId);
                $order->setLastShippingId($methodId);
                $order->setShippingId($methodId);
                $order->setShippingMethodName($carrier_subcode);
            else :
                $this->_print($orderId . ': Shipping method Id not found from carrier subcode: ' . $carrier_subcode);
            endif;
            \XLite\Core\Database::getEM()->persist($order);
            // =============================================================
            
            // -----------------------------------------------------------------
            // UPDATE TRACKING NUMBER
            // =================================================================
            //var_dump($new_tracking_number);
            $order = $orderModel->findOneByOrderNumber($orderId); // findOneByOrderNumber
            foreach ($order->getTrackingNumbers() as $number) {
                //var_dump($number->getValue());
                if ($new_tracking_number === $number->getValue()) {
                    $trackingObject = $number;
                    $this->_print($orderId . ': tracking number ' . $new_tracking_number . ' exists.');
                    break;
                }
            }

            if ($trackingObject) :
                //$trackingObject->setTrackingId($new_tracking_number);
                $trackingObject->setValue($new_tracking_number);
                $this->_print($orderId . ': setting up tracking number ' . $new_tracking_number);
                \XLite\Core\Database::getEM()->persist($trackingObject);
            else :
                $trackingObject = new XLite\Model\OrderTrackingNumber();
                $trackingObject->setValue($new_tracking_number);
                $trackingObject->setOrder($order);
                
                $this->_print($orderId . ': new tracking number ' . $new_tracking_number . '');
                \XLite\Core\Database::getEM()->persist($trackingObject);
                $this->_print($orderId . ': saved');
                
                $order->addTrackingNumbers($trackingObject);
                \XLite\Core\Database::getEM()->persist($order);
                $this->_print($orderId . ': saved new tracking to order');
            endif;
            
            // -----------------------------------------------------------------
            // UPDATE SHIPPING STATUS
            // =================================================================
            $order = $orderModel->findOneByOrderNumber($orderId); // findOneByOrderNumber
            $order->setShippingStatus(\XLite\Model\Order\Status\Shipping::STATUS_SHIPPED);
            $order->setRecent(false); // 2.5.1.0
            
            $this->_print($orderId . ': shipping status is set to shipped');
            \XLite\Core\Database::getEM()->persist($order);
            
            //\XLite\Core\Database::getEM()->persist($order);
            //\XLite\Core\Database::getEM()->persist($trackingObject);
            \XLite\Core\Database::getEM()->flush();

            // 2.5.0.0
            \XLite\Core\Mailer::sendOrderTrackingInformationCustomer($order);
            $this->_print($orderId . ': called [sendOrderTrackingInformationCustomer]. Tracking information has been sent');
            
            $this->_print($orderId . ': imported ');
            $this->xmllog->log($orderId, "success", "");

            /* } catch (Exception $ex) {
              $this->_print($ex->getMessage());
              $this->xmllog->log($orderNumber, "error", $ex->getMessage());
              //continue;
              } */

            unset($order);
        } //Next order to process for tracking..

        //\XLite\Core\Database::getEM()->flush();
        //\XLite\Core\Database::getEM()->clear();
    }

    private function getProductidByVariantid($vid)
    {
        $pid = func_query_first_cell("SELECT productid FROM $sql_tbl[variants] WHERE variantid = '$vid'");
        if ($pid <= 0 || $pid == null) {
            return -1;
        } else {
            return $pid;
        }
    }

    /*
     * Cancel order
     */
    public function importCancelOrder($orderid)
    {
        foreach ($GLOBALS as $key => $val) {
            global $$key;
        }

        $this->_print('Import order cancel');
        
    }
}
