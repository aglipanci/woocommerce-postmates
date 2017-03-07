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
     */
    public function __construct()
    {

        $this->id = 'postmates';
        $this->method_title = __('Postmates', 'postmates-wc');
        $this->method_description = __('Postmates Shipping Support', 'postmates-wc');
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

        $this->flat_rate = $this->get_option('flat_rate');
        $this->delivery_submission = $this->get_option('delivery_submission');
        $this->delivery_cancellation = $this->get_option('delivery_cancellation');

        $this->enabled = $this->get_option('enabled');
        $this->debug = $this->get_option('debug');

        $this->logging_enabled = $this->get_option('logging_enabled');


        add_action('woocommerce_update_options_shipping_' . $this->id, [$this, 'process_admin_options']);

    }

    /**
     * Form Fields
     */
    public function init_form_fields()
    {

        $this->form_fields = include('data-postmates-settings.php');

    }

    /**
     * Main function to calculate shipping based on Postmates or Flat price
     *
     * @param array $package
     */
    public function calculate_shipping($package = [])
    {

        if (isset($this->flat_rate) && !empty($this->flat_rate) && is_numeric($this->flat_rate)) {

            $rate = array(
                'id'       => $this->id,
                'label'    => $this->title,
                'cost'     => $this->flat_rate,
                'calc_tax' => 'box_packing'
            );


            $this->add_rate($rate);

        } else {

            if (empty($package['destination']['address']) || empty($package['destination']['city']) || empty($package['destination']['state']) || empty($package['destination']['postcode'])) {

                return wc_postmates()->debug('Full address is missing so the delivery cost cannot calculated.');

            }

            $dropoff_address = $package['destination']['address'] . ', ' . $package['destination']['city'] . ', ' . $package['destination']['state'] . ' ' . $package['destination']['postcode'];

            wc_postmates()->debug('Requesting quote. Pickup address: ' . $this->get_option('pickup_address') . '. Dropoff address: ' . $dropoff_address);

            $quote = wc_postmates()->api()->getQuote($this->get_option('pickup_address'), $dropoff_address);

            wc_postmates()->debug('Request response: ' . print_r($quote, true));

            if (!is_wp_error($quote)) {

                $rate = array(
                    'id'       => $this->id,
                    'label'    => $this->title,
                    'cost'     => number_format(($quote['fee'] / 100), 2, '.', ' '),
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

        if ((!$this->customer_id || !$this->api_key) && $this->enabled == 'yes') {
            echo '<div class="error">
				<p>' . __('Postmates is enabled, but the customer_id and api_key has not been set.', 'wf-shipping-dhl') . '</p>
			</div>';
        }

        if (!$this->signature_secret_key && $this->enabled == 'yes') {
            echo '<div class="error">
				<p>' . __('Postmates is enabled, but the signature_secret_key is missing. Webhooks wont work until this is set.', 'wf-shipping-dhl') . '</p>
			</div>';
        }

    }


}
