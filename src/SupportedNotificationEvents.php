<?php

declare(strict_types=1);

namespace Itineris\WorldPay;

class SupportedNotificationEvents
{
    public static function toArray(): array
    {
        return [
            'complete_payment' => esc_html__('Payment Completed', 'gf-worldpay'),
            'add_pending_payment' => esc_html__('Payment Pending', 'gf-worldpay'),
            'fail_payment' => esc_html__('Payment Failed', 'gf-worldpay'),
        ];
    }
}
