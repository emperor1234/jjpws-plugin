<?php

namespace JJPWS\Controllers;

use JJPWS\Models\SubscriptionModel;
use JJPWS\Services\StripeService;
use JJPWS\Services\EmailService;

class WebhookController {

    public function register_routes(): void {
        register_rest_route( 'jjpws/v1', '/stripe-webhook', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'handle' ],
            'permission_callback' => '__return_true',
        ] );
    }

    public function handle( \WP_REST_Request $request ): \WP_REST_Response {
        $payload    = $request->get_body();
        $sig_header = $request->get_header( 'stripe-signature' );

        if ( empty( $sig_header ) ) {
            return new \WP_REST_Response( [ 'error' => 'Missing signature' ], 400 );
        }

        try {
            $stripe = new StripeService();
            $event  = $stripe->verify_webhook( $payload, $sig_header );
        } catch ( \Stripe\Exception\SignatureVerificationException $e ) {
            error_log( 'JJPWS webhook signature failed: ' . $e->getMessage() );
            return new \WP_REST_Response( [ 'error' => 'Invalid signature' ], 400 );
        } catch ( \Throwable $e ) {
            error_log( 'JJPWS webhook error: ' . $e->getMessage() );
            return new \WP_REST_Response( [ 'error' => 'Webhook error' ], 400 );
        }

        try {
            $this->dispatch( $event );
        } catch ( \Throwable $e ) {
            error_log( 'JJPWS webhook dispatch error: ' . $e->getMessage() );
            return new \WP_REST_Response( [ 'error' => 'Dispatch error' ], 400 );
        }

        return new \WP_REST_Response( [ 'received' => true ], 200 );
    }

    private function dispatch( \Stripe\Event $event ): void {
        $model = new SubscriptionModel();
        $email = new EmailService();

        switch ( $event->type ) {
            case 'checkout.session.completed':
                $this->handle_checkout_completed( $event->data->object, $model, $email );
                break;

            case 'invoice.payment_succeeded':
                $sub_id = $event->data->object->subscription ?? null;
                if ( $sub_id ) {
                    $period_end = date( 'Y-m-d H:i:s', $event->data->object->lines->data[0]->period->end ?? time() );
                    $model->update_period_end( $sub_id, $period_end );
                }
                break;

            case 'invoice.payment_failed':
                $sub_id = $event->data->object->subscription ?? null;
                if ( $sub_id ) {
                    $model->mark_past_due( $sub_id );
                    $record = $model->find_by_stripe_sub( $sub_id );
                    if ( $record ) {
                        $email->send_payment_failed( (array) $record );
                    }
                }
                break;

            case 'customer.subscription.deleted':
                $model->mark_cancelled( $event->data->object->id );
                break;

            case 'customer.subscription.updated':
                $model->update_status( $event->data->object->id, 'active', [
                    'stripe_status' => $event->data->object->status,
                ] );
                break;
        }
    }

    private function handle_checkout_completed( object $session, SubscriptionModel $model, EmailService $email ): void {
        $meta = $session->metadata ?? null;

        if ( empty( $meta->wp_user_id ) || empty( $session->subscription ) ) {
            error_log( 'JJPWS checkout.session.completed missing metadata or subscription' );
            return;
        }

        $stripe       = new StripeService();
        $stripe_sub   = $stripe->retrieve_subscription( $session->subscription );

        $booking_data = [
            'user_id'             => (int) $meta->wp_user_id,
            'stripe_customer_id'  => $session->customer,
            'stripe_sub_id'       => $stripe_sub->id,
            'stripe_price_id'     => $stripe_sub->items->data[0]->price->id ?? '',
            'street_address'      => $meta->street_address ?? '',
            'city'                => $meta->city  ?? '',
            'state'               => $meta->state ?? '',
            'zip_code'            => $meta->zip_code ?? '',
            'lat'                 => isset( $meta->lat )  ? floatval( $meta->lat )  : null,
            'lng'                 => isset( $meta->lng )  ? floatval( $meta->lng )  : null,
            'lot_size_sqft'       => isset( $meta->lot_size_sqft )  ? absint( $meta->lot_size_sqft )  : null,
            'lot_size_category'   => $meta->lot_size_category ?? '',
            'dog_count'           => absint( $meta->dog_count ?? 1 ),
            'frequency'           => $meta->frequency ?? 'weekly',
            'monthly_price_cents' => absint( $meta->monthly_price_cents ?? 0 ),
            'stripe_status'       => $stripe_sub->status,
            'current_period_end'  => date( 'Y-m-d H:i:s', $stripe_sub->current_period_end ),
        ];

        $id = $model->create( $booking_data );

        if ( $id ) {
            $email->send_confirmation( $booking_data );
        }
    }
}
