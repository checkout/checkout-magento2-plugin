/* eslint-disable func-names, prefer-arrow-callback */
import chai from 'chai';
import Globals from '../../config/globals';

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

  this.Given(/^I disable captcha$/, () => {
    browser.url(URL.magento_base + URL.captcha_path);
    if (!browser.isVisible(BACKEND.captcha_option)) {
      browser.click(BACKEND.captcha_category);
    }
    if(browser.isSelected(BACKEND.captcha_default)) {
      browser.click(BACKEND.captcha_default);
    }
    browser.selectByValue(BACKEND.captcha_option, 0);
    browser.click(BACKEND.plugin.save);
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
    let admin = browser.element('a*=Admin');
    admin.click();
    let security = browser.element('a*=Security');
    security.click();
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
  this.Given(/^I create a product$/, () => {
    browser.url(URL.magento_base + URL.create_product_path);
    if (browser.isVisible(BACKEND.admin_username)) {
      browser.setValue(BACKEND.admin_username, VAL.admin.username);
      browser.setValue(BACKEND.admin_password, VAL.admin.password);
      browser.click(BACKEND.admin_sign_in);
      browser.url(URL.magento_base + URL.create_product_path); // avoid magento cache popup
    }
    browser.waitUntil(function () {
      return !browser.isVisible(BACKEND.admin_loader);
    }, VAL.timeout_out, 'add product page loader should not be visible');
    browser.setValue(BACKEND.new_product_name, VAL.new_product_name);
    browser.setValue(BACKEND.new_product_price, VAL.new_product_price);
    browser.setValue(BACKEND.new_product_quantity, VAL.new_product_quantity);
    browser.selectByValue(BACKEND.new_product_stock, VAL.new_product_stock);
    browser.click(BACKEND.test_product_save);
    browser.waitUntil(function () {
      return browser.isVisible(BACKEND.new_product_load_mask);
    }, VAL.timeout_out, 'the product should be saved');
    browser.waitUntil(function () {
      return !browser.isVisible(BACKEND.new_product_load_mask);
    }, VAL.timeout_out, 'the product should be saved');
  });
  this.Given(/^I go to the backend of Checkout's plugin$/, () => {
    browser.url(URL.magento_base + URL.payments_path);
    if (browser.isVisible(BACKEND.admin_username)) {
      browser.setValue(BACKEND.admin_username, VAL.admin.username);
      browser.setValue(BACKEND.admin_password, VAL.admin.password);
      browser.click(BACKEND.admin_sign_in);
      browser.url(URL.magento_base + URL.payments_path); // avoid magento cache popup
    }
    browser.waitUntil(function () {
      return browser.isVisibleWithinViewport(BACKEND.other_payments);
    }, VAL.timeout_out, 'wait for plugin to be loaded');
    if (!browser.isVisible(BACKEND.plugin.selector)) {
      browser.click(BACKEND.other_payments);
    }
    browser.waitUntil(function () {
      return browser.isVisibleWithinViewport(BACKEND.plugin.selector);
    }, VAL.timeout_out, 'wait for plugin to be loaded');
    browser.pause(1000); // animation delay 
    if (!browser.isVisible(BACKEND.plugin.basic_category.selector)) {
      browser.pause(1000); // animation delay 
      browser.click(BACKEND.plugin.selector);
    }
    if (!browser.isVisible(BACKEND.plugin.basic_category.title)) {
      browser.pause(1000); // animation delay 
      browser.click(BACKEND.plugin.basic_category.selector);
    }
    if (!browser.isVisible(BACKEND.plugin.advanced_category.cvv_vetification)) {
      browser.pause(1000); // animation delay 
      browser.click(BACKEND.plugin.advanced_category.selector);
    }
    if (!browser.isVisible(BACKEND.plugin.order_category.order_creation)) {
      browser.pause(1000); // animation delay 
      browser.click(BACKEND.plugin.order_category.selector);
    }
    if (!browser.isVisible(BACKEND.plugin.keys_category.public)) {
      browser.pause(1000); // animation delay 
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
    browser.waitUntil(function () {
      return browser.isVisible(BACKEND.save_success_message);
    }, VAL.timeout_out, 'the changes should be saved');
  });
  this.Given(/^I have (.*) and (.*) and (.*)$/, (integration, threed, customisation) => {
    browser.url(URL.magento_base + URL.payments_path);
    if (browser.isVisible(BACKEND.admin_username)) {
      browser.setValue(BACKEND.admin_username, VAL.admin.username);
      browser.setValue(BACKEND.admin_password, VAL.admin.password);
      browser.click(BACKEND.admin_sign_in);
      browser.url(URL.magento_base + URL.payments_path); // avoid magento cache popup
    }
    browser.pause(1000); // animation delay 
    if (!browser.isVisible(BACKEND.plugin.selector)) {
      browser.click(BACKEND.other_payments);
    }
    if (!browser.isVisible(BACKEND.plugin.basic_category.selector)) {
      browser.pause(1000); // animation delay 
      browser.click(BACKEND.plugin.selector);
    }
    if (!browser.isVisible(BACKEND.plugin.basic_category.title)) {
      browser.pause(1000); // animation delay 
      browser.click(BACKEND.plugin.basic_category.selector);
    }
    if (!browser.isVisible(BACKEND.plugin.advanced_category.cvv_vetification)) {
      browser.pause(1000); // animation delay 
      browser.click(BACKEND.plugin.advanced_category.selector);
    }
    if (!browser.isVisible(BACKEND.plugin.order_category.order_creation)) {
      browser.pause(1000); // animation delay 
      browser.click(BACKEND.plugin.order_category.selector);
    }
    if (!browser.isVisible(BACKEND.plugin.keys_category.public)) {
      browser.pause(1000); // animation delay 
      browser.click(BACKEND.plugin.keys_category.selector);
    }
    if (integration === 'frames' && browser.getValue(BACKEND.plugin.basic_category.integration) !== 'embedded') {
      browser.selectByValue(BACKEND.plugin.basic_category.integration, 'embedded');
    } else if (integration === 'hosted' && browser.getValue(BACKEND.plugin.basic_category.integration) !== 'hosted') {
      browser.selectByValue(BACKEND.plugin.basic_category.integration, 'hosted');
    }
    if (threed === 'THREE D' && browser.getValue(BACKEND.plugin.advanced_category.three_d) === '0') {
      browser.selectByValue(BACKEND.plugin.advanced_category.three_d, '1');
    } else if (threed === 'no THREE D' && browser.getValue(BACKEND.plugin.advanced_category.three_d) === '1') {
      browser.selectByValue(BACKEND.plugin.advanced_category.three_d, '0');
    }
    if (customisation === 'customisation' && browser.getValue(BACKEND.plugin.basic_category.integration) !== 'hosted') {
      browser.selectByValue(BACKEND.plugin.basic_category.integration, 'hosted');
      browser.setValue(BACKEND.plugin.basic_category.hosted_theme_color, VAL.theme_color);
      browser.setValue(BACKEND.plugin.basic_category.hosted_button_label, VAL.button_label);
    }
    browser.click(BACKEND.plugin.save);
    browser.waitUntil(function () {
      return browser.isVisible(BACKEND.save_success_message);
    }, VAL.timeout_out, 'the changes should be saved');
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
    let Address = browser.element("=Address Book");
    Address.click();
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
    browser.pause(1000); // avoid magetno error
  });
  this.Given(/^I check the sandbox keys$/, () => {
    browser.url(URL.magento_base + URL.payments_path);
    if (browser.isVisible(BACKEND.admin_username)) {
      browser.setValue(BACKEND.admin_username, VAL.admin.username);
      browser.setValue(BACKEND.admin_password, VAL.admin.password);
      browser.click(BACKEND.admin_sign_in);
      browser.url(URL.magento_base + URL.payments_path); // avoid magento cache popup
    }
    if (!browser.isVisible(BACKEND.plugin.selector)) {
      browser.pause(1000);
      browser.click(BACKEND.other_payments);
    }
    if (!browser.isVisible(BACKEND.plugin.basic_category.selector)) {
      browser.pause(1000);
      browser.click(BACKEND.plugin.selector);
    }
    if (!browser.isVisible(BACKEND.plugin.basic_category.title)) {
      browser.pause(1000);
      browser.click(BACKEND.plugin.basic_category.selector);
    }
    if (!browser.isVisible(BACKEND.plugin.advanced_category.cvv_vetification)) {
      browser.pause(1000);
      browser.click(BACKEND.plugin.advanced_category.selector);
    }
    if (!browser.isVisible(BACKEND.plugin.order_category.order_creation)) {
      browser.pause(1000);
      browser.click(BACKEND.plugin.order_category.selector);
    }
    if (!browser.isVisible(BACKEND.plugin.keys_category.public)) {
      browser.pause(1000);
      browser.click(BACKEND.plugin.keys_category.selector);
    }
    browser.setValue(BACKEND.plugin.keys_category.public, VAL.admin.public_key);
    browser.setValue(BACKEND.plugin.keys_category.secret, VAL.admin.secret_key);
    browser.setValue(BACKEND.plugin.keys_category.private_shared, VAL.admin.private_shared_key);
    browser.click(BACKEND.plugin.save);
    browser.waitUntil(function () {
      return browser.isVisible(BACKEND.save_success_message);
    }, VAL.timeout_out, 'the changes should be saved');
  });
  this.Then(/^I clear cache$/, () => {
    browser.url(URL.magento_base + URL.cache_path);
    browser.waitUntil(function () {
      return browser.isVisible(BACKEND.flash_cache);
    }, VAL.timeout_out, 'Flash cache button should be visible');
    browser.click(BACKEND.flash_cache);
    browser.waitUntil(function () {
      return browser.isVisible(BACKEND.flash_cache_success);
    }, VAL.timeout_out, 'Cache flash confirmation should be visible');
  });
}
