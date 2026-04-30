<?php

namespace JJPWS\Core;

use JJPWS\Services\PricingEngine;

class Activator {

    public static function activate(): void {
        self::create_tables();
        self::seed_defaults();
        flush_rewrite_rules();
    }

    private static function create_tables(): void {
        global $wpdb;

        $charset = $wpdb->get_charset_collate();
        $subs    = $wpdb->prefix . 'jjpws_subscriptions';
        $quotes  = $wpdb->prefix . 'jjpws_quote_requests';

        $sql_subs = "CREATE TABLE {$subs} (
            id                       BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id                  BIGINT(20) UNSIGNED NOT NULL,
            service_type             VARCHAR(20) NOT NULL DEFAULT 'recurring',
            stripe_customer_id       VARCHAR(255) DEFAULT NULL,
            stripe_sub_id            VARCHAR(255) DEFAULT NULL,
            stripe_payment_intent_id VARCHAR(255) DEFAULT NULL,
            stripe_price_id          VARCHAR(255) DEFAULT NULL,
            street_address           VARCHAR(255) NOT NULL,
            city                     VARCHAR(100) NOT NULL,
            state                    VARCHAR(50)  NOT NULL,
            zip_code                 VARCHAR(10)  NOT NULL,
            lat                      DECIMAL(10,7) DEFAULT NULL,
            lng                      DECIMAL(10,7) DEFAULT NULL,
            distance_miles           DECIMAL(5,2) DEFAULT NULL,
            lot_size_sqft            INT(11) UNSIGNED DEFAULT NULL,
            lot_size_acres           DECIMAL(5,3) DEFAULT NULL,
            acreage_tier             VARCHAR(10) NOT NULL DEFAULT 'small',
            dog_count                TINYINT(3) UNSIGNED NOT NULL DEFAULT 1,
            dog_tier                 VARCHAR(10) DEFAULT NULL,
            frequency                VARCHAR(20) DEFAULT NULL,
            time_since_cleaned       VARCHAR(20) DEFAULT NULL,
            annual_prepay            TINYINT(1) NOT NULL DEFAULT 0,
            base_price_cents         INT(11) UNSIGNED DEFAULT 0,
            acreage_premium_cents    INT(11) UNSIGNED DEFAULT 0,
            distance_fee_cents       INT(11) UNSIGNED DEFAULT 0,
            neglect_surcharge_cents  INT(11) UNSIGNED DEFAULT 0,
            annual_discount_cents    INT(11) UNSIGNED DEFAULT 0,
            recurring_monthly_cents  INT(11) UNSIGNED DEFAULT 0,
            total_price_cents        INT(11) UNSIGNED NOT NULL DEFAULT 0,
            status                   VARCHAR(20) NOT NULL DEFAULT 'active',
            stripe_status            VARCHAR(50) DEFAULT NULL,
            current_period_end       DATETIME DEFAULT NULL,
            cancelled_at             DATETIME DEFAULT NULL,
            created_at               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_id (user_id),
            KEY idx_status (status),
            KEY idx_stripe_sub_id (stripe_sub_id),
            KEY idx_stripe_pi (stripe_payment_intent_id)
        ) {$charset};";

        $sql_quotes = "CREATE TABLE {$quotes} (
            id                BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            customer_name     VARCHAR(150) DEFAULT NULL,
            customer_email    VARCHAR(150) NOT NULL,
            customer_phone    VARCHAR(40)  DEFAULT NULL,
            street_address    VARCHAR(255) DEFAULT NULL,
            city              VARCHAR(100) DEFAULT NULL,
            state             VARCHAR(50)  DEFAULT NULL,
            zip_code          VARCHAR(10)  DEFAULT NULL,
            lot_size_acres    DECIMAL(5,3) DEFAULT NULL,
            dog_count         TINYINT(3) UNSIGNED DEFAULT NULL,
            distance_miles    DECIMAL(5,2) DEFAULT NULL,
            reason            VARCHAR(50)  DEFAULT NULL,
            message           TEXT DEFAULT NULL,
            status            VARCHAR(20)  NOT NULL DEFAULT 'new',
            created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_status (status)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql_subs );
        dbDelta( $sql_quotes );
    }

    private static function seed_defaults(): void {
        if ( ! get_option( 'jjpws_pricing_matrix' ) ) {
            update_option( 'jjpws_pricing_matrix', wp_json_encode( PricingEngine::default_matrix() ) );
        }

        if ( ! get_option( 'jjpws_surcharges' ) ) {
            update_option( 'jjpws_surcharges', wp_json_encode( PricingEngine::default_surcharges() ) );
        }

        if ( ! get_option( 'jjpws_distance_config' ) ) {
            update_option( 'jjpws_distance_config', wp_json_encode( PricingEngine::default_distance_config() ) );
        }

        if ( get_option( 'jjpws_one_time_price_cents' ) === false ) {
            update_option( 'jjpws_one_time_price_cents', 7000 );
        }

        if ( ! get_option( 'jjpws_stripe_mode' ) ) {
            update_option( 'jjpws_stripe_mode', 'test' );
        }
    }
}
