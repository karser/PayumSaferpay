# Saferpay Payum Plugin

[![Build Status](https://github.com/karser/PayumSaferpay/workflows/Code_Checks/badge.svg)](https://github.com/karser/PayumSaferpay/actions)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/karser/PayumSaferpay/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/karser/PayumSaferpay/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/karser/PayumSaferpay/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/karser/PayumSaferpay/?branch=master)
[![Total Downloads](https://poser.pugx.org/karser/payum-saferpay/downloads)](https://packagist.org/packages/karser/payum-saferpay)

## Introduction

This plugin implements [Saferpay specification v1.10](https://saferpay.github.io/jsonapi/index.html), including all features from e-commerce and business licenses.
Saferpay e-commerce license provides access to Payment Page interface only.
Saferpay Business license provides Transaction Interface, [recurring payments](https://saferpay.github.io/sndbx/recurring.html), storing cards aliases and etc.
For more detailed comparison please refer to [this](https://saferpay.github.io/sndbx/Interfaces.html) page.

Transaction Interface provides two options: an iframe (similar to Payment Page) and submitting the card data
directly. The last option is available only if you are PCI compliant.

Here is how Payment Page interface looks like:
![](docs/payment-page-interface.png)

Here is how Transaction interface looks like:
![](docs/transaction-interface.png)

## Requirements

- PHP 7.3+
- [Payum](https://github.com/Payum/Payum)
- Optionally [PayumBundle](https://github.com/Payum/PayumBundle) and Symfony 3 or 4+

## Features

This plugin supports

- [Payment Page interface](https://saferpay.github.io/sndbx/Integration_PP.html)
- [Transaction interface](https://saferpay.github.io/sndbx/Integration_trx.html)
- Recurring Payments [with the referenced transactions Method](https://saferpay.github.io/sndbx/recurring.html#recurring-referenced)
- Recurring Payments [using an alias](https://saferpay.github.io/sndbx/recurring.html#recurring-alias)
- Aliases support: Add and remove, Capture transaction
- Specifying LIABILITY_SHIFT condition (payment will not be accepted if LiabilityShift is not possible)
- Handling payment notification for Payment Page interface

All features are covered with tests. You can find useful examples in functional tests.

## Installation

```bash
$ composer require karser/payum-saferpay
```

## Configuration

### With PayumBundle (Symfony)

First register the gateway factory in your services definition:
```yaml
# config/services.yaml or app/config/services.yml
services:
    app.saferpay.gateway_factory:
        class: Payum\Core\Bridge\Symfony\Builder\GatewayFactoryBuilder
        arguments: [Karser\PayumSaferpay\SaferpayGatewayFactory]
        tags:
            - { name: payum.gateway_factory_builder, factory: saferpay }
```

Then configure the gateway:

You can use `General test account` credentials or create personal test account
[here](https://www.six-payment-services.com/en/site/e-commerce/support/testaccount.html)

```yaml
# config/packages/payum.yaml or app/config/config.yml

payum:
    gateways:
        saferpay:
            factory: saferpay
            # this is test credentials
            username: 'API_401860_80003225'
            password: 'C-y*bv8346Ze5-T8'
            customerId: '401860'
            terminalId: '17795278'
            interface: 'TRANSACTION' #optionally, can be defined via details too
            optionalParameters: #optionally, add some additional interface options, read more below in section "Additional Configuration"
                styling_css_url: 'https://acme.com/hosted-page-styles.css'
            sandbox: true
```

### With Payum

```php
//config.php

use Payum\Core\GatewayFactoryInterface;
use Payum\Core\PayumBuilder;
use Payum\Core\Payum;
use Karser\PayumSaferpay\SaferpayGatewayFactory;

/** @var Payum $payum */
$payum = (new PayumBuilder())
    ->addDefaultStorages()
    ->addGatewayFactory('saferpay', static function(array $config, GatewayFactoryInterface $coreGatewayFactory) {
        return new SaferpayGatewayFactory($config, $coreGatewayFactory);
    })
    ->addGateway('saferpay', [
        'factory' => 'saferpay',
        # this is test credentials
        'username' => 'API_401860_80003225',
        'password' => 'C-y*bv8346Ze5-T8',
        'customerId' => '401860',
        'terminalId' => '17795278',
        'interface' => 'TRANSACTION', #optionally, can be defined via details too
        'sandbox' => true,
    ])
    ->getPayum()
;
```

### Configure the routes

You can include the default Payum Bundle's routes, although
only these routes are actually required by this plugin:
  
```yaml
#config/routes.yaml
payum_capture_do:
    path: /payment/capture/{payum_token}
    controller: PayumBundle:Capture:do

#notification route is required only if you use payment page interface
#payum_notify_do:
#    path: /payment/notify/{payum_token}
#    controller: PayumBundle:Notify:do
```

## Usage

### Capture example

Make sure you defined `Payment` and `Token` entities like it is described [here](https://github.com/Payum/Payum/blob/master/docs/storages.md)

```php
//capture.php

use App\Entity\Payment;
use Payum\Core\Payum;
use Payum\Core\Request\Capture;
use Karser\PayumSaferpay\Constants;

/** @var Payum $payum */
$storage = $payum->getStorage(Payment::class);
$payment = $storage->create();
$payment->setNumber(uniqid());
$payment->setCurrencyCode('USD');
$payment->setTotalAmount(123); //$1.23 USD
$payment->setDescription('test payment');

// capture using TRANSACTION interface (default)
$payment->setDetails(['Interface' => Constants::INTERFACE_TRANSACTION]);
// or capture using PAYMENT_PAGE interface
$payment->setDetails(['Interface' => Constants::INTERFACE_PAYMENT_PAGE]);

$storage->update($payment);

$token = $payum->getTokenFactory()->createCaptureToken('saferpay', $payment, 'done.php');

$captureRequest = new Capture($token);
$captureRequest->setModel($payment);
$reply = $this->gateway->execute($captureRequest, true);

//then redirect user to $reply->getUrl();
```

```php
//done.php

use App\Entity\Payment;
use Payum\Core\Payum;
use Payum\Core\Request\GetHumanStatus;

/** @var Payum $payum */
$token = $payum->getHttpRequestVerifier()->verify($_GET);
$this->payum->getHttpRequestVerifier()->invalidate($token);

$payment = $payum->getStorage(Payment::class)->find($token);

$this->assertStatus(GetHumanStatus::STATUS_CAPTURED, $payment);

$this->gateway->execute($status = new GetHumanStatus($payment));

//status of the payment is in $status->getValue()
```

### Recurring Payments with the referenced transactions Method

1. Capture payment with Recurring or Installment option:
```php
use Karser\PayumSaferpay\Constants;

$payment = $storage->create();

$payment->setDetails(['Payment' => ['Recurring' => ['Initial' => true]]]);
//or
$payment->setDetails(['Payment' => ['Installment' => ['Initial' => true]]]);

//then capture the payment
$captureRequest = new Capture($token);
$captureRequest->setModel($payment);
$reply = $this->gateway->execute($captureRequest, true);
//then redirect user to $reply->getUrl();
```

2. Capture a new transaction by providing a reference to the previous one:
```php
$refTransactionId = $payment->getDetails()['Transaction']['Id'];

$payment = $storage->create();

$payment->setDetails([
    'TransactionReference' => [
        'TransactionId' =>  $refTransactionId,
    ]
]);

//then capture the payment
$captureRequest = new Capture($token);
$captureRequest->setModel($payment);
$this->gateway->execute($captureRequest);
```

### Recurring Payments using an alias

1. Obtaining the Alias:
The user will have to enter their card details in an iframe.
```php
use Karser\PayumSaferpay\Constants;
use Karser\PayumSaferpay\Model\CardAlias;


$cardAliasStorage = $this->payum->getStorage(CardAlias::class);
$alias = $cardAliasStorage->create();
$alias->setDetails([
    'Alias' => [
        'IdGenerator' => Constants::ALIAS_ID_GENERATOR_MANUAL,
        'Id' => $generatedId = uniqid('id', true),
        'Lifetime' => 1600, //days
    ]
]);
$this->cardAliasStorage->update($alias);
$token = $this->payum->getTokenFactory()->createCaptureToken(self::GATEWAY_NAME, $cardAlias, 'done.php');

$insertCardAliasRequest = new InsertCardAlias($token);
$insertCardAliasRequest->setModel($cardAlias);
$reply = $this->gateway->execute($insertCardAliasRequest, true);
//then redirect user to $reply->getUrl();
```

2. Capture a new transaction by providing an alias id:
```php
$aliasId = $cardAlias->getDetails()['Alias']['Id'];

$payment = $storage->create();

$payment->setDetails([
    'PaymentMeans' => [
        'Alias' => [
            'Id' => $aliasId,
        ],
    ],
]);

//then capture the payment
$captureRequest = new Capture($token);
$captureRequest->setModel($payment);
$this->gateway->execute($captureRequest);
```

### Change Payload Locale

1. Create a custom extension (If you're using the symfony bundle):
```yaml
AppBundle\Extension\ConvertPaymentExtension:
    autowire: true
    public: true
    tags:
        - { name: payum.extension, alias: saferpay_locale_extension, factory: saferpay, gateway: saferpay, prepend: false }
```

2. Create service:
```php
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Extension\Context;
use Payum\Core\Extension\ExtensionInterface;
use Payum\Core\Request\Convert;

class ConvertPaymentExtension implements ExtensionInterface
{
    public function onPostExecute(Context $context)
    {
        $action = $context->getAction();
        $previousActionClassName = get_class($action);

        if (false === stripos($previousActionClassName, 'ConvertPaymentAction')) {
            return;
        }

        /** @var Convert $request */
        $request = $context->getRequest();
        if (false === $request instanceof Convert) {
            return;
        }
        
        // do your locale logic here
        $customLocale = 'de';

        $result = ArrayObject::ensureArrayObject($request->getResult());

        $payerData = [];
        if (isset($result['Payer']) && is_array($result['Payer'])) {
            $payerData = $result['Payer'];
        }

        $payerData['LanguageCode'] = $customLocale;

        $result['Payer'] = $payerData;

        $request->setResult((array) $result);
    }
}

```

### Additional Configuration
Depending on given interface, there are several optional options available. 

Example: 

```yaml
payum:
    gateways:
        saferpay:
            optionalParameters:
                styling_css_url: 'https://acme.com/hosted-page-styles.css'
```

#### Payment Page interface
| Key                                   | Description |
| --------------------------------------| ------------|
| `config_set`                          | This parameter let you define your payment page config (PPConfig) by name. If this parameters is not set, your default PPConfig will be applied if available. When the PPConfig can't be found (e.g. wrong name), the Saferpay basic style will be applied to the payment page. |
| `payment_methods`                     | Used to restrict the means of payment which are available to the payer for this transaction. If only one payment method id is set, the payment selection step will be skipped. |
| `wallets`                             | Used to control if wallets should be enabled on the payment selection page and to go directly to the given wallet (if exactly one wallet is filled and PaymentMethods is not set). |
| `notification_merchant_email`         | Email addresses to which a confirmation email will be sent to the merchants after successful authorizations. |
| `notification_payer_email`            | Email address to which a confirmation email will be sent to the payer after successful authorizations. |
| `styling_css_url`                     | Deprecated  |
| `styling_content_security_enabled`    | When enabled, then ContentSecurity/SAQ-A is requested, which leads to the CSS being loaded from the saferpay server. |
| `styling_theme`                       | This parameter let you customize the appearance of the displayed payment pages. Per default a lightweight responsive styling will be applied.If you don't want any styling use 'NONE'. |
| `payer_note`                          | Text which will be printed on payer's debit note. Supported by SIX Acquiring. No guarantee that it will show up on the payer's debit note, because his bank has to support it too. Please note that maximum allowed characters are rarely supported. It's usually around 10-12. |

#### Transaction interface
| Key                                   | Description |
| --------------------------------------| ------------|
| `config_set`                          | This parameter let you define your payment page config (PPConfig) by name. If this parameters is not set, your default PPConfig will be applied if available. When the PPConfig can't be found (e.g. wrong name), the Saferpay basic style will be applied to the payment page.  |
| `payment_methods`                     | Used to restrict the means of payment which are available to the payer for this transaction. If only one payment method id is set, the payment selection step will be skipped. |
| `styling_css_url`                     | Deprecated  |
| `styling_content_security_enabled`    | When enabled, then ContentSecurity/SAQ-A is requested, which leads to the CSS being loaded from the saferpay server. |
| `styling_theme`                       | This parameter let you customize the appearance of the displayed payment pages. Per default a lightweight responsive styling will be applied. If you don't want any styling use 'NONE'. |
| `payer_note`                          | Text which will be printed on payer's debit note. Supported by SIX Acquiring. No guarantee that it will show up on the payer's debit note, because his bank has to support it too. Please note that maximum allowed characters are rarely supported. It's usually around 10-12. |

## Testing
```
composer update
vendor/bin/phpunit
```

## ToDo
- Implement separate actions: Authorize, Cancel transaction
- Improve and add more unit tests
- config parameters: LIABILITY_SHIFT condition

## Credits
- Dmitrii Poddubnyi <dpoddubny@gmail.com>

## License
This plugin is under the MIT license. For the whole copyright, see the [LICENSE](LICENSE) file distributed with this source code.
