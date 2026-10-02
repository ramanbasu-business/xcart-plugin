<?php

getOrders();

function getOrders() {
    /*
     * currentpageindex starts from 1
     */
    global $db;
    $scoc_lib = new scoc_lib();
    $scoc_utility = new scoc_utility();
    $scoc_encoder = new scoc_encoder();

    $qtrStrArray = $scoc_encoder->authenticate(TRUE, FALSE);
    if ($qtrStrArray == NULL) {
	unset($scoc_utility, $scoc_encoder);
	return;
    }

    $order_id = isset($_GET['id']) ? $_GET['id'] : 0;
    $currentPageIndex = isset($_GET['currentpageindex']) ? (int) $_GET['currentpageindex'] : 1;
    $currentPageIndex--;
    $rowsPerPage = isset($_GET['rowsperpage']) ? $_GET['rowsperpage'] : 10;
    $fromDate = isset($_GET['fromdate']) ? $_GET['fromdate'] : NULL;
    $toDate = isset($_GET['todate']) ? $_GET['todate'] : NULL;

    $output = '<?xml version="1.0" encoding="UTF-8" ?>';
    $output .= '<orders>';
    $output .= '<channel>';
    //$output .= '<title>' . $this->config->get('config_name') . '</title>'; 
    //$output .= '<description>' . $this->config->get('config_meta_description') . '</description>';
    $output .= '<link>' . HTTP_SERVER . '</link>';
    $output .= '</channel>';

    $splitter = $scoc_lib->getOrders($currentPageIndex, $rowsPerPage, $fromDate, $toDate, $order_id);

    $recordCount = $splitter->number_of_rows;
    $totalPages = (int) ($recordCount / $rowsPerPage) + ( (int) ($recordCount % $rowsPerPage) > 0 ? 1 : 0 );

    $output .= '<recordcount>' . $recordCount . '</recordcount>';
    $output .= '<currentpageindex>' . $currentPageIndex . '</currentpageindex>';
    $output .= '<totalpages>' . $totalPages . '</totalpages>';

    //var_dump($orders);
    //return;
    $cDataHeaders = array();
    $orders = $db->Execute($splitter->sql_query);
    while (!$orders->EOF) :
	$order_id = $orders->fields['orders_id'];
	$output .= '<order>';
	if (TRUE) {
	    //$output .= '<' . (string) $key . ' lang="en"><![CDATA[' . $value . ']]></' . $key . '>';
	    $output .= '<orders_id>' . $orders->fields['orders_id'] . '</orders_id>';
	    $output .= '<orders_status>' . $orders->fields['orders_status'] . '</orders_status>';
	    $output .= '<orders_status_name>' . $orders->fields['orders_status_name'] . '</orders_status_name>';
	    $output .= '<customers_id>' . $orders->fields['customers_id'] . '</customers_id>';
	    $output .= '<customers_name><![CDATA[' . $orders->fields['customers_name'] . ']]></customers_name>';
	    $output .= '<customers_company><![CDATA[' . $orders->fields['customers_company'] . ']]></customers_company>';
	    $output .= '<customers_street_address><![CDATA[' . $orders->fields['customers_street_address'] . ']]></customers_street_address>';
	    $output .= '<customers_suburb><![CDATA[' . $orders->fields['customers_suburb'] . ']]></customers_suburb>';
	    $output .= '<customers_city><![CDATA[' . $orders->fields['customers_city'] . ']]></customers_city>';
	    $output .= '<customers_postcode>' . $orders->fields['customers_postcode'] . '</customers_postcode>';
	    $output .= '<customers_state>' . $orders->fields['customers_state'] . '</customers_state>';
	    $output .= '<customers_country>' . $orders->fields['customers_country'] . '</customers_country>';
	    $output .= '<customers_telephone><![CDATA[' . $orders->fields['customers_telephone'] . ']]></customers_telephone>';
	    $output .= '<customers_email_address>' . $orders->fields['customers_email_address'] . '</customers_email_address>';
	    $output .= '<customers_address_format_id>' . $orders->fields['customers_address_format_id'] . '</customers_address_format_id>';
	    $output .= '<delivery_name><![CDATA[' . $orders->fields['delivery_name'] . ']]></delivery_name>';
	    $output .= '<delivery_company><![CDATA[' . $orders->fields['delivery_company'] . ']]></delivery_company>';
	    $output .= '<delivery_street_address><![CDATA[' . $orders->fields['delivery_street_address'] . ']]></delivery_street_address>';
	    $output .= '<delivery_suburb><![CDATA[' . $orders->fields['delivery_suburb'] . ']]></delivery_suburb>';
	    $output .= '<delivery_city>' . $orders->fields['delivery_city'] . '</delivery_city>';
	    $output .= '<delivery_postcode>' . $orders->fields['delivery_postcode'] . '</delivery_postcode>';
	    $output .= '<delivery_state>' . $orders->fields['delivery_state'] . '</delivery_state>';
	    $output .= '<delivery_country>' . $orders->fields['delivery_country'] . '</delivery_country>';
	    $output .= '<delivery_address_format_id>' . $orders->fields['delivery_address_format_id'] . '</delivery_address_format_id>';
	    $output .= '<billing_name><![CDATA[' . $orders->fields['billing_name'] . ']]></billing_name>';
	    $output .= '<billing_company><![CDATA[' . $orders->fields['billing_company'] . ']]></billing_company>';
	    $output .= '<billing_street_address><![CDATA[' . $orders->fields['billing_street_address'] . ']]></billing_street_address>';
	    $output .= '<billing_suburb>' . $orders->fields['billing_suburb'] . '</billing_suburb>';
	    $output .= '<billing_city>' . $orders->fields['billing_city'] . '</billing_city>';
	    $output .= '<billing_postcode>' . $orders->fields['billing_postcode'] . '</billing_postcode>';
	    $output .= '<billing_state>' . $orders->fields['billing_state'] . '</billing_state>';
	    $output .= '<billing_country>' . $orders->fields['billing_country'] . '</billing_country>';
	    $output .= '<billing_address_format_id>' . $orders->fields['billing_address_format_id'] . '</billing_address_format_id>';
	    $output .= '<payment_method>' . $orders->fields['payment_method'] . '</payment_method>';
	    $output .= '<payment_module_code>' . $orders->fields['payment_module_code'] . '</payment_module_code>';
	    $output .= '<shipping_method>' . $orders->fields['shipping_method'] . '</shipping_method>';
	    $output .= '<shipping_module_code>' . $orders->fields['shipping_module_code'] . '</shipping_module_code>';
	    $output .= '<coupon_code>' . $orders->fields['coupon_code'] . '</coupon_code>';
	    $output .= '<cc_type>' . $orders->fields['cc_type'] . '</cc_type>';
	    $output .= '<cc_owner>' . $orders->fields['cc_owner'] . '</cc_owner>';
	    $output .= '<cc_number>' . $orders->fields['cc_number'] . '</cc_number>';
	    $output .= '<cc_expires>' . $orders->fields['cc_expires'] . '</cc_expires>';
	    $output .= '<cc_cvv>' . $orders->fields['cc_cvv'] . '</cc_cvv>';
	    $output .= '<last_modified>' . $orders->fields['last_modified'] . '</last_modified>';
	    $output .= '<date_purchased>' . $orders->fields['date_purchased'] . '</date_purchased>';
	    $output .= '<orders_date_finished>' . $orders->fields['orders_date_finished'] . '</orders_date_finished>';
	    $output .= '<currency>' . $orders->fields['currency'] . '</currency>';
	    $output .= '<currency_value>' . $orders->fields['currency_value'] . '</currency_value>';
	    $output .= '<order_total>' . $orders->fields['order_total'] . '</order_total>';
	    $output .= '<order_tax>' . $orders->fields['order_tax'] . '</order_tax>';
	    $output .= '<paypal_ipn_id>' . $orders->fields['paypal_ipn_id'] . '</paypal_ipn_id>';
	    $output .= '<ip_address>' . $orders->fields['ip_address'] . '</ip_address>';
	    
	}

	//foreach ($orders->fields as $key => $value) {
	//    echo "\$output .= '<" . $key . ">'" . "." . "\$orders->fields['" . $key . "']" . "." . "'</" . $key . ">'" . ";";
	//}
	$ot_subtotal = 0.0;
	$ot_shipping = 0.0;
	$ot_shipping_title = "";
	$ot_total = 0.0;

	$totals_query = $db->Execute("select title, value, class from " . TABLE_ORDERS_TOTAL . " where orders_id = '" . (int) $order_id . "' order by sort_order");
	while (!$totals_query->EOF) :
	    if ($totals_query->fields['class'] == "ot_shipping") {
		$ot_shipping += $totals_query->fields['value'];
		$ot_shipping_title = $totals_query->fields['title'];
	    }
	    if ($totals_query->fields['class'] == "ot_subtotal") {
		$ot_subtotal += $totals_query->fields['value'];
	    }
	    if ($totals_query->fields['class'] == "ot_total") {
		$ot_total += $totals_query->fields['value'];
	    }

	    $totals_query->MoveNext();
	endwhile;
	$output .= '<subtotal>' . $ot_subtotal . '</subtotal>';
	$output .= '<shipping>' . $ot_shipping . '</shipping>';
	$output .= '<nettotal>' . $ot_total . '</nettotal>';

	if (TRUE) {
	    $orderitems = $scoc_lib->getOrderItems($order_id);
	    $output .= '<orderitems>';
	    foreach ($orderitems as $oi) {
		//if ($key != 'attributes')
		//var_dump($oi);
		$output .= '<orderitem>';
		$output .= '<products_id>' . $oi["products_id"] . '</products_id>';
		$output .= '<products_name><![CDATA[' . $oi["products_name"] . ']]></products_name>';
		$output .= '<products_quantity>' . $oi["products_quantity"] . '</products_quantity>';
		$output .= '<products_model>' . $oi["products_model"] . '</products_model>';
		$output .= '<products_price>' . $oi["products_price"] . '</products_price>';
		$output .= '<products_tax>' . $oi["products_tax"] . '</products_tax>';
		$output .= '<final_price>' . $oi["final_price"] . '</final_price>';
		$output .= '</orderitem>';
	    }
	    $output .= '</orderitems>';
	}

	$orders->MoveNext();
	$output .= '</order>';
    endwhile;

    $output .= '</orders>';
    $scoc_utility->printHeader();
    echo $output;
    unset($scoc_utility, $scoc_lib, $scoc_encoder);
}

exit;
