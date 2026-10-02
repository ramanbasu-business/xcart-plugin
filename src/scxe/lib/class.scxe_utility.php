<?php

class SimpleXMLExtended extends SimpleXMLElement {

    public function addCData($cdata_text)
    {
        $node = dom_import_simplexml($this);
        $no = $node->ownerDocument;
        $node->appendChild($no->createCDATASection($cdata_text));
    }

    /**
   * Adds a child with $value inside CDATA
   * @param unknown $name
   * @param unknown $value
   */
  public function addChildWithCDATA($name, $value = NULL) {
    $new_child = $this->addChild($name);

    if ($new_child !== NULL) {
      $node = dom_import_simplexml($new_child);
      $no   = $node->ownerDocument;
      $node->appendChild($no->createCDATASection($value));
    }

    return $new_child;
  }
  
}

class scxe_utility {
    
    public function getPluginVersion()
    {
        return '2.5.4.0';
    }

    public function getVersionXml()
    {
        return "<channel>"
                . "<plugin_version>" . $this->getPluginVersion() . "</plugin_version>"
                . "<xc_version>" . "xc5" . '</xc_version>'
                . "<php_version>" . PHP_VERSION . "</php_version>"
                . "</channel>";
    }
    
    public static function cleanName($text)
    {
        $text = str_replace("Â", "", $text);
        $text = str_replace("–", "-", $text);
        $text = str_replace(" ", " ", $text);
        $text = str_replace("ç", "c", $text);
        $text = str_replace("Ç", "C", $text);
        $text = str_replace("ñ", "n", $text);
        $text = str_replace("Ñ", "N", $text);

        //2) Translation CP1252. &ndash; => -
        $trans = array();
        $trans['&sbquo;'] = '&#x82;';    // Single Low-9 Quotation Mark
        $trans['&mdash;'] = '&#x97;';    // Latin Small Letter F With Hook
        $trans['&ndash;'] = '&#x97;';    // Double Low-9 Quotation Mark
        $trans['&hellip;'] = '&#8230;';    // Horizontal Ellipsis
        $trans['&circ;'] = '&#8853;';    // Modifier Letter Circumflex Accent
        $trans['&tilde;'] = '&#732;';    // Small Tilde
        $trans['&trade;'] = '&#174;';    // Trade Mark Sign
        $trans['&nbsp;'] = '&#xA0;';
        $trans['&ldquo;'] = '&#x93;'; // Left Double Quote
        $trans['&rdquo;'] = '&#x94;'; // right Double Quote
        $trans['&eacute;'] = '&#201;'; // right Double Quote
        $trans['&acute;'] = '&#201;'; // right Double Quote
        $trans['&euro;'] = '&#8364;';    // euro currency symbol
        $trans['&rsquo;'] = '&#x92;';
        $trans['&lsquo;'] = '&#x91;';
        $trans['&agrave;'] = '&#192;';
        $trans['&reg;'] = '&#174;';
        $trans['&Atilde;'] = '&#195;';
        $trans['&frac14;'] = '&#188;';
        $trans['&frac12;'] = '&#189;';
        $trans['&frac34;'] = '&#190;';

        ksort($trans);

        foreach ($trans as $k => $v) {
            $text = str_replace($k, $v, $text);
        }

        $text = str_ireplace('&Acirc;', '', $text);
        $text = str_replace('"', "&#34;", $text);
        // 3) remove <p>, <br/> ...
        //$text = strip_tags($text);
        // 4) &amp; => & &quot; => '
        //$text = html_entity_decode($text);
        // 5) remove Windows-1252 symbols like "TradeMark", "Euro"...
        //$text = preg_replace('/[^(\x20-\x7F)]*/', '', $text);

        $targets = array('\r\n', '\n', '\r', '\t');
        $results = array(" ", " ", " ", "");
        $text = str_replace($targets, $results, $text);

        //XML compatible
        /*
          $text = str_replace("&", "and", $text);
          $text = str_replace("<", ".", $text);
          $text = str_replace(">", ".", $text);
          $text = str_replace("\\", "-", $text);
          $text = str_replace("/", "-", $text);
         */

        return ($text);
    }

