<?php

declare(strict_types=1);

namespace Itineris\WorldPay;

use GFAddOn;
use GFForms;
use Itineris\WorldPay\Preflight\ProductionMode;

class GFWorldPay
{
    public const VERSION = '0.2.3';

    public function run(): void
    {
        // TODO: Check `\GFForms` is loaded.
        GFForms::include_payment_addon_framework();
        GFAddOn::register(AddOn::class);

        ConfirmationHandler::init();

        add_action('preflight_checker_collection_register', [ProductionMode::class, 'register']);
    }
}
