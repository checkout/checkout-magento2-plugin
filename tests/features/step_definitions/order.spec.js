/* eslint-disable func-names, prefer-arrow-callback */
import Globals from '../../globals/globals';

const URL = Globals.value.url;
const VAL = Globals.value;
const FRONTEND = Globals.selector.frontend;

export default function () {
  this.Then(/^I complete the order flow as a (.*) customer until the payment stage$/, (customer) => {
    switch (customer) {
      case 'unregistered':
        browser.url(URL.magento_base + URL.product_path);
        browser.waitUntil(function () {
          return !browser.getAttribute('body', 'class').includes(FRONTEND.order.ajax_loader);
        }, VAL.timeout_out, 'the product should be loaded');
        browser.waitUntil(function () {
          return !browser.isVisible(FRONTEND.order.loader);
        }, VAL.timeout_out, 'the product page to be loaded');
        browser.waitUntil(function () {
          return !browser.getAttribute('body', 'class').includes(FRONTEND.order.ajax_loader);
        }, VAL.timeout_out, 'the product should be loaded');
        browser.click(FRONTEND.order.add_product);
        browser.waitUntil(function () {
          return !browser.getAttribute('body', 'class').includes(FRONTEND.order.ajax_loader);
        }, VAL.timeout_out, 'the product should be loaded');
        browser.waitUntil(function () {
          return !browser.isVisible(FRONTEND.order.loader);
        }, VAL.timeout_out, 'the product page to be loaded');
        browser.click(FRONTEND.order.add_product);
        browser.waitUntil(function () {
          return browser.isVisible(FRONTEND.order.product_counter);
        }, VAL.timeout_out, 'the basket products counter to be visible');
        browser.click(FRONTEND.order.cart);
        browser.waitUntil(function () {
          return browser.isVisible(FRONTEND.order.go_to_checkout);
        }, VAL.timeout_out, 'the go to checkout button should be visible');
        browser.waitUntil(function () {
          return !browser.getAttribute('body', 'class').includes(FRONTEND.order.ajax_loader);
        }, VAL.timeout_out, 'the shopping basket should be updated with the product');
        browser.click(FRONTEND.order.go_to_checkout);
        browser.waitUntil(function () {
          return !browser.isVisible(FRONTEND.order.checkout_page_loader);
        }, VAL.timeout_out, 'the customer data page should be loaded');
        browser.waitUntil(function () {
          return browser.isVisible(FRONTEND.order.customer_email);
        }, VAL.timeout_out, 'the customer email field should be visible');
        browser.setValue(FRONTEND.order.customer_email, VAL.guest.email);
        browser.waitUntil(function () {
          return !browser.isVisible(FRONTEND.order.loader);
        }, VAL.timeout_out, 'loader should not be visible');
        browser.waitUntil(function () {
          return !browser.isVisible(FRONTEND.order.checkout_page_loader);
        }, VAL.timeout_out, 'the customer data page should be loaded');
        browser.waitUntil(function () {
          return !browser.getAttribute(FRONTEND.order.email_fieldset, 'class').includes(VAL.fieldset_block);
        }, VAL.timeout_out, 'the email check should be completed');
        browser.pause(2000); // avoid magento problems
        if (browser.getValue(FRONTEND.order.customer_firstname) === VAL.guest.name && browser.getValue(FRONTEND.order.customer_phone) === VAL.guest.phone) {
          browser.waitUntil(function () {
            return !browser.getAttribute('body', 'class').includes(FRONTEND.order.ajax_loader);
          }, VAL.timeout_out, 'wait for ajax');
          browser.waitUntil(function () {
            return !browser.isVisible(FRONTEND.order.loader);
          }, VAL.timeout_out, 'the shipping methods should be updated');
          browser.waitUntil(function () {
            return !browser.isVisible(FRONTEND.order.checkout_page_loader);
          }, VAL.timeout_out, 'the shipping methods should be updated');
          browser.waitUntil(function () {
            return browser.isVisible(FRONTEND.order.shipping_method);
          }, VAL.timeout_out, 'the shipping methods should be ticked');
          browser.waitUntil(function () {
            return !browser.getAttribute('body', 'class').includes(FRONTEND.order.ajax_loader);
          }, VAL.timeout_out, 'wait for ajax');
          browser.click(FRONTEND.order.go_to_payment);
          browser.waitUntil(function () {
            return !browser.isVisible(FRONTEND.order.loader);
          }, VAL.timeout_out, 'the loader before the payment options should not be visible');
          browser.waitUntil(function () {
            return !browser.isVisible(FRONTEND.order.checkout_page_loader);
          }, VAL.timeout_out, 'the payment page should be loaded');
        } else {
          browser.setValue(FRONTEND.order.customer_firstname, VAL.guest.name);
          browser.waitUntil(function () {
            return !browser.getAttribute('body', 'class').includes(FRONTEND.order.ajax_loader);
          }, VAL.timeout_out, 'wait for ajax');
          browser.setValue(FRONTEND.order.customer_firstname, VAL.guest.name);
          browser.waitUntil(function () {
            return !browser.getAttribute('body', 'class').includes(FRONTEND.order.ajax_loader);
          }, VAL.timeout_out, 'wait for ajax');
          browser.setValue(FRONTEND.order.customer_lastname, VAL.guest.lastname);
          browser.waitUntil(function () {
            return !browser.getAttribute('body', 'class').includes(FRONTEND.order.ajax_loader);
          }, VAL.timeout_out, 'wait for ajax');
          browser.setValue(FRONTEND.order.customer_street, VAL.guest.address);
          browser.waitUntil(function () {
            return !browser.getAttribute('body', 'class').includes(FRONTEND.order.ajax_loader);
          }, VAL.timeout_out, 'wait for ajax');
          browser.selectByValue(FRONTEND.order.customer_country, VAL.guest.country);
          browser.waitUntil(function () {
            return !browser.getAttribute('body', 'class').includes(FRONTEND.order.ajax_loader);
          }, VAL.timeout_out, 'wait for ajax');
          try {
            browser.setValue(FRONTEND.order.customer_city, VAL.guest.city);
          } catch (er) {
            browser.pause(10000); // avoid magento error
            browser.setValue(FRONTEND.order.customer_city, VAL.guest.city);
          }
          browser.waitUntil(function () {
            return !browser.getAttribute('body', 'class').includes(FRONTEND.order.ajax_loader);
          }, VAL.timeout_out, 'wait for ajax');
          browser.setValue(FRONTEND.order.customer_postcode, VAL.guest.postcode);
          browser.waitUntil(function () {
            return !browser.getAttribute('body', 'class').includes(FRONTEND.order.ajax_loader);
          }, VAL.timeout_out, 'wait for ajax');
          browser.setValue(FRONTEND.order.customer_phone, VAL.guest.phone);
          browser.waitUntil(function () {
            return !browser.getAttribute('body', 'class').includes(FRONTEND.order.ajax_loader);
          }, VAL.timeout_out, 'wait for ajax');
          browser.waitUntil(function () {
            return !browser.isVisible(FRONTEND.order.loader);
          }, VAL.timeout_out, 'the shipping methods should be updated');
          browser.waitUntil(function () {
            return !browser.isVisible(FRONTEND.order.checkout_page_loader);
          }, VAL.timeout_out, 'the shipping methods should be updated');
          browser.waitUntil(function () {
            return browser.isVisible(FRONTEND.order.shipping_method);
          }, VAL.timeout_out, 'the shipping methods should be ticked');
          browser.waitUntil(function () {
            return !browser.getAttribute('body', 'class').includes(FRONTEND.order.ajax_loader);
          }, VAL.timeout_out, 'wait for ajax');
          browser.click(FRONTEND.order.go_to_payment);
          browser.waitUntil(function () {
            return !browser.isVisible(FRONTEND.order.loader);
          }, VAL.timeout_out, 'the loader before the payment options should not be visible');
          browser.waitUntil(function () {
            return !browser.isVisible(FRONTEND.order.checkout_page_loader);
          }, VAL.timeout_out, 'the payment page should be loaded');
        }
        break;
      case 'registered':
        browser.url(URL.magento_base + URL.product_path);
        browser.waitUntil(function () {
          return !browser.isVisible(FRONTEND.order.loader);
        }, VAL.timeout_out, 'the product page to be loaded');
        browser.click(FRONTEND.order.add_product);
        browser.waitUntil(function () {
          return !browser.getAttribute('body', 'class').includes(FRONTEND.order.ajax_loader);
        }, VAL.timeout_out, 'the product should be loaded');
        browser.waitUntil(function () {
          return !browser.isVisible(FRONTEND.order.loader);
        }, VAL.timeout_out, 'the product page to be loaded');
        browser.click(FRONTEND.order.add_product);
        browser.waitUntil(function () {
          return browser.isVisible(FRONTEND.order.product_counter);
        }, VAL.timeout_out, 'the basket products counter to be visible');
        browser.click(FRONTEND.order.cart);
        browser.waitUntil(function () {
          return browser.isVisible(FRONTEND.order.go_to_checkout);
        }, VAL.timeout_out, 'the go to checkout button should be visible');
        browser.waitUntil(function () {
          return !browser.getAttribute('body', 'class').includes(FRONTEND.order.ajax_loader);
        }, VAL.timeout_out, 'the order details should be updated before checkout');
        browser.click(FRONTEND.order.go_to_checkout);
        browser.waitUntil(function () {
          return !browser.isVisible(FRONTEND.order.loader);
        }, VAL.timeout_out, 'the customer data page should be loaded');
        browser.waitUntil(function () {
          return browser.isVisible(FRONTEND.order.go_to_payment);
        }, VAL.timeout_out, 'the go to payment button should be enabled');
        browser.click(FRONTEND.order.go_to_payment);
        browser.waitUntil(function () {
          return !browser.isVisible(FRONTEND.order.loader);
        }, VAL.timeout_out, 'the loader before the payment options should not be visible');
        browser.waitUntil(function () {
          return !browser.isVisible(FRONTEND.order.checkout_page_loader);
        }, VAL.timeout_out, 'the payment page should be loaded');
        break;
      default:
        break;
    }
  });

  this.Given(/^I create an account$/, () => {
    browser.url(URL.magento_base + URL.sign_up_path);
    browser.waitUntil(function () {
      return browser.isVisible(FRONTEND.registration.firstname);
    }, VAL.timeout_out, 'name field should be visible');
    browser.setValue(FRONTEND.registration.firstname, VAL.customer.name);
    browser.setValue(FRONTEND.registration.lastname, VAL.customer.lastname);
    browser.setValue(FRONTEND.registration.email, VAL.customer.email);
    browser.setValue(FRONTEND.registration.password, VAL.customer.password);
    browser.setValue(FRONTEND.registration.confirm_password, VAL.customer.password);
    browser.click(FRONTEND.registration.submit);
    browser.waitUntil(function () {
      return browser.isVisible(FRONTEND.registration.success);
    }, VAL.timeout_out, 'loadershould not be visible');
    browser.click(FRONTEND.registration.address_tab);
    browser.waitUntil(function () {
      return browser.isVisible(FRONTEND.registration.street);
    }, VAL.timeout_out, 'street input be visible');
    browser.setValue(FRONTEND.registration.street, VAL.customer.street);
    browser.selectByValue(FRONTEND.registration.country, VAL.customer.country);
    browser.setValue(FRONTEND.registration.city, VAL.customer.city);
    browser.setValue(FRONTEND.registration.phone, VAL.customer.phone);
    browser.click(FRONTEND.registration.save);
  });

  this.Then(/^I logout from the registered customer account$/, () => {
    browser.url(URL.magento_base + URL.sign_out_path);
  });

  this.Given(/^I login the registered customer account$/, () => {
    browser.url(URL.magento_base + URL.sign_in_path);
    browser.waitUntil(function () {
      return !browser.getAttribute('body', 'class').includes(FRONTEND.order.ajax_loader);
    }, VAL.timeout_out, 'page should be loaded');
    if (browser.isVisible(FRONTEND.sign_in_email)) { // Only sign in if you are not signed in yet
      browser.setValue(FRONTEND.sign_in_email, VAL.customer.email);
      browser.setValue(FRONTEND.sign_in_password, VAL.customer.password);
      browser.waitUntil(function () {
        return browser.isEnabled(FRONTEND.sign_in_button);
      }, VAL.timeout_out, 'sign in button should be enabled');
      browser.click(FRONTEND.sign_in_button);
      browser.waitUntil(function () {
        return !browser.getAttribute('body', 'class').includes(FRONTEND.order.ajax_loader);
      }, VAL.timeout_out, 'the product should be loaded');
    }
  });

  this.Then(/^I should see the plugin title$/, () => {
    browser.waitUntil(function () {
      return browser.getText(FRONTEND.checkout_option_title) === VAL.title;
    }, VAL.timeout_out, 'Checkout plugin title should be set and visible');
  });

  this.Then(/^I should see the (.*) tab$/, (option) => {
    switch (option) {
      case 'alternative payments':
        browser.waitUntil(function () {
          return browser.isVisible(FRONTEND.hosted.hosted_alt_payments_tab);
        }, VAL.timeout_out, 'Checkout plugin title should be set and visible');
        break;
      case 'card':
        browser.waitUntil(function () {
          return browser.isVisible(FRONTEND.hosted.hosted_card_tab);
        }, VAL.timeout_out, 'Checkout plugin title should be set and visible');
        break;
      default:
        break;
    }
  });

  this.Then(/^I should see the just the (.*) options$/, (option) => {
    switch (option) {
      case 'alternative payments':
        browser.waitUntil(function () {
          return browser.isVisible(FRONTEND.hosted.hosted_region_selector);
        }, VAL.timeout_out, 'Alternative payments should be loaded');
        break;
      case 'card':
        browser.waitUntil(function () {
          return !browser.isVisible(FRONTEND.hosted.hosted_region_selector);
        }, VAL.timeout_out, 'Alternative payments should not be visible');
        break;
      default:
        break;
    }
  });

  this.Then(/^I should see a customised hosted page$/, () => {
    browser.waitUntil(function () {
      return browser.getCssProperty(FRONTEND.hosted.hosted_header, 'background-color').parsed.hex === VAL.theme_color;
    }, VAL.timeout_out, 'Theme color should be set');
    browser.waitUntil(function () {
      return browser.getValue(FRONTEND.hosted.hosted_pay_button) === VAL.button_label;
    }, VAL.timeout_out, 'Button label should be set');
  });
}
