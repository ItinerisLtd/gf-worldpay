<?php

declare(strict_types=1);

namespace Itineris\WorldPay;

use GFFeedAddOn;

class FeedSettingsFields
{
    public static function toArray(GFFeedAddOn $addOn): array
    {
        return [
            [
                'fields' => [
                    [
                        'name' => 'feedName',
                        'label' => esc_html__('Name', 'gf-worldpay'),
                        'type' => 'text',
                        'class' => 'medium',
                        'required' => true,
                        'tooltip' => '<h6>' .
                            esc_html__('Name', 'gf-worldpay') .
                            '</h6>' .
                            esc_html__('Enter a feed name to uniquely identify this setup.', 'gf-worldpay'),
                    ],
                ],
            ],
            [
                'title' => esc_html__('WorldPay Settings', 'gf-worldpay'),
                'fields' => self::worldPaySettingsFields($addOn),
            ],
            [
                'title' => esc_html__('Products &amp; Services Settings', 'gf-worldpay'),
                'fields' => [
                    [
                        'name' => 'paymentAmount',
                        'label' => esc_html__('Payment Amount', 'gf-worldpay'),
                        'type' => 'select',
                        'choices' => $addOn->product_amount_choices(),
                        'required' => true,
                        'default_value' => 'form_total',
                        'tooltip' => '<h6>' .
                            esc_html__('Payment Amount', 'gf-worldpay') .
                            '</h6>' .
                            esc_html__(
                                "Select which field determines the payment amount, or select 'Form Total' to use the total of all pricing fields as the payment amount.",
                                'gf-worldpay'
                            ),
                    ],
                ],
            ],
            [
                'title' => esc_html__('Order Settings', 'gf-worldpay'),
                'fields' => self::orderSettingsFields(),
            ],
            [
                'title' => esc_html__('Other Settings', 'gf-worldpay'),
                'fields' => [
                    [
                        'name' => 'conditionalLogic',
                        'label' => esc_html__('Conditional Logic', 'gf-worldpay'),
                        'type' => 'feed_condition',
                        'tooltip' => '<h6>' .
                            esc_html__('Conditional Logic', 'gf-worldpay') .
                            '</h6>' .
                            esc_html__(
                                'When conditions are enabled, form submissions will only be sent to the payment gateway when the conditions are met. When disabled, all form submissions will be sent to the payment gateway.',
                                'gf-worldpay'
                            ),
                    ],
                ],
            ],
        ];
    }

    private static function worldPaySettingsFields(GFFeedAddOn $addOn): array
    {
        return [
            [
                'type' => 'select_custom',
                'name' => 'installationId',
                'label' => esc_html__('Installation ID', 'gf-worldpay'),
                'required' => true,
                'choices' => self::getAllChoices('installationId', $addOn),
                'tooltip' => esc_html__('The ID for this installation. For example, 204596.', 'gf-worldpay'),
            ],
            [
                'type' => 'select_custom',
                'name' => 'merchantCode',
                'label' => esc_html__('Merchant Code', 'gf-worldpay'),
                'required' => true,
                'choices' => self::getAllChoices('merchantCode', $addOn),
                'tooltip' => esc_html__(
                    'This specifies which merchant code should receive funds for this payment.',
                    'gf-worldpay'
                ),
            ],
            [
                'type' => 'select_custom',
                'name' => 'cartId',
                'label' => esc_html__('Cart ID', 'gf-worldpay'),
                'required' => true,
                'choices' => self::getAllChoices('cartId', $addOn),
                'after_input' => esc_html__(
                    'Letters (A-Z and a-z) and Numbers(0-9); Maximum 255 characters',
                    'gf-worldpay'
                ),
                'tooltip' => esc_html__(
                    'Your own reference number for this purchase. It is returned to you along with the authorisation results by whatever method you have chosen for being informed (email and / or Payment Responses).',
                    'gf-worldpay'
                ),
            ],
            [
                'type' => 'select_custom',
                'name' => 'md5Secret',
                'label' => esc_html__('MD5 Secret', 'gf-worldpay'),
                'required' => true,
                'choices' => self::getAllChoices('md5Secret', $addOn),
                'tooltip' => esc_html__(
                    'MD5 secret for transactions field in the Integration Setup for your installation using the Merchant Interface > Installations option',
                    'gf-worldpay'
                ),
            ],

            [
                'type' => 'select_custom',
                'name' => 'paymentResponsePassword',
                'label' => esc_html__('Payment Response Password', 'gf-worldpay'),
                'required' => true,
                'choices' => self::getAllChoices('installationId', $addOn),
                'tooltip' => esc_html__(
                    'This password is used to validate a Payment Notifications message.',
                    'gf-worldpay'
                ),
            ],
            [
                'type' => 'text',
                'name' => 'description',
                'label' => esc_html__('Description', 'gf-worldpay'),
                'required' => true,
                'tooltip' => esc_html__('A brief description of the goods or services purchased.', 'gf-worldpay'),
            ],
            [
                'name' => 'isTest',
                'label' => esc_html__('Environment', 'gf-worldpay'),
                'required' => true,
                'type' => 'radio',
                'default_value' => 'test',
                'choices' => [
                    [
                        'label' => esc_html__('Production', 'gf-worldpay'),
                        'value' => 'production',
                    ],
                    [
                        'label' => esc_html__('Test', 'gf-worldpay'),
                        'value' => 'test',
                    ],
                ],
            ],
            [
                'type' => 'text',
                'name' => 'cancelUrl',
                'label' => esc_html__('Cancel URL', 'gf-worldpay'),
                'class' => 'large',
                'after_input' => esc_html__('Leave blank to use Gravity Forms confirmations', 'gf-worldpay'),
                'tooltip' => esc_html__(
                    'Enter the URL the user should be sent to if they cancelled the WorldPay checkout form or payment failed.',
                    'gf-worldpay'
                ),
            ],
        ];
    }

