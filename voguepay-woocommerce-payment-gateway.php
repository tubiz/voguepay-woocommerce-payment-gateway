<?php
/*
	Plugin Name: Voguepay WooCommerce Payment Gateway
	Plugin URI: https://bosun.me/voguepay-woocommerce-payment-gateway
	Description: Voguepay Woocommerce Payment Gateway allows you to accept payment on your Woocommerce store via Visa Card, MasterCard and Verve Card.
	Version: 4.1.0
	Author: Tunbosun Ayinla
	Author URI: https://bosun.me/
	License:           GPL-2.0+
 	License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 	GitHub Plugin URI: https://github.com/tubiz/voguepay-woocommerce-payment-gateway
*/

if ( ! defined( 'ABSPATH' ) )
	exit;

add_action('plugins_loaded', 'tbz_wc_voguepay_init', 0);

function tbz_wc_voguepay_init() {

	if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;

	/**
 	 * Gateway class
 	 */
	class WC_Tbz_Voguepay_Gateway extends WC_Payment_Gateway {

		public function __construct(){

			$this->id 					= 'tbz_voguepay_gateway';
    		$this->icon 				= apply_filters('woocommerce_vogueway_icon', plugins_url( 'assets/pay-via-voguepay.png' , __FILE__ ) );
			$this->has_fields 			= false;
			$this->order_button_text 	= 'Make Payment';
        	$this->payment_url 			= 'https://voguepay.com/pay/';
			$this->notify_url        	= WC()->api_request_url( 'WC_Tbz_Voguepay_Gateway' );
        	$this->method_title     	= 'VoguePay';
        	$this->method_description  	= 'MasterCard, Verve Card and Visa Card accepted';

			// Load the form fields.
			$this->init_form_fields();

			// Load the settings.
			$this->init_settings();

			// Define user set variables
			$this->title 					= $this->get_option( 'title' );
			$this->description 				= $this->get_option( 'description' );
			$this->voguePayMerchantId 		= $this->get_option( 'voguePayMerchantId' );
			$this->storeId 					= $this->get_option( 'storeId' );

			//Actions
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

			// Payment listener/API hook
			add_action( 'woocommerce_api_wc_tbz_voguepay_gateway', array( $this, 'check_voguepay_response' ) );

			// Check if the gateway can be used
			if ( ! $this->is_valid_for_use() ) {
				$this->enabled = false;
			}
		}


		public function is_valid_for_use() {

			if( ! in_array( get_woocommerce_currency(), array( 'NGN', 'USD' ) ) ) {

				$this->msg = 'Voguepay doesn\'t support your store currency, set it to Nigerian Naira &#8358 or United State Dollars &#36 <a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=wc-settings&tab=general">here</a>';

				return false;

			}

			return true;
		}


		/**
		 * Check if this gateway is enabled
		 */
		public function is_available() {

			if ( $this->enabled == "yes" ) {

				if ( ! $this->voguePayMerchantId ) {
					return false;
				}
				return true;
			}

			return false;
		}


        /**
         * Admin Panel Options
         **/
        public function admin_options() {
            echo '<h3>VoguePay</h3>';
            echo '<p>VoguePay allows you to accept payment through various methods such as Interswitch, MasterCard, Verve card, eTranzact and Visa Card.</p>';

			if ( $this->is_valid_for_use() ){
	            echo '<table class="form-table">';
	            $this->generate_settings_html();
	            echo '</table>';
            }
			else{	 ?>
			<div class="inline error"><p><strong>Voguepay Payment Gateway Disabled</strong>: <?php echo $this->msg ?></p></div>

			<?php }
        }


	    /**
	     * Initialise Gateway Settings Form Fields
	    **/
		public function init_form_fields() {
			$this->form_fields = array(
				'enabled' => array(
					'title' 		=> 'Enable/Disable',
					'type' 			=> 'checkbox',
					'label' 		=> 'Enable VoguePay Payment Gateway',
					'description' 	=> 'Enable or disable the gateway.',
            		'desc_tip'      => true,
					'default' 		=> 'yes'
				),
				'title' => array(
					'title' 		=> 'Title',
					'type' 			=> 'text',
					'description' 	=> 'This controls the title which the user sees during checkout.',
        			'desc_tip'      => false,
					'default' 		=> 'VoguePay Payment Gateway'
				),
				'description' => array(
					'title' 		=> 'Description',
					'type' 			=> 'textarea',
					'description' 	=> 'This controls the description which the user sees during checkout.',
					'default' 		=> 'MasterCard, Verve Card and Visa Card accepted'
				),
				'voguePayMerchantId' => array(
					'title' 		=> 'VoguePay Merchant ID',
					'type' 			=> 'text',
					'description' 	=> 'Enter Your VoguePay Merchant ID, this can be gotten on your account page when you login on VoguePay' ,
					'default' 		=> '',
        			'desc_tip'      => true
				),
				'storeId' => array(
					'title' 		=> 'Store ID',
					'type' 			=> 'text',
					'description' 	=> 'Enter Your Store ID here, if you have created a unique store within your Voguepay account.' ,
					'default' 		=> '',
        			'desc_tip'      => true
				),
			);
		}


		/**
		 * Get voguepay args
		**/
		public function get_voguepay_args( $order ) {

            $order_id 		= method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;

			$order_total	= $order->get_total();
			$merchantID 	= $this->voguePayMerchantId;
			$memo        	= "Payment for Order ID: $order_id on ". get_bloginfo('name');
            $notify_url  	= $this->notify_url;

			$success_url  	= $this->get_return_url( $order );

			$fail_url	  	= $this->get_return_url( $order );

			$store_id 		= $this->storeId  ? $this->storeId : '';

			// voguepay Args
			$voguepay_args = array(
				'v_merchant_id' 		=> $merchantID,
				'cur' 					=> get_woocommerce_currency(),
				'memo'					=> $memo,
				'total' 				=> $order_total,
				'merchant_ref'			=> $order_id,
				'notify_url'			=> $notify_url,
				'success_url'			=> $success_url,
				'fail_url'				=> $fail_url,
				'store_id'				=> $store_id
			);

			$voguepay_args = apply_filters( 'woocommerce_voguepay_args', $voguepay_args );
			return $voguepay_args;
		}


	    /**
	     * Process the payment and return the result
	    **/
		public function process_payment( $order_id ) {

			$response = $this->get_payment_link( $order_id );

			if( 'success' == $response['result'] ) {

		        return array(
		        	'result' 	=> 'success',
					'redirect'	=> $response['redirect']
		        );

			} else {

				wc_add_notice( 'Unable to connect to the payment gateway, please try again.', 'error' );

		        return array(
		        	'result' 	=> 'fail',
					'redirect'	=> ''
		        );

			}
		}


	    /**
	     * Get Voguepay payment link
	    **/
		public function get_payment_link( $order_id ) {

			$order = wc_get_order( $order_id );

			$voguepay_args = $this->get_voguepay_args( $order );

        	$voguepay_redirect  = 'https://voguepay.com/?p=linkToken&';
		    $voguepay_redirect .= http_build_query( $voguepay_args );

		    $args = array(
		        'timeout'   => 60
		    );

	    	$request = wp_remote_get( $voguepay_redirect, $args );

	    	$valid_url = strpos( $request['body'], 'https://voguepay.com/pay' );

          	if ( ! is_wp_error( $request ) && 200 == wp_remote_retrieve_response_code( $request ) && $valid_url !== false ) {

		        $response = array(
		        	'result'	=> 'success',
		        	'redirect'	=> $request['body']
		        );

          	} else {

		        $response = array(
		        	'result'	=> 'fail',
		        	'redirect'	=> ''
		        );

          	}

		    return $response;
		}


		/**
		 * Verify a successful Payment!
		**/
		public function check_voguepay_response() {

			if( isset( $_POST['transaction_id'] ) ) {

				$transaction_id = $_POST['transaction_id'];

				$args = array( 'timeout' => 60 );

				if( 'demo' == $this->voguePayMerchantId ) {

					$json = wp_remote_get( 'https://voguepay.com/?v_transaction_id='.$transaction_id.'&type=json&demo=true', $args );

				} else {

					$json = wp_remote_get( 'https://voguepay.com/?v_transaction_id='.$transaction_id.'&type=json', $args );

				}

				$transaction 	= json_decode( $json['body'], true );
				$transaction_id = $transaction['transaction_id'];
				$order_id 		= $transaction['merchant_ref'];
				$order_id 		= (int) $order_id;

		        $order 			= wc_get_order($order_id);
		        $order_total	= $order->get_total();

				$amount_paid 	= $transaction['total'];

	            //after payment hook
                do_action( 'tbz_wc_voguepay_after_payment', $transaction );

				if( $transaction['status'] == 'Approved' ) {

					if( $transaction['merchant_id'] != $this->voguePayMerchantId ) {

		                //Update the order status
						$order->update_status('on-hold', '');

						//Error Note
						$message = 'Thank you for shopping with us.<br />Your payment transaction was successful, but the amount was paid to the wrong merchant account. Illegal hack attempt.<br />Your order is currently on-hold.<br />Kindly contact us for more information regarding your order and payment status.';
						$message_type = 'notice';

	                    //Add Admin Order Note
	                    $order->add_order_note('Look into this order. <br />This order is currently on hold.<br />Reason: Illegal hack attempt. The order was successfull but the money was paid to the wrong Voguepay account.<br /> Your Voguepay Merchant ID '. $this->voguePayMerchantId .' the Merchant ID the payment was sent to '.$transaction['merchant_id'].'<br />Voguepay Transaction ID: '.$transaction_id);

		                add_post_meta( $order_id, '_transaction_id', $transaction_id, true );

						// Reduce stock levels
						$order->reduce_order_stock();

						// Empty cart
						wc_empty_cart();

					} else {

						// check if the amount paid is equal to the order amount.
						if( $amount_paid < $order_total ) {

			                //Update the order status
							$order->update_status( 'on-hold', '' );

							//Error Note
							$message = 'Thank you for shopping with us.<br />Your payment transaction was successful, but the amount paid is not the same as the total order amount.<br />Your order is currently on-hold.<br />Kindly contact us for more information regarding your order and payment status.';
							$message_type = 'notice';

		                    //Add Admin Order Note
		                    $order->add_order_note('Look into this order. <br />This order is currently on hold.<br />Reason: Amount paid is less than the total order amount.<br />Amount Paid was &#8358;'.$amount_paid.' while the total order amount is &#8358;'.$order_total.'<br />Voguepay Transaction ID: '.$transaction_id);

		                    add_post_meta( $order_id, '_transaction_id', $transaction_id, true );

							// Reduce stock levels
							$order->reduce_order_stock();

							// Empty cart
							wc_empty_cart();

						} else {

            				$order->payment_complete( $transaction_id );

		                    //Add admin order note
		                    $order->add_order_note( 'Payment Via Voguepay.<br />Transaction ID: '.$transaction_id );

							$message = 'Payment was successful.';
							$message_type = 'success';

							// Empty cart
							wc_empty_cart();
		                }
					}

	                $voguepay_message = array(
	                	'message'		=> $message,
	                	'message_type' 	=> $message_type
	                );

					update_post_meta( $order_id, '_tbz_voguepay_message', $voguepay_message );

                    die( 'IPN Processed OK. Payment Successfully' );

				} else {

					$message = 'Payment failed.';
					$message_type = 'error';

					$transaction_id = $transaction['transaction_id'];

                    //Add Admin Order Note
                  	$order->add_order_note($message.'<br />Voguepay Transaction ID: '.$transaction_id);

	                //Update the order status
					$order->update_status( 'failed', '' );

	                $voguepay_message = array(
	                	'message'		=> $message,
	                	'message_type' 	=> $message_type
	                );

					update_post_meta( $order_id, '_tbz_voguepay_message', $voguepay_message );

					add_post_meta( $order_id, '_transaction_id', $transaction_id, true );

                    die( 'IPN Processed OK. Payment Failed' );

	            }

			} else {

            	$message = 	'Thank you for shopping with us. <br />However, the transaction wasn\'t successful, payment wasn\'t received.';
				$message_type = 'error';

                $voguepay_message = array(
                	'message'		=> $message,
                	'message_type' 	=> $message_type
                );

				update_post_meta( $order_id, '_tbz_voguepay_message', $voguepay_message );

                die( 'IPN Processed OK' );

			}
		}

	}


	function tbz_wc_voguepay_message() {

		if( get_query_var( 'order-received' ) ){

			$order_id 		= absint( get_query_var( 'order-received' ) );
			$order 			= wc_get_order( $order_id );
			$payment_method = method_exists( $order, 'get_payment_method' ) ? $order->get_payment_method() : $order->payment_method;

			if( is_order_received_page() &&  ( 'tbz_voguepay_gateway' == $payment_method ) ) {

				$voguepay_message 	= get_post_meta( $order_id, '_tbz_voguepay_message', true );

				if( ! empty( $voguepay_message ) ) {

					$message 			= $voguepay_message['message'];
					$message_type 		= $voguepay_message['message_type'];

					delete_post_meta( $order_id, '_tbz_voguepay_message' );

					wc_add_notice( $message, $message_type );

				}
			}

		}

	}
	add_action( 'wp', 'tbz_wc_voguepay_message' );


	/**
 	* Add Voguepay Gateway to WC
 	**/
	function tbz_wc_add_voguepay_gateway($methods) {
		$methods[] = 'WC_Tbz_Voguepay_Gateway';
		return $methods;
	}

	add_filter( 'woocommerce_payment_gateways', 'tbz_wc_add_voguepay_gateway' );


	/**
	 * only add the naira currency and symbol if WC versions is less than 2.1
	 */
	if ( version_compare( WOOCOMMERCE_VERSION, "2.1" ) <= 0 ) {

		/**
		* Add NGN as a currency in WC
		**/
		add_filter( 'woocommerce_currencies', 'tbz_add_my_currency' );

		if( ! function_exists( 'tbz_add_my_currency' ) ) {
			function tbz_add_my_currency( $currencies ) {
			     $currencies['NGN'] = __( 'Naira', 'woocommerce' );
			     return $currencies;
			}
		}

		/**
		* Enable the naira currency symbol in WC
		**/
		add_filter('woocommerce_currency_symbol', 'tbz_add_my_currency_symbol', 10, 2);

		if( ! function_exists( 'tbz_add_my_currency_symbol' ) ) {
			function tbz_add_my_currency_symbol( $currency_symbol, $currency ) {
			     switch( $currency ) {
			          case 'NGN': $currency_symbol = '&#8358; '; break;
			     }
			     return $currency_symbol;
			}
		}
	}


	/**
	* Add Settings link to the plugin entry in the plugins menu
	**/
	function tbz_voguepay_plugin_action_links( $links, $file ) {
	    static $this_plugin;

	    if ( ! $this_plugin ) {
	        $this_plugin = plugin_basename( __FILE__ );
	    }

	    if ($file == $this_plugin) {
	        $settings_link = '<a href="' . get_bloginfo( 'wpurl' ) . '/wp-admin/admin.php?page=wc-settings&tab=checkout&section=wc_tbz_voguepay_gateway">Settings</a>';
	        array_unshift( $links, $settings_link );
	    }
	    return $links;
	}
	add_filter( 'plugin_action_links', 'tbz_voguepay_plugin_action_links', 10, 2 );
}