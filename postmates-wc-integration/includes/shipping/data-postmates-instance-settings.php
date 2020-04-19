<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Array of settings
 */
return array(
    'title'                   => array(
        'title'       => __('Method Title', 'postmates-wc'),
        'type'        => 'text',
        'description' => __('This controls the title which the user sees during checkout.', 'postmates-wc'),
        'default'     => __('Postmates', 'postmates-wc'),
        'desc_tip'    => true
    ),
    'flat_rate'               => array(
        'title'       => __('Flat Rate', 'postmates-wc'),
        'type'        => 'text',
        'description' => __('You can make your customers pay a flat rate for the deliveries.', 'postmates-wc'),
        'default'     => '',
        'desc_tip'    => true
    ),
    'pickup_business_name'    => array(
        'title'       => __('Pickup Business Name', 'postmates-wc'),
        'type'        => 'text',
        'description' => __('Your business name. If left empty the default one from settings will be used.', 'postmates-wc'),
        'default'     => '',
    ),
    'pickup_name'             => array(
        'title'       => __('Pickup Name', 'postmates-wc'),
        'type'        => 'text',
        'description' => __('Your business manager name. If left empty the default one from settings will be used.', 'postmates-wc'),
        'default'     => '',
    ),
    'pickup_address'          => array(
        'title'       => __('Pickup Address', 'postmates-wc'),
        'type'        => 'text',
        'description' => __('Your business address. If left empty the default one from settings will be used.', 'postmates-wc'),
        'default'     => '',
    ),
    'pickup_phone_number'     => array(
        'title'       => __('Pickup Phone Number', 'postmates-wc'),
        'type'        => 'text',
        'description' => __('Your business phone number. If left empty the default one from settings will be used.', 'postmates-wc'),
        'default'     => '',
    ),
    'pickup_notes'            => array(
        'title'       => __('Pickup Notes', 'postmates-wc'),
        'type'        => 'text',
        'description' => __('Notes for the Postmate courier during the pickup. If left empty the default one from settings will be used.', 'postmates-wc'),
        'default'     => '',
    ),
);
