<?php
/*
Plugin Name: Woocommerce 24pay Payment gateway
Plugin URI: http://www.24-pay.sk
Description: 24pay Payment Gateway for WooCommerce e-shop.
Author: 24pay
Version: 1.0.0
Author URI: https://www.24-pay.sk
License: MIT
*/
 
defined( 'ABSPATH' ) or exit;

define( 'PLUGIN_PATH_24PAY', plugin_dir_path( __FILE__ ) );

require_once( PLUGIN_PATH_24PAY . 'woo-24pay-signgenerator.php' );
require_once( PLUGIN_PATH_24PAY . 'woo-24pay-datavalidator.php' );
require_once( PLUGIN_PATH_24PAY . 'woo-24pay-formbuilder.php' );
require_once( PLUGIN_PATH_24PAY . 'woo-24pay-nurlparser.php' );

// Make sure WooCommerce is active
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	return;
}


function woo_24pay_add_to_gateways( $gateways ) {
	$gateways[] = 'Woo_24pay_Gateway';
	return $gateways;
}
add_filter( 'woocommerce_payment_gateways', 'woo_24pay_add_to_gateways' );

function woo_24pay_gateway_plugin_links( $links ) {

	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=24pay_gateway' ) . '">' . __( 'Configure', 'wc-gateway-offline' ) . '</a>'
	);

	return array_merge( $plugin_links, $links );
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'woo_24pay_gateway_plugin_links' );

add_action( 'plugins_loaded', 'woo_24pay_gateway_init', 11 );

