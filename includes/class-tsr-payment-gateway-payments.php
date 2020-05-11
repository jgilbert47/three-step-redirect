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
 * Generates requests to send to the Three Step Redirect API.
 */
class TSR_Payment_Gateway_Payments {
    

    /**
	 * Get order info for Three Step Redirect API request and build step 1 XML tree.
	 *
	 * @param WC_Order $order Order object.
     * @param string $apiKey API key
	 * @return string
	 */
    public function build_step1_xml_request ( $order, $apiKey ) {

        $redirect_url = $order->get_checkout_payment_url( true );

        $xmlRequest = new DOMDocument('1.0','UTF-8');
        $xmlRequest->formatOutput = true;
                    
        $xmlSale = $xmlRequest->createElement('sale');

        tsrpg_append_xml_node( $xmlRequest, $xmlSale, 'api-key', $apiKey );
        tsrpg_append_xml_node( $xmlRequest, $xmlSale, 'redirect-url', $redirect_url );
        tsrpg_append_xml_node( $xmlRequest, $xmlSale, 'amount', $order->get_total() );
        tsrpg_append_xml_node( $xmlRequest, $xmlSale, 'ip-address', $_SERVER['REMOTE_ADDR'] );
        tsrpg_append_xml_node( $xmlRequest, $xmlSale, 'currency', $order->get_currency() );
        tsrpg_append_xml_node( $xmlRequest, $xmlSale, 'order-id', $order->get_id() );
        tsrpg_append_xml_node( $xmlRequest, $xmlSale, 'tax-amount', $order->get_total_tax() );
        tsrpg_append_xml_node( $xmlRequest, $xmlSale, 'shipping-amount', $order->get_shipping_total() );

        $xmlBillingAddress = $xmlRequest->createElement('billing');
        
        tsrpg_append_xml_node( $xmlRequest, $xmlBillingAddress, 'first-name', $order->get_billing_first_name() );
        tsrpg_append_xml_node( $xmlRequest, $xmlBillingAddress, 'last-name', $order->get_billing_first_name() );
        tsrpg_append_xml_node( $xmlRequest, $xmlBillingAddress, 'company', $order->get_billing_company() );
        tsrpg_append_xml_node( $xmlRequest, $xmlBillingAddress, 'country', $order->get_billing_country() );
        tsrpg_append_xml_node( $xmlRequest, $xmlBillingAddress, 'address1', $order->get_billing_address_1() );
        tsrpg_append_xml_node( $xmlRequest, $xmlBillingAddress, 'address2', $order->get_billing_address_2() );
        tsrpg_append_xml_node( $xmlRequest, $xmlBillingAddress, 'city', $order->get_billing_city() );
        tsrpg_append_xml_node( $xmlRequest, $xmlBillingAddress, 'state', $order->get_billing_state() );
        tsrpg_append_xml_node( $xmlRequest, $xmlBillingAddress, 'postal', wc_format_postcode( $order->get_billing_postcode(), $order->get_billing_country() ) );
        tsrpg_append_xml_node( $xmlRequest, $xmlBillingAddress, 'phone', $order->get_billing_phone() );
        tsrpg_append_xml_node( $xmlRequest, $xmlBillingAddress, 'email', $order->get_billing_email() );

        $xmlSale->appendChild($xmlBillingAddress);

        $xmlShippingAddress = $xmlRequest->createElement('shipping');

        tsrpg_append_xml_node( $xmlRequest, $xmlShippingAddress, 'first-name', $order->get_shipping_first_name() );
        tsrpg_append_xml_node( $xmlRequest, $xmlShippingAddress, 'last-name', $order->get_shipping_first_name() );
        tsrpg_append_xml_node( $xmlRequest, $xmlShippingAddress, 'company', $order->get_shipping_company() );
        tsrpg_append_xml_node( $xmlRequest, $xmlShippingAddress, 'country', $order->get_shipping_country() );
        tsrpg_append_xml_node( $xmlRequest, $xmlShippingAddress, 'address1', $order->get_shipping_address_1() );
        tsrpg_append_xml_node( $xmlRequest, $xmlShippingAddress, 'address2', $order->get_shipping_address_2() );
        tsrpg_append_xml_node( $xmlRequest, $xmlShippingAddress, 'city', $order->get_shipping_city() );
        tsrpg_append_xml_node( $xmlRequest, $xmlShippingAddress, 'state', $order->get_shipping_state() );
        tsrpg_append_xml_node( $xmlRequest, $xmlShippingAddress, 'postal', wc_format_postcode( $order->get_shipping_postcode(), $order->get_shipping_country() ) );

        $xmlSale->appendChild($xmlShippingAddress);

        foreach ( $order->get_items() as $item ) {
            $itemSku       = $item->get_product_id();
            $itemName      = $item->get_name();
            $itemQuantity  = $item->get_quantity();
            $itemTax       = $item->get_subtotal_tax();
            $itemTotal     = $item->get_total();

            $xmlProduct = $xmlRequest->createElement('product');

            tsrpg_append_xml_node($xmlRequest, $xmlProduct,'product-code' , $itemSku);
            tsrpg_append_xml_node($xmlRequest, $xmlProduct,'description' , $itemName);
            tsrpg_append_xml_node($xmlRequest, $xmlProduct,'quantity' , $itemQuantity);
            tsrpg_append_xml_node($xmlRequest, $xmlProduct,'tax-amount' , $itemTax);
            tsrpg_append_xml_node($xmlRequest, $xmlProduct,'total-amount' , $itemTotal);

            $xmlSale->appendChild($xmlProduct);;
        }

        $xmlRequest->appendChild($xmlSale);

        return $xmlRequest;
    } 


