<?php

namespace JJPWS\Models;

class QuoteRequestModel {

    private string $table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'jjpws_quote_requests';
    }

    public function create( array $data ): int|false {
        global $wpdb;

        $row = [
            'customer_name'  => sanitize_text_field( $data['customer_name'] ?? '' ),
            'customer_email' => sanitize_email( $data['customer_email'] ),
            'customer_phone' => sanitize_text_field( $data['customer_phone'] ?? '' ),
            'street_address' => sanitize_text_field( $data['street_address'] ?? '' ),
            'city'           => sanitize_text_field( $data['city'] ?? '' ),
            'state'          => sanitize_text_field( $data['state'] ?? '' ),
            'zip_code'       => sanitize_text_field( $data['zip_code'] ?? '' ),
            'lot_size_acres' => isset( $data['lot_size_acres'] ) ? floatval( $data['lot_size_acres'] ) : null,
            'dog_count'      => isset( $data['dog_count'] ) ? absint( $data['dog_count'] ) : null,
            'distance_miles' => isset( $data['distance_miles'] ) ? floatval( $data['distance_miles'] ) : null,
            'reason'         => sanitize_text_field( $data['reason'] ?? '' ),
            'message'        => sanitize_textarea_field( $data['message'] ?? '' ),
            'status'         => 'new',
        ];

        return $wpdb->insert( $this->table, $row ) ? $wpdb->insert_id : false;
    }

    public function get_all( array $args = [] ): array {
        global $wpdb;

        $where  = '1=1';
        $params = [];

        if ( ! empty( $args['status'] ) ) {
            $where   .= ' AND status = %s';
            $params[] = $args['status'];
        }

        $limit  = absint( $args['limit'] ?? 50 );
        $offset = absint( $args['offset'] ?? 0 );

        $sql = "SELECT * FROM {$this->table} WHERE {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d";
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
}
