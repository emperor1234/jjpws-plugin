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
use JJPWS\Frontend\AccountPage;

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

        // Branded WP login page
        add_action( 'login_enqueue_scripts', [ $this, 'brand_login_page' ] );
        add_filter( 'login_headerurl',       [ $this, 'login_logo_url' ] );
        add_filter( 'login_headertitle',     [ $this, 'login_logo_title' ] );
    }

    private function define_public_hooks(): void {
        // Auth redirect: after WP login, honour jjpws_resume redirect_to
        add_filter( 'login_redirect', [ $this, 'login_redirect_filter' ], 20, 3 );

        // Auth redirect: after WooCommerce registration/login
        add_filter( 'woocommerce_registration_redirect', [ $this, 'wc_registration_redirect' ], 20 );
        add_filter( 'woocommerce_login_redirect',        [ $this, 'wc_login_redirect' ], 20, 2 );

        $form = new BookingForm();
        $this->loader->add_action( 'init', $form, 'register_shortcode' );
        $this->loader->add_action( 'wp_enqueue_scripts', $form, 'enqueue_assets' );

        $tab = new AccountTab();
        $this->loader->add_filter( 'woocommerce_account_menu_items', $tab, 'add_menu_item' );
        $this->loader->add_action( 'woocommerce_account_jjpws-subscriptions_endpoint', $tab, 'render_endpoint' );
        $this->loader->add_action( 'init', $tab, 'register_endpoint' );
        $this->loader->add_filter( 'query_vars', $tab, 'add_query_var', 0 );
        $this->loader->add_action( 'template_redirect', $tab, 'handle_redirect' );

        $account_page = new AccountPage();
        $this->loader->add_action( 'init', $account_page, 'register_shortcode' );
        $this->loader->add_action( 'wp_enqueue_scripts', $account_page, 'enqueue_assets' );
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
        add_action( 'wp_ajax_jjpws_update_profile',          [ $account,  'update_profile' ] );
    }

    private function define_rest_hooks(): void {
        $webhook = new WebhookController();
        $this->loader->add_action( 'rest_api_init', $webhook, 'register_routes' );
    }

    // ── Login / auth redirect hooks ───────────────────────────────────────────

    /**
     * After a standard WP login, honour the redirect_to if it points back to
     * the booking page with ?jjpws_resume=1.  WordPress normally does this
     * itself, but some security plugins strip or override redirect_to — this
     * filter ensures it always fires for our resume flow.
     */
    public function login_redirect_filter( string $redirect_to, string $requested, $user ): string {
        if ( is_wp_error( $user ) ) {
            return $redirect_to;
        }
        if ( ! empty( $requested ) && strpos( $requested, 'jjpws_resume' ) !== false ) {
            return $requested;
        }
        return $redirect_to;
    }

    /**
     * After WooCommerce registration (user is immediately logged in), redirect
     * back to the booking page so the form can resume without re-entry.
     */
    public function wc_registration_redirect( string $redirect ): string {
        if ( isset( $_REQUEST['jjpws_resume'] ) ) {
            return $this->get_booking_page_url() . '?jjpws_resume=1';
        }
        return $redirect;
    }

    /**
     * After WooCommerce login via My Account page, redirect back to booking.
     */
    public function wc_login_redirect( string $redirect, $user ): string {
        if ( isset( $_REQUEST['jjpws_resume'] ) ) {
            return $this->get_booking_page_url() . '?jjpws_resume=1';
        }
        return $redirect;
    }

    private function get_booking_page_url(): string {
        $page = get_page_by_path( 'book' );
        if ( $page ) {
            return get_permalink( $page );
        }
        // Try common slugs
        foreach ( [ 'booking', 'book-now', 'schedule' ] as $slug ) {
            $page = get_page_by_path( $slug );
            if ( $page ) return get_permalink( $page );
        }
        return home_url( '/book/' );
    }

    // ── Branded WP login page ─────────────────────────────────────────────────

    public function brand_login_page(): void {
        $brand_color = sanitize_hex_color( get_option( 'jjpws_primary_color', '#2c7a3d' ) ) ?: '#2c7a3d';
        $logo_url    = get_option( 'site_icon' )
            ? wp_get_attachment_image_url( (int) get_option( 'site_icon' ), 'full' )
            : '';
        ?>
        <style>
        body.login { background: #f4f7f4; }
        body.login #login h1 a {
            <?php if ( $logo_url ) : ?>
            background-image: url('<?php echo esc_url( $logo_url ); ?>');
            background-size: contain;
            width: 100%;
            height: 80px;
            <?php endif; ?>
        }
        body.login #loginform,
        body.login #lostpasswordform,
        body.login #registerform {
            border-top: 4px solid <?php echo esc_attr( $brand_color ); ?>;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 4px 24px rgba(0,0,0,.10);
        }
        body.login .button-primary {
            background: <?php echo esc_attr( $brand_color ); ?> !important;
            border-color: <?php echo esc_attr( $brand_color ); ?> !important;
            box-shadow: none !important;
            text-shadow: none !important;
        }
        body.login .button-primary:hover {
            opacity: .88;
        }
        body.login #backtoblog a,
        body.login #nav a { color: <?php echo esc_attr( $brand_color ); ?>; }
        </style>
        <?php
    }

    public function login_logo_url(): string {
        return home_url( '/' );
    }

    public function login_logo_title(): string {
        return get_bloginfo( 'name' );
    }
}
