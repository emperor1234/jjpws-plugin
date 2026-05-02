<?php

namespace JJPWS\Core;

use JJPWS\Controllers\AdminController;
use JJPWS\Controllers\BookingController;
use JJPWS\Controllers\CheckoutController;
use JJPWS\Controllers\WebhookController;
use JJPWS\Controllers\AccountController;
use JJPWS\Controllers\QuoteController;
use JJPWS\Frontend\BookingForm;
use JJPWS\Frontend\AccountTab;

class Plugin {

    private Loader $loader;

    public function __construct() {
        $this->loader = new Loader();
    }

    public function run(): void {
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_ajax_hooks();
        $this->define_rest_hooks();
        $this->loader->run();
    }

    private function define_admin_hooks(): void {
        $admin = new AdminController();
        $this->loader->add_action( 'admin_menu', $admin, 'register_menu' );
        $this->loader->add_action( 'admin_post_jjpws_save_pricing', $admin, 'save_pricing' );
        $this->loader->add_action( 'admin_post_jjpws_save_settings', $admin, 'save_settings' );
        $this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_assets' );
        add_action( 'wp_ajax_jjpws_diagnose_parcel', [ $admin, 'diagnose_parcel' ] );
    }

    private function define_public_hooks(): void {
        $form = new BookingForm();
        $this->loader->add_action( 'init', $form, 'register_shortcode' );
        $this->loader->add_action( 'wp_enqueue_scripts', $form, 'enqueue_assets' );

        $tab = new AccountTab();
        $this->loader->add_filter( 'woocommerce_account_menu_items', $tab, 'add_menu_item' );
        $this->loader->add_action( 'woocommerce_account_jjpws-subscriptions_endpoint', $tab, 'render_endpoint' );
        $this->loader->add_action( 'init', $tab, 'register_endpoint' );
        $this->loader->add_filter( 'query_vars', $tab, 'add_query_var', 0 );
        $this->loader->add_action( 'template_redirect', $tab, 'handle_redirect' );
    }

    private function define_ajax_hooks(): void {
        $booking  = new BookingController();
        $checkout = new CheckoutController();
        $account  = new AccountController();
        $quote    = new QuoteController();

        // Public
        add_action( 'wp_ajax_nopriv_jjpws_lookup_lot_size', [ $booking, 'lookup_lot_size' ] );
        add_action( 'wp_ajax_jjpws_lookup_lot_size',        [ $booking, 'lookup_lot_size' ] );

        add_action( 'wp_ajax_nopriv_jjpws_calculate_price', [ $booking, 'calculate_price' ] );
        add_action( 'wp_ajax_jjpws_calculate_price',        [ $booking, 'calculate_price' ] );

        add_action( 'wp_ajax_nopriv_jjpws_submit_quote',    [ $quote, 'submit_quote' ] );
        add_action( 'wp_ajax_jjpws_submit_quote',           [ $quote, 'submit_quote' ] );

        // Login required
        add_action( 'wp_ajax_jjpws_create_checkout_session', [ $checkout, 'create_session' ] );
        add_action( 'wp_ajax_jjpws_cancel_subscription',     [ $account,  'cancel_subscription' ] );
    }

    private function define_rest_hooks(): void {
        $webhook = new WebhookController();
        $this->loader->add_action( 'rest_api_init', $webhook, 'register_routes' );
    }
}
