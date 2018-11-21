<?php

if(!class_exists('WOO_24pay_SignGenerator'))
{
  class WOO_24pay_SignGenerator{
  	private $data;
  	private $key;
  	private $iv;

	function __construct($data, $key)
    {
    	$this->data = $data;
    	$this->key = $key;

    	$this->iv = $data['Mid'] . strrev($data['Mid']);
		  $this->key = pack('H*', $key);
    }

    public function sign($message=''){
    	if (empty($message))
    		$hash = hash("sha1", $this->buildMessage(), true);
    	else
    		$hash = hash("sha1", $message, true);

		if ( PHP_VERSION_ID >= 50303 && extension_loaded( 'openssl' ) ) {
			$crypted = openssl_encrypt( $hash, 'AES-256-CBC', $this->key, 1, $this->iv );
		} else {
			$crypted = mcrypt_encrypt( MCRYPT_RIJNDAEL_128, $this->key, $hash, MCRYPT_MODE_CBC, $this->iv );
		}
		$sign = strtoupper(bin2hex(substr($crypted, 0, 16)));
		return $sign;
    }

    private function buildMessage(){
		return $this->data['Mid'].$this->data['Amount'].$this->data['CurrAlphaCode'].$this->data['MsTxnId'].$this->data['FirstName'].$this->data['FamilyName'].$this->data['Timestamp'];
    }
  }
 }