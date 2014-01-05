<?php
/*
	Plugin Name: Voguepay WooCommerce Payment Gateway
	Plugin URI: http://bosun.me/voguepay-woocommerce-payment-gateway
	Description: Voguepay Woocommerce Payment Gateway allows you to accept payment on your Woocommerce store via Visa Cards, Mastercards, Verve Cards and eTranzact.
	Version: 1.0.0
	Author: Tunbosun Ayinla
	Author URI: http://bosun.me/
	License:           GPL-2.0+
 	License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 	GitHub Plugin URI: https://github.com/tubiz/voguepay-woocommerce-payment-gateway
*/

if ( ! defined( 'ABSPATH' ) )
	exit;

add_action('plugins_loaded', 'woocommerce_voguepay_init', 0);

function woocommerce_voguepay_init() {

	if ( !class_exists( 'WC_Payment_Gateway' ) ) return;

	/**
 	 * Gateway class
 	 */
	class WC_Tbz_Voguepay_Gateway extends WC_Payment_Gateway {

		public function __construct(){
			global $woocommerce;

			$this->id 					= 'tbz_voguepay_gateway';
    		$this->icon 				= apply_filters('woocommerce_vogueway_icon', plugins_url( 'assets/pay-via-voguepay.png' , __FILE__ ) );
			$this->has_fields 			= false;
        	$this->liveurl 				= 'https://voguepay.com/?p=linkToken'; 
        	$this->method_title     	= 'VoguePay Payment Gateway';
        	$this->method_description  	= 'VoguePay Payment Gateway allows you to receive Mastercard, Verve Card and Visa Card Payments On your Woocommerce Powered Site.';


			// Load the form fields.
			$this->init_form_fields();

			// Load the settings.
			$this->init_settings();


			// Define user set variables
			$this->title 					= $this->get_option( 'title' );
			$this->description 				= $this->get_option( 'description' );
			$this->voguePayMerchantId 		= $this->get_option( 'voguePayMerchantId' );

			//Actions
			add_action('woocommerce_receipt_tbz_voguepay_gateway', array($this, 'receipt_page'));
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

			// Payment listener/API hook
			add_action( 'woocommerce_api_wc_tbz_voguepay_gateway', array( $this, 'check_voguepay_response' ) );
		}

        /**
         * Admin Panel Options
         **/
        public function admin_options(){
            echo '<h3>VoguePay Payment Gateway</h3>';
            echo '<p>VoguePay Payment Gateway allows you to accept payment through various channels such as Interswitch, Mastercard, Verve cards, eTranzact and Visa cards.</p>';
            echo '<table class="form-table">';
            $this->generate_settings_html();
            echo '</table>';
        }


	    /**
	     * Initialise Gateway Settings Form Fields
	    **/

		function init_form_fields(){

			$this->form_fields = array(
			'enabled' => array(
							'title' 			=> 	'Enable/Disable',
							'type' 				=> 	'checkbox',
							'label' 			=>	'Enable VoguePay Payment Gateway',
							'description' 		=> 	'Enable or disable the gateway.',
                    		'desc_tip'      	=> 	true,
							'default' 			=> 	'yes'
						),
				 'title' => array(
								'title' 		=> 	'Title',
								'type' 			=> 	'text',
								'description' 	=> 	'This controls the title which the user sees during checkout.',
                    			'desc_tip'      => 	false,
								'default' 		=>  'VoguePay Payment Gateway'
							), 
				'description' => array(
								'title' 		=> 	'Description',
								'type' 			=> 	'textarea',
								'description' 	=> 	'This controls the description which the user sees during checkout.',
								'default' 		=> 	'Pay Via Voguepay: Accepts Interswitch, Mastercard, Verve cards, eTranzact and Visa cards.'
							), 
							
				'voguePayMerchantId' => array(
								'title' 		=> 	'VoguePay Merchant ID',
								'type' 			=> 	'text',
								'description' 	=> 'Enter Your VoguePay Merchant ID, this can be gotten on your account page when you login on VoguePay' ,
								'default' 		=> '',
                    			'desc_tip'      => true
							)			
			);			
		}



		/**
		 * Get Voguepay Args for passing to Voguepay
		**/
		function get_voguepay_args( $order ) {
			global $woocommerce;

			$order_id 		= $order->id;

			$order_total	= $order->get_total();
			$merchantID 	= $this->voguePayMerchantId;
			$memo        	= "Payment for Order ID: $order_id";
            $notify_url  	= str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'WC_Tbz_Voguepay_Gateway', home_url( '/' ) ) );


			// voguepay Args
			$voguepay_args = array(
				'v_merchant_id' 		=> $merchantID,
				'memo'					=> $memo,
				'total' 				=> $order_total,
				'merchant_ref'			=> $order_id,
				'notify_url'			=> $notify_url,
				'success_url'			=> $notify_url,
				'fail_url'				=> $notify_url
			);

			$voguepay_args = apply_filters( 'woocommerce_voguepay_args', $voguepay_args );
			return $voguepay_args;
		}


	    /**
		 * Generate the VoguePay Payment button link
	    **/
	    function generate_voguepay_form( $order_id ) {
			global $woocommerce;

			$order = new WC_Order( $order_id );
			
			$voguepay_adr = $this->liveurl . '?';
			
			$voguepay_args = $this->get_voguepay_args( $order );

			$voguepay_args_array = array();

			foreach ($voguepay_args as $key => $value) {
				$voguepay_args_array[] = '<input type="hidden" name="'.esc_attr( $key ).'" value="'.esc_attr( $value ).'" />';
			}

			return '<form action="'.esc_url( $voguepay_adr ).'" method="post" id="voguepay_payment_form" target="_top">
					' . implode('', $voguepay_args_array) . '
					<input type="submit" class="button-alt" id="submit_voguepay_payment_form" value="'.__('Make Payment', 'woocommerce').'" /> <a class="button cancel" href="'.esc_url( $order->get_cancel_order_url() ).'">'.__('Cancel order &amp; restore cart', 'woocommerce').'</a>
				</form>';

		}


	    /**
	     * Process the payment and return the result
	    **/
		function process_payment( $order_id ) {

			$order = new WC_Order( $order_id );
	        return array(
	        	'result' => 'success', 
	        	'redirect' => add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(get_option('woocommerce_pay_page_id'))))
	        );

		}


	    /**
	     * Output for the order received page.
	    **/
		function receipt_page( $order ) {
			echo '<p>'.__('Thank you for your order, please click the button below to make payment.', 'woocommerce').'</p>';
			echo $this->generate_voguepay_form( $order );
		}


		/**
		 * Successful Payment!
		**/
		function check_voguepay_response( $posted ){
			global $woocommerce;

			if(isset($_POST['transaction_id']))
			{
				$transaction_id = $_POST['transaction_id'];
				$json = wp_remote_get( 'https://voguepay.com/?v_transaction_id='.$transaction_id.'&type=json');
				$transaction = json_decode($json['body'], true);

				if($transaction['status'] == 'Approved')
				{
					$transaction_id = $transaction['transaction_id'];
					$order_id 		= $transaction['merchant_ref'];
					$order_id 		= (int) $order_id;

	                $order = new WC_Order($order_id);

	                do_action('vwpg_after_payment', $transaction); 

	                if($order->status == 'processing'){

	                }
	                else{
						$order->update_status('processing', 'Payment received, your order is currently being processed.');

	                    $order->add_order_note('Payment Via Voguepay Payment Gateway<br />Transaction ID: '.$transaction_id);
	                    $order->add_order_note($this->msg['message']);

	                    //Add customer order note
	 					$order->add_order_note("Payment Received.<br />Your order is currently being processed.<br />We will be shipping your order to you soon.", 1);
	 					
						// Reduce stock levels
						$order->reduce_order_stock();

						// Empty cart
						$woocommerce->cart->empty_cart();
						$woocommerce->add_message( 'Thank you for shopping with us.<br />Your transaction was successful, payment was received.<br />Your order is currently being processed.' );

						$woocommerce->set_messages();
	                }				
				}

	            else
	            {            	
	                $woocommerce->add_error(' Thank you for shopping with us. <br />However, the transaction was declined, payment wasn\'t recieved.');
	                $woocommerce->set_messages();
	            }   
			}   
			else
			{
	                $woocommerce->add_error(' Thank you for shopping with us. <br />However, the transaction was declined, payment wasn\'t received.');
	                $woocommerce->set_messages();
			}

            $redirect_url = get_permalink(woocommerce_get_page_id('myaccount'));
            wp_redirect( $redirect_url );
            exit;
		}
	}

	/**
 	* Add the Gateway to WooCommerce
 	**/
	function woocommerce_add_voguepay_gateway($methods) {
		$methods[] = 'WC_Tbz_Voguepay_Gateway';
		return $methods;
	}
	
	add_filter('woocommerce_payment_gateways', 'woocommerce_add_voguepay_gateway' );


	/**
	* Add NGN as a currency in Woocommerce
	**/ 
	add_filter( 'woocommerce_currencies', 'add_my_currency' );

	function add_my_currency( $currencies ) {
	     $currencies['NGN'] = __( 'Naira', 'woocommerce' );
	     return $currencies;
	}
	
	 
	/**
	* Enable the Naira currency symbol in Woocommerce
	**/ 
	add_filter('woocommerce_currency_symbol', 'add_my_currency_symbol', 10, 2);	 
	function add_my_currency_symbol( $currency_symbol, $currency ) {
	     switch( $currency ) {
	          case 'NGN': $currency_symbol = '&#8358 '; break;
	     }
	     return $currency_symbol;
	}



	/**
	* Add Settings link to the plugin entry in the plugins menu
	**/ 
	add_filter('plugin_action_links', 'myplugin_plugin_action_links', 10, 2);

	function myplugin_plugin_action_links($links, $file) {
	    static $this_plugin;

	    if (!$this_plugin) {
	        $this_plugin = plugin_basename(__FILE__);
	    }

	    if ($file == $this_plugin) {
	        $settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=woocommerce_settings&tab=payment_gateways&section=WC_Tbz_Voguepay_Gateway">Settings</a>';
	        array_unshift($links, $settings_link);
	    }
	    return $links;
	}
} 