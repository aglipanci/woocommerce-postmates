<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Adds Postmates Shipping funcitonality
 *
 * Class WC_Shipping_Postmates
 */
class WC_Shipping_Postmates extends WC_Shipping_Method
{

    /**
     * WC_Shipping_Postmates constructor.
     *
     * @param int $instance_id Shipping method instance ID.
     */
    public function __construct($instance_id = 0)
    {
        $this->id = 'postmates';
        $this->instance_id = absint($instance_id);
        $this->method_title = __('Postmates', 'postmates-wc');
        $this->method_description = __('Postmates Shipping Support', 'postmates-wc');

        $this->supports = array(
            'settings',
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal',
        );

        $this->init();

    }

    /**
     * Initialize Plugin settings
     */
    private function init()
    {
        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title', $this->method_title);
        $this->customer_id = $this->get_option('customer_id');
        $this->api_key = $this->get_option('api_key');
        $this->signature_secret_key = $this->get_option('signature_secret_key');

        $this->pickup_business_name = $this->get_option('pickup_business_name');
        $this->pickup_name = $this->get_option('pickup_name');
        $this->pickup_address = $this->get_option('pickup_address');
        $this->pickup_phone_number = $this->get_option('pickup_phone_number');
        $this->pickup_notes = $this->get_option('pickup_notes');

        $this->delivery_submission = $this->get_option('delivery_submission');
        $this->delivery_cancellation = $this->get_option('delivery_cancellation');

        $this->enabled = 'yes';
        $this->debug = $this->get_option('debug');

        $this->logging_enabled = $this->get_option('logging_enabled');


        add_action('woocommerce_update_options_shipping_' . $this->id, [$this, 'process_admin_options']);
    }

    /**
     * Form Fields
     */
    public function init_form_fields()
    {
        $this->instance_form_fields = include('data-postmates-instance-settings.php');
        $this->form_fields = include('data-postmates-settings.php');
    }

    /**
     * Main function to calculate shipping based on Postmates or Flat price
     *
     * @param array $package
     */
    public function calculate_shipping($package = [])
    {
        $instance_settings = $this->get_merged_instance_settings();

        $tip_to_charge_customer = 0;

        if ($instance_settings['driver_tip_charge_customer'] === 'yes') {
            if ($instance_settings['driver_tip_method'] === 'fixed') {
                $tip_to_charge_customer = (int)$instance_settings['driver_tip'];
            }

            if ($instance_settings['driver_tip_method'] === 'percentage') {
                $percentage_tip_to_charge_customer = (int)$instance_settings['driver_tip_percentage'];
                $card_total = WC()->cart->get_cart_contents_total();
                $tip_to_charge_customer = ($card_total * $percentage_tip_to_charge_customer) / 100;
            }
        }

        wc_postmates()->debug('Tip amount of ' . $tip_to_charge_customer . ' USD will be charged to the customer.');

        if (!empty($instance_settings['flat_rate']) && is_numeric($instance_settings['flat_rate'])) {

            $shippingCost = $instance_settings['flat_rate'] + $tip_to_charge_customer;

            $rate = array(
                'id' => $this->id,
                'label' => $this->title,
                'cost' => $shippingCost,
                'calc_tax' => 'box_packing'
            );

            $this->add_rate($rate);

        } else {

            if (empty($package['destination']['address']) || empty($package['destination']['city']) || empty($package['destination']['state']) || empty($package['destination']['postcode'])) {

                $rate = array(
                    'id' => $this->id,
                    'label' => $this->title,
                    'cost' => 0,
                    'calc_tax' => 'box_packing'
                );

                $this->add_rate($rate);

                return wc_postmates()->debug('Full address is missing so the delivery cost cannot calculated.');

            }

            $dropoff_address = $package['destination']['address'] . ', ' . $package['destination']['city'] . ', ' . $package['destination']['state'] . ' ' . $package['destination']['postcode'];

            wc_postmates()->debug('Requesting quote. Pickup address: ' . $instance_settings['pickup_address'] . '. Dropoff address: ' . $dropoff_address);

            $quote = wc_postmates()->api()->getQuote($instance_settings['pickup_address'], $dropoff_address);

            wc_postmates()->debug('Request response: ' . print_r($quote, true));

            if (!is_wp_error($quote)) {

                $shippingCost = number_format(($quote['fee'] / 100) + $tip_to_charge_customer, 2, '.', ' ');

                $shippingCost = apply_filters('postmates_shipping_cost', (double) $shippingCost, $quote, $tip_to_charge_customer);

                $rate = array(
                    'id' => $this->id,
                    'label' => $this->title,
                    'cost' => $shippingCost,
                    'calc_tax' => 'box_packing'
                );

                $this->add_rate($rate);

            }

        }

    }

    /**
     * Check if settings are not empty
     */
    public function admin_options()
    {
        // Check users environment supports this method
        $this->environment_check();

        // Show settings
        parent::admin_options();
    }

    /**
     * Show error in case of config missing
     */
    private function environment_check()
    {
        if ((!$this->customer_id || !$this->api_key || !$this->pickup_name || !$this->pickup_address || !$this->pickup_phone_number ) && $this->enabled == 'yes') {
            echo '<div class="error">
				<p>' . __('Postmates is enabled, but one of the fields customer_id, api_key, pickup_name, pickup_address or pickup_phone_number has not been set.', 'wf-shipping-dhl') . '</p>
			</div>';
        }

        if (!$this->signature_secret_key && $this->enabled == 'yes') {
            echo '<div class="error">
				<p>' . __('Postmates is enabled, but the signature_secret_key is missing. Webhooks wont work until this is set.', 'wf-shipping-dhl') . '</p>
			</div>';
        }
    }

    /**
     * Get instance settings merged with default settings.
     *
     * @return array
     */
    public function get_merged_instance_settings()
    {
        $instance_settings = $this->instance_settings;

        /**
         * General Settings should not have default values from instance.
         */
        $keys_not_to_be_merged = [
            'delivery_submission',
            'delivery_cancellation',
            'debug',
            'send_products_to_postmates',
            'default_product_size',
            'notify_admin_on_failure',
            'logging_enabled',
        ];

        $instance_settings = array_diff_key($instance_settings, array_flip($keys_not_to_be_merged));

        $instance_settings = array_filter($instance_settings, function ($option_value) {
            return $option_value !== '';
        });

        return array_merge($this->settings, $instance_settings);
    }

}
