<?php
/**
 * Plugin Name:     GF WorldPay
 * Plugin URI:      https://www.itineris.co.uk/
 * Description:     WorldPay payment gateway for Gravity Forms
 * Version:         0.2.4
 * Author:          Itineris Limited
 * Author URI:      https://www.itineris.co.uk/
 * Text Domain:     gf-WorldPay
 */

declare(strict_types=1);

namespace Itineris\WorldPay;

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

/**
 * Begins execution of the plugin.
 *
 * @return void
 */
function run(): void
{
    $plugin = new GFWorldPay();
    $plugin->run();
}

run();