    private static function getAllChoices(string $key, GFFeedAddOn $addOn): array
    {
        $rawFeeds = $addOn->get_feeds();

        $feeds = array_map(function (array $rawFeed): Feed {
            return new Feed($rawFeed);
        }, $rawFeeds);

        $allChoices = array_map(function (Feed $feed) use ($key): string {
            return $feed->getSelectCustom($key);
        }, $feeds);

        $uniqueChoices = array_unique(
            array_filter($allChoices)
        );

        $choices = array_map(function (string $choice): array {
            return ['label' => $choice];
        }, $uniqueChoices);

        return array_merge([
            [
                'value' => '',
                'label' => 'Select a choice',
            ],
        ], $choices);
    }

    private static function orderSettingsFields(): array
    {
        return [
            [
                'name' => 'customerInformation',
                'label' => esc_html__('Customer Information', 'gf-worldpay'),
                'type' => 'field_map',
                'field_map' => self::customerInfoFields(),
                'tooltip' => '<h6>' .
                    esc_html__('Customer Information', 'gf-worldpay') .
                    '</h6>' .
                    esc_html__('Map your Form Fields to the available listed fields.', 'gf-worldpay'),
            ],
            [
                'name' => 'billingInformation',
                'label' => esc_html__('Billing Information', 'gf-worldpay'),
                'type' => 'field_map',
                'field_map' => self::addressFields(),
                'tooltip' => '<h6>' .
                    esc_html__('Billing Information', 'gf-worldpay') .
                    '</h6>' .
                    esc_html__('Map your Form Fields to the available listed fields.', 'gf-worldpay'),
            ],
        ];
    }

    private static function customerInfoFields(): array
    {
        return [
            [
                'name' => 'firstName',
                'label' => esc_html__('First Name', 'gf-worldpay'),
                'required' => true,
            ],
            [
                'name' => 'lastName',
                'label' => esc_html__('Last Name', 'gf-worldpay'),
                'required' => true,
            ],
            [
                'name' => 'email',
                'label' => esc_html__('Email', 'gf-worldpay'),
                'required' => false,
            ],
            [
                'name' => 'phone',
                'label' => esc_html__('Phone', 'gf-worldpay'),
                'required' => false,
            ],
        ];
    }

    private static function addressFields(): array
    {
        return [
            [
                'name' => 'address',
                'label' => esc_html__('Address', 'gf-worldpay'),
                'required' => true,
            ],
            [
                'name' => 'address2',
                'label' => esc_html__('Address 2', 'gf-worldpay'),
                'required' => false,
            ],
            [
                'name' => 'city',
                'label' => esc_html__('City', 'gf-worldpay'),
                'required' => true,
            ],
            [
                'name' => 'zip',
                'label' => esc_html__('Zip', 'gf-worldpay'),
                'required' => true,
            ],
            [
                'name' => 'country',
                'label' => esc_html__('Country', 'gf-worldpay'),
                'required' => true,
            ],
            [
                'name' => 'state',
                'label' => esc_html__('State', 'gf-worldpay'),
                'required' => false,
            ],
        ];
    }
}
