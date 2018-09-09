<?php

declare(strict_types=1);

namespace Itineris\WorldPay;

use GFAPI;
use GFCommon;
use GFFormDisplay;
use GFFormsModel;

/**
 * Taken from `GFPayPal::maybe_thankyou_page`.
 */
class ConfirmationHandler
{
    private const HASH_HMAC_ALGO = 'sha512';

    public static function init(): void
    {
        add_action('wp', [self::class, 'maybeContinue'], 5);
    }

    public static function maybeContinue(): void
    {
        $entryId = (int) rgget('entry');
        $token = rgget('gf-worldpay-token');

        if (empty($entryId) || empty($token)) {
            return;
        }

        $rawEntry = GFAPI::get_entry($entryId);
        if (is_wp_error($rawEntry)) {
            return;
        }
        $entry = new Entry($rawEntry);

        if (time() > $entry->getConfirmationTokenExpiredAt()) {
            return;
        }

        $correctHash = $entry->getConfirmationTokenHash();
        $hash = self::hash($token);
        if (! hash_equals($correctHash, $hash)) {
            return;
        }

        // Token validation passed. Make it invalid after first use.
        $entry->expireConfirmationTokenNow();

        if (! $entry->isPaidOrPending()) {
            self::handleFailedPayment($entry);
        }

        self::handle($entry);
    }

    private static function hash(string $confirmationToken): string
    {
        return hash_hmac(
            self::HASH_HMAC_ALGO,
            $confirmationToken,
            wp_salt('auth')
        );
    }

    private static function handleFailedPayment(Entry $entry): void
    {
        $feed = Feed::findByEntry(
            $entry,
            AddOn::get_instance()
        );
        if (empty($feed)) {
            wp_die('Unable to locate feed');
        }

        $cancelUrl = $feed->getCancelUrl();
        if (empty($cancelUrl)) {
            // Continue normal Gravity Forms confirmation.
            return;
        }

        // phpcs:ignore
        wp_redirect($cancelUrl);
        exit;
    }

    private static function handle(Entry $entry): void
    {
        if (! class_exists('GFFormDisplay')) {
            // phpcs:ignore
            require_once GFCommon::get_base_path() . '/form_display.php';
        }

        $form = GFAPI::get_form(
            $entry->getFormId()
        );

        $confirmation = GFFormDisplay::handle_confirmation($form, $entry->toArray(), false);

        if (is_array($confirmation) && isset($confirmation['redirect'])) {
            // phpcs:ignore
            wp_redirect($confirmation['redirect']);
            exit;
        }

        GFFormDisplay::$submission[$entry->getFormId()] = [
            'is_confirmation' => true,
            'confirmation_message' => $confirmation,
            'form' => $form,
            'lead' => $entry->toArray(),
        ];
    }

    public static function buildUrlFor(Entry $entry, int $ttlInSeconds = 3600): string
    {
        $confirmationToken = GFFormsModel::get_uuid('-');

        $entry->setConfirmationTokenHash(
            self::hash($confirmationToken),
            time() + $ttlInSeconds
        );

        return esc_url_raw(
            add_query_arg(
                [
                    'entry' => $entry->getId(),
                    'gf-worldpay-token' => $confirmationToken,
                ],
                $entry->getProperty('source_url')
            )
        );
    }
}
