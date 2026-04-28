<?php
/**
 * Plugin Name: JJ Pet Waste Booking System
 * Plugin URI:  https://jjpetwasteservices.com
 * Description: Smart booking & Stripe subscription system for JJ Pet Waste Services.
 * Version:     1.0.0
 * Author:      William
 * Text Domain: jjpws-booking
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'JJPWS_VERSION', '1.0.0' );
define( 'JJPWS_PLUGIN_FILE', __FILE__ );
define( 'JJPWS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'JJPWS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once JJPWS_PLUGIN_DIR . 'vendor/autoload.php';

use JJPWS\Core\Activator;
use JJPWS\Core\Deactivator;
use JJPWS\Core\Plugin;

register_activation_hook( JJPWS_PLUGIN_FILE, [ Activator::class, 'activate' ] );
register_deactivation_hook( JJPWS_PLUGIN_FILE, [ Deactivator::class, 'deactivate' ] );

function jjpws_run(): void {
    $plugin = new Plugin();
    $plugin->run();
}

jjpws_run();
