/* eslint-disable func-names, prefer-arrow-callback */
import Globals from '../../globals/globals';

const URL = Globals.value.url;
const VAL = Globals.value;
const BACKEND = Globals.selector.backend;
const FRONTEND = Globals.selector.frontend;

export default function () {
  this.Given(/^I set the viewport and timeout$/, () => {
    this.setDefaultTimeout(120 * 1000);
    browser.setViewportSize({
      width: VAL.resolution_w,
      height: VAL.resolution_h,
    }, true);
  });

  this.Given(/^I disable the url secret key encryption$/, () => {
    browser.url(URL.magento_base + URL.admin_path);
    if (browser.isVisible(BACKEND.admin_username)) {
      browser.setValue(BACKEND.admin_username, VAL.admin.username);
      browser.setValue(BACKEND.admin_password, VAL.admin.password);
      browser.click(BACKEND.admin_sign_in);
    }
    browser.url(URL.magento_base + URL.admin_path); // avoid magento cache warning
    browser.waitUntil(function () {
      return browser.isVisible(BACKEND.stores);
    }, VAL.timeout_out, 'stores button should be visible');
    browser.click(BACKEND.stores);
    browser.waitUntil(function () {
      return browser.isVisible(BACKEND.configuration);
    }, VAL.timeout_out, 'configuration button should be visible');
    browser.click(BACKEND.configuration);
    browser.waitUntil(function () {
      return browser.isVisible(BACKEND.advanced);
    }, VAL.timeout_out, 'advanced button should be visible');
    browser.click(BACKEND.advanced);
    browser.waitUntil(function () {
      return browser.isVisible(BACKEND.admin);
    }, VAL.timeout_out, 'admin button should be visible');
    browser.click(BACKEND.admin);
    browser.waitUntil(function () {
      return browser.isVisible(BACKEND.admin_security);
    }, VAL.timeout_out, 'admin security button should be visible');
    browser.click(BACKEND.admin_security);
    browser.waitUntil(function () {
      return browser.isVisible(BACKEND.admin_security_key);
    }, VAL.timeout_out, 'admin security key option button should be visible');
    browser.click(BACKEND.admin_security_key);
    browser.waitUntil(function () {
      return browser.isVisible(BACKEND.security_key_option);
    }, VAL.timeout_out, 'admin security option button should be visible');
    browser.selectByValue(BACKEND.security_key_option, '0');
    browser.click(BACKEND.plugin.save);
  });

  this.Given(/^I go to the backend of Checkout's plugin$/, () => {
    browser.url(URL.magento_base + URL.payments_path);
    if (browser.isVisible(BACKEND.admin_username)) {
      browser.setValue(BACKEND.admin_username, VAL.admin.username);
      browser.setValue(BACKEND.admin_password, VAL.admin.password);
      browser.click(BACKEND.admin_sign_in);
      browser.url(URL.magento_base + URL.payments_path); // avoid magento cache popup
    }
    if (!browser.isVisible(BACKEND.plugin.selector)) {
      browser.click(BACKEND.other_payments);
    }
    if (!browser.isVisible(BACKEND.plugin.basic_category.selector)) {
      browser.click(BACKEND.plugin.selector);
    }
    if (!browser.isVisible(BACKEND.plugin.basic_category.title)) {
      browser.click(BACKEND.plugin.basic_category.selector);
    }
    if (!browser.isVisible(BACKEND.plugin.advanced_category.cvv_vetification)) {
      browser.click(BACKEND.plugin.advanced_category.selector);
    }
    if (!browser.isVisible(BACKEND.plugin.order_category.order_creation)) {
      browser.click(BACKEND.plugin.order_category.selector);
    }
    if (!browser.isVisible(BACKEND.plugin.keys_category.public)) {
      browser.click(BACKEND.plugin.keys_category.selector);
    }
  });

  this.Given(/^I set the sandbox keys$/, () => {
    browser.setValue(BACKEND.plugin.keys_category.public, VAL.admin.public_key);
    browser.setValue(BACKEND.plugin.keys_category.secret, VAL.admin.secret_key);
    browser.setValue(BACKEND.plugin.keys_category.private_shared, VAL.admin.private_shared_key);
  });

  this.Given(/^I save the backend settings$/, () => {
    browser.click(BACKEND.plugin.save);
  });

  this.Given(/^I set the integration type to (.*)$/, (integration) => {
    switch (integration) {
      case 'frames':
        browser.selectByValue(BACKEND.plugin.basic_category.integration, 'embedded');
        break;
      case 'hosted':
        browser.selectByValue(BACKEND.plugin.basic_category.integration, 'hosted');
        break;
      default:
        browser.selectByValue(BACKEND.plugin.basic_category.integration, 'hosted');
        break;
    }
  });

  this.Given(/^I set the payment option title$/, () => {
    browser.setValue(BACKEND.plugin.basic_category.title, VAL.title);
  });

  this.Given(/^I set the payment option order$/, () => {
    browser.setValue(BACKEND.plugin.basic_category.sort_order, VAL.sort_order);
  });

  this.Given(/^I set the payment mode to (.*)$/, (mode) => {
    switch (mode) {
      case 'cards':
        browser.selectByValue(BACKEND.plugin.basic_category.hosted_payment_mode, 'cards');
        break;
      case 'local payments':
        browser.selectByValue(BACKEND.plugin.basic_category.hosted_payment_mode, 'localpayments');
        break;
      case 'mixed':
        browser.selectByValue(BACKEND.plugin.basic_category.hosted_payment_mode, 'mixed');
        break;
      default:
        browser.selectByValue(BACKEND.plugin.basic_category.hosted_payment_mode, 'cards');
        break;
    }
  });

  this.Given(/^I set the theme color$/, () => {
    browser.setValue(BACKEND.plugin.basic_category.hosted_theme_color, VAL.theme_color);
  });

  this.Given(/^I set the button label$/, () => {
    browser.setValue(BACKEND.plugin.basic_category.hosted_button_label, VAL.button_label);
  });

  this.Given(/^I (.*) Vault$/, (option) => {
    switch (option) {
      case 'enable':
        browser.selectByValue(BACKEND.plugin.advanced_category.vault, '1');
        break;
      case 'disable':
        browser.selectByValue(BACKEND.plugin.advanced_category.vault, '0');
        break;
      default:
        browser.selectByValue(BACKEND.plugin.advanced_category.vault, '0');
        break;
    }
  });

  this.Given(/^I set Vault title$/, () => {
    browser.setValue(BACKEND.plugin.advanced_category.vaut_title, VAL.vaut_title);
  });

  this.Given(/^I (.*) 3D Secure$/, (option) => {
    switch (option) {
      case 'enable':
        browser.selectByValue(BACKEND.plugin.advanced_category.three_d, '1');
        break;
      case 'disable':
        browser.selectByValue(BACKEND.plugin.advanced_category.three_d, '0');
        break;
      default:
        browser.selectByValue(BACKEND.plugin.advanced_category.three_d, '0');
        break;
    }
  });

  this.Given(/^I (.*) autocapture$/, (option) => {
    switch (option) {
      case 'enable':
        browser.selectByValue(BACKEND.plugin.advanced_category.autocapture, '1');
        break;
      case 'disable':
        browser.selectByValue(BACKEND.plugin.advanced_category.autocapture, '0');
        break;
      default:
        browser.selectByValue(BACKEND.plugin.advanced_category.autocapture, '0');
        break;
    }
  });

  this.Given(/^I update the stock for my test item$/, () => {
    browser.url(URL.magento_base + URL.test_product_path);
    if (browser.isVisible(BACKEND.admin_username)) {
      browser.setValue(BACKEND.admin_username, VAL.admin.username);
      browser.setValue(BACKEND.admin_password, VAL.admin.password);
      browser.click(BACKEND.admin_sign_in);
      browser.url(URL.magento_base + URL.test_product_path); // avoid magento cache popup
    }
    browser.waitUntil(function () {
      return browser.isVisible(BACKEND.test_product_quantity);
    }, VAL.timeout_out, 'stores button should be visible');
    browser.setValue(BACKEND.test_product_quantity, 999);
    browser.selectByValue(BACKEND.test_product_stock, '1');
    browser.click(BACKEND.test_product_save);
    browser.waitUntil(function () {
      return !browser.isVisible(FRONTEND.order.loader);
    }, VAL.timeout_out, 'Product should be updated');
    browser.waitUntil(function () {
      return !browser.isVisible(BACKEND.admin_loader);
    }, VAL.timeout_out, 'Product should be updated');
  });

  this.Then(/^I clear magento's cache$/, () => {
    browser.url(URL.magento_base + URL.cache_path);
    browser.click(BACKEND.flash_catch);
    browser.waitUntil(function () {
      return !browser.isVisible(FRONTEND.order.loader);
    }, VAL.timeout_out, 'Cache should be cleared');
  });
}
