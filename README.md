<img src="https://docs.checkout.com/img/Logo-black.png" alt="Checkout.com" width="380"/>

## Magento 2 Extension &nbsp; ![N|Solid](https://circleci.com/gh/checkout/checkout-magento2-plugin.svg?style=shield&circle-token=c246af998b0859be11b269afd0b0162303f1ac5f)

[Checkout.com](https://www.checkout.com "Checkout.com") is a software platform that has integrated 100% of the value chain to create payment infrastructures that truly make a difference. Checkout.com is authorised and regulated as a Payment institution by the UK Financial Conduct Authority.

The Checkout.com extension for Magento 2 allows shop owners to process online payments through the [Checkout.com Payment Gateway](https://docs.checkout.com/getting-started/introduction "Checkout.com Payment Gateway").

This extension is an integration of [Checkout.js](https://docs.checkout.com "Checkout.js") and offers 2 payment modes:

* Checkout.js Hosted<br>
Shoppers are redirected from your website to Checkout.com servers to complete payments.

* Frames.js<br>
The payment form is embedded and shoppers complete payments without leaving your website.

Checkout.js Hosted and Frames.js are cross-browser and cross-device compatible, and can accept online payments from all major credit cards. Checkout.js Hosted offers in addition many of the most popular [Alternative Payment methods](https://docs.checkout.com/reference/checkout-js-reference/alternative-payments "Alternative Payment methods") used around the world.

## Supported payment methods
The Checkout.com extension for Magento 2 supports VISA, Mastercard, American Express, Discover, Diners Club, JCB, in addition to the Alternative Payment options described above.

<img src="https://docs.checkout.com/img/supported-payment-methods-v2.png" alt="Checkout.com" width="400"/>

## Features
The Checkout.com extension for Magento 2 offers useful and unique features, allowing Magento 2 shop owners to process online payments in the best conditions. These features have been designed to offer an optimal shopping and payment experience to Magento 2 merchants and shoppers.

Amongst many others, the major features are: 

* Hosted payment gateway
* Embedded payment form
* New order status management
* Invoice generation management
* 3D Secure handling
* Non 3D Secure payment fallback
* Alternative payments
* Payment currency flexibility
* Dynamic descriptors
* REST API for mobile payments
* Payment form customization

## Installation
The easiest and recommended way to install the Checkout.com Magento 2 extension is to run the following commands in a terminal, from your Magento 2 root directory:

```bash
composer require checkoutcom/magento2:*
bin/magento setup:upgrade
rm -rf var/cache var/generation/ var/di
bin/magento setup:di:compile && php bin/magento cache:clean
```
For more information on the Magento 2 module installation process, please have a look at the [Magento 2 official documentation](http://devdocs.magento.com/guides/v2.0/install-gde/install/cli/install-cli-subcommands-enable.html "Magento 2 official documentation")

## Configuration
Once the Checkout.com extension for Magento 2 installed, go to **Stores > Configuration > Sales > Payment Methods > Checkout.com** to see the configuration and customization options available. Here is a short selection of some useful parameters available:

| Option               | Values               | Description  |
| :------------------- | :------------------- | :-------------|
| Integration   | Embedded/Hosted | Display an emedded form for the payment or redirect to the payment gateway |
| Environment   | Sandbox/Live    | Use the payment processor in test or production mode |
| New order status | M2 order statuses | Which status should be given to newly created orders | 
| Invoice generation | Yes/No  | Should invoices be generated manually or automatically | 
| Vault enabled | Yes/No  | Allow shoppers to save a payment card for reuse | 
| 3D Secure check | Yes/No  | Should payments follow a 3D Secure verification process | 
| Auto Capture | Yes/No  | Should payments be captured immediately or with a specified delay |
| Payment Currency | M2 currencies | Which currency should be used for the payment |
| Button label | Pay now | Text to display on the payment button (Hosted)|
| Theme color | #00b660 | Hexadecimal color code to use for the theme (Hosted) |
| Theme | Standard/Simple | Theme to use for the embedded payment form (Embedded)|
| Custom CSS file | ------- | Upload a CSS file to customize the display parameters (Embedded)|

In order to effectively process payments through the Checkout.com Payment Gateway, you will need to open an account.
Contact your [Checkout.com](https://www.checkout.com "Checkout.com") account manager or send an email to support@checkout.com for more information.

## Compatibility
The Checkout.com extension for Magento 2 is compatible with Magento 2.1 and above.
