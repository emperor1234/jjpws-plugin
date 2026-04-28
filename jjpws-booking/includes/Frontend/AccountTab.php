<?php

namespace JJPWS\Frontend;

use JJPWS\Models\SubscriptionModel;
use JJPWS\Services\LotSizeClassifier;
use JJPWS\Services\PricingEngine;

class AccountTab {

    private string $endpoint = 'jjpws-subscriptions';

    // ── WooCommerce hooks ────────────────────────────────────────────────────

    public function add_menu_item( array $items ): array {
        $items[ $this->endpoint ] = 'My Subscriptions';
        return $items;
    }

    public function render_endpoint(): void {
        $this->render_subscriptions();
    }

    // ── Native WP (no WooCommerce) fallback ──────────────────────────────────

    public function register_endpoint(): void {
        add_rewrite_endpoint( $this->endpoint, EP_ROOT | EP_PAGES );
    }

    public function add_query_var( array $vars ): array {
        $vars[] = $this->endpoint;
        return $vars;
    }

    public function handle_redirect(): void {
        global $wp_query;

        if ( ! isset( $wp_query->query_vars[ $this->endpoint ] ) ) {
            return;
        }

        if ( ! is_user_logged_in() ) {
            wp_redirect( wp_login_url( get_permalink() ) );
            exit;
        }

        $this->render_subscriptions();
        exit;
    }

    // ── Shared rendering ─────────────────────────────────────────────────────

    private function render_subscriptions(): void {
        if ( ! is_user_logged_in() ) {
            echo '<p>' . esc_html__( 'You must be logged in to view your subscriptions.', 'jjpws-booking' ) . '</p>';
            return;
        }

        $model         = new SubscriptionModel();
        $subscriptions = $model->get_by_user( get_current_user_id() );
        $nonce         = wp_create_nonce( 'jjpws_nonce' );

        include JJPWS_PLUGIN_DIR . 'templates/account-subscriptions.php';
    }
}
