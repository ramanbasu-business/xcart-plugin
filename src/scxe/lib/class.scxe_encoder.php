<?php

class scxe_encoder
{
    private $encryption = 0;
    private $key; 
    private $tripleDes;
    private $scxe_utility = null;
    private $verbose = 0;

    public function __construct()
    {
        $this->tripleDes = new scxe_tripledes();
        $this->scxe_utility = new scxe_utility();
        $this->key = '';
    }

    public function authenticate() {
        /*         * *******************************************************
         * 	Query string validation
         * ******************************************************* */
        
        $this->encryption = 0;
        $qryStrArray = array();
        $q = $_SERVER["QUERY_STRING"];
        
        parse_str($q, $qryStrArray);
        //var_dump($qryStrArray);
        
        if(count($qryStrArray)==1):
            reset($qryStrArray);
            $first_key = key($qryStrArray);
            if($first_key !== "" && $qryStrArray[$first_key]==""):
                $this->encryption = 1;
            endif;
        endif;
        
        //var_dump($this->encryption);
        $this->verbose = 0;
        $qryStrArray2 = $this->validateLogin();
        //var_dump($qryStrArray);

        if ($qryStrArray2 == NULL) {
            return NULL;
        } else {
            return $qryStrArray2;
        }
        /*         * ******************************************************** */
        //*	@ End of Query string validation
    }
    
    public function validateLogin() {
        $msg = "<?xml version=\"1.0\"?><response>"
                . $this->scxe_utility->getVersionXml()
                . "<status>failure</status><message>%s</message></response>";
        try {
            $qryStrArray2 = $this->getQueryStringArrayByDecryption();
            
            
            
            if ($qryStrArray2 == NULL || empty($qryStrArray2)) {
                echo sprintf($msg, "invalid login");
                return NULL;
            }

            if (!isset($qryStrArray2["u"])) {
                echo sprintf($msg, "invalid user");
                return NULL;
            }
            if (!isset($qryStrArray2["p"])) {
                echo sprintf($msg, "invalid password");
                return NULL;
            }

            if (empty($qryStrArray2["u"])) {
                echo sprintf($msg, 'Required parameter "u" is missing');
                return NULL;
            }
            if (empty($qryStrArray2["p"])) {
                echo sprintf($msg, 'Required parameter "p" is missing');
                return NULL;
            }
            if ($qryStrArray2["u"] == "") {
                echo sprintf($msg, 'Required parameter "u" is missing');
                return NULL;
            }
            if ($qryStrArray2["p"] == "") {
                echo sprintf($msg, 'Required parameter "p" is missing');
                return NULL;
            }

            $email = trim((string) $qryStrArray2["u"]);
            $email = preg_replace('/[\x00-\x1F\x7F]/', '', $email);
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo sprintf($msg, 'Invalid user');
                return NULL;
            }

            $adminPassword = trim((string) $qryStrArray2["p"]);
            $adminPassword = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $adminPassword);
            if (strlen($adminPassword) === 0 || strlen($adminPassword) > 255) {
                echo sprintf($msg, 'Invalid password');
                return NULL;
            }

            if ($this->dbLogin($email, $adminPassword)) {
                return $qryStrArray2;
            } else {
                echo (sprintf($msg, "Authentications failed"));
                return NULL;
            }
        } catch (Exception $ex) {
            echo sprintf($msg, $ex->getMessage());
            return NULL;
        }
    }
    
    public function getQueryStringArrayByDecryption() {
        $qryStrArray = array();
        $q = $_SERVER["QUERY_STRING"];
        

        if (empty($q)) {
            return NULL;
        }

        //if ($this->verbose == 1) {
        //    echo '<br>q before url decode ' . $q;
        //}
        
        // cannot urldecode. each query string param can contain ? and & characters. If 
        // we decode now, additional element may be created in the array.
        //$q = urldecode($q);
        
        //if ($this->verbose == 1) {
        //    echo '<br>q after url decode ' . $q;
        //}

        // proceed with decryption of query string
        try {
            // Decide of we need encryption
            if ($this->encryption == 1):
                $strDecrypted = $this->decrypt(urldecode($q)); //echo $qryString;
                //echo ' decrypted string ' . $strDecrypted;
                //if ($this->verbose == 1) {
                //    echo ' decrypted string ' . $strDecrypted;
                //}
                
                if (!empty($strDecrypted)):
                    $qryString = "".$strDecrypted;
                    
                    // removed Nov 13, 2017
                    //$qryString = urldecode($qryString);
                endif;
                $qryString = $this->cleanString($qryString);
                parse_str($qryString, $qryStrArray);
                
            else:
                parse_str($q, $qryStrArray);
                //var_dump($qryStrArray);
            endif;

            //var_dump($qryStrArray);
            // added Nov 13, 2017
            $qryStrArray = array_map(function($val) { return urldecode($val); }, $qryStrArray);
            $qryStrArray = $this->makeParamLower($qryStrArray);
            
            return $qryStrArray;
        } catch (Exception $ex) {
            echo "<response><status>failure</status><message>" . $ex->getMessage() . "</message></response>";
            return $qryStrArray;
        }
    }
    
    function encrypt($string) {
        $phpEncrypted = $this->tripleDes->Encrypt($string, $this->key);
        return $phpEncrypted;
    }

    function decrypt($vbEncrypted) {
        $phpDecrypted = $this->tripleDes->Decrypt($vbEncrypted, $this->key);
        return $phpDecrypted;
    }

    private function makeParamLower($qryStrArray) {
        return array_change_key_case($qryStrArray, CASE_LOWER);
    }

    /*     * *******************************************************
     * 	Query string validation
     * ******************************************************* */

    function isValid($str) {
        return !preg_match('/[^A-Za-z0-9.#\\-$]/', $str);
    }

    public function dbLogin($email, $password)
    {
        $allow_login = FALSE;
        //$profile = \XLite\Core\Auth::getInstance()->loginAdministrator($username, $password);
        //$profile = \XLite\Core\Database::getRepo('XLite\Model\Profile')->findByLoginPassword($username, null, 0);
        list($profile, $result) = \XLite\Core\Auth::getInstance()->checkLoginPassword($email, $password);
        
        if (isset($profile) && $result === true) {
            $isAdmin = \XLite\Core\Auth::getInstance()->isAdmin($profile);
            $allow_login = $isAdmin;
        }
                
        return $allow_login;
    }

    
}