    public static function cleanString($text) {
        // 1) convert á ô => a o
        /*
          $text = preg_replace("/[áàâãªä]/u", "a", $text);
          $text = preg_replace("/[ÁÀÂÃÄ]/u", "A", $text);
          $text = preg_replace("/[ÍÌÎÏ]/u", "I", $text);
          $text = preg_replace("/[íìîï]/u", "i", $text);
          $text = preg_replace("/[éèêë]/u", "e", $text);
          $text = preg_replace("/[ÉÈÊË]/u", "E", $text);
          $text = preg_replace("/[óòôõºö]/u", "o", $text);
          $text = preg_replace("/[ÓÒÔÕÖ]/u", "O", $text);
          $text = preg_replace("/[úùûü]/u", "u", $text);
          $text = preg_replace("/[ÚÙÛÜ]/u", "U", $text);
          $text = preg_replace("/[’‘‹›‚]/u", "'", $text);
          $text = preg_replace("/[“”«»„]/u", '"', $text);
         */
        $text = str_replace("–", "-", $text);
        $text = str_replace(" ", " ", $text);
        $text = str_replace("ç", "c", $text);
        $text = str_replace("Ç", "C", $text);
        $text = str_replace("ñ", "n", $text);
        $text = str_replace("Ñ", "N", $text);
        $text = str_replace("É", "", $text); //added
        //
        
        // ---- 2.2.0.0 -- First decode the text
        $text = mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
        
        //2) Translation CP1252. &ndash; => -
        $trans = array();
        $trans['&sbquo;'] = '&#x82;';    // Single Low-9 Quotation Mark 
        $trans['&mdash;'] = '&#x97;';    // Latin Small Letter F With Hook 
        $trans['&ndash;'] = '&#x97;';    // Double Low-9 Quotation Mark 
        $trans['&hellip;'] = '&#8230;';    // Horizontal Ellipsis 
        $trans['&circ;'] = '&#8853;';    // Modifier Letter Circumflex Accent 
        $trans['&tilde;'] = '&#732;';    // Small Tilde 
        $trans['&trade;'] = '&#174;';    // Trade Mark Sign 
        $trans['&nbsp;'] = '&#xA0;';
        $trans['&ldquo;'] = '&#x93;'; // Left Double Quote
        $trans['&rdquo;'] = '&#x94;'; // right Double Quote
        $trans['&eacute;'] = '&#201;'; // right Double Quote
        $trans['&acute;'] = '&#201;';

        $trans['&euro;'] = '&#8364;';    // euro currency symbol 
        $trans['&rsquo;'] = '&#x92;';
        $trans['&lsquo;'] = '&#x91;';
        $trans['&agrave;'] = '&#192;';
        $trans['&reg;'] = '&#174;';
        $trans['&Atilde;'] = '&#195;';
        $trans['&frac14;'] = '&#188;';
        $trans['&frac12;'] = '&#189;';
        $trans['&frac34;'] = '&#190;';

        ksort($trans);

        foreach ($trans as $k => $v) {
            $text = str_replace($k, $v, $text);
        }

        // 3) remove <p>, <br/> ...
        //$text = strip_tags($text);
        // 4) &amp; => & &quot; => '
        //$text = html_entity_decode($text);
        // 5) remove Windows-1252 symbols like "TradeMark", "Euro"...
        //$text = preg_replace('/[^(\x20-\x7F)]*/', '', $text);
        
        // // ---- 2.2.0.0 -- From xml spec valid chars:
        // #x9 | #xA | #xD | [#x20-#xD7FF] | [#xE000-#xFFFD] | [#x10000-#x10FFFF]
        // any Unicode character, excluding the surrogate blocks, FFFE, and FFFF.
        $text = preg_replace('/[^\x09\x0A\x0D\x20-\xD7FF\xE000-\xFFFD\x10000-x10FFFF]*/', '', $text);
        //$text = preg_replace('/[^\x1D]/', '', $text);
        
        $targets = array('\r\n', '\n', '\r', '\t');
        $results = array(" ", " ", " ", "");
        $text = str_replace($targets, $results, $text);

        //XML compatible
        /*
          $text = str_replace("&", "and", $text);
          $text = str_replace("<", ".", $text);
          $text = str_replace(">", ".", $text);
          $text = str_replace("\\", "-", $text);
          $text = str_replace("/", "-", $text);
         */

        // ---- 2.2.0.0 -- encode again and return
        return mb_convert_encoding($text, 'UTF-8', 'ISO-8859-1');
    }

    public static function printHeader()
    {
        header('HTTP/1.1 200 OK');
        header("Pragma: no-cache");
        header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate');
        header("Content-type: text/xml");
    }

    public static function convertToXmlAndPrint($simpleXml)
    {


        // FOR PROPER FORMATTING XML
        // Create a new DOMDocument object
        $doc = new DOMDocument('1.0', 'utf-8');
        // add spaces, new lines and make the XML more readable format
        $doc->formatOutput = false;
        // Get a DOMElement object from a SimpleXMLElement object
        $domnode = dom_import_simplexml($simpleXml);
        $domnode->preserveWhiteSpace = false;
        $domnode = $doc->importNode($domnode, true);
        // Add new child at the end of the children
        $domnode = $doc->appendChild($domnode);

        // Dump the internal XML tree back into a string
        if (true) { //$this->debug== false
            try {
                $saveXml = $doc->saveXML();
                //$text = preg_replace('/\b&amp;\b/', '&', $text);
                //$text = preg_replace('/\b&\b/', '&amp;', $text);
                echo scxe_utility::cleanString($saveXml);
                //echo ($saveXml);
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        }
    }

    public static function convertToXmlAndPrint_NoEncode($simpleXml)
    {

        // FOR PROPER FORMATTING XML
        // Create a new DOMDocument object
        $doc = new DOMDocument('1.0', 'utf-8');
        // add spaces, new lines and make the XML more readable format
        $doc->formatOutput = false;
        // Get a DOMElement object from a SimpleXMLElement object
        $domnode = dom_import_simplexml($simpleXml);
        $domnode->preserveWhiteSpace = false;
        $domnode = $doc->importNode($domnode, true);
        // Add new child at the end of the children
        $domnode = $doc->appendChild($domnode);

        // Dump the internal XML tree back into a string
        if (true) { //$this->debug== false
            try {
                $saveXml = $doc->saveXML();
                echo($saveXml);
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        }
    }

    public static function toLocalDateString($subvalue)
    {
        global $sql_tbl, $config, $active_modules;
        $subvalue = strftime($config['Appearance']['date_format'], $subvalue) . " " . strftime($config['Appearance']['time_format'], $subvalue);
        return $subvalue;
    }

    public static function convertUnixToW3c($timestamp)
    {
        return date(DATE_W3C, $timestamp);
    }

    public static function convertW3cToUnix($timestamp)
    {
        return strtotime($timestamp);
    }

    public static function lastIndexOf($string, $item)
    {
        $index = strpos(strrev($string), strrev($item));
        if ($index) {
            $index = strlen($string) - strlen($item) - $index;
            return $index;
        } else {
            return -1;
        }
    }
}