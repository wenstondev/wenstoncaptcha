<?php
/**
 * Plugin Name:       wenstonCaptcha
 * Plugin URI:        https://wordpress.org/plugins/wenstoncaptcha/
 * Description:       Self-hosted image CAPTCHA for pages. No third-party APIs.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            wenstondev
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wenstoncaptcha
 *
 * @package WenstonCaptcha
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

define('WENSTONCAPTCHA_VERSION', '1.0.0');
define('WENSTONCAPTCHA_PATH', plugin_dir_path(__FILE__));
define('WENSTONCAPTCHA_URL', plugin_dir_url(__FILE__));

require_once WENSTONCAPTCHA_PATH . 'includes/class-wenstoncaptcha-service.php';
require_once WENSTONCAPTCHA_PATH . 'includes/class-wenstoncaptcha-plugin.php';

/**
 * Public API for themes and other plugins.
 */
function wenstoncaptcha_service(): WenstonCaptcha_Service
{
    return WenstonCaptcha_Plugin::instance()->service();
}

add_action('plugins_loaded', static function (): void {
    WenstonCaptcha_Plugin::instance()->init();
});
