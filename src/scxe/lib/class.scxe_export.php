<?php

class scxe_export {

    private $verbose = false;
    private $scxe_utility = null;
    
    public function __construct($configurationName = 'Default')
    {
        foreach ($GLOBALS as $key => $val) {
            global $$key;
        }
        if (isset($_REQUEST["verbose"])) {
            $this->verbose = true;
        }
        $this->scxe_utility = new scxe_utility();
    }

    public function getProducts($start, $limit, $name, $sku)
    {
        $products = array();

        if ($name != "") {
            $products = func_scxe_export_get_products($start, $limit, 'name', $name);
        } elseif ($sku != "") {
            $products = func_scxe_export_get_products($start, $limit, 'sku', $sku);
        } else {
            $products = func_scxe_export_get_products($start, $limit, '', '');
        }
        return $products;
    }

    public function printInvPriceXml($currentPageIndex, $rowsPerPage, $_product_sku)
    {
        $productModel = \XLite\Core\Database::getRepo('XLite\Model\Product');
        $qbCount = $productModel->createQueryBuilder()->select('COUNT(DISTINCT p.product_id)');
        $qbProducts = $productModel->createQueryBuilder(null, null)
                ->select('p.product_id');

        if (!empty($_product_sku)) :
            $qbCount->andWhere('p.sku = :sku')->setParameter('sku', $_product_sku);
            $qbProducts->andWhere('p.sku = :sku')->setParameter('sku', $_product_sku);
        endif;
        
        $recordCount = intval($qbCount->getSingleScalarResult());
        $totalPages = (int) ($recordCount / $rowsPerPage) + ((int) ($recordCount % $rowsPerPage) > 0 ? 1 : 0);
        $qbProducts->setFrameResults($currentPageIndex * $rowsPerPage, $rowsPerPage);
        $products = $qbProducts->getResult();

        $output = '<?xml version="1.0" encoding="UTF-8" ?>';
        $output .= '<products>';
        $output .= $this->scxe_utility->getVersionXml();
        $output .= '<recordcount>' . $recordCount . '</recordcount>';
        $output .= '<currentpageindex>' . ($currentPageIndex + 1) . '</currentpageindex>';
        $output .= '<totalpages>' . $totalPages . '</totalpages>';
        
        //
        foreach ($products as $p) :
            $product = \XLite\Core\Database::getRepo('XLite\Model\Product')->find($p['product_id']);
            
            if ($product && $product->getId() > 0) :
                $output .= '<product>';
                $output .= '<id>' . $p["product_id"] . '</id>';
                $output .= '<sku>' . $product->getSku() . '</sku>';
                $output .= '<qty>' . $product->getAmount() . '</qty>';
                $output .= '<price>' . $product->getPrice() . '</price>';
                $output .= '<url>' . $product->getCleanURL() . '</url>';

                $variants = $product->getVariantsCollection();
                $output .= '<children>';
                if ($variants->count() > 0) :
                    foreach ($variants as $variant) :
                        $output .= '<child>';
                        $output .= '<id>' . $variant->getId() . '</id>';
                        $output .= '<sku>' . $variant->getSku() . '</sku>';
                        $output .= '<price>' . $variant->getPrice() . '</price>';
                        $output .= '<qty>' . $variant->getAmount() . '</qty>';
                        $output .= '<url/>';
                        $output .= '</child>';
                    endforeach;
                endif;
                $output .= '</children>';

                /*$variantModel= \XLite\Core\Database::getRepo('\XLite\Module\XC\ProductVariants\Model\ProductVariant');
                $variants = $variantModel->createQueryBuilder('p')
                        ->select('p.id, p.sku, p.amount, p.price')
                        ->getResult();*/
                $output .= '</product>';
            endif;
        endforeach;
        
        $output .= '</products>';
        
        echo $output;
    }
    
