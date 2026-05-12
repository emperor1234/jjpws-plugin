<?php

namespace JJPWS\Controllers;

use JJPWS\Services\PricingEngine;
use JJPWS\Services\StripeService;
use JJPWS\Services\LotSizeClassifier;

class CheckoutController {

    private static array $valid_acreage_tiers = [
        LotSizeClassifier::TIER_SMALL,
        LotSizeClassifier::TIER_MEDIUM,
    ];

    private static array $valid_frequencies = [
        PricingEngine::FREQ_TWICE_WEEKLY,
        PricingEngine::FREQ_WEEKLY,
        PricingEngine::FREQ_BIWEEKLY,
    ];

    private static array $valid_time_since = [ 'recent', 'mid', 'long' ];

    public function create_session(): void {
        check_ajax_referer( 'jjpws_nonce', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( [ 'code' => 'UNAUTHENTICATED', 'message' => 'Please log in to complete your booking.' ], 401 );
        }

        $user_id      = get_current_user_id();
        $service_type = sanitize_text_field( $_POST['service_type']      ?? PricingEngine::SERVICE_RECURRING );
        $street       = sanitize_text_field( $_POST['street']            ?? '' );
        $city         = sanitize_text_field( $_POST['city']              ?? '' );
        $state        = sanitize_text_field( $_POST['state']             ?? '' );
        $zip          = sanitize_text_field( $_POST['zip']               ?? '' );
        $tier         = sanitize_text_field( $_POST['acreage_tier']      ?? '' );
        $dog_count    = absint( $_POST['dog_count']                       ?? 0 );
        $frequency    = sanitize_text_field( $_POST['frequency']         ?? '' );
        $time_since   = sanitize_text_field( $_POST['time_since_cleaned'] ?? 'recent' );
        $annual       = ! empty( $_POST['annual_prepay'] ) && $_POST['annual_prepay'] !== '0';
        $submitted    = absint( $_POST['total_price_cents']               ?? 0 );

        $lat   = isset( $_POST['lat'] )            ? floatval( $_POST['lat'] )            : null;
        $lng   = isset( $_POST['lng'] )            ? floatval( $_POST['lng'] )            : null;
        $sqft  = isset( $_POST['lot_size_sqft'] )  ? absint( $_POST['lot_size_sqft'] )    : null;
        $acres = isset( $_POST['lot_size_acres'] ) ? floatval( $_POST['lot_size_acres'] ) : null;

        // Server-side distance recompute — never trust client miles
        $miles = 0;
        if ( $lat !== null && $lng !== null && ( $lat !== 0.0 || $lng !== 0.0 ) ) {
            $computed = ( new \JJPWS\Services\DistanceService() )->miles_to_customer( $lat, $lng );
            if ( $computed !== null ) {
                $miles = (float) $computed;
            } elseif ( isset( $_POST['distance_miles'] ) ) {
                $miles = max( 0, floatval( $_POST['distance_miles'] ) );
            }
        }

        // ── Validation ────────────────────────────────────────────────────────
        if ( strlen( $street ) < 5 || empty( $city ) || empty( $state ) || ! preg_match( '/^\d{5}$/', $zip ) ) {
            wp_send_json_error( [ 'code' => 'INVALID_ADDRESS', 'message' => 'Invalid address.' ] );
        }

        if ( ! in_array( $tier, self::$valid_acreage_tiers, true ) ) {
            wp_send_json_error( [ 'code' => 'INVALID_LOT_SIZE', 'message' => 'Lot size requires a custom quote.' ] );
        }

        if ( ! in_array( $time_since, self::$valid_time_since, true ) ) {
            $time_since = 'recent';
        }

        // Distance cap
        $distance_cfg = PricingEngine::get_distance_config();
        if ( $miles > (float) $distance_cfg['max_miles'] ) {
            wp_send_json_error( [ 'code' => 'OUT_OF_RANGE', 'message' => 'This address is outside our service area.' ] );
        }

        // ── Server-side price recalculation ───────────────────────────────────
        try {
            if ( $service_type === PricingEngine::SERVICE_ONE_TIME ) {
                $breakdown = PricingEngine::calculate( [
                    'service_type'       => PricingEngine::SERVICE_ONE_TIME,
                    'acreage_tier'       => $tier,
                    'time_since_cleaned' => $time_since,
                    'distance_miles'     => $miles,
                ] );
            } else {
                if ( $dog_count < 1 || PricingEngine::requires_quote_for_dogs( $dog_count ) ) {
                    wp_send_json_error( [ 'code' => 'INVALID_DOG_COUNT', 'message' => 'Dog count requires a custom quote.' ] );
                }
                if ( ! in_array( $frequency, self::$valid_frequencies, true ) ) {
                    wp_send_json_error( [ 'code' => 'INVALID_FREQUENCY', 'message' => 'Invalid frequency.' ] );
                }
                $breakdown = PricingEngine::calculate( [
                    'service_type'       => PricingEngine::SERVICE_RECURRING,
                    'acreage_tier'       => $tier,
                    'dog_count'          => $dog_count,
                    'frequency'          => $frequency,
                    'time_since_cleaned' => $time_since,
                    'distance_miles'     => $miles,
                    'annual_prepay'      => $annual,
                ] );
            }
        } catch ( \Throwable $e ) {
            error_log( 'JJPWS pricing error: ' . $e->getMessage() );
            wp_send_json_error( [ 'code' => 'PRICING_ERROR', 'message' => 'Could not verify pricing.' ] );
        }

        $server_total = $breakdown['total_cents'] ?? 0;

        if ( abs( $server_total - $submitted ) > 1 ) {
            error_log( "JJPWS price mismatch: submitted={$submitted} server={$server_total} user={$user_id}" );
            wp_send_json_error( [ 'code' => 'PRICE_MISMATCH', 'message' => 'Price verification failed. Please refresh and try again.' ] );
        }

        // ── Build Stripe checkout ─────────────────────────────────────────────
        try {
            $stripe       = new StripeService();
            $book_url     = $this->resolve_book_url();
            $address_meta = compact( 'street', 'city', 'state', 'zip', 'lat', 'lng', 'sqft', 'acres', 'miles' );

            $checkout_url = $stripe->create_checkout_session(
                $user_id,
                $service_type,
                $breakdown,
                $address_meta,
                $tier,
                $dog_count,
                $frequency,
                $time_since,
                $annual,
                $book_url
            );

            wp_send_json_success( [ 'checkout_url' => $checkout_url ] );

        } catch ( \Throwable $e ) {
            error_log( 'JJPWS Stripe checkout error: ' . $e->getMessage() );
            wp_send_json_error( [ 'code' => 'STRIPE_ERROR', 'message' => 'Payment setup failed. Please try again or contact support.' ] );
        }
    }

    private function resolve_book_url(): string {
        foreach ( [ 'book', 'booking', 'book-now', 'schedule' ] as $slug ) {
            $page = get_page_by_path( $slug );
            if ( $page ) {
                return get_permalink( $page );
            }
        }
        // Last resort: fall back to the page that has the booking shortcode on it
        global $wpdb;
        $post_id = $wpdb->get_var(
            "SELECT ID FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_content LIKE '%jjpws_booking_form%' LIMIT 1"
        );
        if ( $post_id ) {
            return get_permalink( (int) $post_id );
        }
        return home_url( '/book/' );
    }
}