    /**
	 * Handle step 1 results.
	 *
     * @param int $order_id Order ID.
     * @param string $response Step1 response body.
	 * @return string
	 */
    public function get_form_url( $order, $response ) {  

        $formURL = $response->{'form-url'};            
            
        if ( !empty( $formURL ) ) {
            update_post_meta( $order->get_id(), '_tsr_form_url', wc_clean( strval( $formURL ) ) );
        } else {
            wc_add_notice(__('There was a problem with submitting your order. Please refresh this page and try again.', 'tsrpg'), 'error');
        }

        return $formURL;

    }


    /**
	 * Get step 2 payment details to send to Three Step Redirect API for the token id.
	 *
     * @param string $formURL Form URL from step 1 response
	 */
    public function get_payment_details( $formURL ) {   
        
        // Get payment details
            
        ?>
        <h2>Please enter your payment details below.</h2>

        <form id="tsr-payment-form" action="<?php echo $formURL ?>" method="post">
            <div class="tsr-payment-form-fields">
                <div class="tsrpg-field-group first validate-required" id="billing_cc_number_field">
                    <label for="billing-cc-number">Credit Card Number&nbsp;<abbr class="required" title="required">*</abbr></label>
                    <input type="text" class="input-text" name="billing-cc-number" id="billing-cc-number" autocomplete="off" required>
                </div>
                <div class="tsrpg-field-group validate-required" id="billing_cc_exp_field">
                    <label for="billing-cc-exp">Expiration Date&nbsp;<abbr class="required" title="required">*</abbr></label>
                    <input type="hidden" name="billing-cc-exp" id="billing-cc-exp" value="">
                    <div id="tsrpg-cc-exp-month">
                        <select name="billing-cc-exp-month" id="billing-cc-exp-month" required>
                            <option value="">Select Month</option>
                            <option value="01">Jan</option>
                            <option value="02">Feb</option>
                            <option value="03">Mar</option>
                            <option value="04">Apr</option>
                            <option value="05">May</option>
                            <option value="06">Jun</option>
                            <option value="07">Jul</option>
                            <option value="08">Aug</option>
                            <option value="09">Sep</option>
                            <option value="10">Oct</option>
                            <option value="11">Nov</option>
                            <option value="12">Dec</option>
                        </select>
                    </div>
                    <div id="tsrpg-cc-exp-year">
                        <select name="billing-cc-exp-year" id="billing-cc-exp-year" required>
                            <option value="">Select Year</option>
                            <?php
                            for ( $x = date('Y'); $x <= ( date('Y') + 15 ); $x++) {
                                $year = substr($x,2);
                                echo '
                                <option value="' . $year . '">' . $x . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="tsrpg-field-group last validate-required" id="billing_cvv_field">
                    <label for="billing-cvv">CVV</label>
                    <input type="text" class="input-text " name="billing-cvv" id="billing-cvv" autocomplete="off">
                </div>
            </div>
            <button type="submit" class="button alt" id="tsr-pay-button" value="Pay Now">Pay Now</button>
        </form>

        <script type="text/javascript">
            const tsrpgCCNum        = document.getElementById('billing-cc-number');
            const tsrpgCCExp        = document.getElementById('billing-cc-exp');
            const tsrpgCCExpMonth   = document.getElementById('billing-cc-exp-month');
            const tsrpgCCExpYear    = document.getElementById('billing-cc-exp-year');

            tsrpgCCNum.addEventListener('blur', validateRequiredField);
            tsrpgCCExpMonth.addEventListener('blur', validateRequiredField);
            tsrpgCCExpYear.addEventListener('blur', validateRequiredField);
            tsrpgCCExpMonth.addEventListener('change', updateCCExpValue);
            tsrpgCCExpYear.addEventListener('change', updateCCExpValue);

            function validateRequiredField(e) {

                $target = e.target;

                if ($target.value == "" ) {
                    console.log($target);
                    jQuery($target).addClass('required-field-invalid').removeClass('required-field-validated');
                } else {
                    jQuery($target).addClass('required-field-validated').removeClass('required-field-invalid');
                }
            }

            function updateCCExpValue(e) {

                // Get the value of the expiration month and year
                var expMonth = tsrpgCCExpMonth.value;
                var expYear = tsrpgCCExpYear.value;  

                // Set the expiration date value to the concatenated month and year values
                tsrpgCCExp.value = expMonth.concat(expYear);
            }
        </script>
        <?php

    } 


    /**
	 * Parse step 2 Three Step Redirect API response to get the token id for steps 3.
	 *
     * @param string $tsrGetDetails return url token-id parm from query string
	 * @return string
	 */
    public function get_token_id( $tsrGetDetails ) {   
        
        if (isset($_GET['token-id'])) {
            $tokenResponse = $_GET['token-id'];
        } else {
            $tokenResponse = '';            
        }

        return $tokenResponse;

    } 
    

    /**
	 * Build XML tree to send step 3 request to complete the transaction.
	 *
	 * @param string $apiKey API key.
	 * @param string $token Token ID.
	 * @return string
	 */
    public function build_step3_complete_order_request ( $apiKey, $token ) {

        $xmlRequest = new DOMDocument('1.0','UTF-8');
        $xmlRequest->formatOutput = true;
                    
        $xmlCompleteSale = $xmlRequest->createElement('complete-action');

        tsrpg_append_xml_node( $xmlRequest, $xmlCompleteSale, 'api-key', $apiKey );
        tsrpg_append_xml_node( $xmlRequest, $xmlCompleteSale, 'token-id', $token );

        $xmlRequest->appendChild($xmlCompleteSale);

        return $xmlRequest;
    }


    /**
	 * Parse step 3 Three Step Redirect API response and complete the transaction.
	 *
     * @param object $rawResponse XML raw response
	 * @return array
	 */
    public function complete_payment_transaction( $woocommerce, $order, $response, $orderStatus ) {        
            
        $resultText = $response->{'result-text'};
        $resultCode = $response->{'result-code'};
        $transactionID = $response->{'transaction-id'};
        $authCode = $response->{'authorization-code'};
        $transAmount = $response->{'amount'};

        if ( $resultCode == '100' ) {
                    
            wc_add_notice( __( 'Your payment was successfully processed.', 'tsrpg' ) );
                    
            if ( !empty( $transactionID ) ) {
                update_post_meta( $order->get_id(), '_tsr_transaction_id', wc_clean( strval( $transactionID ) ) );
            }

            $order->update_status( $orderStatus, '' );

            $orderNote = "Three Step Redirect API Payment Gateway payment COMPLETE.\n";
            $orderNote .= " \n";
            $orderNote .= "<u>Transaction Details</u>\n";
            $orderNote .= "Transaction ID: " . wc_clean( strval( $transactionID ) ) . "\n";
            $orderNote .= "Transaction Status: " . wc_clean( strval( $resultText ) ) . " (Code: " . wc_clean( strval( $resultCode ) ) . ")\n";
            $orderNote .= "Authorization Code: " . wc_clean( strval( $authCode ) );  
                    
            $order->add_order_note( __( $orderNote, 'tsrpg' ) );

            // Remove cart
            $woocommerce->cart->empty_cart();     

            header('Location: ' . $order->get_checkout_order_received_url() . '');
            exit();
                    
        } elseif ( $resultCode == '200' ) {

            wc_add_notice(__('Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction.', 'tsrpg'), 'error');

            // Can't process payment after a failed transaction because the token has 
            // already been used, so the order status must be updated to cancelled
            $order->update_status( 'cancelled', '' );

            $orderNote = "Three Step Redirect API Payment Gateway payment DECLINED.\n";
            $orderNote .= " \n";
            $orderNote .= "<u>Transaction Details:</u>\n";
            $orderNote .= "Transaction ID: " . wc_clean( strval( $transactionID ) ) . "\n";
            $orderNote .= "Transaction Status: " . wc_clean( strval( $resultText ) ) . " (Code: " . wc_clean( strval( $resultCode ) ) . ")";  
                    
            $order->add_order_note( __( $orderNote, 'tsrpg' ) );   

            header('Location: ' . wc_get_checkout_url() . '');
            exit();

        } else {

            $displayError = wc_clean( strval( $resultText ) );

            wc_add_notice(__('There was a problem with your order: '. $displayError . '.', 'tsrpg'), 'error');

            // Can't process payment after a failed transaction because the token has 
            // already been used, so the order status must be updated to cancelled
            $order->update_status( 'cancelled', '' );

            $orderNote = "Error in transaction data or system error.\n";
            $orderNote .= " \n";
            $orderNote .= "<u>Transaction Details:</u>\n";
            $orderNote .= "Transaction ID: " . wc_clean( strval( $transactionID ) ) . "\n";
            $orderNote .= "Transaction Status: " . wc_clean( strval( $resultText ) ) . " (Code: " . wc_clean( strval( $resultCode ) ) . ")";  
                    
            $order->add_order_note( __( $orderNote, 'tsrpg' ) );   

            header('Location: ' . wc_get_checkout_url() . '');
            exit();

        }

    }
    

}