    public function printProductsXml($currentPageIndex, $rowsPerPage, $_product_name, $_product_sku)
    {
        $productModel = \XLite\Core\Database::getRepo('XLite\Model\Product');
        $qbCount = $productModel->createQueryBuilder()->select('COUNT(DISTINCT p.product_id)');
        $qb = $productModel->createQueryBuilder(null, null)->select('p.product_id')
        //->addSelect('translations')->linkInner('p.translations')
        //
        ;


        if (!empty($_product_name)) {
            // do nothing
        } elseif (!empty($_product_sku)) {
            /*
              $cnd = new \XLite\Core\CommonCell();
              $cnd->{\XLite\Model\Repo\Product::P_SUBSTRING}  = $_product_sku;
              //$cnd->{\XLite\Model\Repo\Product::P_BY_TITLE}   = '';
              $cnd->{\XLite\Model\Repo\Product::P_BY_SKU}     = 'Y';
              $cnd->{\XLite\Model\Repo\Product::P_LIMIT}      = array(0, $limit);
              $cnd->{\XLite\Model\Repo\Product::P_ORDER_BY}   = array('translations.name', 'asc');
              $cnd->{\XLite\Model\Repo\Product::P_INCLUDING}     = 'any';
              $products = \XLite\Core\Database::getRepo('XLite\Model\Product')->search($cnd);
             */
            $qbCount->andWhere('p.sku = :sku')->setParameter('sku', $_product_sku);
            $qb->andWhere('p.sku = :sku')->setParameter('sku', $_product_sku);
        }

        $recordCount = intval($qbCount->getSingleScalarResult());
        $totalPages = (int) ($recordCount / $rowsPerPage) + ((int) ($recordCount % $rowsPerPage) > 0 ? 1 : 0);

        //$qb->setMaxResults($limit);
        $qb->setFrameResults($currentPageIndex * $rowsPerPage, $rowsPerPage);
        $products = $qb->getResult();

        $output = '<?xml version="1.0" encoding="UTF-8" ?>';
        $output .= '<products>';
        $output .= $this->scxe_utility->getVersionXml();
        $output .= '<recordcount>' . $recordCount . '</recordcount>';
        $output .= '<currentpageindex>' . ($currentPageIndex + 1) . '</currentpageindex>';
        $output .= '<totalpages>' . $totalPages . '</totalpages>';

        foreach ($products as $p) :
            $product = \XLite\Core\Database::getRepo('XLite\Model\Product')->find($p['product_id']);
            //var_dump($product->getInventory());
            $output .= '<product>';
            try {
                $productName = (string) $product->getName();
                $productName = utf8_encode(html_entity_decode($productName, null, "UTF-8"));
                // $acirc; error fix Oct-16-2013
                //$productName = scxe_utility::cleanName($productName);

                $descr = (string) $product->getBriefDescription();
                //$descr = scxe_utility::cleanName($descr);
                $descr = utf8_encode(html_entity_decode($descr, null, "UTF-8"));

                //$fulldescr = $product->getDescription();
                //$fulldescr = utf8_encode(html_entity_decode($fulldescr, null, "UTF-8"));
                $fulldescr = (string) $product->getDescription();
                $fulldescr = scxe_utility::cleanString($fulldescr);
        
                $output .= '<product><![CDATA[' . $productName . ']]></product>';
                $output .= '<productid>' . $product->getId() . '</productid>';
                $output .= '<categoryid>' . $product->getCategoryId() . '</categoryid>';
                $output .= '<productcode><![CDATA[' . $product->getSku() . ']]></productcode>';

                $output .= '<inventory_tracking>' . $product->getInventoryEnabled() . '</inventory_tracking>';
                //var_dump($product->getInventory());

                if ($product->getInventory() == null) :

                    if ($product->getAmount() == null) :
                        $output .= '<qty>0</qty>';
                    else :
                        $output .= '<qty>' . $product->getAmount() . '</qty>';
                    endif;
                else :
                    $output .= '<qty>' . $product->getInventory()->getAmount() . '</qty>';
                endif;

                
                $output .= '<price>' . number_format($product->getPrice(), 2) . '</price>';
                //$output .= '<add_date>' . scxe_utility::convertUnixToW3c($product['add_date'] + $config['Appearance']['timezone_offset']) 
                //. '</add_date>';
                $output .= '<descr><![CDATA[' . $descr . ']]></descr>';
                $output .= '<fulldescr><![CDATA[' . $fulldescr . ']]></fulldescr>';
            } catch (Exception $e) {
                //Mage::logException($e);
                //var_dump($key);
                //continue;
                echo $e->getMessage();
            }
            $output .= '</product>';
        endforeach;
        
        $output .= '</products>';
        scxe_utility::printHeader();
        //scxe_utility::convertToXmlAndPrint_NoEncode($productsXML);

        echo $output;
    }

    public function httpHeader()
    {
        header('HTTP/1.1 200 OK');
        header("Pragma: no-cache");
        header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate');
        header("Content-type: text/xml");
        //header('Content-Disposition: attachment; filename="example.xml"');
    }

    /*
     * -------------------------------------------------------------------
     *  Order export
     * -------------------------------------------------------------------
     */

    public function getOrderStatusList()
    {
        global $sql_tbl, $config, $active_modules;

        $statuses = array(
            'I' => "Not finished",
            'Q' => "Queued",
            'A' => "pre-authorized",
            'CA' => 'Canceled',
            'P' => "Processed",
            'D' => "Declined",
            'B' => "Backordered",
            'F' => "Failed",
            'C' => "Complete",
            'S' => "Shipped",
            'X' => 'Refund requested',
            'Y' => 'Refunded',
            'Z' => 'Partially refunded'
        );

        return $statuses;
    }

