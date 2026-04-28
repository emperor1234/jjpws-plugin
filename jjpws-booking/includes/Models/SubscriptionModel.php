<?php

namespace JJPWS\Models;

class SubscriptionModel {

    private string $table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'jjpws_subscriptions';
    }

    public function create( array $data ): int|false {
        global $wpdb;

        $result = $wpdb->insert(
            $this->table,
            [
                'user_id'             => absint( $data['user_id'] ),
                'stripe_customer_id'  => sanitize_text_field( $data['stripe_customer_id'] ),
                'stripe_sub_id'       => sanitize_text_field( $data['stripe_sub_id'] ),
                'stripe_price_id'     => sanitize_text_field( $data['stripe_price_id'] ),
                'street_address'      => sanitize_text_field( $data['street_address'] ),
                'city'                => sanitize_text_field( $data['city'] ),
                'state'               => sanitize_text_field( $data['state'] ),
                'zip_code'            => sanitize_text_field( $data['zip_code'] ),
                'lat'                 => isset( $data['lat'] ) ? floatval( $data['lat'] ) : null,
                'lng'                 => isset( $data['lng'] ) ? floatval( $data['lng'] ) : null,
                'lot_size_sqft'       => isset( $data['lot_size_sqft'] ) ? absint( $data['lot_size_sqft'] ) : null,
                'lot_size_category'   => sanitize_text_field( $data['lot_size_category'] ),
                'dog_count'           => absint( $data['dog_count'] ),
                'frequency'           => sanitize_text_field( $data['frequency'] ),
                'monthly_price_cents' => absint( $data['monthly_price_cents'] ),
                'status'              => 'active',
                'stripe_status'       => sanitize_text_field( $data['stripe_status'] ?? 'active' ),
                'current_period_end'  => $data['current_period_end'] ?? null,
            ],
            [ '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%d', '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s' ]
        );

        return $result ? $wpdb->insert_id : false;
    }

    public function find( int $id ): ?object {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $id )
        );
    }

    public function find_by_stripe_sub( string $stripe_sub_id ): ?object {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$this->table} WHERE stripe_sub_id = %s", $stripe_sub_id )
        );
    }

    public function get_by_user( int $user_id ): array {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM {$this->table} WHERE user_id = %d ORDER BY created_at DESC", $user_id )
        ) ?: [];
    }

    public function get_all( array $args = [] ): array {
        global $wpdb;

        $where  = '1=1';
        $params = [];

        if ( ! empty( $args['status'] ) ) {
            $where   .= ' AND status = %s';
            $params[] = $args['status'];
        }

        if ( ! empty( $args['search'] ) ) {
            $where   .= ' AND (street_address LIKE %s OR city LIKE %s)';
            $like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $limit  = absint( $args['limit'] ?? 50 );
        $offset = absint( $args['offset'] ?? 0 );

        $sql = "SELECT s.*, u.display_name, u.user_email
                FROM {$this->table} s
                LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
                WHERE {$where}
                ORDER BY s.created_at DESC
                LIMIT %d OFFSET %d";

        $params[] = $limit;
        $params[] = $offset;

        return $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) ) ?: [];
    }

    public function count_all( string $status = '' ): int {
        global $wpdb;

        if ( $status ) {
            return (int) $wpdb->get_var(
                $wpdb->prepare( "SELECT COUNT(*) FROM {$this->table} WHERE status = %s", $status )
            );
        }

        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table}" );
    }

    public function update_status( string $stripe_sub_id, string $status, array $extra = [] ): bool {
        global $wpdb;

        $data   = array_merge( [ 'status' => $status ], $extra );
        $format = array_fill( 0, count( $data ), '%s' );

        $result = $wpdb->update(
            $this->table,
            $data,
            [ 'stripe_sub_id' => $stripe_sub_id ],
            $format,
            [ '%s' ]
        );

        return $result !== false;
    }

    public function update_period_end( string $stripe_sub_id, string $period_end ): bool {
        return $this->update_status( $stripe_sub_id, 'active', [
            'current_period_end' => $period_end,
            'stripe_status'      => 'active',
        ] );
    }

    public function mark_cancelled( string $stripe_sub_id ): bool {
        return $this->update_status( $stripe_sub_id, 'cancelled', [
            'cancelled_at'  => current_time( 'mysql' ),
            'stripe_status' => 'cancelled',
        ] );
    }

    public function mark_past_due( string $stripe_sub_id ): bool {
        return $this->update_status( $stripe_sub_id, 'past_due', [
            'stripe_status' => 'past_due',
        ] );
    }
}
