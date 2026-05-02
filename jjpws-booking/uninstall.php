<?php
/**
 * Runs when the plugin is deleted from WP Admin.
 * Removes all plugin data: tables, options, user meta.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// Drop custom tables
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}jjpws_subscriptions" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}jjpws_quote_requests" );

// Remove options
$options = [
    'jjpws_pricing_matrix',
    'jjpws_lot_size_thresholds',
    'jjpws_surcharges',
    'jjpws_distance_config',
    'jjpws_one_time_price_cents',
    'jjpws_stripe_mode',
    'jjpws_api_keys',
    'jjpws_stripe_webhook_secret',
    'jjpws_business_address',
    'jjpws_business_email',
    'jjpws_business_phone',
    'jjpws_business_origin_coords',
    'jjpws_primary_color',
    'jjpws_parcel_endpoint',
    'jjpws_parcel_acreage_field',
    'jjpws_parcel_attribution',
];

foreach ( $options as $option ) {
    delete_option( $option );
}

// Remove user meta
$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key IN ('jjpws_stripe_customer_id', 'jjpws_phone')" );