    public function printOrderStatusXml($statuses)
    {
        $ordersXML = new SimpleXMLElement("<statuses></statuses>");

        foreach ($statuses as $key => $value) {
            $orderXML = $ordersXML->addChild('status');
            $orderXML->addChild("code", $key);
            $orderXML->addChild("value", $value);
        }
        scxe_utility::convertToXmlAndPrint($ordersXML);
    }

    //v5.0
    public function printCategoryListXml()
    {
        $op = '<?xml version="1.0" encoding="UTF-8" ?>';
        
        
        $categoryModel = \XLite\Core\Database::getRepo('XLite\Model\Category');
        $qb = $categoryModel->createQueryBuilder(null, null)
                ->select('c.category_id');
        $result = $qb->getResult();
        $count = count($result);
        //var_dump($count);
        try {
            $op .= '<categories>';
            $op .= $this->scxe_utility->getVersionXml();
            
            $rootCategoryId = \XLite\Core\Database::getRepo('XLite\Model\Category')->getRootCategoryId();
            $categoryObject = \XLite\Core\Database::getRepo('XLite\Model\Category')->find($rootCategoryId);
            if (isset($categoryObject)):
                $op .= '<cat>';
                $op .= '<parentid>' . $categoryObject->getParentId() . '</parentid>';
                $op .= '<categoryid>' . $categoryObject->getId() . '</categoryid>';
                $op .= '<category><![CDATA[' . $categoryObject->getName() . ']]></category>';
                //$op .= '<description><![CDATA[' . $description . ']]></description>';
                $op .= '<avail>Y</avail>';
                //$op .= '<thumb_url><![CDATA[' . $cat['thumb_url'] . ']]></thumb_url>';
                $op .= '</cat>';
            endif;

            foreach ($result as $k => $cat) {
                if ($cat['category_id'] == $rootCategoryId) {
                    continue;
                }

                $categoryObject = \XLite\Core\Database::getRepo('XLite\Model\Category')->find($cat['category_id']);

                //$description = $cat['description'];
                //$description = cleanString($description);
                $_enabled = $categoryObject->getEnabled() === true ? 'Y' : 'N';

                $op .= '<cat>';
                $op .= '<parentid>' . $categoryObject->getParentId() . '</parentid>';
                $op .= '<categoryid>' . $cat['category_id'] . '</categoryid>';
                $op .= '<category><![CDATA[' . $categoryObject->getName() . ']]></category>';
                //$op .= '<description><![CDATA[' . $description . ']]></description>';
                $op .= '<avail>' . $_enabled . '</avail>';
                //$op .= '<thumb_url><![CDATA[' . $cat['thumb_url'] . ']]></thumb_url>';
                $op .= '</cat>';
            }
            $op .= '</categories>';
        } catch (Exception $ex) {
            $op .= '<errors><error>' . $ex->getMessage() . '</error></errors>';
            //$error = $xml->addChild('error', $ex->getMessage());
        }
        scxe_utility::printHeader();
        //scxe_utility::convertToXmlAndPrint($xml);
        echo $op;
        unset($categoryModel);
    }

    /*
     * Shipping method list
     */

    public function printShippingListXml()
    {
        $methodsArray = array();
        $methods = \XLite\Core\Database::getRepo('XLite\Model\Shipping\Method')->findAll();

        foreach ($methods as $p) {
            $methodsArray[] = $p->getProcessor();
        }
        $methodsArray = array_unique($methodsArray);

        //var_dump($p_List);
        //$xml = new SimpleXMLElement("<carriers></carriers>");
        $output = '<?xml version="1.0" encoding="UTF-8" ?>';
        $output .= '<carriers>';
        
        try {
            foreach ($methodsArray as $carrier) {
                //$_carrierXml = $xml->addChild('carrier');
                $output .= '<carrier>';
                
                $code = (string) $carrier;
                //$_carrierXml->addChild('code', $code);
                //$_carrierXml->addChild('shipping', '');
                
                $output .= '<code>' . $code . '</code>';
                $output .= '<shipping></shipping>';
                
                //$_methodOptions = $_value['options'];
                //var_dump( $_methodOptions );
                //$_optionsXml = $_carrierXml->addChild('options');
                $output .= '<options>';
                foreach ($methods as $m) {
                    if ($m->getProcessor() == $carrier) :
                        //$_optionXml = $_optionsXml->addChild('option');
                        //$_optionXml->addChild('subcode', $m->getCode());
                        //$_optionXml->addChild('shippingid', $m->getMethodId());
                        //$_optionXml->addChild('shipping', $m->getName());
                        
                        $output .= '<option>';
                        $output .= '<subcode>' . $m->getCode() . '</subcode>';
                        $output .= '<shippingid>' . $m->getMethodId() . '</shippingid>';
                        $output .= '<shipping><![CDATA[' . $m->getName() . ']]></shipping>';
                        $output .= '</option>';
                    endif;
                }

                //$value = htmlentities( (string)$value );
                $output .= '</options>';
                $output .= '</carrier>';
            }
        } catch (Exception $ex) {
            //$error = $xml->addChild('error', $ex->getMessage());
            $output .= '<error><![CDATA[' . $ex->getMessage() . ']]></error>';
        }
        
        //scxe_utility::convertToXmlAndPrint($xml);
        $output .= '</carriers>';
        echo $output;
    }

