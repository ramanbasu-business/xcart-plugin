<?php
class scxe_xmllog {

    private $_filename;

    function __construct($p_filename) {
	$this->_filename = $p_filename;
	//header("Content-type: text/xml");
	if (file_exists($this->_filename) == false) {
	    //echo 'file missing';
	    $products = new SimpleXMLElement('<products />');
	    $products->asXml($this->_filename);
	    $products = null;
	}
    }

    function __destruct() {
	
    }

    private function saveXml() {
    	$products->asXml($this->_filename);
    }

    private function get($sku) {
		$products = simplexml_load_file($this->_filename);

		if (!$products)
		    return false; //version 10.7
	    
		//var_dump($products[0]);
		//echo isset($products[0]);
		$p = $products->xpath('/products/product[sku="' . $sku . '"]');
		//var_dump($p);
		if ($p)
	    	return true;
		// foreach ($products->product as $p) 
		// {
		// echo 'loop';
		// if ((string)$p->sku== $sku) {
		// var_dump($p);
		// return true;
		// }
		// }
		return false;
    }

    public function append($p_sku, $p_status, $p_error)
    {
		if ($this->get($p_sku)) {
		    return false;
		}
		//return true;
		//$products = new SimpleXMLElement($this->_filename, null, true);
		$products = simplexml_load_file($this->_filename);

		$p = $products->addChild('product');
		$p->addChild('sku', $p_sku);
		$p->addChild('status', $p_status);

		$errors = $p->addChild('errors');
		if ($p_error != "") {
		    $error = $errors->addChild("error", $p_error);
		}
		//echo $products->asXML($this->_filename);
		return true;
    }

    public function appendError($p_sku, $p_error) {
		if ($this->get($p_sku) == false) {
		    return false;
		}

		//return true;
		//$products = simplexml_load_file($this->_filename);
		//$products = new SimpleXMLElement($this->_filename, null, true);
		$products = simplexml_load_file($this->_filename);
		$p = $products->xpath('/products/product[sku="' . $p_sku . '"]');

		//var_dump( $p[0]->sku );
		if ($p_error != "") {
		    $error = $p[0]->errors->addChild("error", $p_error);
		}
		$products->asXML($this->_filename);
		return true;
    }

    public function log($p_sku, $p_status, $p_error)
    {
		if ($this->get($p_sku)) {
		    return $this->appendError($p_sku, $p_error);
		}
		//return true;
		$products = simplexml_load_file($this->_filename);
		//$product = new SimpleXMLElement('<product></product>');
		$p = $products->addChild('product');
		
		$p->addChild('sku', $p_sku);
		//$node = new SimpleXMLExtended();
		//$sku_node= $node->addChildWithCDATA('sku', $p_sku);
		//$p->addChild($sku_node);
		

		$p->addChild('status', $p_status);

		$errors = $p->addChild('errors');
		if ($p_error != "") {
		    $error = $errors->addChild("error", $p_error);
		}
		
		$products->asXML($this->_filename);
		return true;
    }

    public function log1($p_sku, $p_status, $p_error) {
		if ($this->get($p_sku)) {
	    	return $this->appendError($p_sku, $p_error);
		}

		//return true;

		$products = new SimpleXMLElement($this->_filename, null, true);
		$p = $products->addChild('product');
		$p->addChild('sku', $p_sku);
		$p->addChild('status', $p_status);

		$errors = $p->addChild('errors');
		if ($p_error != "") {
		    $error = $errors->addChild("error", $p_error);
		}

		$products->asXML($this->_filename);
		return true;
    }

    public function getContent() {
		
    }

    private function addCData($e, $cdata_text)
    {

        $node = dom_import_simplexml( $e );
        $no = $node->ownerDocument;
        $node->appendChild( $no->createCDATASection( $cdata_text ) );
    }

}
?>
