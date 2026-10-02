<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of scxe_tripledes
 *
 * @author raman
 */

class scxe_tripledes {

    private $bPassword;
    private $sPassword;

    function __construct() {
        $Password = "";
        $this->bPassword = md5($Password, TRUE);
        $this->bPassword .= substr($this->bPassword, 0, 8);
        $this->sPassword = $Password;
    }

    function setSalt($salt) {
        $this->bPassword = md5($salt, TRUE);
        $this->bPassword .= substr($this->bPassword, 0, 8);
        $this->sPassword = $salt;
    }

    function PasswordHash() {
        return $this->bPassword;
    }

    function Encrypt($Message, $salt) {
        if ($salt <> "") {
            $this->setSalt($salt);
        }
        $size = mcrypt_get_block_size('tripledes', 'ecb');
        $padding = $size - ((strlen($Message)) % $size);
        $Message .= str_repeat(chr($padding), $padding);
        $encrypt = mcrypt_encrypt('tripledes', $this->bPassword, $Message, 'ecb');
        return base64_encode($encrypt);
    }

    function Decrypt($message, $salt) {
        if ($salt <> "") {
            $this->setSalt($salt);
        }

        try{
            return mcrypt_decrypt('tripledes', $this->bPassword, base64_decode($message), 'ecb');
        } catch (Exception $ex) {
            return false;
        }
    }

}
