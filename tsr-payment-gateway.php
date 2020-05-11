<?php

/**
 * Plugin Name: Three Step Redirect API Payment Gateway
 * Description: This plugin adds a custom Three Step Redirect API Payment Gateway to Woocommerce.
 * The Three Step Redirect API methodology used in this payment gateway is the "Three Step 
 * Redirect API".
 * Version:     1.0
 * Author:      Jessica Gilbert - JG Web Solutions
 * Author URI:  https://jgwebsolutions.com/
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: tsrpg
 * 
 * Copyright: (c) 2020 Jessica Gilbert - JG Web Solutions (email : info@jgwebsolutions.com)
 *  
 *
 * @package   TSR_Payment_Gateway
 * @author    Jessica Gilbert - JG Web Solutions
 * @category  Admin
 * @copyright Copyright (c) 2020 Jessica Gilbert - JG Web Solutions
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 *
 */
 
defined( 'ABSPATH' ) or exit;

/*
 * Make sure WooCommerce is active
 */
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    echo '<div class="notice error is-dismissible" >';
        echo '<p>' . _e( 'The Three Step Redirect API Payment Gateway requires that the Woocommerce plugin be installed and activated.', 'tsrpg' ) . '</p>';
    echo '</div>';
    return; 
}


/*
 * Registers the class as a WooCommerce payment gateway
 */
function tsrpg_add_gateway_class( $gateways ) {
	$gateways[] = 'WC_TSRPG_Gateway'; // your class name is here
	return $gateways;
}
add_filter( 'woocommerce_payment_gateways', 'tsrpg_add_gateway_class' );

 
/*
 * Function and action hook containing the WC_TSRPG_Gateway class
 */
