<?php

namespace JJPWS\Controllers;

use JJPWS\Admin\AdminDashboard;
use JJPWS\Admin\PricingSettings;

class AdminController {

    public function register_menu(): void {
        add_menu_page(
            'JJ Pet Waste',
            'JJ Pet Waste',
            'manage_options',
            'jjpws-dashboard',
            [ $this, 'render_dashboard' ],
            'dashicons-pets',
            58
        );

        add_submenu_page(
            'jjpws-dashboard',
            'Customers',
            'Customers',
            'manage_options',
            'jjpws-dashboard',
            [ $this, 'render_dashboard' ]
        );

        add_submenu_page(
            'jjpws-dashboard',
            'Quote Requests',
            'Quote Requests',
            'manage_options',
            'jjpws-quotes',
            [ $this, 'render_quotes' ]
        );

        add_submenu_page(
            'jjpws-dashboard',
            'Pricing Settings',
            'Pricing',
            'manage_options',
            'jjpws-pricing',
            [ $this, 'render_pricing' ]
        );

        add_submenu_page(
            'jjpws-dashboard',
            'Business & API Settings',
            'Settings',
            'manage_options',
            'jjpws-settings',
            [ $this, 'render_settings' ]
        );

        add_submenu_page(
            'jjpws-dashboard',
            'Setup Guide',
            'Setup Guide',
            'manage_options',
            'jjpws-guide',
            [ $this, 'render_guide' ]
        );
    }

    public function render_guide(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Access denied.' );

        $business_address = get_option( 'jjpws_business_address', '' );
        $business_email   = get_option( 'jjpws_business_email', '' );
        $keys             = get_option( 'jjpws_api_keys', [] );
        if ( is_string( $keys ) ) $keys = maybe_unserialize( $keys );
        if ( ! is_array( $keys ) ) $keys = [];

        $webhook_secret  = get_option( 'jjpws_stripe_webhook_secret', '' );
        $allow_register  = (int) get_option( 'users_can_register' );
        $webhook_url     = get_rest_url( null, 'jjpws/v1/stripe-webhook' );
        $stripe_mode     = get_option( 'jjpws_stripe_mode', 'test' );

        $checks = [
            'business_address' => ! empty( $business_address ),
            'business_email'   => ! empty( $business_email ),
            'google_maps'      => ! empty( $keys['google_maps'] ),
            'parcel_endpoint'  => ! empty( get_option( 'jjpws_parcel_endpoint' ) ),
            'stripe_test'      => ! empty( $keys['stripe_test_secret'] ),
            'stripe_live'      => ! empty( $keys['stripe_live_secret'] ),
            'webhook_secret'   => ! empty( $webhook_secret ),
            'allow_register'   => (bool) $allow_register,
        ];

        include JJPWS_PLUGIN_DIR . 'templates/admin-guide.php';
    }

    public function render_dashboard(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Access denied.' );
        ( new AdminDashboard() )->render();
    }

    public function render_quotes(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Access denied.' );

        $model  = new \JJPWS\Models\QuoteRequestModel();
        $status = sanitize_text_field( $_GET['status'] ?? '' );
        $args   = $status ? [ 'status' => $status ] : [];
        $quotes = $model->get_all( $args );
        $total  = $model->count_all( $status );

        include JJPWS_PLUGIN_DIR . 'templates/admin-quotes.php';
    }

    public function render_pricing(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Access denied.' );
        ( new PricingSettings() )->render();
    }

    public function render_settings(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Access denied.' );
        $this->render_settings_page();
    }

    public function save_pricing(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Access denied.' );
        check_admin_referer( 'jjpws_save_pricing' );

        ( new PricingSettings() )->save( $_POST );

        wp_redirect( admin_url( 'admin.php?page=jjpws-pricing&saved=1' ) );
        exit;
    }

