<?php

namespace JJPWS\Controllers;

use JJPWS\Services\PricingEngine;
use JJPWS\Services\StripeService;

class CheckoutController {

    private static array $valid_categories  = [ 'xs', 'sm', 'md', 'lg', 'xl' ];
    private static array $valid_frequencies = [ 'twice_weekly', 'weekly', 'biweekly' ];

    public function create_session(): void {
        check_ajax_referer( 'jjpws_nonce', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( [ 'code' => 'UNAUTHENTICATED', 'message' => 'Please log in to complete your booking.' ], 401 );
        }

        $user_id   = get_current_user_id();
        $street    = sanitize_text_field( $_POST['street']    ?? '' );
        $city      = sanitize_text_field( $_POST['city']      ?? '' );
        $state     = sanitize_text_field( $_POST['state']     ?? '' );
        $zip       = sanitize_text_field( $_POST['zip']       ?? '' );
        $category  = sanitize_text_field( $_POST['lot_size_category'] ?? '' );
        $dog_count = absint( $_POST['dog_count'] ?? 0 );
        $frequency = sanitize_text_field( $_POST['frequency'] ?? '' );
        $submitted_cents = absint( $_POST['monthly_price_cents'] ?? 0 );

        $lat  = isset( $_POST['lat']  ) ? floatval( $_POST['lat']  ) : null;
        $lng  = isset( $_POST['lng']  ) ? floatval( $_POST['lng']  ) : null;
        $sqft = isset( $_POST['lot_size_sqft'] ) ? absint( $_POST['lot_size_sqft'] ) : null;

        if ( strlen( $street ) < 5 || empty( $city ) || empty( $state ) || ! preg_match( '/^\d{5}$/', $zip ) ) {
            wp_send_json_error( [ 'code' => 'INVALID_ADDRESS', 'message' => 'Invalid address.' ] );
        }

        if ( ! in_array( $category, self::$valid_categories, true ) ) {
            wp_send_json_error( [ 'code' => 'INVALID_LOT_SIZE', 'message' => 'Invalid lot size category.' ] );
        }

        if ( $dog_count < 1 || $dog_count > 10 ) {
            wp_send_json_error( [ 'code' => 'INVALID_DOG_COUNT', 'message' => 'Dog count must be between 1 and 10.' ] );
        }

        if ( ! in_array( $frequency, self::$valid_frequencies, true ) ) {
            wp_send_json_error( [ 'code' => 'INVALID_FREQUENCY', 'message' => 'Invalid frequency.' ] );
        }

        // Server-side price verification — reject if client tampered with the price
        try {
            $server_cents = PricingEngine::calculate( $category, $dog_count, $frequency );
        } catch ( \Throwable $e ) {
            wp_send_json_error( [ 'code' => 'PRICING_ERROR', 'message' => 'Could not verify pricing.' ] );
        }

        if ( abs( $server_cents - $submitted_cents ) > 1 ) {
            error_log( "JJPWS price mismatch: submitted={$submitted_cents} server={$server_cents} user={$user_id}" );
            wp_send_json_error( [ 'code' => 'PRICE_MISMATCH', 'message' => 'Price verification failed. Please refresh and try again.' ] );
        }

        try {
            $stripe      = new StripeService();
            $book_url    = get_permalink( get_page_by_path( 'book' ) ) ?: home_url( '/book' );
            $checkout_url = $stripe->create_checkout_session(
                $user_id,
                $server_cents,
                $category,
                $dog_count,
                $frequency,
                $street, $city, $state, $zip,
                $lat, $lng, $sqft,
                $book_url,
                $book_url
            );

            wp_send_json_success( [ 'checkout_url' => $checkout_url ] );

        } catch ( \Throwable $e ) {
            error_log( 'JJPWS create_session Stripe error: ' . $e->getMessage() );
            wp_send_json_error( [ 'code' => 'STRIPE_ERROR', 'message' => 'Payment setup failed. Please try again or contact support.' ] );
        }
    }
}