    // NEW
    protected function appendAddress($order, $addressType)
    {
        $output = '';
        if ($addressType == 'billing') :
            $address = $order->getProfile()->getBillingAddress(); //->getAddressId();
            //var_dump();
            //$ba = \XLite\Core\Database::getRepo('XLite\Model\Order')->find($orderid);
            $output .= '<email>' . $order->getProfile()->getLogin() . '</email>';
            $output .= '<b_title>' . $address->getTitle() . '</b_title>';
            $output .= '<b_firstname><![CDATA[' . utf8_encode($address->getFirstname()) . ']]></b_firstname>';
            $output .= '<b_lastname><![CDATA[' . utf8_encode($address->getLastname()) . ']]></b_lastname>';
            $output .= '<b_address><![CDATA[' . utf8_encode($address->getStreet()) . ']]></b_address>';
            $output .= '<b_address_2><![CDATA[]]></b_address_2>';
            $output .= '<b_city><![CDATA[' . utf8_encode($address->getCity()) . ']]></b_city>';
            //$output .= '<b_county><![CDATA[' . utf8_encode($address->getCounty()) . ']]></b_county>';
            $output .= '<b_state><![CDATA[' . utf8_encode($address->getState()->getCode()) . ']]></b_state>';
            $output .= '<b_country><![CDATA[' . $address->getCountry()->getCode() . ']]></b_country>';
            $output .= '<b_statename><![CDATA[' . utf8_encode($address->getState()->getState()) . ']]></b_statename>';
            $output .= '<b_countryname><![CDATA[' . utf8_encode($address->getCountry()->getCountry()) . ']]></b_countryname>';

            $output .= '<b_zipcode>' . $address->getZipcode() . '</b_zipcode>';
            //$output .= '<b_zip4>' . $order_data['b_zip4'] . '</b_zip4>';
            $output .= '<b_phone><![CDATA[' . $address->getPhone() . ']]></b_phone>';
            //$output .= '<b_fax>' . $order_data['b_fax'] . '</b_fax>';
        else :
            $address = $order->getProfile()->getShippingAddress(); //->getAddressId();

            $output .= '<s_title>' . $address->getTitle() . '</s_title>';
            $output .= '<s_firstname><![CDATA[' . utf8_encode($address->getFirstname()) . ']]></s_firstname>';
            $output .= '<s_lastname><![CDATA[' . utf8_encode($address->getLastname()) . ']]></s_lastname>';
            $output .= '<s_address><![CDATA[' . utf8_encode($address->getStreet()) . ']]></s_address>';
            $output .= '<s_address_2><![CDATA[]]></s_address_2>';
            $output .= '<s_city><![CDATA[' . utf8_encode($address->getCity()) . ']]></s_city>';
            //$output .= '<s_county><![CDATA[' . utf8_encode($address->getCounty()) . ']]></s_county>';
            $output .= '<s_state><![CDATA[' . utf8_encode($address->getState()->getCode()) . ']]></s_state>';
            $output .= '<s_country><![CDATA[' . $address->getCountry()->getCode() . ']]></s_country>';
            $output .= '<s_statename><![CDATA[' . utf8_encode($address->getState()->getState()) . ']]></s_statename>';
            $output .= '<s_countryname><![CDATA[' . utf8_encode($address->getCountry()->getCountry()) . ']]></s_countryname>';

            $output .= '<s_zipcode>' . $address->getZipcode() . '</s_zipcode>';
            //$output .= '<s_zip4>' . $order_data['s_zip4'] . '</s_zip4>';
            $output .= '<s_phone><![CDATA[' . $address->getPhone() . ']]></s_phone>';
            //$output .= '<b_fax>' . $order_data['b_fax'] . '</b_fax>';
        endif;
        return $output;
    }

