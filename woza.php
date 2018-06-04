<?php
/*
Plugin Name: Woza
Plugin URI: https://www.jisort.com/
Description: Extend WooCommerce by adding Woza Payment Gateway.
Version: 1.0.0
Author: Dennis Mwagiru
Author URI: https://github.com/dennismwagiru/
*/

// Include the Gateway Clas and register Payment Gateway with WooCommerce

add_action( 'plugins_loaded', 'init', 0 );

function init() {
	// If the parent WC_Payment_Gateway class doesn't exist
	// it means WooCommerce is not installed on the site
	// so do nothing.
	if ( ! class_exists('WC_Payment_Gateway' ) ) return;

	// If we made it this far, then include our Gateway Class
	include_once( 'woza-gateway.php' );

	// Now that we have successfully included our class,
	// Let's add it to WooCommerce
	add_filter( 'woocommerce_payment_gateways', 'add_woza_gateway' );

	function add_woza_gateway( $methods ){
		$methods[] = 'Woza_Gateway_Class';

		return $methods;
	}
}

// Add custom action links
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'woza_action_links' );

function woza_action_links( $links ) {
	$plugin_links = array(
		'<a href="' .admin_url('admin.php?page=wc-settings&tab=checkout') . '">' .__( 'Settings', 'woza' ) . '</a>',
	);

	// Merge our new link with the default ones
	return array_merge( $plugin_links, $links );
}

add_action('woocommerce_checkout_process', 'process_custom_payment');
function process_custom_payment(){

    if($_POST['payment_method'] != 'custom')
        return;

    if( !isset($_POST['mobile']) || empty($_POST['mobile']) )
        wc_add_notice( __( 'Please add your mobile number', 'woza' ), 'error' );


    if( !isset($_POST['transaction']) || empty($_POST['transaction']) )
        wc_add_notice( __( 'Please add your transaction ID', 'woza' ), 'error' );

}

/**
 * Update the order meta with field value
 */
add_action( 'woocommerce_checkout_update_order_meta', 'custom_payment_update_order_meta' );
function custom_payment_update_order_meta( $order_id ) {

    if($_POST['payment_method'] != 'custom')
        return;

    // echo "<pre>";
    // print_r($_POST);
    // echo "</pre>";
    // exit();

    update_post_meta( $order_id, 'mobile', $_POST['mobile'] );
    update_post_meta( $order_id, 'transaction', $_POST['transaction'] );
}

/**
 * Display field value on the order edit page
 */
add_action( 'woocommerce_admin_order_data_after_billing_address', 'custom_checkout_field_display_admin_order_meta', 10, 1 );
function custom_checkout_field_display_admin_order_meta($order){
    $method = get_post_meta( $order->id, '_payment_method', true );
    if($method != 'custom')
        return;

    $mobile = get_post_meta( $order->id, 'mobile', true );
    $transaction = get_post_meta( $order->id, 'transaction', true );

    echo '<p><strong>'.__( 'Mobile Number' ).':</strong> ' . $mobile . '</p>';
    echo '<p><strong>'.__( 'Transaction ID').':</strong> ' . $transaction . '</p>';
}
?>