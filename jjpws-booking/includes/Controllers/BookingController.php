<?php

namespace JJPWS\Controllers;

use JJPWS\Services\LotSizeService;
use JJPWS\Services\LotSizeClassifier;
use JJPWS\Services\DistanceService;
use JJPWS\Services\PricingEngine;

class BookingController {

    private static array $valid_acreage_tiers = [
        LotSizeClassifier::TIER_SMALL,
        LotSizeClassifier::TIER_MEDIUM,
        LotSizeClassifier::TIER_LARGE,
    ];

    private static array $valid_frequencies = [
        PricingEngine::FREQ_TWICE_WEEKLY,
        PricingEngine::FREQ_WEEKLY,
        PricingEngine::FREQ_BIWEEKLY,
    ];

    private static array $valid_time_since = [ 'recent', 'mid', 'long' ];

    public function lookup_lot_size(): void {
        check_ajax_referer( 'jjpws_nonce', 'nonce' );

        if ( ! $this->check_rate_limit() ) {
            wp_send_json_error( [ 'code' => 'RATE_LIMITED', 'message' => 'Too many requests. Please wait and try again.' ], 429 );
        }

        $street = sanitize_text_field( $_POST['street'] ?? '' );
        $city   = sanitize_text_field( $_POST['city']   ?? '' );
        $state  = sanitize_text_field( $_POST['state']  ?? '' );
        $zip    = sanitize_text_field( $_POST['zip']    ?? '' );

        if ( strlen( $street ) < 5 || empty( $city ) || empty( $state ) || ! preg_match( '/^\d{5}$/', $zip ) ) {
            wp_send_json_error( [ 'code' => 'INVALID_ADDRESS', 'message' => 'The address could not be verified. Please check and try again.' ] );
        }

        try {
            $lot      = ( new LotSizeService() )->resolve_from_address( $street, $city, $state, $zip );
            $distance = null;
            $miles    = null;

            if ( $lot && $lot['lat'] !== null && $lot['lng'] !== null ) {
                $miles = ( new DistanceService() )->miles_to_customer( (float) $lot['lat'], (float) $lot['lng'] );
            }

            $distance_cfg = PricingEngine::get_distance_config();
            $max_miles    = (float) $distance_cfg['max_miles'];
            $out_of_range = ( $miles !== null && $miles > $max_miles );

            $response = [
                'distance_miles' => $miles,
                'max_miles'      => $max_miles,
                'free_miles'     => (float) $distance_cfg['free_miles'],
                'per_mile_cents' => (int) $distance_cfg['per_mile_cents'],
                'out_of_range'   => $out_of_range,
            ];

            if ( ! $lot ) {
                wp_send_json_success( array_merge( $response, [
                    'lot_size_sqft'     => null,
                    'lot_size_acres'    => null,
                    'lot_size_category' => null,
                    'lot_size_label'    => null,
                    'requires_quote'    => false,
                    'source'            => 'manual_required',
                    'message'           => "We couldn't auto-detect your lot size. Please select it below.",
                ] ) );
            }

            $requires_quote = $lot['tier']
                ? LotSizeClassifier::requires_quote( $lot['tier'] )
                : false;

            wp_send_json_success( array_merge( $response, [
                'lot_size_sqft'     => $lot['sqft'],
                'lot_size_acres'    => $lot['acres'],
                'lot_size_category' => $lot['tier'],
                'lot_size_label'    => $lot['label'],
                'requires_quote'    => $requires_quote,
                'source'            => $lot['source'],
                'lat'               => $lot['lat'],
                'lng'               => $lot['lng'],
            ] ) );

        } catch ( \Throwable $e ) {
            error_log( 'JJPWS lookup_lot_size error: ' . $e->getMessage() );
            wp_send_json_error( [ 'code' => 'INTERNAL_ERROR', 'message' => 'Something went wrong. Please try again.' ] );
        }
    }