    public function getOrdersFull($orderids, $from, $to)
    {
        //global $sql_tbl, $config, $active_modules;

        $cnd = new \XLite\Core\CommonCell;
        $cnd->{\XLite\Model\Repo\Product::P_LIMIT} = array(1, 90);
        //$orders = \XLite\Core\Database::getRepo('XLite\Model\Order')->search($cnd, false);

        $orderModel = \XLite\Core\Database::getRepo('XLite\Model\Order');
        $qb = $orderModel->createQueryBuilder(null, null)
                ->select('o.order_id')
                ->addSelect('o.orderNumber') // Use orderNumber as order
                ->addSelect('o.date')
                ->addSelect('o.payment_method_name')
                ->addSelect('o.tracking')
                ->addSelect('o.notes')
                ->addSelect('o.total')
                ->addSelect('o.subtotal')
                //->addSelect('o.payment_status')
                //->addSelect('o.shipping_status_id')
                ->andWhere('o.orderNumber != :null')->setParameter('null', 'NULL')
                ->addOrderBy('o.order_id', 'desc')
                ->innerJoin('o.paymentStatus', 'ps')
                ->addSelect('ps.code AS paymentstatus_code')
                ->innerJoin('o.shippingStatus', 'ss')
                ->addSelect('ss.code AS shippingstatus_code')
        ;
        //$qb->innerJoin('o.currency', 'currency', 'WITH', 'currency.currency_id = o.currency_id');
        $qb->innerJoin('o.currency', 'currency')
                ->addSelect('currency.code AS currency_code');
        //payment_status_translations

        $output = '<?xml version="1.0" encoding="UTF-8" ?>';
        $output .= '<orders>';

        if (!empty($orderids)) {
            $qb->andWhere('o.orderNumber = :orderNumber')->setParameter('orderNumber', $orderids);
        }
        if (!empty($from)) {
            $f = strtotime($from);
            $from_date = date('Y-m-d' . ' 00:00:00', $f);
            $from_date = scxe_utility::convertW3cToUnix($from_date);
            $qb->andWhere('o.date >= :start')->setParameter('start', $from_date);
        }
        if (!empty($to)) {
            //$condition[] = " $sql_tbl[orders].date <= " . scxe_utility::convertW3cToUnix($to) . "";
            $f = strtotime($to);
            $to_date = date('Y-m-d' . ' 23:59:59', $f);
            $to_date = scxe_utility::convertW3cToUnix($to_date);
            $qb->andWhere('o.date <= :end')->setParameter('end', $to_date);
        }

        $result = $qb->getResult();
        $count = count($result);


        $key = 0;

        $output .= $this->scxe_utility->getVersionXml();
        $output .= '<recordcount>' . $count . '</recordcount>';
        //var_dump($result); exit;
        foreach ($result as $key => $o) :
            $orderid = intval($o['order_id']);
            $order = \XLite\Core\Database::getRepo('XLite\Model\Order')->find($orderid);

            $output .= '<order>';
            $output .= '<actual_order_id>' . $order->getOrderId() . '</actual_order_id>';
            $output .= '<orderid>' . $order->getOrderNumber() . '</orderid>';
            $output .= $this->appendOrder($order);
            $output .= $this->appendAddress($order, 'billing');
            $output .= $this->appendAddress($order, 'shipping');
            $output .= $this->appendOrderItems($order);

            // Payment transactions
            $output .= '<orderpayments>';
            $output .= $this->append_PaymentMethodToOrder_PayPalStandard($order);
            $output .= $this->appendAuthorizeNet_sim_XmlToOrder($order);
            $output .= '</orderpayments>';

            $output .= $this->appendOrderEvents($order);
            
            $output .= '</order>';
        endforeach;

        \XLite\Core\Database::getEM()->flush();

        $output .= '</orders>';
        scxe_utility::printHeader();
        echo $output;
    }

    // @End of function
    // V5.0
    protected function appendOrder($order)
    {
        $output = '';
        $discount = $order->getSurchargesSubtotal(\XLite\Model\Base\Surcharge::TYPE_DISCOUNT);
        if ($discount && $discount < 0) {
            $discount = (-1) * $discount;
        }

        $output .= '<date>' . scxe_utility::convertUnixToW3c($order->getDate()) . '</date>';
        $output .= '<subtotal>' . $order->getSubtotal() . '</subtotal>';
        //$output .= '<coupon_discount>' . $order_data['coupon_discount'] . '</coupon_discount>';
        //$output .= '<discounted_subtotal>' . $order_data['discounted_subtotal'] . '</discounted_subtotal>';
        $output .= '<shipping_cost>' . $order->getSurchargesSubtotal(\XLite\Model\Base\Surcharge::TYPE_SHIPPING) . '</shipping_cost>';
        //$output .= '<payment_surcharge>' . $order_data['payment_surcharge'] . '</payment_surcharge>';
        $output .= '<discount>' . $discount . '</discount>';
        //$output .= '<giftcert_discount>' . $order_data['giftcert_discount'] . '</giftcert_discount>';
        $output .= '<tax>' . $order->getSurchargesSubtotal(\XLite\Model\Base\Surcharge::TYPE_TAX) . '</tax>';
        $output .= '<total>' . $order->getTotal() . '</total>';
        //$output .= '<giftcert_ids>' . $order_data['giftcert_ids'] . '</giftcert_ids>';
        
        $output .= '<shipping><![CDATA[' . $order->getShippingMethodName() . ']]></shipping>';
        $output .= '<shipping_id>' . $order->getShippingId() . '</shipping_id>';
        $output .= '<shipping_status><![CDATA[' . $order->getShippingStatusCode() . ']]></shipping_status>';
        
        $trackingNumbers = $order->getTrackingNumbers();
        
        if (count($trackingNumbers)==0) :
            $output .= '<tracking></tracking>';
        else :
            foreach ($trackingNumbers as $number) {
                $output .= '<tracking>' . $number->getValue() . '</tracking>';
            }
        endif;
        
        $output .= '<customer_notes><![CDATA[' . $order->getNotes() . ']]></customer_notes>';
        $output .= '<payment_method><![CDATA[' . $order->getPaymentMethodName() . ']]></payment_method>';
        $output .= '<status><![CDATA[' . $order->getPaymentStatusCode() . ']]></status>';

        

        return $output;
    }

