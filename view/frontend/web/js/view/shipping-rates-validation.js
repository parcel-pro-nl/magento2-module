/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/shipping-rates-validator',
        'Magento_Checkout/js/model/shipping-rates-validation-rules',
        'Parcelpro_Shipment/js/model/shipping-rates-validator',
        'Parcelpro_Shipment/js/model/shipping-rates-validation-rules'
    ],
    function (
        Component,
        defaultShippingRatesValidator,
        defaultShippingRatesValidationRules,
        shippingRatesValidator,
        shippingRatesValidationRules
    ) {
        "use strict";
        defaultShippingRatesValidator.registerValidator('parcelpro', shippingRatesValidator);
        defaultShippingRatesValidationRules.registerRules('parcelpro', shippingRatesValidationRules);
        return Component;
    }
);
