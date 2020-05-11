<?php
/**
 * Settings for Three Step Redirect API Payment Gateway.
 *
 * @package WooCommerce/Classes/Payment
 */

defined( 'ABSPATH' ) || exit;

return array(
	'enabled' => array(
		'title'   => __( 'Enable/Disable', 'tsrpg' ),
		'type'    => 'checkbox',
		'label'   => __( 'Enable Three Step Redirect API Payment Gateway', 'tsrpg' ),
		'default' => 'no'
	),
	'title' => array(
		'title'       => __( 'Title', 'tsrpg' ),
		'type'        => 'text',
		'description' => __( 'This controls the title which the user sees during checkout.', 'tsrpg' ),
		'default'     => __( 'Three Step Redirect API Payment Gateway', 'tsrpg' ),
		'desc_tip'    => true
	),
	'description' => array(
		'title'       => __( 'Description', 'tsrpg' ),
		'type'        => 'textarea',
		'description' => __( 'This controls the description which the user sees during checkout.', 'tsrpg' ),
		'desc_tip'    => true,
		'default'     => ''
	),
	'instructions'             => array(
		'title'       => __( 'Instructions', 'tsrpg' ),
		'type'        => 'textarea',
		'description' => __( 'Instructions that will be added to the thank you page.', 'tsrpg' ),
		'desc_tip'    => true,
		'default'     => ''
	),
	'endpoint' => array(
		'title'       => __( 'Gateway URL', 'tsrpg' ),
		'type'        => 'text',
		'description' => __( 'Enter the gateway URL that transaction requests are to be sent to.', 'tsrpg' ),
		'description' => __( 'Enter the Three Step Redirect API gateway URL that transaction requests are to be sent to.', 'tsrpg' ),
		'default'     => '',
		'desc_tip'    => true
	),
	'live_api_key' => array(		
		'title'       => __( 'API Key', 'tsrpg' ),
		'type'        => 'text',
		'description' => __( 'Enter your Three Step Redirect API credentials.', 'tsrpg' ),
		'desc_tip'    => true,
		'default'     => ''
	),
	'testmode' => array(
		'title'   => __( 'Test mode', 'tsrpg' ),
		'type'    => 'checkbox',
		'label'   => __( 'Enable Test Mode', 'tsrpg' ),
		'description' => __( 'Place the payment gateway in test mode using test API keys.', 'tsrpg' ),
		'default' => 'no',
		'desc_tip'    => true
	),
	'test_api_key' => array(
		'title'       => __( 'Test API Key', 'tsrpg' ),
		'type'        => 'text',
		'default'     => __( '2F822Rw39fx762MaV7Yy86jXGTC7sCDy', 'tsrpg' )
	),
	'final_order_status' => array(
		'title'       => __( 'Final Order Status', 'tsrpg' ),
		'type'        => 'select',
		'description' => __( 'This option allows you to set the final status of an order after payment been processed successfully by the gateway.', 'tsrpg' ),
		'default'     => 'Processing',
        'desc_tip'    => true,
        'options'     => array(
            'processing' => 'Processing',
            'pending' => 'Pending',
            'on-hold' => 'On-Hold',
            'completed' => 'Completed',
        ),
	)
);
