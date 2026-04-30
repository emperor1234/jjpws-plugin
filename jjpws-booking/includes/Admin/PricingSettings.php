<?php

namespace JJPWS\Admin;

use JJPWS\Services\PricingEngine;

class PricingSettings {

    private array $dog_tiers = [
        PricingEngine::DOG_TIER_1,
        PricingEngine::DOG_TIER_2_3,
        PricingEngine::DOG_TIER_4,
    ];

    private array $frequencies = [
        PricingEngine::FREQ_TWICE_WEEKLY,
        PricingEngine::FREQ_WEEKLY,
        PricingEngine::FREQ_BIWEEKLY,
    ];

    public function render(): void {
        $matrix      = PricingEngine::get_matrix();
        $surcharges  = PricingEngine::get_surcharges();
        $one_time    = PricingEngine::get_one_time_price_cents();
        $dog_labels  = PricingEngine::DOG_TIER_LABELS;
        $freq_labels = PricingEngine::FREQ_LABELS;
        $time_opts   = PricingEngine::TIME_SINCE_OPTIONS;
        $saved       = isset( $_GET['saved'] );

        include JJPWS_PLUGIN_DIR . 'templates/admin-pricing.php';
    }

    public function save( array $post ): void {
        // Pricing matrix (per-visit base prices, in dollars)
        $matrix = PricingEngine::get_matrix();

        foreach ( $this->dog_tiers as $dt ) {
            foreach ( $this->frequencies as $f ) {
                $key = "base_{$dt}_{$f}";
                if ( isset( $post[ $key ] ) ) {
                    $matrix['base'][ $dt ][ $f ] = (int) round( floatval( $post[ $key ] ) * 100 );
                }
            }
        }

        if ( isset( $post['acreage_premium_pct'] ) ) {
            $matrix['acreage_premium_pct'] = max( 0, floatval( $post['acreage_premium_pct'] ) );
        }

        update_option( 'jjpws_pricing_matrix', wp_json_encode( $matrix ) );

        // Time-since-cleaned surcharges
        $surcharges = PricingEngine::get_surcharges();
        foreach ( [ 'recent', 'mid', 'long' ] as $key ) {
            $field = "neglect_{$key}";
            if ( isset( $post[ $field ] ) ) {
                $surcharges['time_since_cleaned'][ $key ] = (int) round( floatval( $post[ $field ] ) * 100 );
            }
        }
        update_option( 'jjpws_surcharges', wp_json_encode( $surcharges ) );

        // One-time cleanup price
        if ( isset( $post['one_time_price'] ) ) {
            update_option( 'jjpws_one_time_price_cents', (int) round( floatval( $post['one_time_price'] ) * 100 ) );
        }
    }
}
