<?php

namespace JJPWS\Frontend;

use JJPWS\Services\LotSizeClassifier;

class BookingForm {

    public function register_shortcode(): void {
        add_shortcode( 'jjpws_booking_form', [ $this, 'render_shortcode' ] );
    }

    public function render_shortcode( array $atts ): string {
        ob_start();
        include JJPWS_PLUGIN_DIR . 'templates/booking-form.php';
        return ob_get_clean();
    }

    public function enqueue_assets(): void {
        if ( ! $this->is_booking_page() ) {
            return;
        }

        $google_key = $this->get_google_api_key();
        $mode       = get_option( 'jjpws_stripe_mode', 'test' );

        wp_enqueue_style(
            'jjpws-booking',
            JJPWS_PLUGIN_URL . 'assets/css/booking-form.css',
            [],
            JJPWS_VERSION
        );

        if ( $google_key ) {
            wp_enqueue_script(
                'google-places',
                "https://maps.googleapis.com/maps/api/js?key={$google_key}&libraries=places&callback=jjpwsInitAutocomplete",
                [],
                null,
                true
            );
        }

        wp_enqueue_script(
            'jjpws-autocomplete',
            JJPWS_PLUGIN_URL . 'assets/js/address-autocomplete.js',
            [],
            JJPWS_VERSION,
            true
        );

        wp_enqueue_script(
            'jjpws-booking',
            JJPWS_PLUGIN_URL . 'assets/js/booking-form.js',
            [ 'jjpws-autocomplete' ],
            JJPWS_VERSION,
            true
        );

        wp_localize_script( 'jjpws-booking', 'jjpwsData', [
            'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
            'nonce'       => wp_create_nonce( 'jjpws_nonce' ),
            'hasGoogle'   => ! empty( $google_key ),
            'loginUrl'    => wp_login_url( get_permalink() . '?jjpws_resume=1' ),
            'registerUrl' => wp_registration_url() . '?redirect_to=' . urlencode( get_permalink() . '?jjpws_resume=1' ),
            'isLoggedIn'  => is_user_logged_in() ? 1 : 0,
            'resumeFlow'  => isset( $_GET['jjpws_resume'] ) ? 1 : 0,
            'lotCategories' => LotSizeClassifier::all_labels(),
            'stripeMode'  => $mode,
        ] );
    }

    private function is_booking_page(): bool {
        global $post;

        if ( is_page( 'book' ) ) {
            return true;
        }

        if ( $post && has_shortcode( $post->post_content, 'jjpws_booking_form' ) ) {
            return true;
        }

        return false;
    }

    private function get_google_api_key(): string {
        $keys = get_option( 'jjpws_api_keys', [] );

        if ( is_string( $keys ) ) {
            $keys = maybe_unserialize( $keys );
        }

        return is_array( $keys ) ? ( $keys['google_maps'] ?? '' ) : '';
    }
}