    // V5.0
    protected function appendOrderItems($order)
    {
        $output = '<orderitems>';
        $items = $order->getItems();
        //var_dump($order->getItems()->toArray());exit;
        $variant_repo = \XLite\Core\Database::getRepo('\XLite\Module\XC\ProductVariants\Model\ProductVariant');
        
        if (!empty($items)) :
            foreach ($items as $item) :
                $product = $item->getProduct();
                //var_dump($item->getId());
                $output .= '<orderitem>';

                $output .= '<productid>' . $product->getProductId() . '</productid>';
                $output .= '<productcode>' . $product->getSku() . '</productcode>';
                
                $variant_code = "";
                $variant_id = "";
                if ($item->getSku() !== $product->getSku()) :
                    $variant_code = $item->getSku();
                
                    $variantObject = $variant_repo->findOneBy(array('sku' => $variant_code));
                    if ($variantObject && $variantObject->getId()) :
                        $variant_id = $variantObject->getId();
                    endif;
                endif;
                $output .= '<variantid>' . $variant_id . '</variantid>';
                $output .= '<variantcode>' . $variant_code . '</variantcode>';
                
                $output .= '<name><![CDATA[' . $product->getTranslation()->name . ']]></name>';
                $output .= '<price>' . $item->getItemNetPrice() . '</price>';
                $output .= '<amount>' . $item->getAmount() . '</amount>';

                //var_dump($item->getAttributeValuesPlain());
                if ($item->hasAttributeValues()) {
                    //var_dump($item->getAttributeValues());
                    $attributeValues = $item->getAttributeValues();
                    $output .= $this->appendProductOptionsXMLForOrder($attributeValues);
                    $output .= '<product_option_text><![CDATA[' . $item->getAttributeValuesAsString() . ']]></product_option_text>';
                }
                $output .= '</orderitem>';
            endforeach;
        endif;

        $output .= '</orderitems>';
        return $output;
    }

    public function appendProductOptionsXMLForOrder($attributeValues)
    {
        //var_dump($poArray);
        $str = '<product_options>';

        foreach ($attributeValues as $av) :
            $str .= '<product_option>';

            $str .= '<class>' . '</class>';
            $str .= '<classtext>' . $av->getName() . '</classtext>';
            $str .= '<orderby>' . '</orderby>';
            $str .= '<avail>' . '</avail>';

            $str .= '<option>';
            $str .= '<option_name>' . $av->getValue() . '</option_name>';
            $str .= '<price_modifier>' . '</price_modifier>';
            $str .= '<modifier_type>' . '</modifier_type>';
            $str .= '</option>';

            $str .= '</product_option>';
        endforeach;

        $str .= '</product_options>';
        return $str;
    }

    public function _addPaypalRefundedXmlToOrder($refunds)
    {
        $array_dates = array("update_date");
        $array_keys = array("date", "amount", "note", "currencycode");
        //$orderItemsXML = $orderXML->addChild('refunds');
        $output = '<refunds>';
        //var_dump($paypal_data);
        if (isset($refunds)) {
            foreach ($refunds as $item => $itemdata) {
                //$orderpaymentXML = $orderItemsXML->addChild('refund');
                $output .='<refund>';
                //$orderpaymentXML->addChild("transactionid", $item);
                //$orderpaymentXML->addChild("type", "Refunded");
                $output .= '<transactionid>' . $item . '</transactionid>';
                $output .= '<type>Refunded</type>';

                foreach ($itemdata as $key => $value) {
                    if (!in_array($key, $array_keys)) {
                        continue;
                    }
                    if (in_array($key, $array_dates)) {// Date fields
                        $value = scxe_utility::convertUnixToW3c($value); //1375082815
                    }

                    $value = htmlentities((string) $value);
                    $value = scxe_utility::cleanString($value);
                    //$orderpaymentXML->addChild($key, $value);
                    $output .= '<' . $key . '>' . $value . '</' . $key . '>';
                }

                $output .='</refund>';
            }
        }
        $output .= '</refunds>';
        return $output;
    }

