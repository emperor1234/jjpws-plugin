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
            'lot_size_acres' => isset( $data['lot_size_acres'] ) && $data['lot_size_acres'] !== '' ? floatval( $data['lot_size_acres'] ) : null,
            'dog_count'      => isset( $data['dog_count'] ) && $data['dog_count'] !== '' ? absint( $data['dog_count'] ) : null,
            'distance_miles' => isset( $data['distance_miles'] ) && $data['distance_miles'] !== '' ? floatval( $data['distance_miles'] ) : null,
            'reason'         => sanitize_text_field( $data['reason'] ?? '' ),
            'message'        => sanitize_textarea_field( $data['message'] ?? '' ),
            'status'         => 'new',
        ];

        $result = $wpdb->insert( $this->table, $row );

        if ( $result === false ) {
            error_log( 'JJPWS QuoteRequestModel insert failed: ' . $wpdb->last_error . ' | table: ' . $this->table );
            $this->ensure_table_exists();
            // One retry after attempting to create the table
            $result = $wpdb->insert( $this->table, $row );
            if ( $result === false ) {
                error_log( 'JJPWS QuoteRequestModel retry insert failed: ' . $wpdb->last_error );
                return false;
            }
        }

        return $wpdb->insert_id;
    }

    /**
     * Self-heal: if the quote table is missing on this install (e.g., the
     * activation hook never fired because the user upgraded via WP auto-updater
     * before the migration shim shipped), create it now.
     */
    private function ensure_table_exists(): void {
        global $wpdb;

        $exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $this->table ) );
        if ( $exists === $this->table ) {
            return;
        }

        if ( class_exists( '\\JJPWS\\Core\\Activator' ) ) {
            \JJPWS\Core\Activator::migrate();
        }
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
