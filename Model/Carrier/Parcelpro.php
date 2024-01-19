<?php

namespace Parcelpro\Shipment\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Rate\Result;

class Parcelpro extends \Magento\Shipping\Model\Carrier\AbstractCarrier implements
    \Magento\Shipping\Model\Carrier\CarrierInterface
{
    /**
     * @var string
     */
    protected $_code = 'parcelpro';

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Magento\Framework\Serialize\Serializer\Json $serialize,
        array $data = []
    ) {
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        $this->serialize = $serialize;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * @return array
     */
    public function getAllowedMethods()
    {
        return [
            'postnl_afleveradres' => 'Afleveradres',
            'postnl_pakjegemak' => 'PakjeGemak',
            'postnl_nbb' => 'Alleen Huisadres',
            'postnl_hvo' => 'Handtekening',
            'postnl_or' => 'Onder Rembours',
            'postnl_vb' => 'Verzekerd bedrag',
            'postnl_bp' => 'Brievenbuspakje',
            'postnl_pricerule' => 'Pricerule',
            'dhl_afleveradres' => 'Afleveradres',
            'dhl_parcelshop' => 'Parcelshop',
            'dhl_nbb' => 'Niet bij buren',
            'dhl_hvo' => 'Handtekening',
            'dhl_ez' => 'Extra zeker',
            'dhl_eve' => 'Avondlevering',
            'dhl_bp' => 'Brievenbuspakje',
            'dhl_pricerule' => 'Pricerule',
            'vsp_bp' => 'Brievenbuspakje',
            'sameday_dc' => 'Sameday',
            'dpd_b2c' => 'Afleveradres (woonadres)',
            'dpd_b2b' => 'Afleveradres (zakelijk)',
            'dpd_parcelshop' => 'Parcel Shop',
            'intrapost_parcelshop' => 'Parcelshop',
            'custom_pricerule' => 'Pricerule'
        ];
    }

    protected function _rateresult($key, $value)
    {
        $rate = $this->_rateMethodFactory->create();
        $rate->setCarrier($this->_code);

        $matches = explode('_', $key);
        if ($matches[0] === 'dhl') {
            $rate->setCarrierTitle($this->getConfigData('dhl_title'));
        }
        if ($matches[0] === 'postnl') {
            $rate->setCarrierTitle($this->getConfigData('postnl_title'));
        }
        if ($matches[0] === 'vsp') {
            $rate->setCarrierTitle($this->getConfigData('vsp_title'));
        }
        if ($matches[0] === 'sameday') {
            $rate->setCarrierTitle($this->getConfigData('sameday_title'));
        }
        if ($matches[0] === 'intrapost') {
            $rate->setCarrierTitle($this->getConfigData('intrapost_title'));
        }

        $rate->setMethod($key);
        $rate->setMethodTitle($value);

        $price = (float)$this->getConfigData($key);

        $rate->setPrice($price);
        $rate->setCost();
        return $rate;
    }

    /**
     * @param RateRequest $request
     * @return bool|Result
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        $result = $this->_rateResultFactory->create();
        $am = $this->getAllowedMethods();
        foreach ($am as $key => $_) {
            if ($this->getConfigData($key)) {
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $state = $objectManager->get('\Magento\Framework\App\State');
                $_pricIncl = $this->getConfigData('price_incl');
                if ($state->getAreaCode() == \Magento\Framework\App\Area::AREA_ADMINHTML) {
                    $object = $objectManager->create('\Magento\Sales\Model\AdminOrder\Create');
                    $total = $object->getQuote()->getSubtotal();
                    $grandTotal = $object->getQuote()->getGrandTotal();
                } else {
                    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                    $total = $objectManager->create('\Magento\Checkout\Model\Session')
                        ->getQuote()->getSubtotal();

                    $grandTotal = $objectManager->create('\Magento\Checkout\Model\Session')
                        ->getQuote()->getGrandTotal();
                }

                if ($_pricIncl) {
                    $total = $grandTotal; // Verzendkosten berekenen op basis van bedrag incl. BTW
                }

                $countryId = $request->getDestCountryId();
                $weight = $request->getPackageWeight();
                $shippingPrice = false;

                $configData = $this->getConfigData($key);
                if (is_string($configData)) {
                    $pricerules = $this->serialize->unserialize($configData);
                }

                if (!empty($pricerules)) {
                    foreach ($pricerules as $pricerule) {
                        if ($pricerule['country'] != $countryId) {
                            continue;
                        }

                        if (($weight >= (float)$pricerule['min_weight']) && ($weight <= (float)$pricerule['max_weight']) && ($total >= (float)$pricerule['min_total']) && ($total <= (float)$pricerule['max_total'])) {
                            if (is_null($pricerule['btw_tarief'])) {
                                $pricerule['btw_tarief'] = 0;
                            }
                            $shippingPrice = ( (float)$pricerule['btw_tarief'] ? ( (float)$pricerule['price'] + ((float)$pricerule['price'] / 100 ) * (float)$pricerule['btw_tarief'] ) : (float)$pricerule['price'] );
                            break;
                        }
                    }

                    if ($shippingPrice !== false && $key != "custom_pricerule") {
                        $method = $this->_rateMethodFactory->create();

                        $method->setCarrier($this->_code);

                        if (strpos(strtolower($key), 'postnl') !== false) {
                            $method->setCarrierTitle('PostNL');
                        } elseif (strpos(strtolower($key), 'dhl') !== false) {
                            $method->setCarrierTitle('DHL');
                        } elseif (strpos(strtolower($key), 'vsp') !== false) {
                            $method->setCarrierTitle('Van Straaten Post');
                        } elseif (strpos(strtolower($key), 'sameday') !== false) {
                            $method->setCarrierTitle('Sameday');
                        } elseif (strpos(strtolower($key), 'intrapost') !== false) {
                            $method->setCarrierTitle('Intrapost');
                        }
                        $method->setMethod($key);
                        $method->setMethodTitle($pricerule['titel']);
                        $method->setPrice($request->getFreeShipping() === true ? 0 : $shippingPrice);
                        $method->setCost($request->getFreeShipping() === true ? 0 : $shippingPrice);
                        $result->append($method);
                    }

                    if ($key == "custom_pricerule") {
                        $counter = 0;
                        foreach ($pricerules as $pricerule) {
                            if ($pricerule['country'] != $countryId) {
                                continue;
                            }

                            if (($weight >= (float)$pricerule['min_weight']) && ($weight <= (float)$pricerule['max_weight']) && ($total >= (float)$pricerule['min_total']) && ($total <= (float)$pricerule['max_total'])) {
                                if (is_null($pricerule['btw_tarief'])) {
                                    $pricerule['btw_tarief'] = 0;
                                }
                                $shippingPrice = ( (float)$pricerule['btw_tarief'] ? ( (float)$pricerule['price'] + ((float)$pricerule['price'] / 100 ) * (float)$pricerule['btw_tarief'] ) : (float)$pricerule['price'] );

                                if ($shippingPrice !== false) {
                                    $method = $this->_rateMethodFactory->create();

                                    $method->setCarrier($this->_code);
                                    $method->setCarrierTitle($pricerule['carrier']);

                                    $method->setMethod($key . "_" . $counter);
                                    $method->setMethodTitle($pricerule['titel']);
                                    $method->setPrice($request->getFreeShipping() === true ? 0 : $shippingPrice);
                                    $method->setCost($request->getFreeShipping() === true ? 0 : $shippingPrice);
                                    $result->append($method);
                                }
                            }
                            if ($key == "custom_pricerule") {
                                $counter++;
                            }
                        }
                    }
                }
            }
        }
        return $result;
    }
}
