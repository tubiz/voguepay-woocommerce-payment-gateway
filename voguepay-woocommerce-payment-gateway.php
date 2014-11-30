<?php
/*
	Plugin Name: Voguepay WooCommerce Payment Gateway
	Plugin URI: http://bosun.me/voguepay-woocommerce-payment-gateway
	Description: Voguepay Woocommerce Payment Gateway allows you to accept payment on your Woocommerce store via Visa Cards, Mastercards, Verve Cards and eTranzact.
	Version: 2.0.3
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
        	$this->liveurl 				= 'https://voguepay.com/pay/';
			$this->notify_url        	= WC()->api_request_url( 'WC_Tbz_Voguepay_Gateway' );
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

			// Check if the gateway can be used
			if ( ! $this->is_valid_for_use() ) {
				$this->enabled = false;
			}
		}

		public function is_valid_for_use(){

			if( ! in_array( get_woocommerce_currency(), array('NGN') ) ){
				$this->msg = 'Voguepay doesn\'t support your store currency, set it to Nigerian Naira &#8358; <a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=wc-settings&tab=general">here</a>';
				return false;
			}

			return true;
		}

        /**
         * Admin Panel Options
         **/
        public function admin_options(){
            echo '<h3>VoguePay Payment Gateway</h3>';
            echo '<p>VoguePay Payment Gateway allows you to accept payment through various channels such as Interswitch, Mastercard, Verve cards, eTranzact and Visa cards.</p>';


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
			$memo        	= "Payment for Order ID: $order_id on ". get_bloginfo('name');
            $notify_url  	= $this->notify_url;
            $notify_url 	= 'http://postcatcher.in/catchers/54732efed811f60200001410';

			$success_url  	= esc_url( $this->get_return_url( $order ) );

			$fail_url	  	= esc_url( $this->get_return_url( $order ) );

			// voguepay Args
			$voguepay_args = array(
				'v_merchant_id' 		=> $merchantID,
				'memo'					=> $memo,
				'total' 				=> $order_total,
				'merchant_ref'			=> $order_id,
				'notify_url'			=> $notify_url,
				'success_url'			=> $success_url,
				'fail_url'				=> $fail_url
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

			$voguepay_adr = $this->liveurl;

			$voguepay_args = $this->get_voguepay_args( $order );

			$voguepay_args_array = array();

			foreach ($voguepay_args as $key => $value) {
				$voguepay_args_array[] = '<input type="hidden" name="'.esc_attr( $key ).'" value="'.esc_attr( $value ).'" />';
			}

			wc_enqueue_js( '
				$.blockUI({
						message: "' . esc_js( __( 'Thank you for your order. We are now redirecting you to Voguepay to make payment.', 'woocommerce' ) ) . '",
						baseZ: 99999,
						overlayCSS:
						{
							background: "#fff",
							opacity: 0.6
						},
						css: {
							padding:        "20px",
							zindex:         "9999999",
							textAlign:      "center",
							color:          "#555",
							border:         "3px solid #aaa",
							backgroundColor:"#fff",
							cursor:         "wait",
							lineHeight:		"24px",
						}
					});
				jQuery("#submit_voguepay_payment_form").click();
			' );

			return '<form action="' . esc_url( $voguepay_adr ) . '" method="post" id="voguepay_payment_form" target="_top">
					' . implode( '', $voguepay_args_array ) . '
					<!-- Button Fallback -->
					<div class="payment_buttons">
						<input type="submit" class="button alt" id="submit_voguepay_payment_form" value="' . __( 'Pay via Voguepay', 'woocommerce' ) . '" /> <a class="button cancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">' . __( 'Cancel order &amp; restore cart', 'woocommerce' ) . '</a>
					</div>
					<script type="text/javascript">
						jQuery(".payment_buttons").hide();
					</script>
				</form>';
		}

	    /**
	     * Process the payment and return the result
	    **/
		function process_payment( $order_id ) {

			$order = new WC_Order( $order_id );
	        return array(
	        	'result' => 'success',
				'redirect'	=> $order->get_checkout_payment_url( true )
	        );
		}

	    /**
	     * Output for the order received page.
	    **/
		function receipt_page( $order ) {
			echo '<p>' . __( 'Thank you - your order is now pending payment. You should be automatically redirected to Voguepay to make payment.', 'woocommerce' ) . '</p>';
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

				$args = array( 'sslverify' => false );

				$json = wp_remote_get( 'https://voguepay.com/?v_transaction_id='.$transaction_id.'&type=json', $args );

				$transaction 	= json_decode($json['body'], true);
				$transaction_id = $transaction['transaction_id'];
				$order_id 		= $transaction['merchant_ref'];
				$order_id 		= (int) $order_id;

		        $order 			= new WC_Order($order_id);
		        $order_total	= $order->get_total();

				update_post_meta( $order_id, '_tbz_voguepay_transaction_id', wc_clean( $_POST['transaction_id'] ) );

				$amount_paid 	= $transaction['total'];

	            //after payment hook
                do_action('tbz_wc_voguepay_after_payment', $transaction);

				if($transaction['status'] == 'Approved')
				{

					// check if the amount paid is equal to the order amount.
					if($order_total != $amount_paid)
					{

		                //Update the order status
						$order->update_status('on-hold', '');

						//Error Note
						$message = 'Thank you for shopping with us.<br />Your payment transaction was successful, but the amount paid is not the same as the total order amount.<br />Your order is currently on-hold.<br />Kindly contact us for more information regarding your order and payment status.';
						$message_type = 'notice';

						//Add Customer Order Note
	                    $order->add_order_note($message.'<br />Voguepay Transaction ID: '.$transaction_id, 1);

	                    //Add Admin Order Note
	                    $order->add_order_note('Look into this order. <br />This order is currently on hold.<br />Reason: Amount paid is less than the total order amount.<br />Amount Paid was &#8358; '.$amount_paid.' while the total order amount is &#8358; '.$order_total.'<br />Voguepay Transaction ID: '.$transaction_id);

						// Reduce stock levels
						$order->reduce_order_stock();

						// Empty cart
						$woocommerce->cart->empty_cart();
					}
					else
					{

		                if($order->status == 'processing'){
		                    $order->add_order_note('Payment Via Voguepay<br />Transaction ID: '.$transaction_id);

		                    //Add customer order note
		 					$order->add_order_note('Payment Received.<br />Your order is currently being processed.<br />We will be shipping your order to you soon.<br />Voguepay Transaction ID: '.$transaction_id, 1);

							// Reduce stock levels
							$order->reduce_order_stock();

							// Empty cart
							WC()->cart->empty_cart();

							$message = 'Thank you for shopping with us.<br />Your transaction was successful, payment was received.<br />Your order is currently being processed.';
							$message_type = 'success';
		                }
		                else{

		                	if( $order->has_downloadable_item() ){

		                		//Update order status
								$order->update_status( 'completed', 'Payment received, your order is now complete.' );

			                    //Add admin order note
			                    $order->add_order_note('Payment Via Voguepay Payment Gateway<br />Transaction ID: '.$transaction_id);

			                    //Add customer order note
			 					$order->add_order_note('Payment Received.<br />Your order is now complete.<br />Voguepay Transaction ID: '.$transaction_id, 1);

								$message = 'Thank you for shopping with us.<br />Your transaction was successful, payment was received.<br />Your order is now complete.';
								$message_type = 'success';

		                	}
		                	else{

		                		//Update order status
								$order->update_status( 'processing', 'Payment received, your order is currently being processed.' );

								//Add admin order noote
			                    $order->add_order_note('Payment Via Voguepay Payment Gateway<br />Transaction ID: '.$transaction_id);

			                    //Add customer order note
			 					$order->add_order_note('Payment Received.<br />Your order is currently being processed.<br />We will be shipping your order to you soon.<br />Voguepay Transaction ID: '.$transaction_id, 1);

								$message = 'Thank you for shopping with us.<br />Your transaction was successful, payment was received.<br />Your order is currently being processed.';
								$message_type = 'success';
		                	}

							// Reduce stock levels
							$order->reduce_order_stock();

							// Empty cart
							WC()->cart->empty_cart();
		                }
	                }

	                $voguepay_message = array(
	                	'message'	=> $message,
	                	'message_type' => $message_type
	                );

					if ( version_compare( WOOCOMMERCE_VERSION, "2.2" ) >= 0 ) {
						add_post_meta( $order_id, '_transaction_id', $transaction_id, true );
					}

					update_post_meta( $order_id, '_tbz_voguepay_message', $voguepay_message );

                    die( 'IPN Processed OK. Payment Successfully' );
				}

	            else
	            {
	            	$message = 	'Thank you for shopping with us. <br />However, the transaction wasn\'t successful, payment wasn\'t recieved.';
					$message_type = 'error';

					$transaction_id = $transaction['transaction_id'];

					//Add Customer Order Note
                   	$order->add_order_note($message.'<br />Voguepay Transaction ID: '.$transaction_id, 1);

                    //Add Admin Order Note
                  	$order->add_order_note($message.'<br />Voguepay Transaction ID: '.$transaction_id);


	                //Update the order status
					$order->update_status('failed', '');

	                $voguepay_message = array(
	                	'message'	=> $message,
	                	'message_type' => $message_type
	                );

					update_post_meta( $order_id, '_tbz_voguepay_message', $voguepay_message );

                    die( 'IPN Processed OK. Payment Failed' );
	            }

			}
			else
			{
            	$message = 	'Thank you for shopping with us. <br />However, the transaction wasn\'t successful, payment wasn\'t recieved.';
				$message_type = 'error';

                $voguepay_message = array(
                	'message'	=> $message,
                	'message_type' => $message_type
                );

				update_post_meta( $order_id, '_tbz_voguepay_message', $voguepay_message );

                die( 'IPN Processed OK' );
			}

		}


		public function get_transaction_url( $order ) {
			if( version_compare( WOOCOMMERCE_VERSION, "2.2" ) >= 0 ) {

				$this->view_transaction_url = 'https://voguepay.com/?v_transaction_id=%s&type=xml';

				return parent::get_transaction_url( $order );
			}
		}

	}

	function tbz_voguepay_message(){
		$order_id 		= absint( get_query_var( 'order-received' ) );
		$order 			= new WC_Order( $order_id );
		$payment_method =  $order->payment_method;

		if( is_order_received_page() &&  ( 'tbz_voguepay_gateway' == $payment_method ) ){

			$voguepay_message 	= get_post_meta( $order_id, '_tbz_voguepay_message', true );
			$message 			= $voguepay_message['message'];
			$message_type 		= $voguepay_message['message_type'];

			delete_post_meta( $order_id, '_tbz_voguepay_message' );

			if(! empty( $voguepay_message) ){
				wc_add_notice( $message, $message_type );
			}
		}
	}
	add_action('wp', 'tbz_voguepay_message');


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
	* Add Settings link to the plugin entry in the plugins menu for WC below 2.1
	**/
	if ( version_compare( WOOCOMMERCE_VERSION, "2.1" ) <= 0 ) {

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
	/**
	* Add Settings link to the plugin entry in the plugins menu for WC 2.1 and above
	**/
	else{
		add_filter('plugin_action_links', 'tbz_voguepay_plugin_action_links', 10, 2);

		function tbz_voguepay_plugin_action_links($links, $file) {
		    static $this_plugin;

		    if (!$this_plugin) {
		        $this_plugin = plugin_basename(__FILE__);
		    }

		    if ($file == $this_plugin) {
		        $settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=wc-settings&tab=checkout&section=wc_tbz_voguepay_gateway">Settings</a>';
		        array_unshift($links, $settings_link);
		    }
		    return $links;
		}
	}
}