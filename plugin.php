<?php
/**
 * Plugin Name: TMS Place of Business Sync
 * Plugin URI: https://github.com/devgeniem/tms-plugin-place-of-business-sync
 * Description: Sync Place Of Business CPT't from Tampere.fi Drupal site.
 * Version: 1.0.0
 * Requires PHP: 8.1
 * Author: Geniem Oy
 * Author URI: https://geniem.com
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: tms-plugin-place-of-business-sync
 * Domain Path: /languages
 */

use Tms\Plugin\PlaceOfBusinessSync\Plugin;

// Check if Composer has been initialized in this directory.
// Otherwise we just use global composer autoloading.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Get the plugin version.
$plugin_data    = get_file_data( __FILE__, [ 'Version' => 'Version' ], 'plugin' );
$plugin_version = $plugin_data['Version'];

$plugin_path = __DIR__;

// Initialize the plugin.
Plugin::init( $plugin_version, $plugin_path );

if ( ! function_exists( 'TmsPlaceOfBusinessSync' ) ) {
    /**
     * Get the plugin instance.
     *
     * @return Plugin
     */
    function TmsPlaceOfBusinessSync() : Plugin {
        return Plugin::plugin();
    }
}
