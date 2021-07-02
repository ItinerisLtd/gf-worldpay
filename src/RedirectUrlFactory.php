<?php

declare(strict_types=1);

namespace Itineris\WorldPay;

use GFFormsModel;
use GFPaymentAddOn;
use Omnipay\WorldPay\Message\PurchaseRequest;
use Omnipay\WorldPay\Message\PurchaseResponse;

class RedirectUrlFactory
{
    public static function build(GFPaymentAddOn $addOn, Feed $feed, Entry $entry, float $amount): string
    {
        $entry->markAsProcessing(
            GFFormsModel::get_uuid('-'),
            $amount
        );

        // Temporarily reset $_FILES to prevent conflicts with symfony/http-foundation.
        $originalFiles = $_FILES; // phpcs:ignore
        $_FILES = [];
        
        $gateway = GatewayFactory::buildFromFeed($feed);
        
        // Restore original $_FILES so that GravityForms saves it.
        $_FILES = $originalFiles;

        /* @var PurchaseRequest $request The request instance. */
        $request = $gateway->purchase([
            'amount' => $entry->getProperty('payment_amount'),
            'currency' => $entry->getProperty('currency'),
            'card' => CreditCardFactory::build($feed, $entry),
            'notifyUrl' => self::getNotifyUrl($addOn, $entry),
            'transactionId' => $feed->getCartId(),
            'description' => $feed->getDescription(),
        ]);

        /* @var PurchaseResponse $response The response instance. */
        $response = $request->send();
        $addOn->log_debug(__METHOD__ . '():  PurchaseResponse - ' . $response->getMessage());

        if (! $response->isRedirect()) {
            self::handleFailure($response, $entry, $addOn);

            return '';
        }

        $addOn->log_debug(__METHOD__ . '(): Forward user onto WorldPay checkout form.');

        return $response->getRedirectUrl();
    }

    private static function getNotifyUrl(GFPaymentAddOn $addOn, Entry $entry): string
    {
        return esc_url_raw(
            add_query_arg(
                [
                    'callback' => $addOn->get_slug(),
                    'transactionId' => $entry->getTransactionId(),
                ],
                home_url('/')
            )
        );
    }

    private static function handleFailure(PurchaseResponse $response, Entry $entry, GFPaymentAddOn $addOn): void
    {
        $entry->markAsFailed(
            $addOn,
            __METHOD__ . '(): Unable to retrieve WorldPay redirect url - ' . $response->getMessage()
        );

        $shouldWpDie = (bool) apply_filters('gf_worldpay_redirect_url_failure_wp_die', true, $response, $entry, $addOn);

        if (! $shouldWpDie) {
            return;
        }

        wp_die(
            esc_html__(
                'Error: Failed to retrieve WorldPay checkout form URL. Please contact site administrators.',
                'gf-worldpay'
            )
        );
    }
}
