<?php
/*
Woza Payment Gateway Class
*/

class Woza_Gateway_Class extends WC_Payment_Gateway {

	// Setup Our Gateway's id, descriptio and other values

	function __construct() {

		// The global ID for this Payment method
		$this->id = 'woza';

		// The Title shown on the top of the Payment Gateways Page next to all the other Payment Gateways
		$this->method_title = __( 'Woza', 'woza' );

		// The description for this Payment Gateway, shown on the actual Payment options page on the backend
		$this->method_description = __( 'Woza Payment Gateway Plugin for WooCommerce', 'woza' );

		//The title to be used for the vertical tabs that can be ordered top to bottom
		$this->title = __( 'Woza', 'woza' );

		// If you want to show an image next to the gateway's name on the frontend, enter a URL to an image.
		$this->icon = null;

		// If doing a direct integration
		$this->has_fields = true;

		// Support default form with credit card
		$this->supports = array( 'default_credit_card_form' );

		// setting defines
		$this->init_form_fields();

		// load time variable setting
		$this->init_settings();

		// Turn these settings into variables we can use
		foreach ( $this->settings as $key => $val ) $this->$key = $val;

		// further check for ssl
		add_action('admin_notices', array( $this, 'do_ssl_check' ) );

		add_action('woocommerce_checkout_process', 'process_custom_payment');


		// Save settings
		if ( is_admin() ) {
			add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		}
	}

