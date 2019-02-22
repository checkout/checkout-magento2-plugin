/* eslint-disable func-names, prefer-arrow-callback */
import Globals from '../../config/globals';

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
        browser.click(FRONTEND.order.add_product);
        // Add product twice to make sure Magento updates the basket
        browser.waitUntil(function () {
          return !browser.getAttribute('body', 'class').includes(FRONTEND.order.ajax_loader);
        }, VAL.timeout_out, 'the product should be loaded');
        browser.waitUntil(function () {
          let addButton = browser.getText(FRONTEND.order.add_product);
          return addButton === 'Add to Cart';
        }, VAL.timeout_out, 'the product should be loaded');
        browser.click(FRONTEND.order.add_product);
        browser.waitUntil(function () {
          return !browser.getAttribute('body', 'class').includes(FRONTEND.order.ajax_loader);
        }, VAL.timeout_out, 'the product should be loaded');
        browser.waitUntil(function () {
          return browser.isVisible(FRONTEND.order.product_counter);
        }, VAL.timeout_out, 'the basket products counter to be visible');
        browser.click(FRONTEND.order.cart);
        browser.waitUntil(function () {
          return !browser.getAttribute('body', 'class').includes(FRONTEND.order.ajax_loader);
        }, VAL.timeout_out, 'the shopping basket should be updated with the product');
        browser.click(FRONTEND.order.go_to_checkout);
        browser.waitUntil(function () {
          return !browser.isVisible(FRONTEND.order.checkout_page_loader);
        }, VAL.timeout_out, 'the customer data page should be loaded');
        browser.waitUntil(function () {
          return !browser.getAttribute('body', 'class').includes(FRONTEND.order.ajax_loader);
        }, VAL.timeout_out, 'the shopping basket should be updated with the product');
        // Don't complete the details if they are present
        if (browser.getValue(FRONTEND.order.customer_firstname) !== VAL.guest.name &&
            browser.getValue(FRONTEND.order.customer_email) !== VAL.guest.email) {
          browser.setValue(FRONTEND.order.customer_firstname, VAL.guest.name);
          browser.setValue(FRONTEND.order.customer_lastname, VAL.guest.lastname);
          browser.setValue(FRONTEND.order.customer_street, VAL.guest.address);
          browser.setValue(FRONTEND.order.customer_city, VAL.guest.city);
          browser.selectByValue(FRONTEND.order.customer_county, '1');
          browser.setValue(FRONTEND.order.customer_postcode, VAL.guest.postcode);
          browser.setValue(FRONTEND.order.customer_phone, VAL.guest.phone);
          browser.pause(2000); // add wait
          browser.setValue(FRONTEND.order.customer_email, VAL.guest.email);
        }
        browser.pause(4000); // avoid errors with magento
        browser.setValue(FRONTEND.order.customer_email, VAL.guest.email);
        browser.pause(4000); // avoid errors with magento
        browser.click(FRONTEND.order.go_to_payment);
        browser.waitUntil(function () {
          return !browser.isVisible(FRONTEND.order.loader);
        }, VAL.timeout_out, 'the loader before the payment options should not be visible');
        browser.waitUntil(function () {
          return !browser.isVisible(FRONTEND.order.checkout_page_loader);
        }, VAL.timeout_out, 'the payment page should be loaded');
        break;
      case 'registered':
        browser.url(URL.magento_base + URL.product_path);
        browser.waitUntil(function () {
          return !browser.isVisible(FRONTEND.order.loader);
        }, VAL.timeout_out, 'the product page to be loaded');
        browser.waitUntil(function () {
          return !browser.getAttribute('body', 'class').includes(FRONTEND.order.ajax_loader);
        }, VAL.timeout_out, 'the product should be loaded');
        browser.waitUntil(function () {
          let addButton = browser.getText(FRONTEND.order.add_product);
          return addButton === 'Add to Cart';
        }, VAL.timeout_out, 'the product should be loaded');
        browser.click(FRONTEND.order.add_product);
        browser.waitUntil(function () {
          return !browser.getAttribute('body', 'class').includes(FRONTEND.order.ajax_loader);
        }, VAL.timeout_out, 'the product should be loaded');
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
  this.Given(/^I login the registered customer account$/, () => {
    browser.waitUntil(function () {
      return !browser.getAttribute('body', 'class').includes(FRONTEND.order.ajax_loader);
    }, VAL.timeout_out, 'page should be loaded');
    browser.pause(2000); // avoid magetno error
    browser.url(URL.magento_base + URL.sign_in_path);
    browser.waitUntil(function () {
      return !browser.getAttribute('body', 'class').includes(FRONTEND.order.ajax_loader);
    }, VAL.timeout_out, 'page should be loaded');
    browser.pause(2000); // avoid magetno error
    if (browser.isVisible(FRONTEND.sign_in_email)) { // Only sign in if you are not signed in yet
      browser.setValue(FRONTEND.sign_in_email, VAL.customer.email);
      browser.setValue(FRONTEND.sign_in_password, VAL.customer.password);
      browser.waitUntil(function () {
        return browser.isEnabled(FRONTEND.sign_in_button);
      }, VAL.timeout_out, 'sign in button should be enabled');
      browser.waitUntil(function () {
        return !browser.getAttribute('body', 'class').includes(FRONTEND.order.ajax_loader);
      }, VAL.timeout_out, 'page should be loaded');
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
