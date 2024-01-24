/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*global define,alert*/
define(
    [
        'ko',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/resource-url-manager',
        'mage/storage',
        'Magento_Checkout/js/model/payment-service',
        'Magento_Checkout/js/model/payment/method-converter',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/select-billing-address',
        'Magento_Checkout/js/model/shipping-save-processor/payload-extender'
    ],
    function (
        ko,
        quote,
        resourceUrlManager,
        storage,
        paymentService,
        methodConverter,
        errorProcessor,
        fullScreenLoader,
        selectBillingAddressAction,
        payloadExtender
    ) {
        'use strict';

        return {
            saveShippingInformation: function () {
                var payload;

                if (!quote.billingAddress() || (quote.shippingMethod().method_code == 'postnl_pakjegemak' || quote.shippingMethod().method_code == 'dpd_parcelshop' || quote.shippingMethod().method_code == 'dhl_parcelshop' || quote.shippingMethod().method_code == 'intrapost_parcelshop')) {
                    if(quote.billingAddress() === null) selectBillingAddressAction(quote.shippingAddress());
                    var billingstreet = quote.billingAddress().street[0];
                    if(quote.shippingMethod().method_code == 'postnl_pakjegemak' || quote.shippingMethod().method_code == 'dhl_parcelshop' || quote.shippingMethod().method_code == 'dpd_parcelshop' || quote.shippingMethod().method_code == 'intrapost_parcelshop'){

                        quote.shippingAddress().company = jQuery("#shipping_method\\:company").val();
                        quote.shippingAddress().firstname = jQuery("#shipping_method\\:firstname").val();
                        quote.shippingAddress().lastname = jQuery("#shipping_method\\:lastname").val();
                        var street = jQuery("#shipping_method\\:street1").val();
                        var number = jQuery("#shipping_method\\:street2").val();
                        quote.shippingAddress().street = [street, number];
                        quote.shippingAddress().postcode = jQuery("#shipping_method\\:postcode").val();
                        quote.shippingAddress().city = jQuery("#shipping_method\\:city").val();
                        quote.shippingAddress().customerAddressId = null;
                        quote.shippingAddress().customerId = null;
                        quote.shippingAddress().isDefaultShipping(false);
                        quote.shippingAddress().canUseForBilling(false);
                    }
                    if(
                        quote.shippingAddress().firstname.toLowerCase().indexOf("postnl") == -1 &&
                        quote.shippingAddress().firstname.toLowerCase().indexOf("dhl") == -1 &&
                        quote.shippingAddress().firstname.toLowerCase().indexOf("dpd") == -1 &&
                        quote.shippingAddress().firstname.toLowerCase().indexOf("intrapost") == -1
                    ){
                        quote.billingAddress().street[0] = billingstreet;
                        selectBillingAddressAction(quote.billingAddress());
                    }
                }

                payload = {
                    addressInformation: {
                        'shipping_address': quote.shippingAddress(),
                        'billing_address': quote.billingAddress(),
                        'shipping_method_code': quote.shippingMethod()['method_code'],
                        'shipping_carrier_code': quote.shippingMethod()['carrier_code']
                    }
                };

                payloadExtender(payload);

                fullScreenLoader.startLoader();

                return storage.post(
                    resourceUrlManager.getUrlForSetShippingInformation(quote),
                    JSON.stringify(payload)
                ).done(
                    function (response) {
                        quote.setTotals(response.totals);
                        paymentService.setPaymentMethods(methodConverter(response['payment_methods']));
                        fullScreenLoader.stopLoader();
                    }
                ).fail(
                    function (response) {
                        errorProcessor.process(response);
                        fullScreenLoader.stopLoader();
                    }
                );
            }
        };
    }
);
