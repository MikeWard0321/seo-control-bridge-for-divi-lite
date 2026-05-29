<?php
/**
 * Plugin Name: SEO Control Bridge for Divi Lite
 * Plugin URI: https://eecons.com/product/seo-control-bridge-for-divi/
 * Description: Free SEO workflow bridge for Divi and Rank Math. Adds lightweight SEO fields, social metadata helpers, and a Divi-friendly SEO launcher.
 * Version: 1.0.10
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * Tested up to: 7.0
 * Author: Electronic Enterprises, Inc.
 * Author URI: https://eecons.com
 * Text Domain: seo-control-bridge-for-divi-lite
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

defined('ABSPATH') || exit;

define('SCBD_LITE_VERSION', '1.0.10');
define('SCBD_LITE_FILE', __FILE__);
define('SCBD_LITE_DIR', plugin_dir_path(__FILE__));
define('SCBD_LITE_URL', plugin_dir_url(__FILE__));

require_once SCBD_LITE_DIR . 'includes/class-scbd-lite.php';
require_once SCBD_LITE_DIR . 'includes/class-scbd-lite-github-updater.php';

add_action('plugins_loaded', static function () {
    \SCBD_Lite\Plugin::instance();
    (new \SCBD_Lite\GitHub_Updater(SCBD_LITE_FILE))->hooks();
});
