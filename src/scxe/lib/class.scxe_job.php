<?php

/**
 * Description of scxe_job
 *
 * @author RAMAN
 */
class scxe_job
{
    protected $db;
    protected $read;

    public function __construct()
    {
        foreach ($GLOBALS as $key => $val) {
            global $$key;
        }
    }

    public function __destruct()
    {
    }

    public function updateJobStatus($id, $status)
    {
        $jobId = max(0, (int) $id);
        $jobStatus = max(-1, min(2, (int) $status));
        $query = sprintf("UPDATE job SET status=%d WHERE id=%d", $jobStatus, $jobId);
        try {
            $this->db_execute($query);
            return true;
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }
    }

    public function updateJobSubmitted($id)
    {
        $jobId = max(0, (int) $id);
        $query = sprintf("UPDATE job SET status=1 WHERE id=%d", $jobId);
        try {
            $this->db_execute($query);
            return true;
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }
    }

    public function updateJobCompleted($id)
    {
        $jobId = max(0, (int) $id);
        $query = sprintf("UPDATE job SET processedon=NOW(), status=2 WHERE id=%d", $jobId);
        try {
            $this->db_execute($query);
            return true;
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }
    }

    public function updateJobErrored($id)
    {
        $jobId = max(0, (int) $id);
        $query = sprintf("UPDATE job SET processedon=NOW(), status=-1 WHERE id=%d", $jobId);
        try {
            $this->db_execute($query);
            return true;
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }
    }

