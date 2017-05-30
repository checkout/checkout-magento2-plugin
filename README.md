# checkout-magento2-plugin
Checkout.com Magento 2 official extension

### Minimum requirements
Magento 2.1.X

# Installation via composer
The easiest way to install the extension is by running the below commands from your Magento 2 root directory:

```
composer require checkoutcom/magento2:*
bin/magento setup:upgrade
rm -rf var/cache var/generation/ var/di
bin/magento setup:di:compile && php bin/magento cache:clean
```
