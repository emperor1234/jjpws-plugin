<?php

namespace JJPWS\Services;

class EmailService {

    public function send_confirmation( array $booking ): void {
        $user = get_userdata( $booking['user_id'] );
        if ( ! $user ) return;

        $to      = $user->user_email;
        $subject = 'Booking Confirmed — JJ Pet Waste Services';
        $message = $this->render( 'confirmation', $booking );
        $headers = [ 'Content-Type: text/html; charset=UTF-8' ];

        wp_mail( $to, $subject, $message, $headers );
        $this->send_admin_notification( $booking, $user );
    }

    public function send_cancellation( array $booking ): void {
        $user = get_userdata( $booking['user_id'] );
        if ( ! $user ) return;

        $to      = $user->user_email;
        $subject = 'Subscription Cancellation — JJ Pet Waste Services';
        $message = $this->render( 'cancellation', $booking );
        $headers = [ 'Content-Type: text/html; charset=UTF-8' ];

        wp_mail( $to, $subject, $message, $headers );
    }

    public function send_quote_request( array $data ): void {
        $admin_email = $this->get_business_email();
        $subject     = 'New Quote Request — ' . ( $data['customer_name'] ?: $data['customer_email'] );
        $message     = $this->render( 'quote-request', $data );
        $headers     = [
            'Content-Type: text/html; charset=UTF-8',
            'Reply-To: ' . $data['customer_email'],
        ];

        wp_mail( $admin_email, $subject, $message, $headers );

        // Confirmation to the customer
        $cust_subject = 'We received your quote request — JJ Pet Waste Services';
        $cust_msg     = $this->render( 'quote-confirmation', $data );
        wp_mail( $data['customer_email'], $cust_subject, $cust_msg, [ 'Content-Type: text/html; charset=UTF-8' ] );
    }

    private function send_admin_notification( array $booking, \WP_User $customer ): void {
        $admin_email = $this->get_business_email();
        $subject     = 'New Booking — ' . $customer->display_name;
        $message     = $this->render( 'admin-notification', array_merge( $booking, [ 'customer' => $customer ] ) );
        $headers     = [ 'Content-Type: text/html; charset=UTF-8' ];

        wp_mail( $admin_email, $subject, $message, $headers );
    }

    private function get_business_email(): string {
        $email = trim( (string) get_option( 'jjpws_business_email', '' ) );
        return $email !== '' ? $email : (string) get_option( 'admin_email' );
    }

    private function render( string $template, array $data ): string {
        $path = JJPWS_PLUGIN_DIR . "templates/emails/{$template}.php";

        if ( ! file_exists( $path ) ) {
            return '';
        }

        // Prefix all data keys to prevent variable collisions
        $prefixed_data = [];
        foreach ( $data as $key => $value ) {
            $prefixed_data[ 'jjpws_' . $key ] = $value;
        }
        extract( $prefixed_data, EXTR_SKIP );
        ob_start();
        include $path;
        return ob_get_clean();
    }
}
