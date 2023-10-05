config = {
    map: {
        '*': {
            "Amasty_Checkout/js/action/set-shipping-information":"Parcelpro_Shipment/js/action/set-shipping-information",
            "Amasty_Checkout/template/onepage/2columns.html":"Parcelpro_Shipment/template/onepage/2columns.html",
            "Amasty_Checkout/template/onepage/3columns.html":"Parcelpro_Shipment/template/onepage/3columns.html",
            // 'Magento_Checkout/js/view/billing-address': "Parcelpro_Shipment/js/view/billing-address",
            "Amasty_Checkout/template/onepage/shipping/address.html": "Parcelpro_Shipment/template/amastyonepage/shipping.html",
            "Amasty_Checkout/template/onepage/shipping/methods.html": "Parcelpro_Shipment/template/amastyonepage/methods.html"
            /*
            ** FireCheckout ondersteuning
            ** Onderstaande regel activeren zodat gekozen pakketpunt opgeslagen wordt bij de order.
            */
            //,"Magento_Checkout/js/action/set-shipping-information":"Parcelpro_Shipment/js/action/set-shipping-information"
        }
    },
    config: {
      mixins: {
        "Magento_Checkout/js/view/billing-address": {
            "Parcelpro_Shipment/js/view/billing-address-mixin": true
        },
        "Amasty_Checkout/js/action/set-shipping-information": {
          "Parcelpro_Shipment/js/action/set-shipping-information-mixin" : true
        },
        "Magento_Checkout/js/view/shipping": {
          "Parcelpro_Shipment/js/view/shipping-mixin" : true
        },
        "Amasty_Checkout/js/view/place-button": {
          "Parcelpro_Shipment/js/view/place-button-mixin" : true
        }
      }
  }
};
