<?php

use Postmates\PostmatesClient;
use Postmates\PostmatesException;
use Postmates\PostmatesWebhook;
use Postmates\Resources\DeliveryQuote;
use Postmates\Resources\Delivery;

class Postmates_API
{
    private $client;

    public function __construct($config)
    {
        $this->client = new PostmatesClient($config);
    }

    public function getQuote($pickup, $dropoff)
    {

        $delivery_quote = new DeliveryQuote($this->client);

        try {

            return $delivery_quote->getQuote($pickup, $dropoff);

        } catch (PostmatesException $e) {

            return new WP_Error('postmate_error', $e->getMessage());

        }

    }

    public function submitDeliveryRequest($params)
    {
        $delivery = new Delivery($this->client);

        try {

            return $delivery->create($params);

        } catch (PostmatesException $e) {

            return new WP_Error('postmate_error', $e->getMessage(), $e->getInvalidParams());

        }
    }

    public function cancelDelivery($delivery_id)
    {
        $delivery = new Delivery($this->client);

        try {

            return $delivery->cancel($delivery_id);

        } catch (PostmatesException $e) {

            return new WP_Error('postmate_error', $e->getMessage(), $e->getInvalidParams());

        }
    }

    public function webhooks($signature_secret)
    {
        return new PostmatesWebhook($signature_secret);
    }

    public function format_phone_number( $number ) {

        $number = str_replace( '(', '', $number );
        $number = str_replace( ')', '', $number );
        $number = str_replace( '-', '', $number );
        $number = str_replace( ' ', '', $number );
        $number = str_replace( '.', '', $number );

        return substr( $number, 0, 3 ) . "-" . substr( $number, 3, 3 ) . "-" . substr( $number, 6 );

    }

}