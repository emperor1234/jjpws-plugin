<?php
/**
 * Plugin Name: JJ Pet Waste Booking System
 * Plugin URI:  https://jjpetwasteservices.com
 * Description: Smart booking & Stripe subscription system for JJ Pet Waste Services.
 * Version:     1.2.9
 * Author:      William
 * Text Domain: jjpws-booking
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'JJPWS_VERSION', '1.2.9' );
define( 'JJPWS_PLUGIN_FILE', __FILE__ );
define( 'JJPWS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'JJPWS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

if ( ! file_exists( JJPWS_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
    add_action( 'admin_notices', function () {
        echo '<div class="notice notice-error"><p><strong>JJ Pet Waste Booking:</strong> '
            . 'Composer dependencies are missing. Please run <code>composer install</code> inside the plugin folder, '
            . 'or re-upload the plugin using the full zip from the GitHub release.</p></div>';
    } );
    return;
}

require_once JJPWS_PLUGIN_DIR . 'vendor/autoload.php';

use JJPWS\Core\Activator;
use JJPWS\Core\Deactivator;
use JJPWS\Core\Plugin;
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

// Auto-updater: checks GitHub Releases for new versions
PucFactory::buildUpdateChecker(
    'https://github.com/emperor1234/jjpws-plugin/',
    JJPWS_PLUGIN_FILE,
    'jjpws-booking'
)->getVcsApi()->enableReleaseAssets();

register_activation_hook( JJPWS_PLUGIN_FILE, [ Activator::class, 'activate' ] );
register_deactivation_hook( JJPWS_PLUGIN_FILE, [ Deactivator::class, 'deactivate' ] );

/**
 * Run schema migrations on plugin update. WordPress's auto-updater does NOT
 * fire the activation hook, so without this any new tables/columns/options
 * added in a release would never reach existing installs.
 */
add_action( 'plugins_loaded', function () {
    $installed = get_option( 'jjpws_db_version' );
    if ( $installed !== JJPWS_VERSION ) {
        Activator::migrate();
        update_option( 'jjpws_db_version', JJPWS_VERSION );
    }
}, 5 );

function jjpws_run(): void {
    $plugin = new Plugin();
    $plugin->run();
}

jjpws_run();