function woo_24pay_gateway_init() {

	class Woo_24pay_Gateway extends WC_Payment_Gateway {

		/**
		 * Constructor for the gateway.
		 */
		public function __construct() {
	  
			$this->id                 = '24pay_gateway';
			$this->icon = plugins_url('', __FILE__).'/logos/24pay-icon.png';
			$this->has_fields         = false;
			$this->method_title       = '24pay_gateway';
			$this->method_description = 'Payment gateway 24-pay description.';
		  
			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();
		  
			// Define user set variables
			$this->title        = $this->get_option( 'title' );
			$this->description  = $this->get_option( 'description' );
			$this->instructions = $this->get_option( 'instructions', $this->description );
		  
			// Actions
			add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action('woocommerce_receipt_24pay_gateway', array($this, 'payment_form'));
		}

		public function init_form_fields() {
	  
			$this->form_fields = apply_filters( 'woo_24pay_gateway_form_fields', array(
		  
				'enabled' => array(
					'title'   => 'Enable/Disable',
					'type'    => 'checkbox',
					'label'   => 'Enable 24-pay',
					'default' => 'yes'
				),
				
				'title' => array(
				  'title' => 'Title',
				  'type' => 'text',
				  'description' => 'This controls the title which the user sees during checkout.',
				  'default' => '24-pay | Platobná brána',
				),
				
				'description' => array(
				  'title' => 'Method description',
				  'type' => 'textarea',
				  'description' => 'Method description when selected during checkout.',
				  'default' => 'Zaplaťte bezpečne s vašou kreditnou kartou alebo banovým prevodom pomocou služby 24pay.',
				),
				
				'is_test' => array(
				  'title' => 'Test mode',
				  'type' => 'checkbox',
				  'label' => 'Make payment on test environment (Use only during development!)',
				  'default' => 'yes',
				),
				
				'mid' => array(
					'title'       => 'Mid',
					'type'        => 'text',
					'description' => 'This parameter was send to you via SMS after contract sing.',
					'default'     => 'demoOMED',
					'desc_tip'    => true,
				),
				
				'eshop' => array(
					'title'       => 'EshopId',
					'type'        => 'text',
					'description' => 'This parameter was send to you via SMS after contract sing.',
					'default'     => '11111111',
					'desc_tip'    => true,
				),
				
				'key' => array(
					'title'       => 'Key',
					'type'        => 'text',
					'description' => 'This parameter was send to you via SMS after contract sing.',
					'default'     => '1234567812345678123456781234567812345678123456781234567812345678',
					'desc_tip'    => true,
				),

				'rurl' => array(
				  'title' => 'RURL',
				  'type' => 'text',
				  'description' => 'Specify url to which customer will be redirected after payment.',
				  'default' => get_site_url().'/24pay-rurl/',
				),

				'nurl' => array(
				  'title' => 'NURL',
				  'type' => 'text',
				  'description' => 'Specify url to which you will receive notification message.',
				  'default' => get_site_url().'/24pay-nurl/',
				),

				'notify_email' => array(
				  'title' => 'Notify Email (optional)',
				  'type' => 'text',
				  'description' => 'Set email where you want receive notification ater payment.',
				  'default' => '',
				),
				
			) );
		}

		public function thankyou_page() {
			if ( $this->instructions ) {
				echo wpautop( wptexturize( $this->instructions ) );
			}
		}

		function process_payment($order_id)
	    {
	      $order = wc_get_order($order_id);
	      $redirect_url = add_query_arg('key', $order->order_key, $order->get_checkout_payment_url(true));

	      return array(
	        'result'    => 'success',
	        'redirect'  => $redirect_url
	      );
	    }
	
		function payment_form($order_id)
	    {
	      $order = wc_get_order($order_id);

	      $is_test = (!empty($this->settings['is_test']) && $this->settings['is_test']=='yes') ? true : false;

	      $language = 'SK';
	      $country = 'SVK';

	      $data = array(
	        'Mid' => $this->settings['mid'],
	        'EshopId' => $this->settings['eshop'],
	        'MsTxnId' => $order->get_order_number(),
	        'Amount' => number_format($order->get_total(), 2, '.', ''),
	        'CurrAlphaCode' => get_woocommerce_currency(),
	        'ClientId' => $order->get_order_number(),
	        'FirstName' => $order->get_billing_first_name(),
	        'FamilyName' => $order->get_billing_last_name(),
	        'Email' => $order->get_billing_email(),
	        'Country' => $country,
	        'Timestamp' => date("Y-m-d H:i:s"),
	        'LangCode' => $language,
	        'RedirectSign' => 'true',
	        'RURL' => $this->settings['rurl'],
	        'NURL' => $this->settings['nurl'],
	        'Debug' => 'true',
	      );

	      $signGenerator = new WOO_24pay_SignGenerator($data, $this->settings['key']);
	      $data['Sign'] = $signGenerator->sign();

 		  if (!empty($this->settings['notify_email']))
 		  	$data['NotifyEmail'] = $this->settings['notify_email'];
		  
		  if ($is_test)
			$data['url'] = 'https://test.24-pay.eu/pay_gate/paygt';
		  else
			$data['url'] = 'https://admin.24-pay.eu/pay_gate/paygt';

		  $dataValidator = new WOO_24pay_DataValidator();

		  if ($dataValidator->validate($data)){
		  	$formBuilder = new WOO_24pay_FormBuilder();
		  	echo $formBuilder->build($data);
		  	die();
		  }
		  else{
			echo $dataValidator->renderErrors();
			die();
		  }

		}

		public function load_order_by_mstxnid($order_id)
	    {
	      if(function_exists('wc_sequential_order_numbers'))
	        $order_id = wc_sequential_order_numbers()->find_order_by_order_number($order_id);

	      if(function_exists('wc_seq_order_number_pro'))
	        $order_id = wc_seq_order_number_pro()->find_order_by_order_number($order_id);

	      return wc_get_order($order_id);
	    }

		public function process_rurl($msTxnId){
			$order = $this->load_order_by_mstxnid($msTxnId);
			$order->add_order_note("Client was successfully redirected");

			$redirectTarget = home_url();

			if($order!= false)
      		{
      			$signGenerator = new WOO_24pay_SignGenerator(array('Mid'=>$this->settings['mid']), $this->settings['key']);
      			$message = $_GET['MsTxnId'].$_GET['Amount'].$_GET['CurrCode'].$_GET['Result'];
      			if ($signGenerator->sign($message) == $_GET['Sign']){
      				$redirectTarget = $this->get_return_url($order);
      			}
      			else{
      				wc_add_notice('INVALID REDIRECT SIGN!', 'error');
      			}
      		}

      		wp_safe_redirect($redirectTarget);
        	die();
		}		

		public function process_nurl($xml)
    	{
	      
	      $notification = new WOO_24pay_NurlParser($xml, $this->settings['mid'], $this->settings['key']);
	      if ($notification->parsed){
	      	if ($notification->validateSign()){
	      		
	      		$order = $this->load_order_by_mstxnid($notification->msTxnId);
	      		
	      		/* OK - FAIL - PENDING */
	      		
	      		if($notification->result == 'OK')
		        {
		        	$order->add_order_note("Notification message received with Success result");
		        	$order->payment_complete();
		        }
		        else if($notification->result == 'PENDING')
		        {
		        	$order->add_order_note("Notification message received with Pending result");
		        	$order->update_status('on-hold', '24-pay payment is pending. Payment status will be processed with next notification message.');
		        }
		        else
		        {
		        	$order->add_order_note("Notification message received with Fail result");
		        	$order->update_status('failed', '24-pay payment failed.');
		        }
		        
		        return true;
	      	}
	      }
	      return false;
	    }
		
	  }
  
	  add_action('init', 'listener_24pay');
	  function listener_24pay()
	  {
		$fullUrl = get_site_url().$_SERVER['REQUEST_URI'];
		$httpsUrl="https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		$httpUrl="http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; 
		
		$gateway = new Woo_24pay_Gateway();
	    if(isset($_GET['MsTxnId']) && isset($_GET['Result'])) // RURL
	    {
      		$gateway->process_rurl($_GET['MsTxnId']);
	    }
	    else if(isset($_POST['params'])) // NURL
	    {	
	    	if (($httpsUrl == $gateway->settings['nurl']) || ($httpUrl == $gateway->settings['nurl']) || ($fullUrl == $gateway->settings['nurl'])){
		    	if(!$gateway->process_nurl($_POST['params']))
		        	echo 'FAIL';
		    	else
		    		echo 'OK';
	    	}
		else{
			echo "URL MISMATCH <br/>";
			echo "LISTENING ON: ".$gateway->settings['nurl']. "<br/>";
			echo "HTTPS: ".$httpsUrl. "<br/>";
			echo "HTTP: ".$httpUrl. "<br/>";
			echo "FULL: ".$fullUrl. "<br/>";
		}

	    	die();
	    }
	  }
}