    public function installPlugin()
    {
        $q = 'CREATE TABLE IF NOT EXISTS job (
		    id BIGINT NOT NULL  AUTO_INCREMENT,
		    submittedon DATETIME NOT NULL ,
		    processedon DATETIME NULL ,
		    localfilename VARCHAR( 100 ) NOT NULL ,
		    status TINYINT NOT NULL ,
		    logfilename VARCHAR( 100 ) NOT NULL ,
		    module varchar(50) NULL,
	    PRIMARY KEY (  id )
	)';
        $this->db_execute($q);
    }

    public function db_execute($query)
    {
        $dbConnection = \XLite\Core\Database::getEM()->getConnection();
        $dbConnection->executeUpdate($query);
        if ($dbConnection->isConnected()) {
            $dbConnection->close();
        }
        unset($dbConnection);
    }

    public function db_select($sql)
    {
        $dbConnection = \XLite\Core\Database::getEM()->getConnection();
        $dbConnection->beginTransaction();

        $statement = $dbConnection->query($sql);
        $rows = $statement->fetchAll(\PDO::FETCH_BOTH);
        $statement->closeCursor();
        $statement = null;

        $dbConnection->commit();
        return $rows;
    }

    public function insertJob($submittedon, $processedon, $localfilename, $status, $logfilename, $module)
    {
        $submittedon = preg_replace('/[^0-9:\- ]/', '', (string) $submittedon);
        $processedon = $processedon === null ? null : preg_replace('/[^0-9:\- ]/', '', (string) $processedon);
        $localfilename = preg_replace('/[^A-Za-z0-9_\-\.\\\/]/', '', (string) $localfilename);
        $logfilename = preg_replace('/[^A-Za-z0-9_\-\.\\\/]/', '', (string) $logfilename);
        $module = preg_replace('/[^A-Za-z0-9_\-]/', '', (string) $module);
        $status = max(0, (int) $status);

        $query = "INSERT INTO job (id, submittedon, processedon, localfilename, status, logfilename, module) ";
        $query .= " values (NULL, '$submittedon', ";
        $query .= $processedon == null ? "NULL," : "'" . $processedon . "',";
        $query .= "'$localfilename', '$status', '$logfilename', '$module')";

        try {
            $this->db_execute($query);
            $lastId = $this->getMaxJobId();
            return $lastId;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return -1;
    }

    public function getMaxJobId()
    {
        $results = $this->db_select("SELECT MAX( id ) FROM  job ");
        if ($results && count($results)>0) {
            return intval($results[0][0]);
        } else {
            return 0;
        }
    }

    public function getOneSubmittedJob($module)
    {
        $module = $module === null ? null : preg_replace('/[^A-Za-z0-9_\-]/', '', (string) $module);

        if ($module == null) {
            $jobRow = $this->db_select("SELECT * FROM job WHERE status in (0,1) ORDER BY submittedon ASC LIMIT 0,1");
        } elseif ($module == '') {
            $jobRow = $this->db_select("SELECT * FROM job WHERE status in (0,1) and module in('', 'invprice') ORDER BY submittedon ASC LIMIT 0,1");
        } else {
            $jobRow = $this->db_select("SELECT * FROM job WHERE status in (0,1) and module='" . $module . "' ORDER BY submittedon ASC LIMIT 0,1");
        }
        return isset($jobRow[0]) ? $jobRow[0] : null;
    }

    public function getJob($id)
    {
        $jobId = max(0, (int) $id);
        $jobRow = $this->db_select("SELECT * FROM  job WHERE id=" . $jobId . "");

        if ($jobRow == null) {
            return null;
        }
        return $jobRow[0];
    }

    public function getJobs($count)
    {
        $jobCount = max(0, (int) $count);
        $jobRow = $this->db_select("SELECT * FROM  job order by id desc LIMIT 0," . $jobCount . "");
        return $jobRow;
    }

    public function printJobXmlError($err)
    {
        $xml_output = "<?xml version=\"1.0\"?>";
        $xml_output .= "<jobresult><id>0</id><status>error</status><submittedon/><processedon/><localfilename/><logfilename/><products/><errors><error>" . $err . "</error></errors></jobresult>";
        echo $xml_output;
    }

    public function printXml($job_row)
    {
        $xml_output = "";
        $error_output = "<errors>";
        $xml_output .= "<?xml version=\"1.0\"?>\n";

        if ($job_row != null) {
            $xml_output .= "<jobresult>\n";

            $status = "";
            if ($job_row['status'] == -1) {
                $status = "error";
            } elseif ($job_row['status'] == 0) {
                $status = "submitted";
            } elseif ($job_row['status'] == 1) {
                $status = "processing";
            } elseif ($job_row['status'] == 2) {
                $status = "completed";
            }

            $xml_output .= "\t<id>" . $job_row['id'] . "</id>\n";
            $xml_output .= "\t<status>" . $status . "</status>\n";
            $xml_output .= "\t<submittedon>" . $job_row['submittedon'] . "</submittedon>\n";
            $xml_output .= "\t<processedon>" . $job_row['processedon'] . "</processedon>\n";
            $xml_output .= "\t<localfilename>" . $job_row['localfilename'] . "</localfilename>\n";
            $xml_output .= "\t<logfilename>" . $job_row['logfilename'] . "</logfilename>\n";
            $xml_output .= "\t<module>" . $job_row['module'] . "</module>\n";
            //$xml_output .= "<errors>\n";
            //$xmllog = new xmllog(dirname(__FILE__) ."\" . $job_row['logfilename']);
            $logfilepath = str_replace("\\", "/", dirname(__FILE__)) . '/' . $job_row['logfilename'];

            $dom = new DOMDocument();

            try {
                if (file_exists($logfilepath) == false) {
                    throw new Exception("LOG XML: '$logfilepath' does not exist");
                }

                $dom->load($logfilepath);
                if ($dom) {
                    $node = $dom->getElementsByTagName('products')->item(0);
                    if ($node) {
                        $nodeXml = $dom->saveXML($node);
                        $xml_output .="\n\t" . $nodeXml . "\n";
                    } else {
                        $xml_output .= "<products></products>";
                    }
                } else {
                    $error_output .= "<error>$logfilepath could notbe loaded into DOM</error>";
                }
            } catch (Exception $ex) {
                $error_output .= "<error>" . htmlentities($ex->getMessage()) . "</error>";
            }

            //$xmllog=null;

            $error_output .= "</errors>\n";
            $xml_output .= $error_output;
            $xml_output .= "</jobresult>";
        } else {
            $xml_output .= "<jobresult><id>0</id><status></status><submittedon/><processedon/><localfilename/><logfilename/><products/><errors></errors></jobresult>";
        }

        echo $xml_output;
    }

    public function getLogFolderPath()
    {
        $_filePath = str_replace("\\", "/", SCXE_LOG);
        if (file_exists($_filePath . ".htaccess") == false) {
            $fp = fopen($_filePath . ".htaccess", "w");
            fwrite($fp, "Allow from all");
            fclose($fp);
        }

        return $_filePath;
    }

    public function getJobXml2($jobarray)
    {
        $error_output = '';
        $jobresult = new SimpleXMLElement("<jobresult></jobresult>");
        //var_dump($jobresult);
        $products = new SimpleXMLElement("<products/>");
        $job = $jobarray;
        $id = 0;
        $status = '';
        $submittedon = '';
        $processedon = '';
        $localfilename = '';
        $logfilename = '';

        if ($job["id"] != null) {
            if ($job['status'] == -1) {
                $status = "error";
            } elseif ($job['status'] == 0) {
                $status = "submitted";
            } elseif ($job['status'] == 1) {
                $status = "processing";
            } elseif ($job['status'] == 2) {
                $status = "completed";
            }

            $id = $job['id'];
            $submittedon = $job['submittedon'];
            $processedon = $job['processedon'];
            $localfilename = $job['localfilename'];
            $logfilename = $job['logfilename'];
            $module = $job['module'];

            $logfilepath = str_replace("\\", "/", $this->getLogFolderPath()) . $job['logfilename'];
        }


        try {
            //var_dump($jobarray);
            $jobresult->addChild("id", $id);
            $jobresult->addChild("status", $status);
            $jobresult->addChild('submittedon', $submittedon);
            $jobresult->addChild('processedon', $processedon);
            $jobresult->addChild('localfilename', $localfilename);
            $jobresult->addChild('logfilename', $logfilename);
            $jobresult->addChild('module', $module);
            //$jobresult->addChild('products', '<da/>');

            if ($status == 'submitted' || $status == "") {
            } else {
                if (file_exists($logfilepath) == false) {
                    $error_output = ("LOG XML: '$logfilepath' does not exist");
                } else {
                    $products_source = simplexml_load_file($logfilepath);
                    //var_dump($products);
                    $productsXml = $jobresult->addChild("products");
                    foreach ($products_source->product as $product_source) {
                        $productXml = $productsXml->addChild("product");
                        $_sku = (string) $product_source->sku;
                        $_status = (string) $product_source->status;

                        $productXml->addChild("sku", $_sku);
                        $productXml->addChild("status", $_status);
                        //echo $product_source->errors->count();

                        $productXml_error = $productXml->addChild("errors");
                        if (isset($product_source->errors) && $product_source->errors->children()->count() > 0) {
                            foreach ($product_source->errors as $product_source_error) {
                                $productXml_error->addChild("error", (string) $product_source_error->error);
                            }
                        }
                    }
                }
            }
        } catch (Exception $ex) {
            $error_output = htmlentities($ex->getMessage());
        }

        $errors = $jobresult->addChild("errors");
        if ($error_output != "") {
            $errors->addChild("error", (string) $error_output);
        }

        return $jobresult;
    }

    public function getJobXml($jobarray)
    {
        $xml_output = "";
        $error_output = "<errors>";
        $xml_output .= "<?xml version=\"1.0\"?>\n";
        $job = $jobarray;
        if ($job == null) {
            $xml_output .= "<jobresult><id>0</id><status></status><submittedon/>"
                    . "<processedon/><localfilename/><logfilename/><products/><errors></errors></jobresult>";
            return $xml_output;
        }

        if ($job["id"] != null) {
            $xml_output .= "<jobresult>\n";

            $status = "";
            if ($job['status'] == -1) {
                $status = "error";
            } elseif ($job['status'] == 0) {
                $status = "submitted";
            } elseif ($job['status'] == 1) {
                $status = "processing";
            } elseif ($job['status'] == 2) {
                $status = "completed";
            }

            $xml_output .= "\t<id>" . $job['id'] . "</id>\n";
            $xml_output .= "\t<status>" . $status . "</status>\n";
            $xml_output .= "\t<submittedon>" . $job['submittedon'] . "</submittedon>\n";
            $xml_output .= "\t<processedon>" . $job['processedon'] . "</processedon>\n";
            $xml_output .= "\t<localfilename>" . $job['localfilename'] . "</localfilename>\n";
            $xml_output .= "\t<logfilename>" . $job['logfilename'] . "</logfilename>\n";
            $xml_output .= "\t<module>" . $job['module'] . "</module>\n";
            //$xml_output .= "<errors>\n";
            //$xmllog = new xmllog(dirname(__FILE__) ."\" . $job['logfilename']);
            $logfilepath = str_replace("\\", "/", $this->getLogFolderPath()) . $job['logfilename'];

            $dom = new DOMDocument();

            try {
                if ($job['status'] == 0) {
                } else {
                    if (file_exists($logfilepath) == false) {
                        throw new Exception("LOG XML: '$logfilepath' does not exist");
                    } else {
                        $dom->load($logfilepath);
                        if ($dom) {
                            $node = $dom->getElementsByTagName('products')->item(0);
                            if ($node) {
                                $nodeXml = $dom->saveXML($node);
                                $xml_output .="\n\t" . $nodeXml . "\n";
                            } else {
                                $xml_output .= "<products></products>";
                            }
                        } else {
                            $error_output .= "<error>$logfilepath could not be loaded into DOM</error>";
                        }
                    }
                }
            } catch (Exception $ex) {
                $error_output .= "<error>" . htmlentities($ex->getMessage()) . "</error>";
            }

            //$xmllog=null;

            $error_output .= "</errors>\n";
            $xml_output .= $error_output;
            $xml_output .= "</jobresult>";
        } else {
            //echo "hjere";
            $xml_output .= "<jobresult><id>0</id><status></status><submittedon/>"
                    . "<processedon/><localfilename/><logfilename/><products/><errors></errors></jobresult>";
        }

        return $xml_output;
    }

    public function getVersion()
    {
        foreach ($GLOBALS as $key => $val) {
            global $$key;
        }
        $store_version = func_query_first_cell("SELECT value FROM $sql_tbl[config] WHERE name='version'");
        return $store_version;
    }
}
