<?php

declare(strict_types=1);

namespace Itineris\WorldPay;

class MinimumRequirements
{
    public const GRAVITY_FORMS_VERSION = '2.3.3.2';

    public static function toArray(): array
    {
        return [
            'wordpress' => [
                'version' => '4.9.8',
            ],
            'php' => [
                'version' => '7.2',
                'extensions' => [
                    'curl',
                ],
            ],
        ];
    }
}
