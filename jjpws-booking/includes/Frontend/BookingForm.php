<?php

namespace JJPWS\Frontend;

class BookingForm {

    public function register_shortcode(): void {
        add_shortcode( 'jjpws_booking_form', [ $this, 'render_shortcode' ] );
    }

    public function render_shortcode( array $atts ): string {
        ob_start();
        include JJPWS_PLUGIN_DIR . 'templates/booking-form.php';
        $html = ob_get_clean();

        // Inline color override
        $color = sanitize_hex_color( get_option( 'jjpws_primary_color', '#2c7a3d' ) ) ?: '#2c7a3d';
        $css   = '<style>:root { --jjpws-green: ' . esc_attr( $color ) . '; --jjpws-green-dark: ' . esc_attr( $this->darken( $color, 12 ) ) . '; --jjpws-green-light: ' . esc_attr( $this->lighten( $color, 88 ) ) . '; }</style>';

        return $css . $html;
    }

    public function enqueue_assets(): void {
        if ( ! $this->is_booking_page() ) return;

        $google_key = $this->get_google_api_key();

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

        wp_enqueue_script( 'jjpws-autocomplete', JJPWS_PLUGIN_URL . 'assets/js/address-autocomplete.js', [], JJPWS_VERSION, true );
        wp_enqueue_script( 'jjpws-booking',      JJPWS_PLUGIN_URL . 'assets/js/booking-form.js', [ 'jjpws-autocomplete' ], JJPWS_VERSION, true );

        $current_user = wp_get_current_user();
        $user_email   = is_user_logged_in() ? $current_user->user_email : '';

        wp_localize_script( 'jjpws-booking', 'jjpwsData', [
            'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
            'nonce'          => wp_create_nonce( 'jjpws_nonce' ),
            'hasGoogle'      => ! empty( $google_key ),
            'loginUrl'       => wp_login_url( get_permalink() . '?jjpws_resume=1' ),
            'isLoggedIn'     => is_user_logged_in() ? 1 : 0,
            'userEmail'      => $user_email,
            'resumeFlow'     => isset( $_GET['jjpws_resume'] ) ? 1 : 0,
            'businessEmail'  => get_option( 'jjpws_business_email', get_option( 'admin_email' ) ),
            'businessPhone'  => get_option( 'jjpws_business_phone', '' ),
        ] );
    }

    private function is_booking_page(): bool {
        global $post;

        if ( is_page( 'book' ) ) return true;
        if ( $post && has_shortcode( $post->post_content, 'jjpws_booking_form' ) ) return true;

        return false;
    }

    private function get_google_api_key(): string {
        $keys = get_option( 'jjpws_api_keys', [] );
        if ( is_string( $keys ) ) $keys = maybe_unserialize( $keys );
        return is_array( $keys ) ? ( $keys['google_maps'] ?? '' ) : '';
    }

    private function darken( string $hex, int $percent ): string {
        return $this->shift_lightness( $hex, -$percent );
    }

    private function lighten( string $hex, int $percent ): string {
        return $this->shift_lightness( $hex, $percent );
    }

    private function shift_lightness( string $hex, int $percent ): string {
        $hex = ltrim( $hex, '#' );
        if ( strlen( $hex ) === 3 ) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        $r = hexdec( substr( $hex, 0, 2 ) );
        $g = hexdec( substr( $hex, 2, 2 ) );
        $b = hexdec( substr( $hex, 4, 2 ) );

        $factor = $percent / 100;
        if ( $factor >= 0 ) {
            $r = (int) round( $r + ( 255 - $r ) * $factor );
            $g = (int) round( $g + ( 255 - $g ) * $factor );
            $b = (int) round( $b + ( 255 - $b ) * $factor );
        } else {
            $f = 1 + $factor;
            $r = (int) round( $r * $f );
            $g = (int) round( $g * $f );
            $b = (int) round( $b * $f );
        }

        return '#' . sprintf( '%02x%02x%02x', max( 0, min( 255, $r ) ), max( 0, min( 255, $g ) ), max( 0, min( 255, $b ) ) );
    }
}
