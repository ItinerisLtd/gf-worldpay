# gf-worldpay

Gravity forms add-on for WorldPay.

<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->


- [Minimum Requirements](#minimum-requirements)
- [Installation](#installation)
  - [Via Composer (Recommended)](#via-composer-recommended)
- [Setup](#setup)
- [Security Concerns about WorldPay HTML API](#security-concerns-about-worldpay-html-api)
- [Not Issue](#not-issue)
- [Features](#features)
- [Not Supported / Not Implemented](#not-supported--not-implemented)
- [Best Practices](#best-practices)
  - [HTTPS Everywhere](#https-everywhere)
  - [Payment Status](#payment-status)
- [Test Sandbox](#test-sandbox)
- [FAQ](#faq)
  - [GF WorldPay is Missing on Form Settings](#gf-worldpay-is-missing-on-form-settings)
- [Public API](#public-api)
  - [Build URL for continuing confirmation](#build-url-for-continuing-confirmation)
  - [Redirect URL Retrieval Failure Handling](#redirect-url-retrieval-failure-handling)
- [Preflight](#preflight)
- [Coding](#coding)
  - [Required Reading List](#required-reading-list)
  - [Gravity Forms](#gravity-forms)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

## Minimum Requirements

- PHP v7.2
- php-curl
- WordPress v4.9.8
- Gravity Forms v2.3.3.2

## Installation

### Via Composer (Recommended)

```bash
# composer.json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "git@github.com:ItinerisLtd/gf-worldpay.git"
    }
  ]
}
```

```bash
$ composer require itinerisltd/gf-worldpay
```

## Setup

[Payment response(redirection)](http://support.worldpay.com/support/kb/bg/htmlredirect/htmlredirect.htm#rhtml/Telling_your_shopper_about.htm#_Payment_Response_messages) and [Enhancing security with MD5](http://support.worldpay.com/support/kb/bg/htmlredirect/htmlredirect.htm#rhtml/Enhancing_security_with_MD5.htm%3FTocPath%3D_____10) are mandatory.

In the Integration Setup for your installation using [the Merchant Interface > Installations option](http://support.worldpay.com/support/kb/bg/customisingadvanced/custa6011.html):

1. Enable **Payment Response enabled?**
1. Enter `<wpdisplay item=MC_callback>` as **Payment Response URL**
1. Enter a random passphrase as **Payment Response password**
1. Enter a random passphrase as **MD5 secret for transactions**
1. Enter `instId:amount:currency:cartId` as **SignatureFields**

## Security Concerns about WorldPay HTML API

- Leaking **MD5 secret for transactions**
  * Allow evil hackers to set up fake checkout pages, pretending to be the merchant
  * WorldPay would accept these checkouts and charges the credit cards
  * Money goes into the merchant's account
- Leaking **Payment Response password**
  * Allow evil hackers to pretending to be WorldPay
  * WordPress would accept evil hackers' payment callbacks and changes entries' payment statuses

## Not Issue

If **Payment Response password**(also known as`callbackPW`) is incorrect, `InvalidResponseException` is throw to *stop the world*.
Credit card holders see white screen of death in such case.

## Features

- [Enhancing security with MD5](http://support.worldpay.com/support/kb/bg/htmlredirect/htmlredirect.htm#rhtml/Enhancing_security_with_MD5.htm%3FTocPath%3D_____10)
- [Gravity Forms Logging](https://docs.gravityforms.com/logging-and-debugging/)
- [Gravity Forms Notification Events](https://docs.gravityforms.com/gravity-forms-notification-events/)
- [Gravity Forms Confirmation](https://docs.gravityforms.com/configuring-confirmations-in-gravity-forms/)
- [Gravity Forms Conditional Logic](https://docs.gravityforms.com/enable-conditional-logic/)

## Not Supported / Not Implemented

- Shipping address
- Reject according to fraud check results
- Token payment
- Recurring payment
- Refund
- Void

## Best Practices

### HTTPS Everywhere

Although WorldPay accepts insecure HTTP sites, you should **always use HTTPS** to protect all communication.

### Payment Status

Always double check payment status on WorldPay Merchant Interface.

## Test Sandbox

Use this [test credit card](http://support.worldpay.com/support/kb/bg/pdf/181450-test-transaction-f.pdf).

## FAQ

### GF WorldPay is Missing on Form Settings

Gravity Forms capabilities behave differently on multi-user sites and its documents are incomplete.
If GF WorldPay is missing on form settings, grant yourself `gf_worldpay` and `gf_worldpay_uninstall` capabilities.
See: [https://docs.gravityforms.com/role-management-guide/](https://docs.gravityforms.com/role-management-guide/)   

## Public API

### Build URL for continuing confirmation

`ConfirmationHandler::buildUrlFor(Entry $entry, int $ttlInSeconds = 3600): string`

Usage:
```php
<?php
$entryId = 123;
$rawEntry = GFAPI::get_entry($entryId);
if (is_wp_error($rawEntry)) {
    wp_die('Entry not found');
}

$url = ConfirmationHandler::buildUrlFor(
    new Entry($rawEntry),
    86400 // expires in 24 hours (24*3600=86400)
);

echo $url;
// https://example.com?entry=123&gf-worldpay-token=XXXXXXXXXXXX
```

Use Case:
With ["using confirmation query strings to populate a form based on another submission"](https://docs.gravityforms.com/using-confirmation-query-strings-to-populate-a-form-based-on-another-submission/):
1. User fills in formA
1. User completes WorldPay checkout form
1. User comes back and hits `CallbackHandler`
1. `CallbackHandler` sends user to formB according to confirmation settings
1. User arrives formB url with merged query strings

If the user quits before completing formB, you could use `ConfirmationHandler::buildUrlFor` generate a single-use, short-lived url for the user to resume formB.

Note:
- The url continues Gravity Forms confirmation
- Whoever got the url will go on confirmation, no authentication performed
- The confirmation will use latest field values from database which could have changed
- No payment status checking

### Redirect URL Retrieval Failure Handling

After form submit, this plugin sends order information to WorldPay in exchange for a redirect URL(the WorldPay hosted checkout form URL).

By default, when redirect URL retrieval fails:
1. Mark entry payment status as `Failed`
1. [Log](https://docs.gravityforms.com/logging-and-debugging/) the error     
1. `wp_die` **immediately**

Common failure reasons:
- Incorrect vendor code
- Server IP not whitelisted

Tips: Check the [log](https://docs.gravityforms.com/logging-and-debugging/).


You can use `'gf_worldpay_redirect_url_failure_wp_die'` filter to:
- continue Gravity Forms' feed and confirmation flow
- perform extra operations
- redirect to a different error page

**Important:** If this filter returns `false`, normal Gravity Forms' feed and confirmation flow continues.
Improper settings might lead to disasters.

Example:
```php
add_filter('gf_worldpay_redirect_url_failure_wp_die', function(bool $shouldWpDie, ServerAuthorizeResponse $response, Entry $entry, GFPaymentAddOn $addOn): bool {

    // Do something.

    return true; // Do `wp_die`
    return false; // Don't `wp_die`, continue normal flow
    return $shouldWpDie; // Undecisive
}, 10, 4);
```

## Preflight

Checker ID: `gf-worldpay-production-mode`

- ensure all gf-worldpay feeds are in production mode
- this checker can't be disabled
- this checker has no config options

## Coding

### Required Reading List

Read the followings before developing:

- [WorldPay HTML API](https://www.worldpay.com/uk/support/guides/business-gateway)
- [Gravity Forms: GFPaymentAddOn](https://docs.gravityforms.com/gfpaymentaddon/)
- [Gravity Forms: Entry Object](https://docs.gravityforms.com/entry-object/)
- [Omnipay: WorldPay](https://github.com/thephpleague/omnipay-worldpay)
- [thephpleague/omnipay#255 (comment)](https://github.com/thephpleague/omnipay/issues/255#issuecomment-90509446)

### Gravity Forms

Gravity Forms has undocumented hidden magics, read its source code.
