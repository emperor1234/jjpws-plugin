<?php

namespace JJPWS\Frontend;

use JJPWS\Models\SubscriptionModel;

class AccountPage {

    public function register_shortcode(): void {
        add_shortcode( 'jjpws_my_account', [ $this, 'render_shortcode' ] );
    }

    public function enqueue_assets(): void {
        if ( ! $this->is_account_page() ) return;

        wp_enqueue_style(
            'jjpws-account-page',
            JJPWS_PLUGIN_URL . 'assets/css/account-page.css',
            [],
            JJPWS_VERSION
        );
    }

    public function render_shortcode( array $atts ): string {
        if ( ! is_user_logged_in() ) {
            $login_url = wp_login_url( get_permalink() );
            $reg_url   = wp_registration_url();
            return sprintf(
                '<div class="jjpws-auth-gate"><p>%s</p><a href="%s" class="jjpws-btn jjpws-btn--primary">%s</a> <a href="%s" class="jjpws-btn jjpws-btn--secondary">%s</a></div>',
                esc_html__( 'Please log in or create an account to manage your profile.', 'jjpws-booking' ),
                esc_url( $login_url ),
                esc_html__( 'Log In', 'jjpws-booking' ),
                esc_url( $reg_url ),
                esc_html__( 'Create Account', 'jjpws-booking' )
            );
        }

        $user          = wp_get_current_user();
        $model         = new SubscriptionModel();
        $subscriptions = $model->get_by_user( $user->ID );
        $nonce         = wp_create_nonce( 'jjpws_nonce' );
        $active_tab    = sanitize_key( $_GET['tab'] ?? 'profile' );

        ob_start();
        include JJPWS_PLUGIN_DIR . 'templates/account-page.php';
        return ob_get_clean();
    }

    private function is_account_page(): bool {
        global $post;
        if ( $post && has_shortcode( $post->post_content, 'jjpws_my_account' ) ) return true;
        return false;
    }
}
