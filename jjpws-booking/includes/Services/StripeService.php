<?php

namespace JJPWS\Services;

use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Checkout\Session;
use Stripe\Subscription;
use Stripe\Price;
use JJPWS\Services\PricingEngine;

class StripeService {

    public function __construct() {
        Stripe::setApiKey( $this->get_secret_key() );
        Stripe::setApiVersion( '2023-10-16' );
    }

    /**
     * Create a Stripe Checkout Session.
     *
     * Handles three modes:
     *  - One-time service     → mode: 'payment'
     *  - Recurring monthly    → mode: 'subscription'
     *  - Recurring annual     → mode: 'subscription' with yearly interval
     */
    public function create_checkout_session(
        int $user_id,
        string $service_type,
        array $breakdown,
        array $address,
        string $acreage_tier,
        int $dog_count,
        string $frequency,
        string $time_since_cleaned,
        bool $annual_prepay,
        string $return_url
    ): string {
        $user             = get_userdata( $user_id );
        $stripe_cust_id   = get_user_meta( $user_id, 'jjpws_stripe_customer_id', true );

        if ( empty( $stripe_cust_id ) ) {
            $customer = Customer::create( [
                'email'    => $user->user_email,
                'name'     => $user->display_name,
                'metadata' => [ 'wp_user_id' => (string) $user_id ],
            ] );
            $stripe_cust_id = $customer->id;
            update_user_meta( $user_id, 'jjpws_stripe_customer_id', $stripe_cust_id );
        }

        // Stripe metadata values must be strings (max 500 chars per value).
        $metadata = [
            'wp_user_id'              => (string) $user_id,
            'service_type'            => (string) $service_type,
            'street_address'          => (string) ( $address['street'] ?? '' ),
            'city'                    => (string) ( $address['city']   ?? '' ),
            'state'                   => (string) ( $address['state']  ?? '' ),
            'zip_code'                => (string) ( $address['zip']    ?? '' ),
            'lat'                     => (string) ( $address['lat']    ?? '' ),
            'lng'                     => (string) ( $address['lng']    ?? '' ),
            'lot_size_sqft'           => (string) ( $address['sqft']   ?? '' ),
            'lot_size_acres'          => (string) ( $address['acres']  ?? '' ),
            'distance_miles'          => (string) ( $address['miles']  ?? 0 ),
            'acreage_tier'            => (string) $acreage_tier,
            'dog_count'               => (string) $dog_count,
            'frequency'               => (string) $frequency,
            'time_since_cleaned'      => (string) $time_since_cleaned,
            'annual_prepay'           => $annual_prepay ? '1' : '0',
            'total_price_cents'       => (string) ( $breakdown['total_cents'] ?? 0 ),
            'recurring_monthly_cents' => (string) ( $breakdown['recurring_monthly_cents'] ?? 0 ),
            'distance_fee_cents'      => (string) ( $breakdown['distance_fee_monthly'] ?? ( $breakdown['distance_fee_cents'] ?? 0 ) ),
            'neglect_surcharge_cents' => (string) ( $breakdown['neglect_surcharge_cents'] ?? 0 ),
            'annual_discount_cents'   => (string) ( $breakdown['annual_savings_cents'] ?? 0 ),
        ];

        if ( $service_type === PricingEngine::SERVICE_ONE_TIME ) {
            return $this->create_one_time_session( $stripe_cust_id, $breakdown, $metadata, $return_url );
        }

        if ( $annual_prepay ) {
            return $this->create_annual_session( $stripe_cust_id, $breakdown, $metadata, $return_url );
        }

        return $this->create_monthly_session( $stripe_cust_id, $breakdown, $metadata, $return_url );
    }

    private function create_one_time_session( string $cust_id, array $breakdown, array $metadata, string $return_url ): string {
        $total = (int) $breakdown['total_cents'];

        $session = Session::create( [
            'customer'    => $cust_id,
            'mode'        => 'payment',
            'line_items'  => [ [
                'quantity'    => 1,
                'price_data'  => [
                    'currency'     => 'usd',
                    'unit_amount'  => $total,
                    'product_data' => [
                        'name' => 'JJ Pet Waste — One-Time Cleanup',
                    ],
                ],
            ] ],
            'success_url' => $return_url . '?booking=success&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => $return_url . '?booking=cancelled',
            'metadata'    => $metadata,
            'payment_intent_data' => [
                'metadata' => $metadata,
            ],
        ] );

        return $session->url;
    }

