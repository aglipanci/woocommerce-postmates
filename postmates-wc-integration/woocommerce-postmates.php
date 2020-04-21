<?php
/*
	Plugin Name: Postmates Shipping for WooCommerce
	Description: Postmates Shipping & Delivery Tracking Integration for WooCommerce
	Version: 1.3.0
	Author: Agli Pançi
	Author URI: www.aglipanci.com
*/

use Postmates\Resources\Delivery;

class WC_Postmates
{
    /**
     * Class Instance
     *
     * @var null
     */
    private static $instance = null;

    /**
     * Plugin Settings
     *
     * @var
     */
    protected $settings;

    /**
     * Postmates API Instance
     *
     * @var null
     */
    private $api = null;

    /**
     * WC_Logger instance
     *
     * @var null
     */
    private $logger = null;

    /**
     * WC_Postmates constructor.
     */
    private function __construct()
    {
        $this->init();
        $this->hooks();

    }

    /**
     * Init function
     */
    public function init()
    {
        $this->settings = get_option('woocommerce_postmates_settings');
    }

    /**
     * Hooks
     */
    private function hooks()
    {
        add_action('woocommerce_shipping_init', array($this, 'postmates_woocommerce_shipping_init'));
        add_filter('woocommerce_shipping_methods', array($this, 'postmates_woocommerce_shipping_methods'));

        add_action('woocommerce_thankyou', array($this, 'handle_order_status_change'));
        add_action('woocommerce_order_status_changed', array($this, 'handle_order_status_change'));

        add_action('template_redirect', array($this, 'handle_postmates_webooks'));

        add_filter('manage_edit-shop_order_columns', array($this, 'add_postmates_delivery_column'));
        add_action('manage_shop_order_posts_custom_column', array($this, 'delivery_status_on_backend'), 10, 2);

        add_action('woocommerce_order_details_after_order_table', array($this, 'show_delivery_details_on_order'), 20);

        add_action('postmate_status_update', array($this, 'add_tip_to_driver'));
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

    /**
     * WC_Shipping_Postmates
     */
    public function postmates_woocommerce_shipping_init()
    {
        require_once('includes/shipping/class-wc-shipping-postmates.php');
    }

    /**
     * Add Postmates as a Shippin method
     *
     * @param $methods
     * @return array
     */
    public function postmates_woocommerce_shipping_methods($methods)
    {
        $methods['postmates'] = 'WC_Shipping_Postmates';
        return $methods;
    }

    /**
     * Order Status Handle to created or delete Postmates delivery
     *
     * @param $order_id
     */
    public function handle_order_status_change($order_id)
    {
        $order = new WC_Order($order_id);

        foreach ($order->get_items('shipping') as $item_id => $order_item_shipping) {

            if ($order_item_shipping->get_method_id() === 'postmates') {

                $shipping_method_instance_id = $order_item_shipping->get_instance_id();
                $postmates_shipping = new WC_Shipping_Postmates($shipping_method_instance_id);
                $postmates_shipping_settings = $postmates_shipping->get_merged_instance_settings();

                if ($order->get_status() == $postmates_shipping_settings['delivery_submission']) {

                    $delivery_id = get_post_meta($order_id, 'postmates_delivery_id', true);

                    if (!$delivery_id) {

                        $dropoff_address = $order->get_shipping_address_1() . ', ' . $order->get_shipping_city() . ', ' . $order->get_shipping_state() . ' ' . $order->get_shipping_postcode();

                        $params = [
                            'manifest'             => 'Order Delivery. Order #' . $order->get_order_number(),
                            'pickup_business_name' => $postmates_shipping_settings['pickup_business_name'],
                            'pickup_name'          => $postmates_shipping_settings['pickup_name'],
                            'pickup_address'       => $postmates_shipping_settings['pickup_address'],
                            'pickup_phone_number'  => wc_postmates()->api()->formatPhoneNumber($postmates_shipping_settings['pickup_phone_number']),
                            'dropoff_name'         => $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
                            'dropoff_address'      => $dropoff_address,
                            'dropoff_phone_number' => wc_postmates()->api()->formatPhoneNumber($order->get_billing_phone()),
                        ];

                        if (!empty($order->get_shipping_company())) {

                            $params['dropoff_business_name'] = $order->get_shipping_company();

                        }

                        if (!empty($order->get_shipping_address_2())) {

                            $params['dropoff_notes'] = $order->get_shipping_address_2();

                        }

                        $delivery = wc_postmates()->api()->submitDeliveryRequest($params);

                        wc_postmates()->debug('Delivery submitted with this parameters: ' . print_r($params, true));
                        wc_postmates()->debug('Postmates response: ' . print_r($delivery, true));

                        if (!is_wp_error($delivery)) {

                            update_post_meta($order_id, 'postmates_delivery_id', $delivery['id']);
                            update_post_meta($order_id, 'postmates_delivery_status', 'sent');

                        } else {

                            wp_mail(get_option('admin_email'), 'Postmates Delivery Failed', print_r($delivery, true));

                        }

                    }

                }

                if ($order->get_status() == $postmates_shipping_settings['delivery_cancellation']) {

                    $delivery_id = get_post_meta($order_id, 'postmates_delivery_id', true);

                    if ($delivery_id) {

                        $delivery_cancellation = wc_postmates()->api()->cancelDelivery($delivery_id);

                        wc_postmates()->debug('Canceling Delivery with ID: ' . $delivery_id);
                        wc_postmates()->debug('Delivery cancellation response: '. print_r($delivery_cancellation, true));

                        if (!is_wp_error($delivery_cancellation)) {

                            update_post_meta($order_id, 'postmates_delivery_status', $delivery_cancellation['status']);

                        } else {

                            wp_mail(get_option('admin_email'), 'Postmates Delivery Cancellation Failed', print_r($delivery_cancellation, true));

                        }

                    }

                }

            }


        }
    }

    /**
     * @return null|Postmates_API
     */
    public function api()
    {
        if (is_object($this->api)) {
            return $this->api;
        }

        $this->api = new Postmates_API([
            'customer_id' => $this->settings['customer_id'],
            'api_key'     => $this->settings['api_key']
        ]);

        return $this->api;
    }

    /**
     * Debug Function to log messages or shown on frontend
     *
     * @param $message
     * @param string $type
     */
    public function debug($message, $type = 'notice')
    {

        if ($this->settings['debug'] == 'yes' && !is_admin()) {
            wc_add_notice($message, $type);
        }

        if (!is_object($this->logger)) {
            $this->logger = new WC_Logger();
        }

        if ($this->settings['logging_enabled'] == 'yes') {
            $this->logger->add('postmates', $message);
        }

    }

    /**
     * Webhook Handler
     */
    public function handle_postmates_webooks()
    {

        if (isset($_GET['postmates_webhook']) && $_GET['postmates_webhook'] == 1) {

            $request = wc_postmates()->api()->webhooks($this->settings['signature_secret_key'])->parseRequest();

            $this->debug('Raw Webhook Request' . print_r($request, true));

            if (isset($request['kind']) && !empty($request['kind']) && $request['kind'] == 'event.delivery_status') {

                $order_post = get_posts([
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
                ]);

                if (count($order_post) > 0) {

                    // delivery post from db
                    $order_post = $order_post[0];
                    $order = new WC_Order($order_post->ID);

                    $this->debug('Found order WC' . print_r($order, true));

                    update_post_meta($order->get_id(), 'postmates_delivery_status', $request['data']['status']);
                    do_action('postmate_status_update', $order, $request);

                }

            }

            // return a 200 response to Postmates
            header('Content-Type:application/json');
            echo json_encode(['success' => 1]);
            exit();

        }

    }

    /**
     * Show shipping information on order view
     *
     * @param $order WC_Order
     */
    public function show_delivery_details_on_order($order)
    {
        $order_shipping_methods = $order->get_shipping_methods();
        $shipping_method = @array_shift($order_shipping_methods);
        $shipping_method_id = $shipping_method['method_id'];

        if ($shipping_method_id !== 'postmates')
            return;

        $delivery_status = get_post_meta($order->get_id(), 'postmates_delivery_status', true);
        $text_status = wc_postmates()->api()->getDeliveryStatus($delivery_status);

        if (!$text_status) {

            $text_status = 'There is no information available at this moment.';

        }

        ?>

        <header><h2>Shipping</h2></header>

        <table class="shop_table postmates_delivery">
            <tbody>
            <tr>
                <th>Shipping method:</th>
                <td><?php echo $shipping_method['name']; ?></td>
            </tr>

            <tr>
                <th>Delivery status:</th>
                <td><?php echo $text_status; ?></td>
            </tr>

            </tbody>
        </table>

        <?php
    }

    /**
     * Add Postmates Column on Backend
     *
     * @param $columns
     * @return mixed
     */
    function add_postmates_delivery_column($columns)
    {
        $columns['postmates_delivery'] = 'Postmates';
        return $columns;
    }

    /**
     * Show Postmates Delivery Status
     *
     * @param $col
     * @param $post_id
     */
    function delivery_status_on_backend($col, $post_id)
    {

        if ($col == 'postmates_delivery') {

            $delivery_status = get_post_meta($post_id, 'postmates_delivery_status', true);
            $text_status = wc_postmates()->api()->getDeliveryStatus($delivery_status, 'admin');

            if ($text_status) {

                echo $text_status;

            } else {

                echo 'N/A';
            }

        }

    }

    /**
     * @param WC_Order $order
     * @param array $postmates_hook_request
     */
    public function add_tip_to_driver(WC_Order $order, array $postmates_hook_request)
    {
        if ($postmates_hook_request['data']['status'] === Delivery::STATUS_DELIVERED) {
            $driver_tip_in_usd = (int) $this->settings['driver_tip'];

            if ($driver_tip_in_usd > 0) {
                $response = $this->api()->addTip($postmates_hook_request['delivery_id'], $driver_tip_in_usd);
                $this->debug('Driver Tip Response' . print_r($response, true));
            }
        }
    }

}

/**
 * @return null|WC_Postmates
 */
function wc_postmates()
{
    return WC_Postmates::get();
}

/**
 * Load Libraries and load main class
 */
require_once 'vendor/autoload.php';
wc_postmates();
