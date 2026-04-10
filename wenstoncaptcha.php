<?php
/**
 * Plugin Name:       wenstonCaptcha
 * Plugin URI:        https://wordpress.org/plugins/wenstoncaptcha/
 * Description:       Self-hosted image CAPTCHA for pages. No third-party APIs.
 * Version:           1.0.1
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            wenstondev
 * Author URI:        https://wenston.io/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wenstoncaptcha
 *
 * LICENSE
 * This file is part of wenstonCaptcha.
 *
 * wenstonCaptcha is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @package    WenstonCaptcha
 * @author     wenstondev
 * @copyright  Copyright 2026 wenstondev
 * @license    https://www.gnu.org/licenses/gpl-2.0.html GPL 2.0
 * @link       https://github.com/wenstondev/wenstoncaptcha

 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

define('WENSTONCAPTCHA_VERSION', '1.0.1');
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