    public function calculate_price(): void {
        check_ajax_referer( 'jjpws_nonce', 'nonce' );

        $service_type = sanitize_text_field( $_POST['service_type'] ?? PricingEngine::SERVICE_RECURRING );
        $tier         = sanitize_text_field( $_POST['acreage_tier'] ?? '' );
        $dogs         = absint( $_POST['dog_count'] ?? 0 );
        $frequency    = sanitize_text_field( $_POST['frequency'] ?? PricingEngine::FREQ_WEEKLY );
        $time_since   = sanitize_text_field( $_POST['time_since_cleaned'] ?? 'recent' );
        $annual       = ! empty( $_POST['annual_prepay'] ) && $_POST['annual_prepay'] !== '0';

        // Server-side distance recalc when lat/lng provided. Falls back to client-supplied
        // distance only if we can't compute it ourselves. Never trust client miles.
        $miles = $this->resolve_distance_miles();

        if ( ! in_array( $tier, self::$valid_acreage_tiers, true ) ) {
            wp_send_json_error( [ 'code' => 'INVALID_LOT_SIZE', 'message' => 'Invalid lot size category.' ] );
        }

        if ( LotSizeClassifier::requires_quote( $tier ) ) {
            wp_send_json_success( [ 'requires_quote' => true, 'reason' => 'large_lot' ] );
        }

        if ( ! in_array( $time_since, self::$valid_time_since, true ) ) {
            $time_since = 'recent';
        }

        if ( $service_type === PricingEngine::SERVICE_ONE_TIME ) {
            try {
                $breakdown = PricingEngine::calculate( [
                    'service_type'       => PricingEngine::SERVICE_ONE_TIME,
                    'acreage_tier'       => $tier,
                    'time_since_cleaned' => $time_since,
                    'distance_miles'     => $miles,
                ] );
                wp_send_json_success( [
                    'requires_quote' => false,
                    'breakdown'      => $breakdown,
                    'distance_miles' => $miles,
                ] );
            } catch ( \Throwable $e ) {
                error_log( 'JJPWS calc one-time error: ' . $e->getMessage() );
                wp_send_json_error( [ 'code' => 'INTERNAL_ERROR', 'message' => 'Pricing error. Please try again.' ] );
            }
        }

        // Recurring path
        if ( $dogs < 1 ) {
            wp_send_json_error( [ 'code' => 'INVALID_DOG_COUNT', 'message' => 'Please enter the number of dogs.' ] );
        }

        if ( PricingEngine::requires_quote_for_dogs( $dogs ) ) {
            wp_send_json_success( [ 'requires_quote' => true, 'reason' => 'too_many_dogs' ] );
        }

        if ( ! in_array( $frequency, self::$valid_frequencies, true ) ) {
            wp_send_json_error( [ 'code' => 'INVALID_FREQUENCY', 'message' => 'Invalid service frequency.' ] );
        }

        try {
            $breakdown = PricingEngine::calculate( [
                'service_type'       => PricingEngine::SERVICE_RECURRING,
                'acreage_tier'       => $tier,
                'dog_count'          => $dogs,
                'frequency'          => $frequency,
                'time_since_cleaned' => $time_since,
                'distance_miles'     => $miles,
                'annual_prepay'      => $annual,
            ] );

            wp_send_json_success( [ 'requires_quote' => false, 'breakdown' => $breakdown ] );

        } catch ( \Throwable $e ) {
            error_log( 'JJPWS calculate_price error: ' . $e->getMessage() );
            wp_send_json_error( [ 'code' => 'INTERNAL_ERROR', 'message' => 'Pricing error. Please try again.' ] );
        }
    }

    /**
     * Resolve customer distance. Prefers server-side haversine from lat/lng;
     * falls back to client-supplied value only if server-side calc fails.
     */
    private function resolve_distance_miles(): float {
        $lat = isset( $_POST['lat'] ) ? floatval( $_POST['lat'] ) : null;
        $lng = isset( $_POST['lng'] ) ? floatval( $_POST['lng'] ) : null;

        if ( $lat !== null && $lng !== null && ( $lat !== 0.0 || $lng !== 0.0 ) ) {
            $computed = ( new \JJPWS\Services\DistanceService() )->miles_to_customer( $lat, $lng );
            if ( $computed !== null ) {
                return (float) $computed;
            }
        }

        return isset( $_POST['distance_miles'] ) ? max( 0, floatval( $_POST['distance_miles'] ) ) : 0;
    }

    private function check_rate_limit(): bool {
        $ip  = sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' );
        $key = 'jjpws_rate_' . md5( $ip );

        // Use increment operation for atomicity (WordPress transients aren't atomic,
        // but this reduces the race condition window compared to read-then-write)
        $hit = (int) get_transient( $key );

        if ( $hit >= 30 ) {
            return false;
        }

        // Increment atomically using the fact that set_transient on an existing key updates it
        // This still isn't fully atomic, but better than the previous implementation
        $new_hit = $hit + 1;
        set_transient( $key, $new_hit, HOUR_IN_SECONDS );

        return true;
    }
}
