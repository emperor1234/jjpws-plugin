<?php

namespace JJPWS\Controllers;

use JJPWS\Services\LotSizeService;
use JJPWS\Services\LotSizeClassifier;
use JJPWS\Services\PricingEngine;

class BookingController {

    private static array $valid_categories  = [ 'xs', 'sm', 'md', 'lg', 'xl' ];
    private static array $valid_frequencies = [ 'twice_weekly', 'weekly', 'biweekly' ];

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
            $service = new LotSizeService();
            $result  = $service->resolve_from_address( $street, $city, $state, $zip );

            if ( ! $result ) {
                wp_send_json_success( [
                    'lot_size_sqft'     => null,
                    'lot_size_category' => null,
                    'lot_size_label'    => null,
                    'source'            => 'manual_required',
                    'message'           => "We couldn't auto-detect your lot size. Please select it below.",
                ] );
            }

            if ( $result['source'] === 'manual_required' ) {
                wp_send_json_success( array_merge( $result, [
                    'message' => "We couldn't auto-detect your lot size. Please select it below.",
                ] ) );
            }

            wp_send_json_success( [
                'lot_size_sqft'     => $result['sqft'],
                'lot_size_category' => $result['category'],
                'lot_size_label'    => $result['label'],
                'source'            => $result['source'],
                'lat'               => $result['lat'],
                'lng'               => $result['lng'],
            ] );

        } catch ( \Throwable $e ) {
            error_log( 'JJPWS lookup_lot_size error: ' . $e->getMessage() );
            wp_send_json_error( [ 'code' => 'INTERNAL_ERROR', 'message' => 'Something went wrong. Please try again.' ] );
        }
    }

    public function calculate_price(): void {
        check_ajax_referer( 'jjpws_nonce', 'nonce' );

        $category  = sanitize_text_field( $_POST['lot_size_category'] ?? '' );
        $dog_count = absint( $_POST['dog_count'] ?? 0 );
        $frequency = sanitize_text_field( $_POST['frequency'] ?? '' );

        if ( ! in_array( $category, self::$valid_categories, true ) ) {
            wp_send_json_error( [ 'code' => 'INVALID_LOT_SIZE', 'message' => 'Invalid lot size category.' ] );
        }

        if ( $dog_count < 1 || $dog_count > 10 ) {
            wp_send_json_error( [ 'code' => 'INVALID_DOG_COUNT', 'message' => 'Dog count must be between 1 and 10.' ] );
        }

        if ( ! in_array( $frequency, self::$valid_frequencies, true ) ) {
            wp_send_json_error( [ 'code' => 'INVALID_FREQUENCY', 'message' => 'Invalid service frequency.' ] );
        }

        try {
            $cents  = PricingEngine::calculate( $category, $dog_count, $frequency );
            $matrix = PricingEngine::get_matrix();
            $base   = $matrix['base'][ $category ][ $frequency ];
            $adder  = $matrix['dog_adder'][ $frequency ];
            $extra  = max( 0, $dog_count - 1 );

            wp_send_json_success( [
                'monthly_price_cents'     => $cents,
                'monthly_price_formatted' => PricingEngine::format_cents( $cents ),
                'breakdown' => [
                    'base_price_cents'      => $base,
                    'extra_dogs_adder_cents' => $adder * $extra,
                    'extra_dog_count'        => $extra,
                    'adder_per_dog_cents'    => $adder,
                ],
            ] );

        } catch ( \Throwable $e ) {
            error_log( 'JJPWS calculate_price error: ' . $e->getMessage() );
            wp_send_json_error( [ 'code' => 'INTERNAL_ERROR', 'message' => 'Something went wrong. Please try again.' ] );
        }
    }

    private function check_rate_limit(): bool {
        $ip  = sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' );
        $key = 'jjpws_rate_' . md5( $ip );
        $hit = (int) get_transient( $key );

        if ( $hit >= 10 ) {
            return false;
        }

        set_transient( $key, $hit + 1, HOUR_IN_SECONDS );
        return true;
    }
}
