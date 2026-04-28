<?php

namespace JJPWS\Services;

use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Checkout\Session;
use Stripe\Subscription;
use Stripe\Price;
use JJPWS\Models\SubscriptionModel;

class StripeService {

    public function __construct() {
        Stripe::setApiKey( $this->get_secret_key() );
        Stripe::setApiVersion( '2023-10-16' );
    }

    /**
     * Create a Stripe Checkout Session for a recurring subscription.
     *
     * Returns the hosted checkout URL.
     */
    public function create_checkout_session(
        int    $user_id,
        int    $monthly_price_cents,
        string $lot_size_category,
        int    $dog_count,
        string $frequency,
        string $street,
        string $city,
        string $state,
        string $zip,
        ?float $lat,
        ?float $lng,
        ?int   $sqft,
        string $success_url,
        string $cancel_url
    ): string {
        $user            = get_userdata( $user_id );
        $stripe_cust_id  = get_user_meta( $user_id, 'jjpws_stripe_customer_id', true );

        if ( empty( $stripe_cust_id ) ) {
            $customer       = Customer::create( [
                'email' => $user->user_email,
                'name'  => $user->display_name,
                'metadata' => [ 'wp_user_id' => $user_id ],
            ] );
            $stripe_cust_id = $customer->id;
            update_user_meta( $user_id, 'jjpws_stripe_customer_id', $stripe_cust_id );
        }

        $price_id = $this->get_or_create_price( $monthly_price_cents, $lot_size_category, $dog_count, $frequency );

        $session = Session::create( [
            'customer'           => $stripe_cust_id,
            'mode'               => 'subscription',
            'line_items'         => [ [ 'price' => $price_id, 'quantity' => 1 ] ],
            'success_url'        => $success_url . '?booking=success&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'         => $cancel_url  . '?booking=cancelled',
            'metadata'           => [
                'wp_user_id'        => $user_id,
                'street_address'    => $street,
                'city'              => $city,
                'state'             => $state,
                'zip_code'          => $zip,
                'lat'               => $lat,
                'lng'               => $lng,
                'lot_size_sqft'     => $sqft,
                'lot_size_category' => $lot_size_category,
                'dog_count'         => $dog_count,
                'frequency'         => $frequency,
                'monthly_price_cents' => $monthly_price_cents,
            ],
            'subscription_data' => [
                'metadata' => [
                    'wp_user_id'        => $user_id,
                    'lot_size_category' => $lot_size_category,
                    'dog_count'         => $dog_count,
                    'frequency'         => $frequency,
                ],
            ],
        ] );

        return $session->url;
    }

    /**
     * Cancel a Stripe subscription at period end.
     */
    public function cancel_at_period_end( string $stripe_sub_id ): Subscription {
        return Subscription::update( $stripe_sub_id, [
            'cancel_at_period_end' => true,
        ] );
    }

    public function retrieve_subscription( string $stripe_sub_id ): Subscription {
        return Subscription::retrieve( $stripe_sub_id );
    }

    public function verify_webhook( string $payload, string $sig_header ): \Stripe\Event {
        $secret = get_option( 'jjpws_stripe_webhook_secret', '' );
        return \Stripe\Webhook::constructEvent( $payload, $sig_header, $secret );
    }

    private function get_or_create_price( int $cents, string $lot_size, int $dogs, string $frequency ): string {
        $nickname = "jjpws_{$lot_size}_{$frequency}_{$dogs}dogs_{$cents}c";

        // Search for existing price with this nickname to avoid duplicates
        $prices = Price::all( [ 'limit' => 100, 'type' => 'recurring' ] );

        foreach ( $prices->data as $p ) {
            if ( ( $p->nickname ?? '' ) === $nickname && $p->unit_amount === $cents && $p->active ) {
                return $p->id;
            }
        }

        $price = Price::create( [
            'currency'        => 'usd',
            'unit_amount'     => $cents,
            'nickname'        => $nickname,
            'recurring'       => [ 'interval' => 'month' ],
            'product_data'    => [
                'name' => "JJ Pet Waste — {$lot_size} lot, {$dogs} dog(s), {$frequency}",
            ],
        ] );

        return $price->id;
    }

    private function get_secret_key(): string {
        $mode = get_option( 'jjpws_stripe_mode', 'test' );
        $keys = get_option( 'jjpws_api_keys', [] );

        if ( is_string( $keys ) ) {
            $keys = maybe_unserialize( $keys );
        }

        return $mode === 'live'
            ? ( $keys['stripe_live_secret'] ?? '' )
            : ( $keys['stripe_test_secret'] ?? '' );
    }
}
