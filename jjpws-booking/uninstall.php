<?php
/**
 * Runs when the plugin is deleted from WP Admin.
 * Removes all plugin data: tables, options, user meta.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// Drop custom table
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}jjpws_subscriptions" );

// Remove options
$options = [
    'jjpws_pricing_matrix',
    'jjpws_lot_size_thresholds',
    'jjpws_stripe_mode',
    'jjpws_api_keys',
    'jjpws_stripe_webhook_secret',
];

foreach ( $options as $option ) {
    delete_option( $option );
}

// Remove user meta
$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key IN ('jjpws_stripe_customer_id', 'jjpws_phone')" );