function tsrpg_init_gateway_class() {
 
	class WC_TSRPG_Gateway extends WC_Payment_Gateway {

        /**
         * API Key
         *
         * @var string
         */
        protected static $api_key;
    
        /**
         * API Endpoint
         *
         * @var string
         */
        protected static $endpoint;
 
 		/**
 		 * Class constructor
 		 */
 		public function __construct() {
            global $woocommerce;
 
            $this->id                   = 'tsrpg'; // payment gateway plugin ID
            $this->icon                 = ''; // URL of the icon that will be displayed on checkout page near the gateway name
            $this->has_fields           = false; 
            $this->method_title         = __( 'Three Step Redirect API Payment Gateway', 'tsrpg' );
            $this->method_description   = 'Take payments via Three Step Redirect API.'; // will be displayed on the options page
            $this->supports             = array(
                'products',
                'refunds'
            );
         
            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();
         
            // Define user set variables.
            $this->enabled              = $this->get_option( 'enabled' );
            $this->title                = wc_clean( $this->get_option( 'title' ) );
            $this->description          = wc_clean( $this->get_option( 'description' ) );
            $this->instructions         = wc_clean( $this->get_option( 'instructions' ) );
            $this->endpoint             = wc_clean( $this->get_option( 'endpoint' ) );
            $this->testmode             = 'yes' === $this->get_option( 'testmode', 'no' );
            $this->api_key              = wc_clean( $this->testmode ? $this->get_option( 'test_api_key' ) : $this->get_option( 'live_api_key' ) );
            $this->final_order_status   = $this->get_option( 'final_order_status' );
         
            // Add action hooks
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'get_payment' ) );
            add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
 
        }
         
 
		/**
 		 * Three Step Redirect API Payment Gateway admin settings
 		 */
 		public function init_form_fields(){

            $this->form_fields = include 'includes/settings-tsr.php';
 
        }  
          

        /**
 		 * Three Step Redirect API Payment Gateway javascript
 		 */
        public function payment_scripts() {
         
            // Register and enqueue TSR payment styles
            wp_register_style( 'tsrpg_payment', plugins_url( 'css/tsrpg-styles.css', __FILE__ ) );         
            wp_enqueue_style( 'tsrpg_payment' );
         
        }

        
        /**
 		 * Process Three Step Redirect API payment
         *
         * @param  int $order_id Order ID.
 		 */
        public function process_payment( $order_id ) {   

            global $woocommerce;
            
            // Create a new order
            $order = new WC_Order($order_id);
            $order_key = $order->get_order_key();  

            include_once dirname(__FILE__) . '/includes/class-tsr-payment-gateway-payments.php';
            include_once dirname(__FILE__) . '/includes/class-tsr-payment-gateway-api-handler.php';

            $tsrPayment     = new TSR_Payment_Gateway_Payments( $this );
            $tsrRequest     = new TSR_Payment_Gateway_API_Handler( $this );

            $xmlRequest     = $tsrPayment->build_step1_xml_request ( $order, $this->api_key );
            $rawResponse    = $tsrRequest->send_gateway_request( $order, $this->endpoint, $xmlRequest );
            $response       = $tsrRequest->get_gateway_response_results( $rawResponse );   
            $formURL        = $tsrPayment->get_form_url( $order, $response ); 
            
            return array(
                'result'   => 'success',
                'redirect' => $order->get_checkout_payment_url( true )
            );            
            
        }
        

        /**
 		 * Get customer payment and complete order
         *
         * @param  int $order_id Order ID.
 		 */
        public function get_payment( $order_id ) {

            global $woocommerce;

            $order = new WC_Order($order_id);
            $formURL = get_post_meta( $order_id, '_tsr_form_url', true );

            include_once dirname(__FILE__) . '/includes/class-tsr-payment-gateway-payments.php';
            include_once dirname(__FILE__) . '/includes/class-tsr-payment-gateway-api-handler.php';

            $tsrPayment     = new TSR_Payment_Gateway_Payments( $this );
            $tsrRequest     = new TSR_Payment_Gateway_API_Handler( $this );

            $tsrGetDetails = $tsrPayment->get_payment_details( $formURL );
            $tsrToken = $tsrPayment->get_token_id( $tsrGetDetails );

            // If the token is set, complete the transaction
            if ( $tsrToken != '' ) {

                $xmlRequest      = $tsrPayment->build_step3_complete_order_request ( $this->api_key, $tsrToken );
                $rawResponse     = $tsrRequest->send_gateway_request( $order, $this->endpoint, $xmlRequest );
                $response        = $tsrRequest->get_gateway_response_results( $rawResponse );   
                $completeRequest = $tsrPayment->complete_payment_transaction( $woocommerce, $order, $response, $this->final_order_status );

                // Redirect the user to the order received page
                $redirect = $order->get_checkout_order_received_url();

                // Return thankyou redirect
                return array(
                    'result'    => 'success',
                    'redirect'  =>  $redirect
                );

            } else {                

                return new WP_Error( 'tsrpg-api', 'Token not set' );

            }                          
            

        }


        /**
         * Process a refund.
         *
         * @param  int    $order_id Order ID.
         * @param  float  $amount Refund amount.
         * @param  string $reason Refund reason.
         * @return bool|WP_Error
         */
        public function process_refund( $order_id, $amount = null, $reason = '' ) {

            $order          = wc_get_order( $order_id );
            $refund         = new WC_Order_Refund($order_id);
            $orderTotal     = $order->get_total();
            $transactionID  = get_post_meta( $order->get_id(), '_tsr_transaction_id', true );  
            $isRefunded     = 'n';        

            include_once dirname(__FILE__) . '/includes/class-tsr-payment-gateway-refunds.php';
            include_once dirname(__FILE__) . '/includes/class-tsr-payment-gateway-api-handler.php';

            $tsrRefund  = new TSR_Payment_Gateway_Refunds( $this );
            $tsrRequest = new TSR_Payment_Gateway_API_Handler( $this );

            //if amount to refund is the same as the total of the order, try to void the transaction first
            if ( $amount == $orderTotal && $transactionID != '' ) { 

                $xmlVoidRequest  = $tsrRefund->build_void_xml_request ( $this->api_key, $transactionID );
                $rawVoidResponse = $tsrRequest->send_gateway_request( $order, $this->endpoint, $xmlVoidRequest );
                $voidResponse    = $tsrRequest->get_gateway_response_results( $rawVoidResponse );  
                $voidResult      = $voidResponse->{'result'};

                if ($voidResult == '1' ) {

                    $isRefunded = 'y';
                    
                    $xmlVoidComplete = $tsrRefund->complete_void_transaction( $order, $reason );

                    return true; 

                } else {

                    return false;
                    
                }                

            } elseif ( $isRefunded == 'n' ) {

                $xmlRefundRequest  = $tsrRefund->build_refund_xml_request ( $this->api_key, $transactionID, $amount );
                $rawRefundResponse = $tsrRequest->send_gateway_request( $order, $this->endpoint, $xmlRefundRequest );
                $refundResponse    = $tsrRequest->get_gateway_response_results( $rawRefundResponse );   
                $refundResult      = $refundResponse->{'result'};

                if ($refundResult == '1' ) {

                    $xmlRefundComplete = $tsrRefund->complete_refund_transaction( $order, $amount, $orderTotal, $reason );

                    $isRefunded = 'y';             

                    return true;
                    
                } else {

                    $orderNote = 'There was an error in posting the refund to this order.';
                    $order->add_order_note( __( $orderNote, 'tsrpg' ) );
                    return false;

                }

            }    

        }       
		
 	}
}
add_action( 'plugins_loaded', 'tsrpg_init_gateway_class' );


/**
* Filter the WooCommerce template paths to use the templates in this plugin instead of the one in WooCommerce.
*
* @param string $template      Default template file path.
* @param string $template_name Template file slug.
* @param string $template_path Template file name.
*
* @return string The new Template file path.
*/
add_filter( 'woocommerce_locate_template', 'tsrpg_intercept_wc_template', 10, 3 );
function tsrpg_intercept_wc_template( $template, $template_name, $template_path ) {

   $template_directory = trailingslashit( plugin_dir_path( __FILE__ ) ) . 'woocommerce/';
   $path = $template_directory . $template_name;

   return file_exists( $path ) ? $path : $template;

}


// Helper function to make building xml dom easier
function tsrpg_append_xml_node( $domDocument, $parentNode, $name, $value ) {
    $childNode      = $domDocument->createElement( $name );
    $childNodeValue = $domDocument->createTextNode( $value );
    $childNode->appendChild( $childNodeValue );
    $parentNode->appendChild( $childNode );
}

?>