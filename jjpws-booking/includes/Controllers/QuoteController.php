<?php

namespace JJPWS\Controllers;

use JJPWS\Models\QuoteRequestModel;
use JJPWS\Services\EmailService;

class QuoteController {

    public function submit_quote(): void {
        check_ajax_referer( 'jjpws_nonce', 'nonce' );

        $email = sanitize_email( $_POST['email'] ?? '' );
        if ( ! is_email( $email ) ) {
            wp_send_json_error( [ 'code' => 'INVALID_EMAIL', 'message' => 'Please enter a valid email address.' ] );
        }

        $data = [
            'customer_name'  => sanitize_text_field( $_POST['name']    ?? '' ),
            'customer_email' => $email,
            'customer_phone' => sanitize_text_field( $_POST['phone']   ?? '' ),
            'street_address' => sanitize_text_field( $_POST['street']  ?? '' ),
            'city'           => sanitize_text_field( $_POST['city']    ?? '' ),
            'state'          => sanitize_text_field( $_POST['state']   ?? '' ),
            'zip_code'       => sanitize_text_field( $_POST['zip']     ?? '' ),
            'lot_size_acres' => isset( $_POST['lot_size_acres'] ) ? floatval( $_POST['lot_size_acres'] ) : null,
            'dog_count'      => isset( $_POST['dog_count'] )      ? absint( $_POST['dog_count'] )      : null,
            'distance_miles' => isset( $_POST['distance_miles'] ) ? floatval( $_POST['distance_miles'] ) : null,
            'reason'         => sanitize_text_field( $_POST['reason']  ?? '' ),
            'message'        => sanitize_textarea_field( $_POST['message'] ?? '' ),
        ];

        if ( empty( $data['message'] ) ) {
            wp_send_json_error( [ 'code' => 'MESSAGE_REQUIRED', 'message' => 'Please include a brief message.' ] );
        }

        try {
            $model = new QuoteRequestModel();
            $id    = $model->create( $data );

            if ( ! $id ) {
                wp_send_json_error( [ 'code' => 'SAVE_FAILED', 'message' => 'Could not save your request. Please try again.' ] );
            }

            ( new EmailService() )->send_quote_request( $data );

            wp_send_json_success( [
                'message' => "Thanks! We've received your request and will reach out within one business day.",
            ] );

        } catch ( \Throwable $e ) {
            error_log( 'JJPWS submit_quote error: ' . $e->getMessage() );
            wp_send_json_error( [ 'code' => 'INTERNAL_ERROR', 'message' => 'Something went wrong. Please try again.' ] );
        }
    }
}
