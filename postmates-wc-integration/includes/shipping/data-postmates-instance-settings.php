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
);
