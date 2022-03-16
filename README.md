<img src="https://cdn.checkout.com/img/checkout-logo-online-payments.jpg" alt="Checkout.com" width="380"/>

## Checkout.com Magento 2 Extension - Unified Payments API &nbsp; ![N|Solid](https://circleci.com/gh/checkout/checkout-magento2-plugin.svg?style=shield&circle-token=c246af998b0859be11b269afd0b0162303f1ac5f)

[Checkout.com](https://www.checkout.com "Checkout.com") is a software platform that has integrated 100% of the value chain to create payment infrastructures that truly make a difference. Checkout.com is authorized and regulated as a Payment institution by the UK Financial Conduct Authority.

The Checkout.com extension for Magento 2 allows shop owners to process online payments through the [Checkout.com Payment Gateway](https://docs.checkout.com/ "Checkout.com Payment Gateway").

The Checkout.com Magento 2 Extension offers 7 payment modes:

* Card Payments with Frames.js <br>
The payment form is embedded and shoppers complete payments without leaving your website.
The Frames.js payment form is cross-browser and cross-device compatible, and can accept online payments from all major credit cards.

* Alternative Payments<br>
Users can place orders with the following alternative and local payment options used around the world:
Alipay, Bancontact, Boleto, EPS, Fawry, Giropay, Ideal, Klarna, KNet, Poli, Sepa, Sofort.

* Apple Pay Payments<br>
Users can place orders with an Apple Pay wallet.

* Google Pay Payments<br>
Users can place orders with a Google Pay wallet.

* Saved Cards Payments<br>
Users can place orders with a payment card saved in their Magento 2 account.

* Instant Purchase Payments<br>
Users can place orders with a payment card saved in their Magento 2 account, using the Magento 2 Instant Purchase feature.

* MOTO Payments<br>
Users can place orders with a website administrator using the MOTO (Mail Order Telephone Order) payment feature.

## Supported card schemes
The Checkout.com extension for Magento 2 supports VISA, Mastercard, American Express, Discover, Diners Club, JCB, in addition to the Alternative Payment options described above.

## Compatibility
The Checkout.com extension for Magento 2 is compatible with Magento 2.3 and above.

## Features
The Checkout.com extension for Magento 2 offers useful and unique features, allowing Magento 2 shop owners to process online payments in the best conditions. These features have been designed to offer an optimal shopping and payment experience to Magento 2 merchants and shoppers.

Amongst many others, the major features are: 

* Embedded payment form
* Instant Purchase
* Saved card payments
* MOTO Payments
* Wallet payments (Apple Pay and Google Pay)
* New order status management
* Invoice generation management
* 3D Secure handling
* Non 3D Secure payment fallback
* Alternative payments
* Payment currency flexibility
* Dynamic descriptors
* REST API for mobile payments
* Payment form customization

## Multi-shipping
The Checkout.com extension for Magento 2 is not compatible with Magento multi-shipping feature.

## Installation
The easiest and recommended way to install the Checkout.com Magento 2 extension is to run the following commands in a terminal, from your Magento 2 root directory:

```bash
composer require checkoutcom/magento2:*
bin/magento setup:upgrade
rm -rf var/cache var/generation/ var/di
bin/magento setup:di:compile && php bin/magento cache:clean
```

## Update
In order to update the Checkout.com Magento 2 extension please run the following commands in a terminal, from your Magento 2 root directory:

```bash
composer clearcache
composer update checkoutcom/magento2 |OR| composer require checkoutcom/magento2:*
bin/magento setup:upgrade
rm -rf var/cache var/generation/ var/di
bin/magento setup:di:compile && php bin/magento cache:clean
```

For more information on the Magento 2 module installation process, please have a look at the [Magento 2 official documentation](http://devdocs.magento.com/guides/v2.0/install-gde/install/cli/install-cli-subcommands-enable.html "Magento 2 official documentation")

## Configuration
Once the Checkout.com extension for Magento 2 installed, go to **Stores > Configuration > Sales > Payment Methods > Checkout.com** to see the configuration and customization options available. 
In order to effectively process payments through the Checkout.com Payment Gateway, you will need to open an account.
Contact your [Checkout.com](https://www.checkout.com "Checkout.com") account manager or send an email to support@checkout.com for more information.

Dedicated technical support is available to all Merchants using Checkout.com via the public GitHub repositories or directly by email at integration@checkout.com. Checkout.com does not provide support for third party plugins or any alterations made to the official Checkout.com plugins.

## Webhook URL
In order to allow the [Checkout.com](https://www.checkout.com "Checkout.com") payment gateway to send payment notifications to your Magento 2 installation, you will have to configure the following URL as a Webhook URL in the Hub:

```bash
yoursite.com/checkout_com/webhook/callback
```

A dynamically generated Webhook URL specific to your installation is available in the "Account settings" section of the Checkout.com Magento 2 module configuration.

**DISCLAIMER**

In no event shall Checkout.com be liable for any claim, damages or other liability, whether in an action of contract, tort or otherwise, arising from the information or code provided or the use of the information or code provided. This disclaimer of liability refers to any technical issue or damage caused by the use or non-use of the information or code provided or by the use of incorrect or incomplete information or code provided.
