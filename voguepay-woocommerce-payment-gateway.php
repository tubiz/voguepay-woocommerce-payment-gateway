<?php
/*
	Plugin Name: Voguepay WooCommerce Payment Gateway
	Plugin URI: http://bosun.me/voguepay-woocommerce-payment-gateway
	Description: Voguepay Woocommerce Payment Gateway allows you to accept payment on your Woocommerce store via Visa Cards, Mastercards, Verve Cards and eTranzact.
	Version: 1.1.0
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
	        	'redirect' => add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, $order->get_checkout_payment_url( true )))
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
		 * Verify a successful Payment!
		**/
		function check_voguepay_response( $posted ){
			global $woocommerce;

			if(isset($_POST['transaction_id']))
			{
				$transaction_id = $_POST['transaction_id'];
				$json = wp_remote_get( 'https://voguepay.com/?v_transaction_id='.$transaction_id.'&type=json');
				$transaction = json_decode($json['body'], true);

				$transaction_id = $transaction['transaction_id'];
				$order_id 		= $transaction['merchant_ref'];
				$order_id 		= (int) $order_id;

		            	$order 			= new WC_Order($order_id);
		            	$order_total	= $order->get_total();

				$amount_paid 	= $transaction['total'];

				if($transaction['status'] == 'Approved')
				{					

					// check if the amount paid is equal to the order amount.
					if($order_total != $amount_paid)
					{
			            //after payment hook
		                do_action('tbz_voguepay_after_payment', $transaction); 
							
		                //Update the order status
						$order->update_status('on-hold', '');

						//Error Note
						$message = 'Thank you for shopping with us.<br />Your payment transaction was successful, but the amount paid is not the same as the total order amount.<br />Your order is currently on-hold.<br />Kindly contact us for more information regarding your order and payment status.';

						//Add Customer Order Note
	                    $order->add_order_note($message.'<br />Voguepay Transaction ID: '.$transaction_id, 1);
	                    
	                    //Add Admin Order Note
	                    $order->add_order_note('This order is currently on hold.<br />Reason: Amount paid is less than the total order amount.<br />Amount Paid was: &#8358; '.$amount.' while the total order amount is: &#8358; '.$posted_amount.'<br />Voguepay Transaction ID: '.$transaction_id);
							
						// Reduce stock levels
						$order->reduce_order_stock();

						// Empty cart
						$woocommerce->cart->empty_cart();

						if ( function_exists( 'wc_add_notice' ) ) {
							wc_add_notice( $message, 'error' );

						} else { // WC < 2.1
							$woocommerce->add_error( $message );
							$woocommerce->set_messages();
						}
					}
					else
					{
		                //after payment hook
		                do_action('tbz_voguepay_after_payment', $transaction); 

		                if($order->status == 'processing'){
		                    $order->add_order_note('Payment Via Voguepay<br />Transaction ID: '.$transaction_id);

		                    //Add customer order note
		 					$order->add_order_note('Payment Received.<br />Your order is currently being processed.<br />We will be shipping your order to you soon.<br />Voguepay Transaction ID: '.$transaction_id, 1);
							
							// Reduce stock levels
							$order->reduce_order_stock();

							// Empty cart
							$woocommerce->cart->empty_cart();

							$message = 'Thank you for shopping with us.<br />Your transaction was successful, payment was received.<br />Your order is currently being processed.';

							if ( function_exists( 'wc_add_notice' ) ) {
								wc_add_notice( $message, 'success' );

							} else { // WC < 2.1
								$woocommerce->add_message( $message );
								$woocommerce->set_messages();
							}
		                }
		                else{
							$order->update_status('processing', 'Payment received, your order is currently being processed.');

		                    $order->add_order_note('Payment Via Voguepay Payment Gateway<br />Transaction ID: '.$transaction_id);

		                    //Add customer order note
		 					$order->add_order_note('Payment Received.<br />Your order is currently being processed.<br />We will be shipping your order to you soon.<br />Voguepay Transaction ID: '.$transaction_id, 1);
		 					
							// Reduce stock levels
							$order->reduce_order_stock();

							// Empty cart
							$woocommerce->cart->empty_cart();

							$message = 'Thank you for shopping with us.<br />Your transaction was successful, payment was received.<br />Your order is currently being processed.';

							if ( function_exists( 'wc_add_notice' ) ) {
								wc_add_notice( $message, 'success' );

							} else { // WC < 2.1
								$woocommerce->add_message( $message );
								$woocommerce->set_messages();
							}
		                }	
	                }			
				}

	            else
	            {            
	            	$message = 	'Thank you for shopping with us. <br />However, the transaction wasn\'t successful, payment wasn\'t recieved.';
					$transaction_id = $transaction['transaction_id'];

					//Add Customer Order Note
                    $order->add_order_note($message.'<br />Voguepay Transaction ID: '.$transaction_id, 1);
                    
                    //Add Admin Order Note
                    $order->add_order_note($message.'<br />Voguepay Transaction ID: '.$transaction_id);

					if ( function_exists( 'wc_add_notice' ) ) 
					{
						wc_add_notice( $message, 'error' );

					} 
					else // WC < 2.1
					{ 
						$woocommerce->add_error( $message );
						$woocommerce->set_messages();
					}
	            }   
			}   
			else
			{
            	$message = 	'Thank you for shopping with us. <br />However, the transaction wasn\'t successful, payment wasn\'t recieved.';

				if ( function_exists( 'wc_add_notice' ) ) 
				{
					wc_add_notice( $message, 'error' );

				} 
				else // WC < 2.1
				{ 
					$woocommerce->add_error( $message );
					$woocommerce->set_messages();
				}
			}

            $redirect_url = get_permalink(woocommerce_get_page_id('myaccount'));
            wp_redirect( $redirect_url );
            exit;
		}
	}

	/**
 	* Add Voguepay Gateway to WC
 	**/
	function woocommerce_add_voguepay_gateway($methods) {
		$methods[] = 'WC_Tbz_Voguepay_Gateway';
		return $methods;
	}
	
	add_filter('woocommerce_payment_gateways', 'woocommerce_add_voguepay_gateway' );

	
	/**
	 * only add the naira currency and symbol if WC versions is less than 2.1
	 */
	if ( version_compare( WOOCOMMERCE_VERSION, "2.1" ) <= 0 ) {

		/**
		* Add NGN as a currency in WC
		**/ 
		add_filter( 'woocommerce_currencies', 'tbz_add_my_currency' );

		if( ! function_exists( 'tbz_add_my_currency' )){
			function tbz_add_my_currency( $currencies ) {
			     $currencies['NGN'] = __( 'Naira', 'woocommerce' );
			     return $currencies;
			}
		}
				 
		/**
		* Enable the naira currency symbol in WC
		**/ 

		add_filter('woocommerce_currency_symbol', 'tbz_add_my_currency_symbol', 10, 2);	 
		
		if( ! function_exists( 'tbz_add_my_currency_symbol' ) ){
			function tbz_add_my_currency_symbol( $currency_symbol, $currency ) {
			     switch( $currency ) {
			          case 'NGN': $currency_symbol = '&#8358; '; break;
			     }
			     return $currency_symbol;
			}
		}
	} 


	/**
	* Add a settings link to the plugin entry in the plugins menu
	**/ 
	add_filter('plugin_action_links', 'tbz_voguepay_plugin_action_links', 10, 2);

	function tbz_voguepay_plugin_action_links($links, $file) {
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
