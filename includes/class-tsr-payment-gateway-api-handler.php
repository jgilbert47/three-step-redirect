<?php
/**
 * Class TSR_Payment_Gateway_Payments file.
 *
 * @package WooCommerce\Gateways
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles requests and responses to and from the Three Step Redirect API.
 */
class TSR_Payment_Gateway_API_Handler {


    /**
     * Post info to Three Step Redirect API to get a response.
     *
     * @param WC_Order $order Order object.
     * @param string $xmlRequest XML request body
     * @return string
     */
    public function send_gateway_request( $order, $endpoint, $xmlRequest ) {        
            
        $xmlRequestString = $xmlRequest->saveXML();              
            
        $options = [
            'body'        => $xmlRequestString,
            'headers'     => [
                'Content-Type' => 'text/xml',
            ],
            'timeout'     => 60,
            'redirection' => 5,
            'blocking'    => true,
            'httpversion' => '1.0',
            'sslverify'   => false,
            'data_format' => 'body',
        ];

        // Send the Step 1 request to Three Step Redirect API
        $rawResponse = wp_safe_remote_post( $endpoint, $options ); 

        $responseBody = $rawResponse['body'];

        if ( ! empty( $responseBody ) ) {
            return $responseBody;
        } elseif ( is_wp_error( $rawResponse ) ) {
            return new WP_Error( 'tsrpg-api', 'Empty Response' );
        } else {
            return new WP_Error( 'tsrpg-api', 'An error occurred' );
        }       

    }

    /**
	 * Parse Three Step Redirect API response.
	 *
     * @param object $rawResponse XML response.
	 * @return array
	 */
    public function get_gateway_response_results( $rawResponse ) {        
            
        // Parse API response as XML
        $tsrResponse = new SimpleXMLElement( $rawResponse );

        return $tsrResponse;

    }
}