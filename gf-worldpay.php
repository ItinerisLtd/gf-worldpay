<?php
/**
 * Plugin Name:     GF WorldPay
 * Plugin URI:      https://www.itineris.co.uk/
 * Description:     WorldPay payment gateway for Gravity Forms
 * Version:         0.1.0
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
