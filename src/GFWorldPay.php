<?php

declare(strict_types=1);

namespace Itineris\WorldPay;

use GFAddOn;
use GFForms;

class GFWorldPay
{
    public const VERSION = '0.1.0';

    public function run(): void
    {
        // TODO: Check `\GFForms` is loaded.
        GFForms::include_payment_addon_framework();
        GFAddOn::register(AddOn::class);

        ConfirmationHandler::init();
    }
}
