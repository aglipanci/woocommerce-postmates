<?php
/*
	Plugin Name: Postmates Shipping WooCommerce Extension
	Description: Postmates Shipping WooCommerce Extension
	Version: 0.0.9
	Author: Agli Panci
	Author URI: www.aglipanci.com
*/

class WC_Postmates
{

    private static $instance = null;
    protected $settings;
    private $api = null;
    private $logger = null;

    private function __construct()
    {
        $this->settings = get_option('woocommerce_postmates_settings');
        $this->hooks();

    }

    private function hooks()
    {
        add_action('woocommerce_shipping_init', [$this, 'postmates_woocommerce_shipping_init']);
        add_filter('woocommerce_shipping_methods', [$this, 'postmates_woocommerce_shipping_methods']);

        add_action('woocommerce_thankyou', [$this, 'handle_order_status_change']);
        add_action('woocommerce_order_status_changed', [$this, 'handle_order_status_change']);

        add_action('template_redirect', [$this, 'handle_postmates_webooks']);
    }

    /**
     * Get singleton instance
     */
    public static function get()
    {

        if (self::$instance == null) {
            self::$instance = new self();
        }

        return self::$instance;

    }

    public function handle_postmates_webooks()
    {

        if(isset($_GET['postmates_webhook']) && $_GET['postmates_webhook'] == 1) {

            $request = wc_postmates()->api()->webhooks($this->settings['signature_secret_key'])->parseRequest();

            $this->debug('Raw Webhook Request' . print_r($request, true));

            if(isset($request['kind']) && !empty($request['kind']) && $request['kind'] == 'event.delivery_status') {

                $order_post = get_posts( [
                    'post_type'   => 'shop_order',
                    'post_status' => 'any',
                    'numberposts' => 1,
                    'meta_query'  => [
                        'relation' => 'AND',
                        [
                            'key'     => 'postmates_delivery_id',
                            'value'   => $request['delivery_id'],
                            'compare' => '='
                        ]
                    ]
                ] );

                if ( count( $order_post ) > 0 ) {

                    // delivery post from db
                    $order_post = $order_post[0];
                    $order = new WC_Order($order_post->ID);

                    $this->debug('Found order WC' . print_r($order, true));

                    update_post_meta($order->id, 'postmates_delivery_status', $request['data']['status']);
                    do_action('postmate_status_update', $order, $request);

                }

            }

            header('Content-Type:application/json');
            echo json_encode(['success' => 1]);
            exit();

        }

    }


    public function handle_order_status_change($order_id)
    {
        $order = new WC_Order($order_id);

        wc_postmates()->debug('handle_order_status_change. OrderID: ' . $order_id . '. Delivery Submission: ' . $this->settings['delivery_submission'] . '. Order Status: ' . $order->status);

        if ($order->status == $this->settings['delivery_submission']) {

            $delivery_id = get_post_meta($order_id, 'postmates_delivery_id', true);

            if (!$delivery_id) {

                $dropoff_address = $order->shipping_address_1 . ', ' . $order->shipping_city . ', ' . $order->shipping_state . ' ' . $order->shipping_postcode;

                $params = [
                    'manifest' => 'Order Delivery',
                    'pickup_business_name' => $this->settings['pickup_business_name'],
                    'pickup_name' => $this->settings['pickup_name'],
                    'pickup_address' => $this->settings['pickup_address'],
                    'pickup_phone_number' => wc_postmates()->api()->format_phone_number($this->settings['pickup_phone_number']),
                    'dropoff_name' => $order->shipping_first_name . ' ' . $order->shipping_last_name,
                    'dropoff_address' => $dropoff_address,
                    'dropoff_phone_number' => wc_postmates()->api()->format_phone_number($order->billing_phone),
                ];

                if (!empty($order->shipping_company)) {

                    $params['dropoff_business_name'] = $order->shipping_company;

                }

                if (!empty($order->shipping_address_2)) {

                    $params['dropoff_notes'] = $order->shipping_address_2;

                }

                $delivery = wc_postmates()->api()->submitDeliveryRequest($params);

                wc_postmates()->debug('Delivery submitted. Params:' . print_r($params, true) . '. ' . print_r($delivery, true));

                if (!is_wp_error($delivery)) {

                    update_post_meta($order_id, 'postmates_delivery_id', $delivery['id']);
                    update_post_meta($order_id, 'postmates_delivery_status', 'sent');

                } else {

                    wp_mail(get_option('admin_email'), 'Postmates Delivery Failed', print_r($delivery, true));

                }

            }

        }

        if ($order->status == $this->settings['delivery_cancellation']) {

            $delivery_id = get_post_meta($order_id, 'postmates_delivery_id', true);

            if ($delivery_id) {

                $delivery_cancellation = wc_postmates()->api()->cancelDelivery($delivery_id);

                wc_postmates()->debug('Delivery cancellation. DeliveryID:' . $delivery_id . ' ' . print_r($delivery_cancellation, true));

                if (!is_wp_error($delivery_cancellation)) {

                    update_post_meta($order_id, 'postmates_delivery_status', $delivery_cancellation['status']);

                } else {

                    wp_mail(get_option('admin_email'), 'Postmates Delivery Cancellation Failed', print_r($delivery_cancellation, true));

                }

            }

        }


    }

    public function debug($message, $type = 'notice')
    {

        if ($this->settings['debug'] && !is_admin()) {
            wc_add_notice($message, $type);
        }

        if (!is_object($this->logger)) {
            $this->logger = new WC_Logger();
        }

        if ($this->settings['logging_enabled']) {
            $this->logger->add('postmates', $message);
        }

    }

    public function api()
    {
        if (is_object($this->api)) {
            return $this->api;
        }

        $this->api = new Postmates_API([
            'customer_id' => $this->settings['customer_id'],
            'api_key' => $this->settings['api_key']
        ]);

        return $this->api;
    }

    public function postmates_woocommerce_shipping_init()
    {
        require_once('includes/shipping/class-wc-shipping-postmates.php');
    }

    public function postmates_woocommerce_shipping_methods($methods)
    {
        $methods[] = 'WC_Shipping_Postmates';
        return $methods;
    }


}

function wc_postmates()
{
    return WC_Postmates::get();
}


require_once 'vendor/autoload.php';
wc_postmates();