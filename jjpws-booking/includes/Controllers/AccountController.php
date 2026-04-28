<?php

namespace JJPWS\Controllers;

use JJPWS\Models\SubscriptionModel;
use JJPWS\Services\StripeService;
use JJPWS\Services\EmailService;

class AccountController {

    public function cancel_subscription(): void {
        check_ajax_referer( 'jjpws_nonce', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( [ 'code' => 'UNAUTHENTICATED', 'message' => 'Please log in.' ], 401 );
        }

        $sub_id  = absint( $_POST['subscription_id'] ?? 0 );
        $user_id = get_current_user_id();

        if ( ! $sub_id ) {
            wp_send_json_error( [ 'code' => 'INVALID_ID', 'message' => 'Invalid subscription ID.' ] );
        }

        try {
            $model  = new SubscriptionModel();
            $record = $model->find( $sub_id );

            if ( ! $record ) {
                wp_send_json_error( [ 'code' => 'NOT_FOUND', 'message' => 'Subscription not found.' ], 404 );
            }

            // Users may only cancel their own subscriptions
            if ( (int) $record->user_id !== $user_id ) {
                wp_send_json_error( [ 'code' => 'FORBIDDEN', 'message' => 'Access denied.' ], 403 );
            }

            if ( $record->status === 'cancelled' ) {
                wp_send_json_error( [ 'code' => 'ALREADY_CANCELLED', 'message' => 'This subscription is already cancelled.' ] );
            }

            $stripe = new StripeService();
            $stripe->cancel_at_period_end( $record->stripe_sub_id );

            $model->mark_cancelled( $record->stripe_sub_id );

            $email = new EmailService();
            $email->send_cancellation( (array) $record );

            $period_end = $record->current_period_end
                ? date( 'F j, Y', strtotime( $record->current_period_end ) )
                : 'the end of your billing period';

            wp_send_json_success( [
                'message'    => "Your subscription has been cancelled. Service continues until {$period_end}.",
                'period_end' => $period_end,
            ] );

        } catch ( \Throwable $e ) {
            error_log( 'JJPWS cancel_subscription error: ' . $e->getMessage() );
            wp_send_json_error( [ 'code' => 'INTERNAL_ERROR', 'message' => 'Cancellation failed. Please try again or contact support.' ] );
        }
    }
}