    public function save_settings(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Access denied.' );
        check_admin_referer( 'jjpws_save_settings' );

        // Stripe mode
        $mode = in_array( $_POST['stripe_mode'] ?? '', [ 'test', 'live' ], true ) ? $_POST['stripe_mode'] : 'test';
        update_option( 'jjpws_stripe_mode', $mode );

        // Business info
        $old_addr = (string) get_option( 'jjpws_business_address', '' );
        $new_addr = sanitize_text_field( $_POST['business_address'] ?? '' );

        update_option( 'jjpws_business_address', $new_addr );
        update_option( 'jjpws_business_email',   sanitize_email( $_POST['business_email'] ?? '' ) );
        update_option( 'jjpws_business_phone',   sanitize_text_field( $_POST['business_phone'] ?? '' ) );

        // Force re-geocode if address changed
        if ( $new_addr !== $old_addr ) {
            delete_option( 'jjpws_business_origin_coords' );
        }

        // Distance config
        $distance = [
            'free_miles'     => max( 0, floatval( $_POST['distance_free_miles']  ?? 5 ) ),
            'per_mile_cents' => max( 0, (int) round( floatval( $_POST['distance_per_mile'] ?? 2.50 ) * 100 ) ),
            'max_miles'      => max( 0, floatval( $_POST['distance_max_miles']   ?? 15 ) ),
        ];
        update_option( 'jjpws_distance_config', wp_json_encode( $distance ) );

        // One-time price
        $one_time_dollars = floatval( $_POST['one_time_price'] ?? 70 );
        update_option( 'jjpws_one_time_price_cents', (int) round( $one_time_dollars * 100 ) );

        // API keys
        $keys = get_option( 'jjpws_api_keys', [] );
        if ( is_string( $keys ) ) $keys = maybe_unserialize( $keys );
        if ( ! is_array( $keys ) ) $keys = [];

        $key_fields = [
            'google_maps',
            'stripe_test_secret',
            'stripe_live_secret',
            'arcgis_developer_key',
        ];

        foreach ( $key_fields as $f ) {
            if ( isset( $_POST[ $f ] ) && trim( $_POST[ $f ] ) !== '' ) {
                $keys[ $f ] = sanitize_text_field( $_POST[ $f ] );
            }
        }

        // Remove any legacy Regrid token (no longer used)
        unset( $keys['regrid'] );

        update_option( 'jjpws_api_keys', $keys );

        if ( ! empty( $_POST['stripe_webhook_secret_direct'] ) ) {
            update_option( 'jjpws_stripe_webhook_secret', sanitize_text_field( $_POST['stripe_webhook_secret_direct'] ) );
        }

        // Parcel data (ArcGIS REST endpoint)
        if ( isset( $_POST['parcel_endpoint'] ) ) {
            update_option( 'jjpws_parcel_endpoint', esc_url_raw( trim( $_POST['parcel_endpoint'] ) ) );
        }
        if ( isset( $_POST['parcel_acreage_field'] ) ) {
            $field = sanitize_text_field( $_POST['parcel_acreage_field'] );
            update_option( 'jjpws_parcel_acreage_field', $field !== '' ? $field : 'Acreage' );
        }
        if ( isset( $_POST['parcel_attribution'] ) ) {
            update_option( 'jjpws_parcel_attribution', sanitize_text_field( $_POST['parcel_attribution'] ) );
        }

        // Color
        $primary = sanitize_hex_color( $_POST['primary_color'] ?? '' );
        if ( $primary ) {
            update_option( 'jjpws_primary_color', $primary );
        }

        wp_redirect( admin_url( 'admin.php?page=jjpws-settings&saved=1' ) );
        exit;
    }

    /**
     * Admin AJAX: run a live parcel-lookup diagnostic for a given address.
     * Surfaces the geocoded coords, the exact ArcGIS request URL, the HTTP
     * status, and the raw response so the admin can see exactly why a
     * lookup is failing.
     */
    public function diagnose_parcel(): void {
        check_ajax_referer( 'jjpws_diagnose', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Access denied.' ], 403 );
        }

        $street = sanitize_text_field( $_POST['street'] ?? '' );
        $city   = sanitize_text_field( $_POST['city']   ?? '' );
        $state  = sanitize_text_field( $_POST['state']  ?? '' );
        $zip    = sanitize_text_field( $_POST['zip']    ?? '' );

        if ( ! $street || ! $city || ! $state || ! $zip ) {
            wp_send_json_error( [ 'message' => 'All four address fields are required.' ] );
        }

        $service = new \JJPWS\Services\LotSizeService();
        $geo     = $service->geocode_diagnostic( $street, $city, $state, $zip );

        if ( $geo['lat'] === null ) {
            wp_send_json_error( [
                'stage'    => 'geocoding',
                'message'  => $geo['error_message'] ?: 'Geocoding failed for an unknown reason.',
                'geocode'  => $geo,
            ] );
        }

        $parcel = $service->arcgis_lookup_diagnostic( $geo['lat'], $geo['lng'] );

        wp_send_json_success( [
            'geocoded'  => [ 'lat' => $geo['lat'], 'lng' => $geo['lng'] ],
            'geocode_detail' => $geo,
            'parcel'    => $parcel,
        ] );
    }

    public function enqueue_assets( string $hook ): void {
        if ( strpos( $hook, 'jjpws' ) === false ) return;
        wp_enqueue_style( 'jjpws-admin', JJPWS_PLUGIN_URL . 'assets/css/admin.css', [], JJPWS_VERSION );
    }

    private function render_settings_page(): void {
        $mode             = get_option( 'jjpws_stripe_mode', 'test' );
        $keys             = get_option( 'jjpws_api_keys', [] );
        $webhook_secret   = get_option( 'jjpws_stripe_webhook_secret', '' );
        $business_address = get_option( 'jjpws_business_address', '' );
        $business_email   = get_option( 'jjpws_business_email', '' );
        $business_phone   = get_option( 'jjpws_business_phone', '' );
        $distance         = json_decode( (string) get_option( 'jjpws_distance_config', '' ), true ) ?: \JJPWS\Services\PricingEngine::default_distance_config();
        $one_time_cents   = (int) get_option( 'jjpws_one_time_price_cents', 7000 );
        $primary_color    = get_option( 'jjpws_primary_color', '#2c7a3d' );
        $origin_coords    = get_option( 'jjpws_business_origin_coords' );

        $parcel_endpoint    = get_option( 'jjpws_parcel_endpoint', 'https://gis.cherokeecountyga.gov/arcgis/rest/services/MainLayers/MapServer/1/query' );
        $parcel_field       = get_option( 'jjpws_parcel_acreage_field', 'Acreage' );
        $parcel_attribution = get_option( 'jjpws_parcel_attribution', 'Parcel data © Georgia GIS / Georgia Open Data. Boundaries and acreage are for reference only and do not constitute a legal survey.' );

        if ( is_string( $keys ) ) $keys = maybe_unserialize( $keys );
        if ( ! is_array( $keys ) ) $keys = [];

        $webhook_url = get_rest_url( null, 'jjpws/v1/stripe-webhook' );
        $saved       = isset( $_GET['saved'] );

        include JJPWS_PLUGIN_DIR . 'templates/admin-settings.php';
    }
}
