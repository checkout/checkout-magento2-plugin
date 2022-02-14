/**
 * Checkout.com
 * Authorized and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
 *
 * PHP version 7
 *
 * @category  Magento2
 * @package   Checkout.com
 * @author    Platforms Development Team <platforms@checkout.com>
 * @copyright 2010-present Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/customer',
        "CheckoutCom_Magento2/js/view/payment/utilities"
    ],
    function ($, Quote, Customer, Utilities) {
        'use strict';

        return {
            /**
             * Is Virtual.
             *
             * @return {object}  The is virtual status.
             */
            getIsVirtual: function () {
                return Utilities.getRestQuoteData(null).is_virtual
            },

            /**
             * Make a call to the Magento API
             */
            getRestData: function(requestBody, m2ApiEndpoint) {
                let restUrl =
                    window.BASE_URL +
                    "rest/all/V1/guest-carts/" +
                    window.checkoutConfig.quoteData.entity_id +
                    "/" +
                    m2ApiEndpoint;
                "?form_key=" + window.checkoutConfig.formKey;

                if (Customer.isLoggedIn()) {
                    var store = window.checkoutConfig.storeCode ? window.checkoutConfig.storeCode : 'default';
                    restUrl =
                        window.BASE_URL +
                        "rest/" + store + "/V1/carts/mine/" +
                        m2ApiEndpoint +
                        "?form_key=" +
                        window.checkoutConfig.formKey;
                }

                let result = null;
                let postType = m2ApiEndpoint == 'totals' ? "GET" : "POST";

                $.ajax({
                    url: restUrl,
                    type: postType,
                    async: false,
                    dataType: "json",
                    contentType: "application/json",
                    data: JSON.stringify(requestBody),
                    success: function (data, status, xhr) {
                        result = data;
                    },
                    error: function (request, status, error) {
                        Utilities.log(error);
                    },
                });
                return result;

            },

            /**
             * Get the area code based on zip and country code.
             * Used for ApplePay payments
             */
            getAreaCode: function(zipCode, countryCode) {
                // Ensure we have exactly 5 characters to parse
                if (zipCode.length === 5 && countryCode.toLowerCase() === "us") {
                    // Ensure we don't parse strings starting with 0 as octal values
                    const thiszip = parseInt(zipCode, 10);

                    let st = null;
                    if (thiszip >= 35000 && thiszip <= 36999) {
                        st = "AL";
                    } else if (thiszip >= 99500 && thiszip <= 99999) {
                        st = "AK";
                    } else if (thiszip >= 85000 && thiszip <= 86999) {
                        st = "AZ";
                    } else if (thiszip >= 71600 && thiszip <= 72999) {
                        st = "AR";
                    } else if (thiszip >= 90000 && thiszip <= 96699) {
                        st = "CA";
                    } else if (thiszip >= 80000 && thiszip <= 81999) {
                        st = "CO";
                    } else if (thiszip >= 6000 && thiszip <= 6999) {
                        st = "CT";
                    } else if (thiszip >= 19700 && thiszip <= 19999) {
                        st = "DE";
                    } else if (thiszip >= 32000 && thiszip <= 34999) {
                        st = "FL";
                    } else if (thiszip >= 30000 && thiszip <= 31999) {
                        st = "GA";
                    } else if (thiszip >= 96700 && thiszip <= 96999) {
                        st = "HI";
                    } else if (thiszip >= 83200 && thiszip <= 83999) {
                        st = "ID";
                    } else if (thiszip >= 60000 && thiszip <= 62999) {
                        st = "IL";
                    } else if (thiszip >= 46000 && thiszip <= 47999) {
                        st = "IN";
                    } else if (thiszip >= 50000 && thiszip <= 52999) {
                        st = "IA";
                    } else if (thiszip >= 66000 && thiszip <= 67999) {
                        st = "KS";
                    } else if (thiszip >= 40000 && thiszip <= 42999) {
                        st = "KY";
                    } else if (thiszip >= 70000 && thiszip <= 71599) {
                        st = "LA";
                    } else if (thiszip >= 3900 && thiszip <= 4999) {
                        st = "ME";
                    } else if (thiszip >= 20600 && thiszip <= 21999) {
                        st = "MD";
                    } else if (thiszip >= 1000 && thiszip <= 2799) {
                        st = "MA";
                    } else if (thiszip >= 48000 && thiszip <= 49999) {
                        st = "MI";
                    } else if (thiszip >= 55000 && thiszip <= 56999) {
                        st = "MN";
                    } else if (thiszip >= 38600 && thiszip <= 39999) {
                        st = "MS";
                    } else if (thiszip >= 63000 && thiszip <= 65999) {
                        st = "MO";
                    } else if (thiszip >= 59000 && thiszip <= 59999) {
                        st = "MT";
                    } else if (thiszip >= 27000 && thiszip <= 28999) {
                        st = "NC";
                    } else if (thiszip >= 58000 && thiszip <= 58999) {
                        st = "ND";
                    } else if (thiszip >= 68000 && thiszip <= 69999) {
                        st = "NE";
                    } else if (thiszip >= 88900 && thiszip <= 89999) {
                        st = "NV";
                    } else if (thiszip >= 3000 && thiszip <= 3899) {
                        st = "NH";
                    } else if (thiszip >= 7000 && thiszip <= 8999) {
                        st = "NJ";
                    } else if (thiszip >= 87000 && thiszip <= 88499) {
                        st = "NM";
                    } else if (thiszip >= 10000 && thiszip <= 14999) {
                        st = "NY";
                    } else if (thiszip >= 43000 && thiszip <= 45999) {
                        st = "OH";
                    } else if (thiszip >= 73000 && thiszip <= 74999) {
                        st = "OK";
                    } else if (thiszip >= 97000 && thiszip <= 97999) {
                        st = "OR";
                    } else if (thiszip >= 15000 && thiszip <= 19699) {
                        st = "PA";
                    } else if (thiszip >= 300 && thiszip <= 999) {
                        st = "PR";
                    } else if (thiszip >= 2800 && thiszip <= 2999) {
                        st = "RI";
                    } else if (thiszip >= 29000 && thiszip <= 29999) {
                        st = "SC";
                    } else if (thiszip >= 57000 && thiszip <= 57999) {
                        st = "SD";
                    } else if (thiszip >= 37000 && thiszip <= 38599) {
                        st = "TN";
                    } else if (
                        (thiszip >= 75000 && thiszip <= 79999) ||
                        (thiszip >= 88500 && thiszip <= 88599)
                    ) {
                        st = "TX";
                    } else if (thiszip >= 84000 && thiszip <= 84999) {
                        st = "UT";
                    } else if (thiszip >= 5000 && thiszip <= 5999) {
                        st = "VT";
                    } else if (thiszip >= 22000 && thiszip <= 24699) {
                        st = "VA";
                    } else if (thiszip >= 20000 && thiszip <= 20599) {
                        st = "DC";
                    } else if (thiszip >= 98000 && thiszip <= 99499) {
                        st = "WA";
                    } else if (thiszip >= 24700 && thiszip <= 26999) {
                        st = "WV";
                    } else if (thiszip >= 53000 && thiszip <= 54999) {
                        st = "WI";
                    } else if (thiszip >= 82000 && thiszip <= 83199) {
                        st = "WY";
                    }

                    return st;
                } else {
                    return "";
                }
            }
        };
    }
);
