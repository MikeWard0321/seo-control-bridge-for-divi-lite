<?php
/**
 * Plugin Name: SEO Control Bridge - Lite
 * Plugin URI: https://eecons.com/seo-control-bridge-lite/
 * Description: Bridge Divi Visual Builder workflows with Rank Math SEO controls for metadata, social fields, and canonical URLs.
 * Version: 1.1.0
 * Requires at least: 6.5
 * Requires PHP: 8.0
 * Requires Plugins: seo-by-rank-math
 * Tested up to: 7.0
 * Author: Electronic Enterprises, Inc.
 * Author URI: https://eecons.com
 * Text Domain: seo-control-bridge-lite
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

defined('ABSPATH') || exit;

define('SCBD_LITE_VERSION', '1.1.0');
define('SCBD_LITE_FILE', __FILE__);
define('SCBD_LITE_DIR', plugin_dir_path(__FILE__));
define('SCBD_LITE_URL', plugin_dir_url(__FILE__));

require_once SCBD_LITE_DIR . 'includes/class-scbd-lite.php';
add_action('plugins_loaded', static function () {
    \SCBD_Lite\Plugin::instance();
});
