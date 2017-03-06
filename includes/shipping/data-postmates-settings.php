<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$delivery_submission_statuses = array_filter(wc_get_order_statuses(), function ($el){

    if(in_array($el, ['wc-cancelled', 'wc-refunded', 'wc-failed'])) {
        return false;
    }

    return $el;

}, ARRAY_FILTER_USE_KEY);

$delivery_cancellation_statuses = array_filter(wc_get_order_statuses(), function ($el){

    if(in_array($el, ['wc-cancelled', 'wc-refunded', 'wc-failed'])) {
        return $el;
    }

    return false;

}, ARRAY_FILTER_USE_KEY);

/**
 * Array of settings
 */
return array(
	'enabled'          => array(
		'title'           => __( 'Postmates Shippping', 'postmates-wc' ),
		'type'            => 'checkbox',
		'label'           => __( 'Enable', 'postmates-wc' ),
		'default'         => 'no'
	),
	'title'            => array(
		'title'           => __( 'Method Title', 'postmates-wc' ),
		'type'            => 'text',
		'description'     => __( 'This controls the title which the user sees during checkout.', 'postmates-wc' ),
		'default'         => __( 'Postmates', 'postmates-wc' ),
		'desc_tip'        => true
	),
	'customer_id'           => array(
		'title'           => __( 'Customer ID', 'postmates-wc' ),
		'type'            => 'text',
		'description'     => __( 'Get it from Posmates Developer Dashboard.', 'postmates-wc' ),
		'default'         => ''
    ),
    'api_key'           => array(
		'title'           => __( 'API Key', 'postmates-wc' ),
		'type'            => 'text',
		'description'     => __( 'Get it from Posmates Developer Dashboard.', 'postmates-wc' ),
		'default'         => '',
    ),
    'signature_secret_key'           => array(
		'title'           => __( 'Signature Secret  Key', 'postmates-wc' ),
		'type'            => 'text',
		'description'     => __( 'Used to validate Webhook requests.', 'postmates-wc' ),
		'default'         => '',
        'desc_tip'        => true
    ),
    'flat_rate'           => array(
		'title'           => __( 'Flat Rate', 'postmates-wc' ),
		'type'            => 'text',
        'description'     => __( 'You can make your customers pay a flat rate for the deliveries.', 'postmates-wc' ),
        'default'         => '',
        'desc_tip'        => true
    ),
    'pickup_business_name'           => array(
		'title'           => __( 'Pickup Business Name', 'postmates-wc' ),
		'type'            => 'text',
		'description'     => __( 'Your business name.', 'postmates-wc' ),
		'default'         => '',
    ),
    'pickup_name'           => array(
        'title'           => __( 'Pickup Name', 'postmates-wc' ),
        'type'            => 'text',
        'description'     => __( 'Your business manager name.', 'postmates-wc' ),
        'default'         => '',
    ),
    'pickup_address'           => array(
		'title'           => __( 'Pickup Address', 'postmates-wc' ),
		'type'            => 'text',
		'description'     => __( 'Your business address.', 'postmates-wc' ),
		'default'         => '',
    ),
    'pickup_phone_number'           => array(
		'title'           => __( 'Pickup Phone Number', 'postmates-wc' ),
		'type'            => 'text',
		'description'     => __( 'Your business phone number.', 'postmates-wc' ),
		'default'         => '',
    ),
    'pickup_notes'           => array(
		'title'           => __( 'Pickup Notes', 'postmates-wc' ),
		'type'            => 'text',
		'description'     => __( 'Notes for the Postmate courier during the pickup.', 'postmates-wc' ),
		'default'         => '',
    ),
    'delivery_submission'           => array(
		'title'           => __( 'Submit delivery to Posmates on this order status', 'postmates-wc' ),
		'type'            => 'select',
		'description'     => __( 'The event that the Delivery should be submitted to Postmates to start the delivery.', 'postmates-wc' ),
		'default'         => '',
        'options' =>  array(
            'manual'    => _x( 'I will handle this manually', 'postmates-wc' ),
            'pending'    => _x( 'Pending Payment', 'postmates-wc' ),
            'processing' => _x( 'Processing', 'postmates-wc' ),
            'on-hold'    => _x( 'On Hold', 'postmates-wc' ),
            'completed'  => _x( 'Completed', 'postmates-wc' )
        ),
        'desc_tip'        => true
    ),
    'delivery_cancellation'           => array(
		'title'           => __( 'Cancel Postmates delivery on this order status', 'postmates-wc' ),
		'type'            => 'select',
		'description'     => __( 'The event that the Delivery should be canceled. This will work only when the pickup has not started yet.', 'postmates-wc' ),
		'default'         => '',
        'options' =>  array(
            'manual'    => _x( 'I will handle this manually\'', 'postmates-wc' ),
            'cancelled'  => _x( 'Cancelled', 'postmates-wc' ),
            'refunded'   => _x( 'Refunded', 'postmates-wc' ),
            'failed'     => _x( 'Failed', 'postmates-wc' )
        ),
        'desc_tip'        => true
    ),
	'debug'      => array(
		'title'           => __( 'Debug Mode', 'postmates-wc' ),
		'label'           => __( 'Enable debug mode', 'postmates-wc' ),
		'type'            => 'checkbox',
		'default'         => 'no',
		'desc_tip'    => true,
		'description'     => __( 'Enable debug mode to show debugging information on the cart/checkout.', 'postmates-wc' )
	),
	'notify_admin_on_failure'      => array(
		'title'           => __( 'Send Admin Email on Failure.', 'postmates-wc' ),
		'label'           => __( 'Enable', 'postmates-wc' ),
		'type'            => 'checkbox',
		'default'         => 'no',
		'description'     => __( 'Send an email to site admin in case the delivery cannot submitted.', 'postmates-wc' )
	),
	'logging_enabled'      => array(
		'title'           => __( 'Enable Logging', 'postmates-wc' ),
		'type'            => 'checkbox',
		'default'         => 'no',
		'desc_tip'    => true,
		'description'     => __( 'Enable Logging to log Postmates actions to wc-logs dir.', 'postmates-wc' )
	)
);