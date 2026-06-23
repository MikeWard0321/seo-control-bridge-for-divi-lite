<?php
/**
 * Plugin Name: SEO Control Bridge - Lite
 * Plugin URI: https://eecons.com/seo-control-bridge-lite/
 * Description: Bridge Divi Visual Builder workflows with Rank Math SEO controls for metadata, social fields, and canonical URLs.
 * Version: 1.1.10
 * Requires at least: 6.5
 * Requires PHP: 8.0
 * Requires Plugins: seo-by-rank-math
 * Tested up to: 7.0
 * Author: Electronic Enterprises, Inc.
 * Author URI: https://eecons.com
 * Text Domain: seo-control-bridge-lite
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

defined('ABSPATH') || exit;

define('SEO_CONTROL_BRIDGE_LITE_VERSION', '1.1.10');
define('SEO_CONTROL_BRIDGE_LITE_FILE', __FILE__);
define('SEO_CONTROL_BRIDGE_LITE_DIR', plugin_dir_path(__FILE__));
define('SEO_CONTROL_BRIDGE_LITE_URL', plugin_dir_url(__FILE__));

require_once SEO_CONTROL_BRIDGE_LITE_DIR . 'includes/class-seo-control-bridge-lite.php';
add_action('plugins_loaded', static function () {
    \SEO_Control_Bridge_Lite\Plugin::instance();
});
