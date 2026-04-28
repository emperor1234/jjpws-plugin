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
            'Pricing Settings',
            'Pricing',
            'manage_options',
            'jjpws-pricing',
            [ $this, 'render_pricing' ]
        );

        add_submenu_page(
            'jjpws-dashboard',
            'API & Stripe Settings',
            'Settings',
            'manage_options',
            'jjpws-settings',
            [ $this, 'render_settings' ]
        );
    }

    public function render_dashboard(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Access denied.' );
        }

        $dashboard = new AdminDashboard();
        $dashboard->render();
    }

    public function render_pricing(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Access denied.' );
        }

        $pricing = new PricingSettings();
        $pricing->render();
    }

    public function render_settings(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Access denied.' );
        }

        $this->render_settings_page();
    }

    public function save_pricing(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Access denied.' );
        }

        check_admin_referer( 'jjpws_save_pricing' );

        $pricing = new PricingSettings();
        $pricing->save( $_POST );

        wp_redirect( admin_url( 'admin.php?page=jjpws-pricing&saved=1' ) );
        exit;
    }

    public function save_settings(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Access denied.' );
        }

        check_admin_referer( 'jjpws_save_settings' );

        $mode = in_array( $_POST['stripe_mode'] ?? '', [ 'test', 'live' ], true )
            ? $_POST['stripe_mode']
            : 'test';

        update_option( 'jjpws_stripe_mode', $mode );

        $keys = get_option( 'jjpws_api_keys', [] );
        if ( is_string( $keys ) ) {
            $keys = maybe_unserialize( $keys );
        }
        if ( ! is_array( $keys ) ) {
            $keys = [];
        }

        $fields = [
            'google_maps'         => 'google_maps',
            'regrid'              => 'regrid',
            'stripe_test_secret'  => 'stripe_test_secret',
            'stripe_live_secret'  => 'stripe_live_secret',
            'stripe_webhook_secret' => 'stripe_webhook_secret',
        ];

        foreach ( $fields as $post_key => $option_key ) {
            if ( isset( $_POST[ $post_key ] ) && ! empty( trim( $_POST[ $post_key ] ) ) ) {
                $keys[ $option_key ] = sanitize_text_field( $_POST[ $post_key ] );
            }
        }

        update_option( 'jjpws_api_keys', $keys );

        if ( ! empty( $_POST['stripe_webhook_secret_direct'] ) ) {
            update_option( 'jjpws_stripe_webhook_secret', sanitize_text_field( $_POST['stripe_webhook_secret_direct'] ) );
        }

        wp_redirect( admin_url( 'admin.php?page=jjpws-settings&saved=1' ) );
        exit;
    }

    public function enqueue_assets( string $hook ): void {
        if ( strpos( $hook, 'jjpws' ) === false ) {
            return;
        }

        wp_enqueue_style( 'jjpws-admin', JJPWS_PLUGIN_URL . 'assets/css/admin.css', [], JJPWS_VERSION );
    }

    private function render_settings_page(): void {
        $mode        = get_option( 'jjpws_stripe_mode', 'test' );
        $keys        = get_option( 'jjpws_api_keys', [] );
        $webhook_secret = get_option( 'jjpws_stripe_webhook_secret', '' );

        if ( is_string( $keys ) ) {
            $keys = maybe_unserialize( $keys );
        }
        if ( ! is_array( $keys ) ) {
            $keys = [];
        }

        $webhook_url = get_rest_url( null, 'jjpws/v1/stripe-webhook' );
        $saved       = isset( $_GET['saved'] );

        include JJPWS_PLUGIN_DIR . 'templates/admin-settings.php';
    }
}
