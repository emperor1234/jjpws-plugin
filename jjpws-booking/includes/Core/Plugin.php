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

        // Native WP registration: inject hidden field + auto-login on submit
        add_action( 'register_form',  [ $this, 'add_resume_to_register_form' ] );
        add_action( 'user_register',  [ $this, 'auto_login_after_jjpws_registration' ] );

        // WooCommerce auth redirect (no-op if WooCommerce not active)
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

    /**
     * Inject a hidden jjpws_resume field into the native WP registration form so
     * the flag survives the POST submission (GET params are not carried through).
     */
    public function add_resume_to_register_form(): void {
        if ( ! empty( $_GET['jjpws_resume'] ) ) {
            echo '<input type="hidden" name="jjpws_resume" value="1" />';
        }
    }

    /**
     * After native WP registration, immediately log the user in and redirect back
     * to the booking page — skipping the "check your email" interstitial.
     * Only fires when the registration originated from the booking form.
     */
    public function auto_login_after_jjpws_registration( int $user_id ): void {
        if ( ! empty( $_POST['jjpws_resume'] ) ) {
            wp_set_auth_cookie( $user_id, false );
            wp_safe_redirect( $this->get_booking_page_url() . '?jjpws_resume=1' );
            exit;
        }
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
        $site_name   = get_bloginfo( 'name' );
        $logo_url    = get_option( 'site_icon' )
            ? wp_get_attachment_image_url( (int) get_option( 'site_icon' ), 'full' )
            : '';
        ?>
        <style>
        /* ── Page ── */
        body.login {
            background: #f0f4f1;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        #login { padding-top: 5vh; }

        /* ── Logo / site name ── */
        body.login #login h1 a {
            background-image: <?php echo $logo_url ? "url('" . esc_url( $logo_url ) . "')" : 'none'; ?>;
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            width: 100%;
            height: <?php echo $logo_url ? '72px' : '0'; ?>;
            margin-bottom: <?php echo $logo_url ? '8px' : '0'; ?>;
        }

        /* ── Site name heading (shown when no logo) ── */
        <?php if ( ! $logo_url ) : ?>
        body.login #login h1::after {
            content: '<?php echo esc_js( $site_name ); ?>';
            display: block;
            text-align: center;
            font-size: 1.6rem;
            font-weight: 800;
            color: <?php echo esc_attr( $brand_color ); ?>;
            margin-bottom: 4px;
            font-family: inherit;
        }
        <?php endif; ?>

        /* ── Form card ── */
        body.login #loginform,
        body.login #registerform,
        body.login #lostpasswordform {
            border: none;
            border-top: 4px solid <?php echo esc_attr( $brand_color ); ?>;
            border-radius: 0 0 14px 14px;
            box-shadow: 0 8px 36px rgba(0,0,0,.13);
            padding: 28px 32px 24px;
            background: #fff;
        }

        /* ── Inputs ── */
        body.login input[type="text"],
        body.login input[type="password"],
        body.login input[type="email"] {
            border: 1.5px solid #d0d7de !important;
            border-radius: 8px !important;
            padding: 10px 14px !important;
            font-size: 15px !important;
            color: #1a1a1a !important;
            box-shadow: none !important;
            transition: border-color .18s, box-shadow .18s;
            box-sizing: border-box;
        }
        body.login input[type="text"]:focus,
        body.login input[type="password"]:focus,
        body.login input[type="email"]:focus {
            border-color: <?php echo esc_attr( $brand_color ); ?> !important;
            box-shadow: 0 0 0 3px <?php echo esc_attr( $brand_color ); ?>22 !important;
            outline: none !important;
        }

        /* ── Labels ── */
        body.login label {
            font-size: 13px;
            font-weight: 600;
            color: #374151;
        }

        /* ── Submit button ── */
        body.login .button-primary {
            background: <?php echo esc_attr( $brand_color ); ?> !important;
            border-color: <?php echo esc_attr( $brand_color ); ?> !important;
            border-radius: 8px !important;
            box-shadow: none !important;
            text-shadow: none !important;
            font-size: 15px !important;
            font-weight: 600 !important;
            height: auto !important;
            padding: 10px 20px !important;
            width: 100%;
            transition: opacity .18s;
        }
        body.login .button-primary:hover { opacity: .88; }

        /* ── Links ── */
        body.login #nav a,
        body.login #backtoblog a {
            color: <?php echo esc_attr( $brand_color ); ?>;
            text-decoration: none;
        }
        body.login #nav a:hover,
        body.login #backtoblog a:hover { text-decoration: underline; }

        body.login #nav,
        body.login #backtoblog {
            text-align: center;
            padding: 8px 0 0;
        }

        /* ── "Back to site" bar ── */
        body.login #backtoblog {
            background: #fff;
            border-radius: 14px;
            margin-top: 6px;
            padding: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,.07);
        }
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