    // V5.0
    public function append_PaymentMethodToOrder_PayPalStandard($order)
    {
        $output = '';

        $transactions = $order->getPaymentTransactions();
        if (empty($transactions)) {
            return '';
        }
        //var_dump($transactions);exit;
        $transaction = $transactions[0];

        //foreach ($transaction->getData() as $cell) {
        //    echo $cell->getName() . '=>' . $cell->getValue() . '<br/>';
        //}
        //$data = $transaction->getEventData();
        //foreach ($data as $dataElement) {
        //    //$data[]
        //}
        //var_dump($data);
        //echo $data['txn_id'];
        //echo $transaction->getPaymentMethod()->getName();
        //echo $transaction->getReadableStatus();


        foreach ($transactions as $transaction):
            //$output .= '<orderpayment>';
            //$output .= '<txnid>' . $this->getDataCell($transaction, 'txn_id')->getValue() . '</txnid>';
            //$output .= '<payer_id>' . $this->getDataCell($transaction, 'payer_id')->getValue() . '</payer_id>';
            //$output .= '<payment_status>' . $transaction->getReadableStatus() . '</payment_status>';
            //$output .= '<payer_email>' . $this->getDataCell($transaction, 'payer_email')->getValue() . '</payer_email>';
            //$output .= '<amount>' . $this->getDataCell($transaction, 'payer_email')->getValue() . '</amount>';
            //$output .= '</orderpayment>';
            if ($transaction->getPaymentMethod() == null) {
                continue;
            }

            $data = $transaction->getEventData();
            if (!empty($data)):
                $paymentMethod = $transaction->getPaymentMethod()->getName();
                if ($paymentMethod == 'PayPal Payments Advanced'):

                    $sumsArray = $order->getPaymentTransactionSums();
                    $captured_amount = 0.0;
                    if (!empty($sumsArray) && isset($sumsArray['Captured amount'])) {
                        $captured_amount = $sumsArray['Captured amount'];
                    }
                    unset($sumsArray);

                    $output .= '<orderpayment>';
                    $output .= '<method>' . $transaction->getPaymentMethod()->getName() . '</method>';
                    $output .= '<txnid>' . $this->_getDataCellFromEventData($data, "Unique PayPal transaction ID (PPREF)") . '</txnid>';
                    //$output .= '<payer_id>' . $this->_getDataCellFromEventData($data, 'Unique customer ID') . '</payer_id>';
                    $output .= '<payment_status>' . $this->_getDataCellFromEventData($data, 'Status') . '</payment_status>';
                    //$output .= '<payer_email>' . $this->_getDataCellFromEventData($data, "Customer's primary email address") . '</payer_email>';
                    $output .= '<amount>' . $captured_amount . '</amount>';
                    //$output .= '<currencycode>' . $this->_getDataCellFromEventData($data, 'Payment currency') . '</currencycode>';
                    $output .= '</orderpayment>';
                else:
                    $output .= '<orderpayment>';
                    $output .= '<method>' . $transaction->getPaymentMethod()->getName() . '</method>';
                    $output .= '<txnid>' . $this->_getDataCellFromEventData($data, "Original transaction identification number") . '</txnid>';
                    $output .= '<payer_id>' . $this->_getDataCellFromEventData($data, 'Unique customer ID') . '</payer_id>';
                    $output .= '<payment_status>' . $this->_getDataCellFromEventData($data, 'Payment status') . '</payment_status>';
                    $output .= '<payer_email>' . $this->_getDataCellFromEventData($data, "Customer's primary email address") . '</payer_email>';
                    $output .= '<amount>' . $this->_getDataCellFromEventData($data, 'Payment amount') . '</amount>';
                    $output .= '<currencycode>' . $this->_getDataCellFromEventData($data, 'Payment currency') . '</currencycode>';
                    $output .= '</orderpayment>';
                endif;

            endif;
        endforeach;
        return $output;
    }

    private function _getDataCellFromEventData($data, $name)
    {
        $value = null;

        foreach ($data as $cellArray) {
            if ($cellArray['name'] == $name) {
                $value = $cellArray['value'];
                break;
            }
        }

        return $value;
    }

    private function getDataCell($transaction, $name)
    {
        $value = null;

        foreach ($transaction->getData() as $cell) {
            if ($cell->getName() == $name) {
                $value = $cell;
                break;
            }
        }

        return $value;
    }

