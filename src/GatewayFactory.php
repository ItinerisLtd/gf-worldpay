<?php

declare(strict_types=1);

namespace Itineris\WorldPay;

use Omnipay\Omnipay;
use Omnipay\WorldPay\Gateway;

class GatewayFactory
{
    public static function buildFromFeed(Feed $feed): Gateway
    {
        /* @var Gateway $gateway The gateway instance. */
        $gateway = Omnipay::create('WorldPay');

        $gateway->setInstallationId($feed->getInstallationId());
        $gateway->setAccountId($feed->getMerchantCode());
        $gateway->setSecretWord($feed->getMd5Secret());
        $gateway->setCallbackPassword($feed->getPaymentResponsePassword());
        $gateway->setTestMode($feed->isTest());
        $gateway->setFixContact(true);

        return $gateway;
    }
}