    private function create_monthly_session( string $cust_id, array $breakdown, array $metadata, string $return_url ): string {
        $monthly   = (int) $breakdown['recurring_monthly_cents'];
        $surcharge = (int) ( $breakdown['neglect_surcharge_cents'] ?? 0 );

        // Build line items. A first-month neglect surcharge is split into two
        // recurring items: the base subscription + a one-time invoice item.
        // We use subscription_data.add_invoice_items (supported in all Stripe
        // subscription checkout versions) rather than mixing billing modes in
        // line_items, which requires explicit Stripe account feature enablement.
        $session_args = [
            'customer'    => $cust_id,
            'mode'        => 'subscription',
            'line_items'  => [ [
                'quantity'   => 1,
                'price_data' => [
                    'currency'     => 'usd',
                    'unit_amount'  => $monthly,
                    'recurring'    => [ 'interval' => 'month' ],
                    'product_data' => [ 'name' => 'JJ Pet Waste — Monthly Service' ],
                ],
            ] ],
            'success_url' => $return_url . '?booking=success&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => $return_url . '?booking=cancelled',
            'metadata'    => $metadata,
            'subscription_data' => [
                'metadata' => $metadata,
            ],
        ];

        // First-month neglect surcharge: attach as an invoice item on the
        // initial subscription invoice via subscription_data.add_invoice_items.
        if ( $surcharge > 0 ) {
            $session_args['subscription_data']['add_invoice_items'] = [ [
                'price_data' => [
                    'currency'     => 'usd',
                    'unit_amount'  => $surcharge,
                    'product_data' => [ 'name' => 'First-time cleanup surcharge' ],
                ],
            ] ];
        }

        return Session::create( $session_args )->url;
    }

    private function create_annual_session( string $cust_id, array $breakdown, array $metadata, string $return_url ): string {
        $annual_total = (int) $breakdown['annual_total_cents'];
        $surcharge    = (int) ( $breakdown['neglect_surcharge_cents'] ?? 0 );

        $session_args = [
            'customer'    => $cust_id,
            'mode'        => 'subscription',
            'line_items'  => [ [
                'quantity'   => 1,
                'price_data' => [
                    'currency'     => 'usd',
                    'unit_amount'  => $annual_total,
                    'recurring'    => [ 'interval' => 'year' ],
                    'product_data' => [ 'name' => 'JJ Pet Waste — Annual Service (10% off)' ],
                ],
            ] ],
            'success_url' => $return_url . '?booking=success&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => $return_url . '?booking=cancelled',
            'metadata'    => $metadata,
            'subscription_data' => [
                'metadata' => $metadata,
            ],
        ];

        if ( $surcharge > 0 ) {
            $session_args['subscription_data']['add_invoice_items'] = [ [
                'price_data' => [
                    'currency'     => 'usd',
                    'unit_amount'  => $surcharge,
                    'product_data' => [ 'name' => 'First-time cleanup surcharge' ],
                ],
            ] ];
        }

        return Session::create( $session_args )->url;
    }

    public function cancel_at_period_end( string $stripe_sub_id ): Subscription {
        return Subscription::update( $stripe_sub_id, [ 'cancel_at_period_end' => true ] );
    }

    public function retrieve_subscription( string $stripe_sub_id ): Subscription {
        return Subscription::retrieve( $stripe_sub_id );
    }

    public function retrieve_session( string $session_id ): Session {
        return Session::retrieve( $session_id );
    }

    public function verify_webhook( string $payload, string $sig_header ): \Stripe\Event {
        $secret = get_option( 'jjpws_stripe_webhook_secret', '' );
        return \Stripe\Webhook::constructEvent( $payload, $sig_header, $secret );
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
