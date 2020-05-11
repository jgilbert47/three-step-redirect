<?php
/**
 * Class TSR_Payment_Gateway_Refunds file.
 *
 * @package WooCommerce\Gateways
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates requests to send to the Three Step Redirect API.
 */
class TSR_Payment_Gateway_Refunds {
    

    /**
	 * Get order info for Three Step Redirect API request and build refund XML tree.
	 *
     * @param string $apiKey API key
     * @param string $transactionID Payment transaction ID saved in order meta data
     * @param string $amount Refund Amount
	 * @return string
	 */
    public function build_refund_xml_request ( $apiKey, $transactionID, $amount ) {

        $xmlRequest = new DOMDocument('1.0','UTF-8');
        $xmlRequest->formatOutput = true;
                    
        $xmlRefund = $xmlRequest->createElement('refund');

        tsrpg_append_xml_node( $xmlRequest, $xmlRefund, 'api-key', $apiKey );
        tsrpg_append_xml_node( $xmlRequest, $xmlRefund, 'transaction-id', $transactionID );
        tsrpg_append_xml_node( $xmlRequest, $xmlRefund, 'amount', $amount );

        $xmlRequest->appendChild($xmlRefund);

        return $xmlRequest;
    } 
    

    /**
	 * Get order info for Three Step Redirect API request and build void XML tree.
	 *
     * @param string $apiKey API key
     * @param string $transactionID Payment transaction ID saved in order meta data
	 * @return string
	 */
    public function build_void_xml_request ( $apiKey, $transactionID ) {

        $xmlRequest = new DOMDocument('1.0','UTF-8');
        $xmlRequest->formatOutput = true;
                    
        $xmlRefund = $xmlRequest->createElement('void');

        tsrpg_append_xml_node( $xmlRequest, $xmlRefund, 'api-key', $apiKey );
        tsrpg_append_xml_node( $xmlRequest, $xmlRefund, 'transaction-id', $transactionID );

        $xmlRequest->appendChild($xmlRefund);

        return $xmlRequest;
    } 
    

    /**
	 * Complete the VOID transaction and update order.
	 *
     * @param WC_Order $order Order object.
	 * @return string
	 */
    public function complete_void_transaction( $order, $reason ) {

        $orderNote = 'This transaction had not been cleared through the customer&apos;s account at the time of the refund.  As a result, the transaction was VOIDED and the customer&apos;s account will not be charged. ';
        if ( !empty( $reason)) {
            $orderNote .= "\nReason: " . $reason;
        }
        $order->add_order_note( __( $orderNote, 'tsrpg' ) );

    } 
    

    /**
	 * Complete the REFUND transaction and update order.
	 *
     * @param WC_Order $order Order object.
	 * @return string
	 */
    public function complete_refund_transaction( $order, $amount, $orderTotal, $reason ) {

        if ( $amount == $orderTotal ) {

            $orderNote = "This transaction was REFUNDED in full";
            if ( !empty( $reason)) {
                $orderNote .= "\nReason: " . $reason;
            }
            $order->add_order_note( __( $orderNote, 'tsrpg' ) );

        } else {

            $orderNote = "$".$amount." was REFUNDED toward this transaction.";
            if ( !empty( $reason)) {
                $orderNote .= "\nReason: " . $reason;
            }
            $order->add_order_note( __( $orderNote, 'tsrpg' ) );

        }

    } 
    

}
