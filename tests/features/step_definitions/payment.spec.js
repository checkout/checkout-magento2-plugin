/* eslint-disable func-names, prefer-arrow-callback */
import Globals from '../../globals/globals';

const VAL = Globals.value;
const FRONTEND = Globals.selector.frontend;

export default function () {
  this.Then(/^I choose Checkout as a payment option$/, () => {
    browser.waitUntil(function () {
      return browser.isVisible(FRONTEND.order.checkout_payment_option);
    }, VAL.timeout_out, 'checkout pyament option should exist');
    browser.click(FRONTEND.order.checkout_payment_option);
  });

  this.Then(/^I complete Checkout Frames with a (.*) card$/, (option) => {
    let card;
    let mounth;
    let year;
    let cvv;
    browser.waitUntil(function () {
      return browser.isVisible(FRONTEND.order.checkout_iframe_selector);
    }, VAL.timeout_out, 'the embedded form should be visible');
    const iframe = browser.element(FRONTEND.order.checkout_iframe_selector);
    browser.frame(iframe.value);
    switch (option) {
      case 'visa':
        card = browser.element(FRONTEND.order.embedded_fields.card_number);
        card.setValue(VAL.card.visa.card_number);
        mounth = browser.element(FRONTEND.order.embedded_fields.mounth);
        mounth.setValue(VAL.card.visa.mounth);
        year = browser.element(FRONTEND.order.embedded_fields.year);
        year.setValue(VAL.card.visa.year);
        cvv = browser.element(FRONTEND.order.embedded_fields.cvv);
        cvv.setValue(VAL.card.visa.cvv);
        break;
      case 'mastercard':
        card = browser.element(FRONTEND.order.embedded_fields.card_number);
        card.setValue(VAL.card.mastercard.card_number);
        mounth = browser.element(FRONTEND.order.embedded_fields.mounth);
        mounth.setValue(VAL.card.mastercard.mounth);
        year = browser.element(FRONTEND.order.embedded_fields.year);
        year.setValue(VAL.card.mastercard.year);
        cvv = browser.element(FRONTEND.order.embedded_fields.cvv);
        cvv.setValue(VAL.card.mastercard.cvv);
        break;
      case 'amex':
        card = browser.element(FRONTEND.order.embedded_fields.card_number);
        card.setValue(VAL.card.amex.card_number);
        mounth = browser.element(FRONTEND.order.embedded_fields.card.mounth);
        mounth.setValue(VAL.card.amex.mounth);
        year = browser.element(FRONTEND.order.embedded_fields.card.year);
        year.setValue(VAL.card.amex.year);
        cvv = browser.element(FRONTEND.order.embedded_fields.card.cvv);
        cvv.setValue(VAL.card.amex.cvv);
        break;
      case 'diners':
        card = browser.element(FRONTEND.order.embedded_fields.card_number);
        card.setValue(VAL.card.diners.card_number);
        mounth = browser.element(FRONTEND.order.embedded_fields.mounth);
        mounth.setValue(VAL.card.diners.mounth);
        year = browser.element(FRONTEND.order.embedded_fields.year);
        year.setValue(VAL.card.diners.year);
        cvv = browser.element(FRONTEND.order.embedded_fields.cvv);
        cvv.setValue(VAL.card.diners.cvv);
        break;
      case 'jcb':
        card = browser.element(FRONTEND.order.embedded_fields.card_number);
        card.setValue(VAL.card.jcb.card_number);
        mounth = browser.element(FRONTEND.order.embedded_fields.mounth);
        mounth.setValue(VAL.card.jcb.mounth);
        year = browser.element(FRONTEND.order.embedded_fields.year);
        year.setValue(VAL.card.jcb.year);
        cvv = browser.element(FRONTEND.order.embedded_fields.cvv);
        cvv.setValue(VAL.card.jcb.cvv);
        break;
      case 'discover':
        card = browser.element(FRONTEND.order.embedded_fields.card_number);
        card.setValue(VAL.card.discover.card_number);
        mounth = browser.element(FRONTEND.order.embedded_fields.mounth);
        mounth.setValue(VAL.card.discover.mounth);
        year = browser.element(FRONTEND.order.embedded_fields.year);
        year.setValue(VAL.card.discover.year);
        cvv = browser.element(FRONTEND.order.embedded_fields.cvv);
        cvv.setValue(VAL.card.discover.cvv);
        break;
      default:
        card = browser.element(FRONTEND.order.embedded_fields.card_number);
        card.setValue(VAL.card.visa.card_number);
        mounth = browser.element(FRONTEND.order.embedded_fields.mounth);
        mounth.setValue(VAL.card.visa.mounth);
        year = browser.element(FRONTEND.order.embedded_fields.year);
        year.setValue(VAL.card.visa.year);
        cvv = browser.element(FRONTEND.order.embedded_fields.cvv);
        cvv.setValue(VAL.card.visa.cvv);
        break;
    }
    browser.frameParent();
  });

  this.Then(/^I complete Checkout Hosted with a (.*) card$/, (option) => {
    let card;
    let mounth;
    let year;
    let cvv;
    browser.waitUntil(function () {
      return browser.isVisible(FRONTEND.hosted.hosted_header);
    }, VAL.timeout_out, 'hosted page should be visible');
    switch (option) {
      case 'visa':
        card = browser.element(FRONTEND.hosted.card_number);
        card.setValue(VAL.card.visa.card_number);
        mounth = browser.element(FRONTEND.hosted.mounth);
        mounth.setValue(VAL.card.visa.mounth);
        year = browser.element(FRONTEND.hosted.year);
        year.setValue(VAL.card.visa.year);
        cvv = browser.element(FRONTEND.hosted.cvv);
        cvv.setValue(VAL.card.visa.cvv);
        break;
      case 'mastercard':
        card = browser.element(FRONTEND.hosted.card_number);
        card.setValue(VAL.card.mastercard.card_number);
        mounth = browser.element(FRONTEND.hosted.mounth);
        mounth.setValue(VAL.card.mastercard.mounth);
        year = browser.element(FRONTEND.hosted.year);
        year.setValue(VAL.card.mastercard.year);
        cvv = browser.element(FRONTEND.hosted.cvv);
        cvv.setValue(VAL.card.mastercard.cvv);
        break;
      case 'amex':
        card = browser.element(FRONTEND.hosted.card_number);
        card.setValue(VAL.card.amex.card_number);
        mounth = browser.element(FRONTEND.hosted.mounth);
        mounth.setValue(VAL.card.amex.mounth);
        year = browser.element(FRONTEND.hosted.year);
        year.setValue(VAL.card.amex.year);
        cvv = browser.element(FRONTEND.hosted.cvv);
        cvv.setValue(VAL.card.amex.cvv);
        break;
      case 'diners':
        card = browser.element(FRONTEND.hosted.card_number);
        card.setValue(VAL.card.diners.card_number);
        mounth = browser.element(FRONTEND.hosted.mounth);
        mounth.setValue(VAL.card.diners.mounth);
        year = browser.element(FRONTEND.hosted.year);
        year.setValue(VAL.card.diners.year);
        cvv = browser.element(FRONTEND.hosted.cvv);
        cvv.setValue(VAL.card.diners.cvv);
        break;
      case 'jcb':
        card = browser.element(FRONTEND.hosted.card_number);
        card.setValue(VAL.card.jcb.card_number);
        mounth = browser.element(FRONTEND.hosted.mounth);
        mounth.setValue(VAL.card.jcb.mounth);
        year = browser.element(FRONTEND.hosted.year);
        year.setValue(VAL.card.jcb.year);
        cvv = browser.element(FRONTEND.hosted.cvv);
        cvv.setValue(VAL.card.jcb.cvv);
        break;
      case 'discover':
        card = browser.element(FRONTEND.hosted.card_number);
        card.setValue(VAL.card.discover.card_number);
        mounth = browser.element(FRONTEND.hosted.mounth);
        mounth.setValue(VAL.card.discover.mounth);
        year = browser.element(FRONTEND.hosted.year);
        year.setValue(VAL.card.discover.year);
        cvv = browser.element(FRONTEND.hosted.cvv);
        cvv.setValue(VAL.card.discover.cvv);
        break;
      default:
        card = browser.element(FRONTEND.hosted.card_number);
        card.setValue(VAL.card.visa.card_number);
        mounth = browser.element(FRONTEND.hosted.mounth);
        mounth.setValue(VAL.card.visa.mounth);
        year = browser.element(FRONTEND.hosted.year);
        year.setValue(VAL.card.visa.year);
        cvv = browser.element(FRONTEND.hosted.cvv);
        cvv.setValue(VAL.card.visa.cvv);
        break;
    }
    browser.click(FRONTEND.hosted.pay_button);
  });

  this.Then(/^I submit the order for the (.*) integration$/, (option) => {
    browser.pause(1000); // Make sure the card token is generated
    switch (option) {
      case 'frames':
        browser.waitUntil(function () {
          return browser.isEnabled(FRONTEND.order.place_order_button);
        }, VAL.timeout_out, 'place order button should be enabled');
        browser.click(FRONTEND.order.place_order_button);
        break;
      case 'hosted':
        browser.waitUntil(function () {
          return browser.isEnabled(FRONTEND.order.hosted_place_order);
        }, VAL.timeout_out, 'place order button should be enabled');
        browser.click(FRONTEND.order.hosted_place_order);
        break;
      default:
        break;
    }
  });

  this.Then(/^I complete the THREE D details$/, () => {
    browser.waitUntil(function () {
      return browser.isVisible(FRONTEND.order.three_d_password);
    }, VAL.timeout_out, '3D password  should be enabled');
    browser.setValue(FRONTEND.order.three_d_password, VAL.admin.three_d_password);
    browser.click(FRONTEND.order.three_d_submit);
  });

  this.Then(/^I should see the success page$/, () => {
    browser.waitUntil(function () {
      return browser.isVisible(FRONTEND.order.checkout_success_message);
    }, VAL.timeout_out, 'success message should be visible');
  });
}