	// administration fields for specific Gateway
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'		=> __( 'Enable / Disable', 'woza'),
				'type'		=> 'checkbox',
				'label'		=> __( 'Enable this payment gateway', 'woza' ),
				'default'	=> 'no',
			),
			// 'title' => array(
			// 	'title'		=> __('Title', 'Woza'),
			// 	'type'		=> 'readonly',
			// 	'desc_tip'	=> __( 'Payment title of checkout process', 'woza' ),
			// 	'default'	=> __( 'M-Pesa', 'woza' ),
			// ),
			'description' => array(
				'title'		=> __( 'Description', 'woza' ),
				'type'		=> 'textarea',
				'desc_tip'	=> __( 'Payment title of checkout process.', 'woza' ),
				'default'	=> __( 'Successfully pay through M-Pesa.', 'woza' ),
				'css'		=> 'max-width:450px;'
			),
			'shortcodetype' => array(
	          	'title'       => __( 'Short Code Type', 'woza' ),
	          	'type'        => 'select',
	          	'description' => __( 'Required - Select short Code Type', 'woza' ),
	          	'options'     => array(
		            'paybill' => esc_html_x( 'paybill', 'shortcodetype', 'woza' ),
		            'tillnumber' => esc_html_x( 'tillnumber', 'shortcodetype', 'woza' ),
          		),
          		'default'     => ''
        	),
			'shortcode' => array(
				'title'       => __( 'Short Code', 'woza' ),
				'type'        => 'Number',
				'description' => __( 'Requierd - Enter your Short Code here.', 'woza' ),
				'default'     => ''
			),
			'consumerkey' => array(
				'title'       => __( 'Consumer Key', 'woza' ),
				'type'        => 'text',
				'description' => __( 'Requierd - Enter your Consumer Key here.', 'woza' ),
				'default'     => ''
			),
			'consumersecret' => array(
				'title'       => __( 'Consumer Secret', 'woza' ),
				'type'        => 'text',
				'description' => __( 'Requierd - Enter your Consumer Secret here.', 'woza' ),
				'default'     => ''
			)
		);
	}

	public function payment_fields(){

            if ( $description = $this->get_description() ) {
                echo wpautop( wptexturize( $description ) );
            }
             if ($this->shortcodetype == 'paybill') {

		        echo '
		        	<div class="mpesa-instructions">
		              <p>
		                <h3>' . __('Payment Instructions', 'woza') . '</h3>
		                <p>
		                  ' . __('1. On your Safaricom phone go the M-PESA menu', 'woza') . '</br>
		                  ' . __('2. Select Lipa Na M-PESA and then select Buy Goods and Services', 'woza') . '</br>
		                  ' . __('3. Enter the Business Number', 'woza') . ' <strong>' . $this->shortcode . '</strong> </br>
		                  ' . __('4. Enter the Account Number', 'woza') . ' (<strong>Your Phone Number</strong>) </br>
		                  ' . __('5. <strong>Enter the total amount due</strong>', 'woza') .' </br>
		                  ' . __('6. Follow subsequent prompts to complete the transaction.', 'woza') . ' </br>
		                  ' . __('7. You will receive a confirmation SMS from M-PESA with a Confirmation Code.', 'woza') . ' </br>
		                  ' . __('8. After you receive the confirmation code, please input the confirmation code that you received from M-PESA below.', 'woza-payments') . '</br>
		                </p>
		              </p>
		            </div>  ';
	        } else {
	        	echo '
		        	<div class="mpesa-instructions">
		              <p>
		                <h3>' . __('Payment Instructions', 'woza') . '</h3>
		                <p>
		                  ' . __('1. On your Safaricom phone go the M-PESA menu', 'woza') . '</br>
		                  ' . __('2. Select Lipa Na M-PESA and then select Buy Goods and Services', 'woza') . '</br>
		                  ' . __('3. Enter the Till Number', 'woza') . ' <strong>' . $this->shortcode . '</strong> </br>
		                  ' . __('4. <strong>Enter the total amount due</strong>', 'woza'). ' </br>
		                  ' . __('5. Follow subsequent prompts to complete the transaction.', 'woza') . ' </br>
		                  ' . __('6. You will receive a confirmation SMS from M-PESA with a Confirmation Code.', 'woza') . ' </br>
		                  ' . __('7. After you receive the confirmation code, please input the confirmation code that you received from M-PESA below.', 'woza') . '</br>
		                </p>
		              </p>
		            </div>  ';
	        }

            ?>

            <div id="custom_input">
                <p class="form-row form-row-wide">
                    <label for="trans_id" class=""><?php _e('Transaction ID', 'woza'); ?></label>
                    <input type="text" class="" name="trans_id" id="trans_id" placeholder="M-Pesa Transaction ID" required>
                </p>
            </div>
            <?php
        }

	// Response handled for payment gateway
	public function process_payment( $order_id ) {
 
        if ( !isset($_POST['trans_id']) || empty($_POST['trans_id']) )
        	wc_add_notice( __( 'Please enter a valid M-Pesa transaction Code', 'woza' ), 'error' );
        
        else {
        
	        global $woocommerce;
	        $customer_order = wc_get_order( $order_id );
	        $order_total = $customer_order->order_total;

	        // This is where the fun stuff begins
			// $payload = array(
				// "trans_id"           	=> $_POST['trans_id'],
				// "busines_no"       		=> $this->shortcode,
				// "consumer_key"			=> $this->consumerkey,
				// "consumer_secret"		=> $this->consumersecret,
				// "invoice_id"			=> $order_id,
			// );

			if ($this->shortcodetype == 'paybill') {
				$base_url = 'https://my.jisort.com/paymentsApi/validate/?consumer_key='.$this->consumerkey.'&consumer_secret='.$this->consumersecret.'&';
			} else {
				$base_url = 'https://my.jisort.com/general_ledger/transaction_ledger/?';
			}
			$url = $base_url.'trans_id='.$_POST['trans_id'].'&business_no='.$this->shortcode;

			// Send this payload to Jisort for processing
			$response = wp_remote_post( $url, array(
				'method'    => 'GET',
				'timeout'   => 10,
				'sslverify' => false,
			) );


			if ( is_wp_error( $response ) ) 
				throw new Exception( __( $response, 'woza' ) );

			if ( empty( $response['body'] ) )
				throw new Exception( __( 'Couldn\'t get any response. Try again after a few seconds.', 'woza' ) );
				
			// get status code while get not error
			$response_code = wp_remote_retrieve_response_code($response);

			// get body response while get not error
			$response_body = wp_remote_retrieve_body( $response );

			if ($response_code != '200') 
				throw new Exception($response_body, 1);

			// Parse the response into something we can read
			$json = json_decode($response_body, true);

			if ($order_total > $json['debit']) {
				$customer_order->set_discount_total( $json['debit'] );
			

				$balance = $customer_order->order_total - $json['debit'];
				$customer_order->save();

				throw new Exception("You have successfully paid ".$json['debit'].".Pending balance is ".$balance, 1);
			}
			
			// Payment successful
			$customer_order->add_order_note( __( 'Payment with Woza successful.', 'woza' ) );
												 
			// paid order marked
			$customer_order->payment_complete();

			// this is important part for empty cart
			$woocommerce->cart->empty_cart();

			// Redirect to thank you page
			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $customer_order ),
			);
			// } else {
			// 	//transiction fail
			// 	wc_add_notice( $r['response_reason_text'], 'error' );
			// 	$customer_order->add_order_note( 'Error: '. $r['response_reason_text'] );
			// }
	            
	      // Return thankyou redirect
	            return array(
	                'result'    => 'success',
	                'redirect'  => $this->get_return_url( $order )
	            );
		}
	}

	public function process_custom_payment(){

	    if($_POST['payment_method'] != 'woza')
	        return;

	    if( !isset($_POST['mobile']) || empty($_POST['mobile']) )
	        wc_add_notice( __( 'Please add your mobile number', $this->domain ), 'error' );


	    if( !isset($_POST['transaction']) || empty($_POST['transaction']) )
	        wc_add_notice( __( 'Please add your transaction ID', $this->domain ), 'error' );

	}


	// Validate fields
	public function validate_fields() {
		return true;
	}

	public function do_ssl_check() {
		if ( $this->enabled == "yes" ) {
			if( get_option( 'woocommerce_force_ssl_checkout' ) == "no" ) {
				echo "<div class=\"error\"><p>". sprintf( __( "<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>" ), $this->method_title, admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) ."</p></div>";	
			}
		}
	}

	public function do_admin_check() {
		if ( $this->enabled == "yes" && $this->shortcode == '') {
			echo "<div class=\"error\"><p>". sprintf( __( "<strong>%s</strong> is enabled and <strong>%s</strong> has not been set"), $this->method_title, $this->shortcodetype) ."</p></div>";
		}
	}
}
?>