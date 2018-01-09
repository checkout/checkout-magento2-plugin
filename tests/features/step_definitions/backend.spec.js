/* eslint-disable func-names, prefer-arrow-callback */
import chai from 'chai';
import Globals from '../../config/globals';

const URL = Globals.value.url;
const VAL = Globals.value;
const BACKEND = Globals.selector.backend;
const FRONTEND = Globals.selector.frontend;

export default function () {
  this.Given(/^I set the integration type to (.*)$/, (integration) => {
    if (!browser.isVisible(BACKEND.plugin.basic_category.title)) {
      browser.click(BACKEND.plugin.basic_category.selector);
    }
    switch (integration) {
      case 'frames':
        browser.selectByValue(BACKEND.plugin.basic_category.integration, 'embedded');
        chai.expect(browser.getValue(BACKEND.plugin.basic_category.integration)).to.equal('embedded');
        break;
      case 'hosted':
        browser.selectByValue(BACKEND.plugin.basic_category.integration, 'hosted');
        chai.expect(browser.getValue(BACKEND.plugin.basic_category.integration)).to.equal('hosted');
        break;
      default:
        console.log('OPTION EXCEPTION');
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

  this.Given(/^I (.*) THREE D$/, (option) => {
    switch (option) {
      case 'enable':
        browser.selectByValue(BACKEND.plugin.advanced_category.three_d, '1');
        chai.expect(browser.getValue(BACKEND.plugin.advanced_category.three_d)).to.equal('1');
        break;
      case 'disable':
        browser.selectByValue(BACKEND.plugin.advanced_category.three_d, '0');
        chai.expect(browser.getValue(BACKEND.plugin.advanced_category.three_d)).to.equal('0');
        break;
      default:
        console.log('EXCEPTIONNNNNN');
        // browser.selectByValue(BACKEND.plugin.advanced_category.three_d, '0');
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
}
