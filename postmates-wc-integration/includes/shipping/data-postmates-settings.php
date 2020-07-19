<?php

if (!defined('ABSPATH')) {
    exit;
}

/*
 * WooCommerce Order Statuses to be used in settings
 */
$delivery_submission_statuses = [];
$delivery_cancellation_statuses = [];

foreach (wc_get_order_statuses() as $order_status_key => $order_status_label) {
    $new_order_status_key = str_replace('wc-', '', $order_status_key);
    $delivery_submission_statuses[$new_order_status_key] = $order_status_label;
    $delivery_cancellation_statuses[$new_order_status_key] = $order_status_label;
}

/**
 * Array of settings
 */
return array(
    'title' => array(
        'title' => __('Method Title', 'postmates-wc'),
        'type' => 'text',
        'description' => __('This controls the title which the user sees during checkout.', 'postmates-wc'),
        'default' => __('Postmates', 'postmates-wc'),
        'desc_tip' => true
    ),
    'customer_id' => array(
        'title' => __('Customer ID', 'postmates-wc'),
        'type' => 'text',
        'description' => __('Get it from Posmates Developer Dashboard.', 'postmates-wc'),
        'default' => ''
    ),
    'api_key' => array(
        'title' => __('API Key', 'postmates-wc'),
        'type' => 'text',
        'description' => __('Get it from Posmates Developer Dashboard.', 'postmates-wc'),
        'default' => '',
    ),
    'signature_secret_key' => array(
        'title' => __('Signature Secret  Key', 'postmates-wc'),
        'type' => 'text',
        'description' => __('Used to validate Webhook requests.', 'postmates-wc'),
        'default' => '',
        'desc_tip' => true
    ),
    'flat_rate' => array(
        'title' => __('Flat Rate in $ (USD)', 'postmates-wc'),
        'type' => 'number',
        'description' => __('You can make your customers pay a flat rate for the deliveries, no matter what you pay to Posmates.', 'postmates-wc'),
        'default' => '',
        'desc_tip' => true
    ),
    'driver_tip_method' => array(
        'title' => __('Driver Tip Method', 'postmates-wc'),
        'type' => 'select',
        'description' => __('You can tip the driver after the delivery is completed. The tip can be fixed or a percentage based on the order total.', 'postmates-wc'),
        'default' => 'fixed',
        'options' => array(
            'fixed' => _x('Fixed', 'postmates-wc'),
            'percentage' => _x('Percentage', 'postmates-wc'),
        ),
        'desc_tip' => true
    ),
    'driver_tip_charge_customer' => array(
        'title' => __('Charge the tip to the customer.', 'postmates-wc'),
        'label' => __('Enable', 'postmates-wc'),
        'type' => 'checkbox',
        'default' => 'no',
        'desc_tip' => true,
        'description' => __('When enabled the tip charge (fixed or percentage) will be added on top of the delivery quote or flat fee and charged to the customer. Otherwise the store will pay the tip.', 'postmates-wc')
    ),
    'driver_tip' => array(
        'title' => __('Driver Tip in $ (USD)', 'postmates-wc'),
        'type' => 'number',
        'description' => __('You can automatically add a tip after each delivery completion. If left empty or 0 nothing will be applied.', 'postmates-wc'),
        'default' => '',
        'desc_tip' => true,
    ),
    'driver_tip_percentage' => array(
        'title' => __('Driver Tip in % of order total', 'postmates-wc'),
        'type' => 'number',
        'description' => __('% of the order that should be applied as a tip. If left empty or 0 nothing will be applied.', 'postmates-wc'),
        'default' => '',
        'desc_tip' => true
    ),
    'pickup_business_name' => array(
        'title' => __('Pickup Business Name', 'postmates-wc'),
        'type' => 'text',
        'description' => __('Your business name.', 'postmates-wc'),
        'default' => '',
    ),
    'pickup_name' => array(
        'title' => __('Pickup Name', 'postmates-wc'),
        'type' => 'text',
        'description' => __('Your business manager name.', 'postmates-wc'),
        'default' => '',
    ),
    'pickup_address' => array(
        'title' => __('Pickup Address', 'postmates-wc'),
        'type' => 'text',
        'description' => __('Your business address.', 'postmates-wc'),
        'default' => '',
    ),
    'pickup_phone_number' => array(
        'title' => __('Pickup Phone Number', 'postmates-wc'),
        'type' => 'text',
        'description' => __('Your business phone number.', 'postmates-wc'),
        'default' => '',
    ),
    'pickup_notes' => array(
        'title' => __('Pickup Notes', 'postmates-wc'),
        'type' => 'text',
        'description' => __('Notes for the Postmate courier during the pickup.', 'postmates-wc'),
        'default' => '',
    ),
    'delivery_submission' => array(
        'title' => __('Submit delivery to Posmates on this order status', 'postmates-wc'),
        'type' => 'select',
        'description' => __('The event that the Delivery should be submitted to Postmates to start the delivery.', 'postmates-wc'),
        'default' => '',
        'options' => $delivery_submission_statuses,
        'desc_tip' => true
    ),
    'delivery_cancellation' => array(
        'title' => __('Cancel Postmates delivery on this order status', 'postmates-wc'),
        'type' => 'select',
        'description' => __('The event that the Delivery should be canceled. This will work only when the pickup has not started yet.', 'postmates-wc'),
        'default' => '',
        'options' => $delivery_cancellation_statuses,
        'desc_tip' => true
    ),
    'send_products_to_postmates' => array(
        'title' => __('Send order products to Postmates', 'postmates-wc'),
        'label' => __('Enable', 'postmates-wc'),
        'type' => 'checkbox',
        'default' => 'no',
        'desc_tip' => true,
        'description' => __('When enabled the list of order products will be send to Postmates delivery manifest.', 'postmates-wc')
    ),
    'default_product_size' => array(
        'title' => __('Default product size', 'postmates-wc'),
        'type' => 'select',
        'description' => __('Valid only if "Send order products to Postmates" is enabled. This can also be set individually to each product on the Shipping section.', 'postmates-wc'),
        'default' => 'small',
        'options' => array(
            'small'     => __( 'Small - You can carry it with one hand.', 'postmates-wc' ),
            'medium'  => __( 'Medium - You need a tote bag to carry it.', 'postmates-wc' ),
            'large' => __( 'Large - You need two hands to carry', 'postmates-wc' ),
            'xlarge' => __( 'X-Large - You will need to make multiple trips to/from a vehicle to transport.', 'postmates-wc' ),
        ),
        'desc_tip' => true
    ),
    'debug' => array(
        'title' => __('Debug Mode', 'postmates-wc'),
        'label' => __('Enable debug mode', 'postmates-wc'),
        'type' => 'checkbox',
        'default' => 'no',
        'desc_tip' => true,
        'description' => __('Enable debug mode to show debugging information on the cart/checkout.', 'postmates-wc')
    ),
    'notify_admin_on_failure' => array(
        'title' => __('Send Admin Email on Failure.', 'postmates-wc'),
        'label' => __('Enable', 'postmates-wc'),
        'type' => 'checkbox',
        'default' => 'no',
        'description' => __('Send an email to site admin in case the delivery cannot submitted.', 'postmates-wc')
    ),
    'logging_enabled' => array(
        'title' => __('Enable Logging', 'postmates-wc'),
        'type' => 'checkbox',
        'default' => 'no',
        'desc_tip' => true,
        'description' => __('Enable Logging to log Postmates actions to wc-logs dir.', 'postmates-wc')
    ),
);
