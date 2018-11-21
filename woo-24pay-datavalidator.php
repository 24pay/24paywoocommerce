<?php

if(!class_exists('WOO_24pay_DataValidator'))
{
  class WOO_24pay_DataValidator{

  	private $valid = true;
  	private $errors = array();
    private $params = array('FirstName','FamilyName','Email');

  	public function validate($data){
      $this->errors = array();
  		foreach ($data as $key => $value){
  			if (in_array($key, $this->params)){

  				if ($key == 'Email'){
  					if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
  						$this->errors ['Email'] = "Vami zadaný email nie je platný! [".$value."]";
              $this->valid = false;
  					}
  				}

  				if ($key == 'FamilyName'){
  					if ((strlen($value)<2)||(strlen($value)>50)) {
  						$this->errors ['Priezvisko'] = "Priezvisko musí byť dlhšie ako 2 znaky ale kratšie ako 50 znakov! \"$value\"";
              $this->valid = false;
  					}
  				}

  				if ($key == 'FirstName'){
  					if ((strlen($value)<2)||(strlen($value)>50)) {
  						$this->errors ['Meno'] = "Meno musí byť dlhšie ako 2 znaky ale kratšie ako 50 znakov! \"$value\"";
              $this->valid = false;
  					}
  				}

  			}
  		}
      return $this->valid;
  	}

    public function renderErrors(){
      
      $out = "<h4>Zle zadané údaje</h4>";
      $out .= "<div class='alert alert-danger'><ul>";

      foreach($this->errors as $error){
        $out .= "<li>".$error."</li>";
      }
      $out .= "</ul></div><br>";
      $out .= "<a href='".wc_get_checkout_url()."' class='button button-primary'>Opraviť zadané údaje</a>";

      return $out;
    }

  }
}