    public function appendAuthorizeNet_sim_XmlToOrder($order)
    {
        if ($this->verbose) {
            $transactionSums = $order->getPaymentTransactionSums();
            var_dump($transactionSums);

            $acts = $order->getAllowedPaymentActions();
            var_dump($acts);
        }
        return "";

        if (array_key_exists('authorizenet_sim_txnid', $order['order']['extra'])) {
            $txn_id = $order['order']['extra']['authorizenet_sim_txnid'];
            //print_r($txnId);

            $output .= '<orderpayment>';
            //$txn_id = scxe_utility::cleanString($txn_id);
            $output .= '<method><![CDATA[authorizenet_sim]]></method>';
            $output .= '<txnid><![CDATA[' . utf8_encode($txn_id) . ']]></txnid>';
            $output .= '</orderpayment>';
        }

        return $output;
    }

    public function _addAuthorizeNetXmlToOrder($order, $payment_method = '')
    {
        global $config, $active_modules, $xcart_dir;

        //var_dump($active_modules['XPayments_Connector']);
        if (empty($active_modules['XPayments_Connector'])) {
            //echo "empty mod";
            return '';
        }
        if (empty($order['order']['extra']['xpc_txnid'])) {
            return;
        }

        // Get info about payment transaction
        //x_load('backoffice', 'tests');
        //x_load('payment');

        $output = '';
        $txn_id = $order['order']['extra']['xpc_txnid'];

        $output .= '<orderpayment>';
        $value = scxe_utility::cleanString($txn_id);
        //$orderpaymentXML->addChild("txnid", $value);
        $output .= '<method><![CDATA[' . (empty($payment_method) ? 'authorizenet' : $payment_method) . ']]></method>';
        $output .= '<txnid><![CDATA[' . utf8_encode($txn_id) . ']]></txnid>';
        $output .= '</orderpayment>';
        return $output;
    }

    public function _addGiftWrapXmlToOrder($data)
    {
        $array_keys = array("need_giftwrap", "giftwrap_cost", "taxed_giftwrap_cost", "giftwrap_message");
        //$nodeXML = $orderXML->addChild('giftwrap');
        //var_dump($paypal_data);
        $output .= '<giftwrap>';

        foreach ($data as $key => $value) {
            if (!in_array($key, $array_keys)) {
                continue;
            }
            if (in_array($key, $array_dates)) {// Date fields
                $value = scxe_utility::convertUnixToW3c($value); //1375082815
            }

            $value = htmlentities((string) $value);
            $value = scxe_utility::cleanString($value);
            //$nodeXML->addChild($key, $value);
            $output .= '<' . $key . '>' . $value . '</' . $key . '>';
        }
        $output .= '</giftwrap>';
        return $output;
    }

    public function _addGiftCertsXmlToOrder($data)
    {
        //$paypal_data = ($paypal_data["main"]); // Actual array of data
        $array_dates = array("add_date");
        $array_keys = array("gcid", "purchaser", "recipient", "send_via", "recipient_email",
            "recipient_firstname", "recipient_lastname", "recipient_address", "recipient_city",
            "recipient_state", "recipient_zipcode", "recipient_zip4", "recipient_country",
            "recipient_phone", "recipient_county",
            "message", "amount", "status", "add_date");
        //$mainNodeXml = $orderXML->addChild('giftcerts');
        $output = '<giftcerts>';
        //var_dump($data);
        foreach ($data as $item => $itemdata) {

            //$subnodeXML = $mainNodeXml->addChild('giftcert');
            $output .= '<giftcert>';
            foreach ($itemdata as $key => $value) {
                if (!in_array($key, $array_keys)) {
                    continue;
                }
                if (in_array($key, $array_dates)) {// Date fields
                    $value = scxe_utility::convertUnixToW3c($value); //1375082815
                }

                $value = htmlentities((string) $value);
                $value = scxe_utility::cleanString($value);
                //$subnodeXML->addChild($key, $value);
                $output .= '<' . $key . '>' . $value . '</' . $key . '>';
            }
            $output .= '</giftcert>';
        }
        $output .= '</giftcerts>';
        return $output;
    }
    
    private function appendOrderEvents($order)
    {
        $output = '<events>';
        $events = \XLite\Core\Database::getRepo('XLite\Model\OrderHistoryEvents')->findAllByOrder($order);
        if (!empty($events)) :
            foreach ($events as $event) :
                $output .= '<event>';
                $output .= '<date>' . scxe_utility::convertUnixToW3c($event->getDate()) . '</date>';
                $output .= '<code><![CDATA[' . $event->getCode() . ']]></code>';
                $output .= '<description><![CDATA[' . $event->getDescription() . ']]></description>';
                $output .= '<comment><![CDATA[' . $event->getComment() . ']]></comment>';
                $output .= '<authorname><![CDATA[' . $event->getAuthorName() . ']]></authorname>';
                $output .= '</event>';
            endforeach;
        endif;
        
        $output .= '</events>';
        
        return $output;
    }
}