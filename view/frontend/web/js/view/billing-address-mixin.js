/**
 * Billing address view mixin for store flag is billing form in edit mode (visible)
 */
define([
    'Magento_Checkout/js/model/quote',
], function (quote) {
    'use strict';

    return function (billingAddress) {
        return billingAddress.extend({
            initObservable: function () {
                this._super();

                if (this.isAddressSameAsShipping() ) {
                    if(quote.shippingMethod().method_code != 'postnl_pakjegemak' && quote.shippingMethod().method_code != 'dhl_parcelshop' && quote.shippingMethod().method_code != "dpd_parcelshop" && quote.shippingMethod().method_code != "intrapost_parcelshop"){
                        selectBillingAddress(quote.billingAddress());
                        this.updateAddresses();
                        this.isAddressDetailsVisible(true);
                    }
                    this.isAddressDetailsVisible(false);
                } else {
                    this.isAddressDetailsVisible(false);
                }

                return this;
            }
        });
    };
});
