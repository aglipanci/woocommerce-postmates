<?php

if (!defined('ABSPATH')) {
    exit;
}

use Postmates\PostmatesClient;
use Postmates\PostmatesException;
use Postmates\PostmatesWebhook;
use Postmates\Resources\DeliveryQuote;
use Postmates\Resources\Delivery;

/**
 * Class Postmates_API
 */
class Postmates_API
{
    /**
     * Postmates Client
     *
     * @var PostmatesClient
     */
    private $client;

    /**
     * Postmates_API constructor.
     * @param $config
     */
    public function __construct($config)
    {
        $this->client = new PostmatesClient($config);
    }

    public static function getDeliveryStatus($status, $type = 'user')
    {
        if ($type == 'admin') {

            switch ($status) {

                case 'sent':
                    return __('Sent to Postmates', 'postmates-wc');
                    break;
                case Delivery::STATUS_PICKUP:
                    return __('Pickup', 'postmates-wc');
                    break;
                case Delivery::STATUS_PICKUP_COMPLETE:
                    return __('Pickup Complete', 'postmates-wc');
                    break;
                case Delivery::STATUS_DROPOFF:
                    return __('Dropoff', 'postmates-wc');
                    break;
                case Delivery::STATUS_DELIVERED:
                    return __('Delivered', 'postmates-wc');
                    break;
                default:
                    return null;
                    break;

            }

        }

        if ($type == 'user') {

            switch ($status) {

                case 'sent':
                    return __('Delivery has been accepted and it will start soon.', 'postmates-wc');
                    break;
                case Delivery::STATUS_PICKUP:
                    return __('Delivery started. Courier is on his way to our store to pickup your order.', 'postmates-wc');
                    break;
                case Delivery::STATUS_PICKUP_COMPLETE:
                    return __('Pickup completed. Courier is on his way to deliver the order.', 'postmates-wc');
                    break;
                case Delivery::STATUS_DROPOFF:
                    return __('Courier is on his way to deliver the order on your address.', 'postmates-wc');
                    break;
                case Delivery::STATUS_DELIVERED:
                    return __('The order has been delivered.', 'postmates-wc');
                    break;
                default:
                    return null;
                    break;

            }

        }


    }

    /**
     * Get quote functionality
     *
     * @param $pickup
     * @param $dropoff
     * @return mixed|WP_Error
     */
    public function getQuote($pickup, $dropoff)
    {

        $delivery_quote = new DeliveryQuote($this->client);

        try {

            return $delivery_quote->getQuote($pickup, $dropoff);

        } catch (PostmatesException $e) {

            return new WP_Error('postmate_error', $e->getMessage());

        }

    }

    /**
     * Submit a delivery to Postmates API
     *
     * @param $params
     * @return mixed|WP_Error
     */
    public function submitDeliveryRequest($params)
    {
        $delivery = new Delivery($this->client);

        try {

            return $delivery->create($params);

        } catch (PostmatesException $e) {

            return new WP_Error('postmate_error', $e->getMessage(), $e->getInvalidParams());

        }
    }

    /**
     * Cancel a delivery by ID
     *
     * @param $delivery_id
     * @return mixed|WP_Error
     */
    public function cancelDelivery($delivery_id)
    {
        $delivery = new Delivery($this->client);

        try {

            return $delivery->cancel($delivery_id);

        } catch (PostmatesException $e) {

            return new WP_Error('postmate_error', $e->getMessage(), $e->getInvalidParams());

        }
    }

    /**
     * Handles Webhook functionality
     *
     * @param $signature_secret
     * @return PostmatesWebhook
     */
    public function webhooks($signature_secret)
    {
        return new PostmatesWebhook($signature_secret);
    }

    /**
     * Format phone number as needed by Postmates
     *
     * @param $number
     * @return string
     */
    public function formatPhoneNumber($number)
    {

        $number = str_replace('(', '', $number);
        $number = str_replace(')', '', $number);
        $number = str_replace('-', '', $number);
        $number = str_replace(' ', '', $number);
        $number = str_replace('.', '', $number);

        return substr($number, 0, 3) . "-" . substr($number, 3, 3) . "-" . substr($number, 6);

    }

    /**
     * Cancel a delivery by ID
     *
     * @param $delivery_id
     * @param integer $tip_in_usd
     * @return mixed|WP_Error
     */
    public function addTip($delivery_id, $tip_in_usd)
    {
        $tip_in_cents = $tip_in_usd * 100;

        try {

            $delivery = new Delivery($this->client);
            return $delivery->addTip($delivery_id, $tip_in_cents);

        } catch (PostmatesException $e) {

            return new WP_Error('postmate_error', $e->getMessage(), $e->getInvalidParams());

        }
    }

}
