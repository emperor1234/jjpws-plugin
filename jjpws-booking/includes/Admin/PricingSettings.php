<?php

namespace JJPWS\Admin;

use JJPWS\Services\PricingEngine;
use JJPWS\Services\LotSizeClassifier;

class PricingSettings {

    private array $categories  = [ 'xs', 'sm', 'md', 'lg', 'xl' ];
    private array $frequencies = [ 'twice_weekly', 'weekly', 'biweekly' ];

    public function render(): void {
        $matrix    = PricingEngine::get_matrix();
        $labels    = LotSizeClassifier::all_labels();
        $freq_labels = [
            'twice_weekly' => 'Twice-a-Week',
            'weekly'       => 'Weekly',
            'biweekly'     => 'Bi-Weekly',
        ];
        $saved = isset( $_GET['saved'] );

        include JJPWS_PLUGIN_DIR . 'templates/admin-pricing.php';
    }

    public function save( array $post ): void {
        $matrix    = PricingEngine::get_matrix();
        $categories  = [ 'xs', 'sm', 'md', 'lg', 'xl' ];
        $frequencies = [ 'twice_weekly', 'weekly', 'biweekly' ];

        foreach ( $categories as $cat ) {
            foreach ( $frequencies as $freq ) {
                if ( isset( $post["base_{$cat}_{$freq}"] ) ) {
                    $dollars = floatval( $post["base_{$cat}_{$freq}"] );
                    $matrix['base'][ $cat ][ $freq ] = (int) round( $dollars * 100 );
                }
            }
        }

        foreach ( $frequencies as $freq ) {
            if ( isset( $post["adder_{$freq}"] ) ) {
                $dollars = floatval( $post["adder_{$freq}"] );
                $matrix['dog_adder'][ $freq ] = (int) round( $dollars * 100 );
            }
        }

        update_option( 'jjpws_pricing_matrix', wp_json_encode( $matrix ) );

        // Update lot size thresholds
        $thresholds = [];
        foreach ( $categories as $cat ) {
            $min = absint( $post["threshold_min_{$cat}"] ?? 0 );
            $max = $cat === 'xl' ? PHP_INT_MAX : absint( $post["threshold_max_{$cat}"] ?? 0 );
            $thresholds[ $cat ] = [ 'min' => $min, 'max' => $max ];
        }

        if ( ! empty( array_filter( array_column( $thresholds, 'max' ) ) ) ) {
            update_option( 'jjpws_lot_size_thresholds', wp_json_encode( $thresholds ) );
        }
    }
}
