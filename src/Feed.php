<?php

declare(strict_types=1);

namespace Itineris\WorldPay;

use GFPaymentAddOn;

class Feed
{
    /**
     * Gravity Forms feed object array
     *
     * @var array
     */
    private $data;

    /**
     * Feed constructor.
     *
     * @param array $data Gravity Forms feed object array.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function findByEntry(Entry $entry, GFPaymentAddOn $addOn): ?self
    {
        $rawFeed = $addOn->get_payment_feed(
            $entry->toArray()
        );

        if (empty($rawFeed)) {
            return null;
        }

        return new Feed($rawFeed);
    }

    public function getFormId(): int
    {
        return (int) rgar($this->data, 'form_id');
    }

    public function getId(): int
    {
        return (int) $this->data['id'];
    }

    public function isActive(): bool
    {
        return (bool) rgar($this->data, 'is_active');
    }

    public function isTest(): bool
    {
        return 'production' !== (string) $this->getMeta('isTest');
    }

    /**
     * Get a specific property of an array without needing to check if that property exists.
     * Provide a default value if you want to return a specific value if the property is not set.
     *
     * @param string $prop    Name of the property to be retrieved.
     * @param string $default Optional. Value that should be returned if the property is not set or empty. Defaults to
     *                        null.
     *
     * @return null|string|mixed The value
     */
    public function getMeta(string $prop, $default = null)
    {
        return rgars($this->data, 'meta/' . $prop, $default);
    }

    public function getCancelUrl(): string
    {
        return esc_url_raw(
            (string) $this->getMeta('cancelUrl')
        );
    }

    public function getInstallationId(): string
    {
        return $this->getSelectCustom('installationId');
    }

    public function getSelectCustom(string $key): string
    {
        $value = (string) $this->getMeta($key);

        if ('gf_custom' === $value) {
            return (string) $this->getMeta($key . '_custom');
        }

        return $value;
    }

    public function getMerchantCode(): string
    {
        return $this->getSelectCustom('merchantCode');
    }

    public function getMd5Secret(): string
    {
        return $this->getSelectCustom('md5Secret');
    }

    public function getPaymentResponsePassword(): string
    {
        return $this->getSelectCustom('paymentResponsePassword');
    }

    public function getCartId(): string
    {
        return $this->getSelectCustom('cartId');
    }

    public function getDescription(): string
    {
        return $this->getMeta('description');
    }
}
