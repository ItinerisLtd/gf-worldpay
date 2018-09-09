<?php

declare(strict_types=1);

namespace Itineris\WorldPay;

use GFPaymentAddOn;
use Omnipay\WorldPay\Message\CompletePurchaseRequest;
use Omnipay\WorldPay\Message\CompletePurchaseResponse;

class CallbackHandler
{
    public static function run(GFPaymentAddOn $addOn): void
    {
        $entry = self::getEntryBySuperglobals($addOn);
        $feed = Feed::findByEntry($entry, $addOn);
        if (empty($feed)) {
            self::wpDieBecauseOfMissingSuperglobal('feed object', $addOn);
        }

        $gateway = GatewayFactory::buildFromFeed($feed);

        $addOn->log_debug(__METHOD__ . '(): Before completing purchase');

        /* @var CompletePurchaseRequest $request The request instance. */
        $request = $gateway->completePurchase();
        self::logDebug($request, $addOn);

        // Get the response message ready for returning.
        /* @var CompletePurchaseResponse $response */
        $response = $request->send();
        self::logDebug($response, $addOn);

        // Save the final transactionReference against the transaction in the database. It will
        // be needed if you want to capture the payment (for an authorize) or void or refund or
        // repeat the payment later.
        $entry->setMeta(
            'final_transaction_reference',
            $response->getTransactionReference()
        );

        if ($response->isSuccessful()) {
            $entry->markAsPaid($addOn, $response->getMessage());
        } else {
            $entry->markAsFailed($addOn, $response->getMessage());
        }

        $addOn->log_debug(__METHOD__ . '(): ' . self::getNextUrl($entry));
        $addOn->log_debug(__METHOD__ . '(): Confirm!');

        wp_safe_redirect(self::getNextUrl($entry));
        exit;
    }

    private static function getEntryBySuperglobals(GFPaymentAddOn $addOn): Entry
    {
        $transactionId = (string) rgget('transactionId');

        $addOn->log_debug(__METHOD__ . '(): transactionId - ' . $transactionId);
        if (empty($transactionId)) {
            self::wpDieBecauseOfMissingSuperglobal('transaction id', $addOn);
        }

        $entry = Entry::findByOurTransactionId($transactionId, $addOn);
        if (empty($entry)) {
            self::wpDieBecauseOfMissingSuperglobal('entry object', $addOn);
        }

        return $entry;
    }

    private static function wpDieBecauseOfMissingSuperglobal(string $noun, GFPaymentAddOn $addOn): void
    {
        $message = 'Unable to get/calculate ' . $noun . ' from superglobals';
        $addOn->log_error(__METHOD__ . '(): ' . $message);
        wp_die(esc_html($message), 'Bad Request', 400);
    }

    /**
     * Log WorldPay api object via Gravity Forms logger.
     *
     * @param CompletePurchaseRequest|CompletePurchaseResponse $request WorldPay api object.
     * @param GFPaymentAddOn                                   $addOn   Add-on instance.
     */
    private static function logDebug($request, GFPaymentAddOn $addOn): void
    {
        $addOn->log_debug(__METHOD__ . '(): Final Transaction Reference - ' . $request->getTransactionReference());
        $addOn->log_debug(__METHOD__ . '(): Message - ' . $request->getMessage());
        $addOn->log_debug(__METHOD__ . '(): Data - ' . wp_json_encode($request->getData()));
    }

    private static function getNextUrl(Entry $entry): string
    {
        return ConfirmationHandler::buildUrlFor($entry);
    }
}
