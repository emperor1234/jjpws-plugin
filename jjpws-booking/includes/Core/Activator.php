<?php

namespace JJPWS\Core;

class Activator {

    public static function activate(): void {
        self::create_tables();
        self::seed_defaults();
        flush_rewrite_rules();
    }

    private static function create_tables(): void {
        global $wpdb;

        $charset = $wpdb->get_charset_collate();
        $table   = $wpdb->prefix . 'jjpws_subscriptions';

        $sql = "CREATE TABLE {$table} (
            id                  BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id             BIGINT(20) UNSIGNED NOT NULL,
            stripe_customer_id  VARCHAR(255) NOT NULL,
            stripe_sub_id       VARCHAR(255) NOT NULL,
            stripe_price_id     VARCHAR(255) NOT NULL,
            street_address      VARCHAR(255) NOT NULL,
            city                VARCHAR(100) NOT NULL,
            state               VARCHAR(50)  NOT NULL,
            zip_code            VARCHAR(10)  NOT NULL,
            lat                 DECIMAL(10,7) DEFAULT NULL,
            lng                 DECIMAL(10,7) DEFAULT NULL,
            lot_size_sqft       INT(11) UNSIGNED DEFAULT NULL,
            lot_size_category   VARCHAR(10) NOT NULL,
            dog_count           TINYINT(3) UNSIGNED NOT NULL,
            frequency           VARCHAR(20) NOT NULL,
            monthly_price_cents INT(11) UNSIGNED NOT NULL,
            status              VARCHAR(20) NOT NULL DEFAULT 'active',
            stripe_status       VARCHAR(50) DEFAULT NULL,
            current_period_end  DATETIME DEFAULT NULL,
            cancelled_at        DATETIME DEFAULT NULL,
            created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_id (user_id),
            KEY idx_status (status),
            KEY idx_stripe_sub_id (stripe_sub_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    private static function seed_defaults(): void {
        if ( ! get_option( 'jjpws_pricing_matrix' ) ) {
            $matrix = [
                'base' => [
                    'xs' => [ 'twice_weekly' => 6000,  'weekly' => 4000,  'biweekly' => 2500 ],
                    'sm' => [ 'twice_weekly' => 7500,  'weekly' => 5000,  'biweekly' => 3200 ],
                    'md' => [ 'twice_weekly' => 9000,  'weekly' => 6000,  'biweekly' => 3800 ],
                    'lg' => [ 'twice_weekly' => 11000, 'weekly' => 7500,  'biweekly' => 4800 ],
                    'xl' => [ 'twice_weekly' => 13000, 'weekly' => 9000,  'biweekly' => 5800 ],
                ],
                'dog_adder' => [
                    'twice_weekly' => 1500,
                    'weekly'       => 1000,
                    'biweekly'     => 700,
                ],
            ];
            update_option( 'jjpws_pricing_matrix', wp_json_encode( $matrix ) );
        }

        if ( ! get_option( 'jjpws_lot_size_thresholds' ) ) {
            $thresholds = [
                'xs' => [ 'min' => 0,     'max' => 2999  ],
                'sm' => [ 'min' => 3000,  'max' => 5999  ],
                'md' => [ 'min' => 6000,  'max' => 9999  ],
                'lg' => [ 'min' => 10000, 'max' => 17999 ],
                'xl' => [ 'min' => 18000, 'max' => PHP_INT_MAX ],
            ];
            update_option( 'jjpws_lot_size_thresholds', wp_json_encode( $thresholds ) );
        }

        if ( ! get_option( 'jjpws_stripe_mode' ) ) {
            update_option( 'jjpws_stripe_mode', 'test' );
        }
    }
}
