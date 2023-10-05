define(
    [
        'ko',
        'jquery',
        'uiElement',
        'uiRegistry',
        'Magento_Checkout/js/model/quote',
        'Amasty_Checkout/js/model/payment/payment-loading',
        'Amasty_Checkout/js/action/start-place-order',
        'Amasty_Checkout/js/model/amalert',
        'Amasty_Checkout/js/action/focus-first-error',
        'Amasty_Checkout/js/model/payment-validators/login-form-validator',
        'Amasty_Checkout/js/model/address-form-state',
        'Amasty_Checkout/js/model/one-step-layout',
        'Parcelpro_Shipment/js/action/set-shipping-information',
        'Magento_Ui/js/lib/knockout/extender/bound-nodes',
        'Magento_Ui/js/lib/view/utils/dom-observer',
        'Magento_Ui/js/lib/view/utils/async',
        'mage/translate'
    ],
    function (
        ko,
        $,
        Component,
        registry,
        quote,
        paymentLoader,
        startPlaceOrderAction,
        alert,
        focusFirstError,
        loginFormValidator,
        addressFormState,
        oneStepLayout,
        setShippingInformationAction,
        boundNodes,
        domObserver
    ) {
        'use strict';

        return function (PlaceButton) {
            return PlaceButton.extend({

              placeOrder: function () {

                var errorMessage = '';

                if (!quote.paymentMethod()) {
                    errorMessage = $.mage.__('No payment method selected');
                    alert({ content: errorMessage });

                    return;
                }

                if (!quote.shippingMethod() && !quote.isVirtual()) {
                    errorMessage = $.mage.__('No shipping method selected');
                    alert({ content: errorMessage });

                    return;
                }

                setShippingInformationAction().done(
                    function() {
                        startPlaceOrderAction();
                    }
                );



              }

            });
        }



    }
);
