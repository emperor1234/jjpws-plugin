<?php

namespace JJPWS\Services;

use JJPWS\Services\PricingEngine;

class EmailService {

    public function send_confirmation( array $booking ): void {
        $user    = get_userdata( $booking['user_id'] );
        $to      = $user->user_email;
        $subject = 'Booking Confirmed — JJ Pet Waste Services';
        $message = $this->render( 'confirmation', $booking );
        $headers = [ 'Content-Type: text/html; charset=UTF-8' ];

        wp_mail( $to, $subject, $message, $headers );
        $this->send_admin_notification( $booking, $user );
    }

    public function send_cancellation( array $booking ): void {
        $user    = get_userdata( $booking['user_id'] );
        $to      = $user->user_email;
        $subject = 'Subscription Cancellation — JJ Pet Waste Services';
        $message = $this->render( 'cancellation', $booking );
        $headers = [ 'Content-Type: text/html; charset=UTF-8' ];

        wp_mail( $to, $subject, $message, $headers );
    }

    private function send_admin_notification( array $booking, \WP_User $customer ): void {
        $admin_email = get_option( 'admin_email' );
        $subject     = 'New Booking — ' . $customer->display_name;
        $message     = $this->render( 'admin-notification', array_merge( $booking, [ 'customer' => $customer ] ) );
        $headers     = [ 'Content-Type: text/html; charset=UTF-8' ];

        wp_mail( $admin_email, $subject, $message, $headers );
    }

    private function render( string $template, array $data ): string {
        $path = JJPWS_PLUGIN_DIR . "templates/emails/{$template}.php";

        if ( ! file_exists( $path ) ) {
            return '';
        }

        extract( $data, EXTR_SKIP );
        ob_start();
        include $path;
        return ob_get_clean();
    }
}
