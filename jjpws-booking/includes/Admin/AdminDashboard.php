<?php

namespace JJPWS\Admin;

use JJPWS\Models\SubscriptionModel;
use JJPWS\Services\LotSizeClassifier;
use JJPWS\Services\PricingEngine;

class AdminDashboard {

    public function render(): void {
        $model  = new SubscriptionModel();
        $status = sanitize_text_field( $_GET['status'] ?? '' );
        $search = sanitize_text_field( $_GET['search'] ?? '' );
        $paged  = max( 1, absint( $_GET['paged'] ?? 1 ) );
        $limit  = 25;
        $offset = ( $paged - 1 ) * $limit;

        $args = [
            'limit'  => $limit,
            'offset' => $offset,
        ];

        if ( $status ) {
            $args['status'] = $status;
        }
        if ( $search ) {
            $args['search'] = $search;
        }

        $subscriptions = $model->get_all( $args );
        $total         = $model->count_all( $status );
        $total_pages   = ceil( $total / $limit );
        $counts        = [
            'all'       => $model->count_all(),
            'active'    => $model->count_all( 'active' ),
            'cancelled' => $model->count_all( 'cancelled' ),
            'past_due'  => $model->count_all( 'past_due' ),
        ];

        include JJPWS_PLUGIN_DIR . 'templates/admin-dashboard.php';
    }
}
