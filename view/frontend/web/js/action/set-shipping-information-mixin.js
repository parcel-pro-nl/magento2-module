/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*global define,alert*/
define(
    [
        'Magento_Checkout/js/model/quote',
        'mage/utils/wrapper',
        'Parcelpro_Shipment/js/model/shipping-save-processor'
    ],
    function (quote, wrapper, shippingSaveProcessor) {
        'use strict';

        return function (setShippingInformationAction) {

            return wrapper.wrap(setShippingInformationAction, function (original) {
                return shippingSaveProcessor.saveShippingInformation(quote.shippingAddress()?.getType());
            });
        };
    }
);
