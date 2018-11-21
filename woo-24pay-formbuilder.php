<?php

if(!class_exists('WOO_24pay_FormBuilder'))
{
  class WOO_24pay_FormBuilder{
  	public function build($data){
  		$form = '<form id="payment_24pay" name="payment24pay"  action="'.$data['url'].'" method="post">';
	  	foreach($data as $key=>$value){
	  		if ($key != 'url')
        		$form .='<input type="hidden" name="'.esc_attr($key).'" value="'.esc_attr($value).'">';
	  	}
	  	$form .= '<input type="submit" class="button button-primary" id="24pay_submit" value="Zaplatiť">';
	  	$form .= '</form>';

	  	$form .= '<script type="text/javascript">';
	  	$form .= 'document.forms.payment24pay.submit();';
	  	$form .= '</script>';
  		return $form;
  	}
  }
 }