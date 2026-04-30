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
                'metadata' => [ 'wp_user_id' => $user_id ],
            ] );
            $stripe_cust_id = $customer->id;
            update_user_meta( $user_id, 'jjpws_stripe_customer_id', $stripe_cust_id );
        }

        $metadata = [
            'wp_user_id'              => $user_id,
            'service_type'            => $service_type,
            'street_address'          => $address['street'] ?? '',
            'city'                    => $address['city']   ?? '',
            'state'                   => $address['state']  ?? '',
            'zip_code'                => $address['zip']    ?? '',
            'lat'                     => $address['lat']    ?? '',
            'lng'                     => $address['lng']    ?? '',
            'lot_size_sqft'           => $address['sqft']   ?? '',
            'lot_size_acres'          => $address['acres']  ?? '',
            'distance_miles'          => $address['miles']  ?? 0,
            'acreage_tier'            => $acreage_tier,
            'dog_count'               => $dog_count,
            'frequency'               => $frequency,
            'time_since_cleaned'      => $time_since_cleaned,
            'annual_prepay'           => $annual_prepay ? '1' : '0',
            'total_price_cents'       => $breakdown['total_cents'] ?? 0,
            'recurring_monthly_cents' => $breakdown['recurring_monthly_cents'] ?? 0,
            'distance_fee_cents'      => $breakdown['distance_fee_monthly'] ?? ( $breakdown['distance_fee_cents'] ?? 0 ),
            'neglect_surcharge_cents' => $breakdown['neglect_surcharge_cents'] ?? 0,
            'annual_discount_cents'   => $breakdown['annual_savings_cents'] ?? 0,
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
        $monthly = (int) $breakdown['recurring_monthly_cents'];
        $surcharge = (int) ( $breakdown['neglect_surcharge_cents'] ?? 0 );

        $line_items = [ [
            'quantity'   => 1,
            'price_data' => [
                'currency'     => 'usd',
                'unit_amount'  => $monthly,
                'recurring'    => [ 'interval' => 'month' ],
                'product_data' => [ 'name' => 'JJ Pet Waste — Monthly Service' ],
            ],
        ] ];

        $session_args = [
            'customer'    => $cust_id,
            'mode'        => 'subscription',
            'line_items'  => $line_items,
            'success_url' => $return_url . '?booking=success&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => $return_url . '?booking=cancelled',
            'metadata'    => $metadata,
            'subscription_data' => [
                'metadata' => $metadata,
            ],
        ];

        // First-month neglect surcharge as one-time line item
        if ( $surcharge > 0 ) {
            $session_args['line_items'][] = [
                'quantity'   => 1,
                'price_data' => [
                    'currency'     => 'usd',
                    'unit_amount'  => $surcharge,
                    'product_data' => [ 'name' => 'First-time cleanup surcharge' ],
                ],
            ];
        }

        return Session::create( $session_args )->url;
    }

    private function create_annual_session( string $cust_id, array $breakdown, array $metadata, string $return_url ): string {
        $annual_total = (int) $breakdown['annual_total_cents'];
        $surcharge    = (int) ( $breakdown['neglect_surcharge_cents'] ?? 0 );

        $line_items = [ [
            'quantity'   => 1,
            'price_data' => [
                'currency'     => 'usd',
                'unit_amount'  => $annual_total,
                'recurring'    => [ 'interval' => 'year' ],
                'product_data' => [ 'name' => 'JJ Pet Waste — Annual Service (10% off)' ],
            ],
        ] ];

        $session_args = [
            'customer'    => $cust_id,
            'mode'        => 'subscription',
            'line_items'  => $line_items,
            'success_url' => $return_url . '?booking=success&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => $return_url . '?booking=cancelled',
            'metadata'    => $metadata,
            'subscription_data' => [
                'metadata' => $metadata,
            ],
        ];

        if ( $surcharge > 0 ) {
            $session_args['line_items'][] = [
                'quantity'   => 1,
                'price_data' => [
                    'currency'     => 'usd',
                    'unit_amount'  => $surcharge,
                    'product_data' => [ 'name' => 'First-time cleanup surcharge' ],
                ],
            ];
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
