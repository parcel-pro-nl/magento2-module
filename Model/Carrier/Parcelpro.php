<?php

namespace Parcelpro\Shipment\Model\Carrier;

use DateInterval;
use DateTime;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Rate\Result;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Parcelpro extends \Magento\Shipping\Model\Carrier\AbstractCarrier implements
    \Magento\Shipping\Model\Carrier\CarrierInterface
{
    protected $_code = 'parcelpro';

    /** @var \Magento\Framework\Locale\Resolver */
    protected $localeResolver;

    private $apiUrl = 'https://login.parcelpro.nl';

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Magento\Framework\Serialize\Serializer\Json $serialize,
        \Magento\Framework\Locale\Resolver $localeResolver,
        array $data = []
    ) {
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        $this->serialize = $serialize;
        $this->localeResolver = $localeResolver;
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
            'dhl_vandaag' => 'Vandaag',
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

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        /** @var \Magento\Checkout\Model\Session $checkoutSession */
        $checkoutSession = $objectManager->create('\Magento\Checkout\Model\Session');
        $shippingAddress = $checkoutSession->getQuote()->getShippingAddress();

        $result = $this->_rateResultFactory->create();
        $am = $this->getAllowedMethods();
        foreach ($am as $key => $_) {
            if ($this->getConfigData($key)) {
                /** @var \Magento\Framework\App\State $state */
                $state = $objectManager->get('\Magento\Framework\App\State');
                $_pricIncl = $this->getConfigData('price_incl');
                if ($state->getAreaCode() == \Magento\Framework\App\Area::AREA_ADMINHTML) {
                    $object = $objectManager->create('\Magento\Sales\Model\AdminOrder\Create');
                    $total = $object->getQuote()->getSubtotal();
                    $grandTotal = $object->getQuote()->getGrandTotal();
                } else {
                    $total = $objectManager->create('\Magento\Checkout\Model\Session')
                        ->getQuote()->getSubtotal();

                    $grandTotal = $objectManager->create('\Magento\Checkout\Model\Session')
                        ->getQuote()->getGrandTotal();
                }

                $freeBoxes = $this->getFreeBoxesCount($request);

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
                            $shippingPrice = ((float)$pricerule['btw_tarief'] ? ((float)$pricerule['price'] + ((float)$pricerule['price'] / 100) * (float)$pricerule['btw_tarief']) : (float)$pricerule['price']);
                            break;
                        }
                    }

                    if ($shippingPrice !== false && $key != "custom_pricerule") {
                        $method = $this->_rateMethodFactory->create();

                        $method->setCarrier($this->_code);

                        if (strpos(strtolower($key), 'postnl') !== false) {
                            $carrierTitle = 'PostNL';

                            if ($this->getConfigData('postnl_show_expected_delivery_date')) {
                                $sendDay = new DateTime();
                                if (!$this->isBeforeLastShippingTime($this->getConfigData('postnl_last_shipping_time'))) {
                                    $sendDay->add(new DateInterval('P1D'));
                                }

                                $deliveryDate = $this->getDeliveryDate(
                                    'PostNL',
                                    $sendDay,
                                    $shippingAddress->getPostcode()
                                );

                                if ($deliveryDate) {
                                    $carrierTitle .= ' (' . $this->formatDeliveryDate($deliveryDate) . ')';
                                }
                            }
                            $method->setCarrierTitle($carrierTitle);
                        } elseif (strpos(strtolower($key), 'dhl') !== false) {
                            $carrierTitle = 'DHL';

                            if ($this->getConfigData('dhl_show_expected_delivery_date')) {
                                $sendDay = new DateTime();
                                if (!$this->isBeforeLastShippingTime($this->getConfigData('dhl_last_shipping_time'))) {
                                    $sendDay->add(new DateInterval('P1D'));
                                }

                                $deliveryDate = $this->getDeliveryDate(
                                    'DHL',
                                    $sendDay,
                                    $shippingAddress->getPostcode()
                                );

                                if ($deliveryDate) {
                                    $carrierTitle .= ' (' . $this->formatDeliveryDate($deliveryDate) . ')';
                                }
                            }
                            $method->setCarrierTitle($carrierTitle);
                        } elseif (strpos(strtolower($key), 'vsp') !== false) {
                            $method->setCarrierTitle('Van Straaten Post');
                        } elseif (strpos(strtolower($key), 'sameday') !== false) {
                            $method->setCarrierTitle('Sameday');
                        } elseif (strpos(strtolower($key), 'intrapost') !== false) {
                            $method->setCarrierTitle('Intrapost');
                        }
                        $method->setMethod($key);
                        $method->setMethodTitle($pricerule['titel']);
                        $method->setPrice($request->getFreeShipping() === true || $request->getPackageQty() == $freeBoxes ? 0 : $shippingPrice);
                        $method->setCost($request->getFreeShipping() === true || $request->getPackageQty() == $freeBoxes ? 0 : $shippingPrice);
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
                                $shippingPrice = ((float)$pricerule['btw_tarief'] ? ((float)$pricerule['price'] + ((float)$pricerule['price'] / 100) * (float)$pricerule['btw_tarief']) : (float)$pricerule['price']);

                                if ($shippingPrice !== false) {
                                    $method = $this->_rateMethodFactory->create();

                                    $method->setCarrier($this->_code);
                                    $method->setCarrierTitle($pricerule['carrier']);

                                    $method->setMethod($key . "_" . $counter);
                                    $method->setMethodTitle($pricerule['titel']);
                                    $method->setPrice($request->getFreeShipping() === true || $request->getPackageQty() == $freeBoxes ? 0 : $shippingPrice);
                                    $method->setCost($request->getFreeShipping() === true || $request->getPackageQty() == $freeBoxes ? 0 : $shippingPrice);
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

    private function getDeliveryDate(string $carrier, \DateTimeInterface $dateTime, $postcode)
    {
        if (!$postcode) {
            return false;
        }

        $date = $dateTime->format('Y-m-d');
        $userId = $this->getConfigData('gebruiker_id');
        $apiKey = $this->getConfigData('api_key');

        $query = http_build_query([
            'Startdatum' => $date,
            'Postcode' => $postcode,
            'GebruikerId' => $userId,
            'Map' => true,
        ]);

        $curlHandle = curl_init();
        curl_setopt_array($curlHandle, [
            CURLOPT_URL => $this->apiUrl . '/api/v3/timeframes.php?' . $query,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Digest: ' . hash_hmac(
                    "sha256",
                    sprintf('GebruikerId=%sPostcode=%sStartdatum=%s', $userId, $postcode, $date),
                    $apiKey
                ),
            ],
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
        ]);

        $responseBody = curl_exec($curlHandle);
        $responseCode = curl_getinfo($curlHandle, CURLINFO_RESPONSE_CODE);

        curl_close($curlHandle);

        if ($responseCode !== 200) {
            $this->_logger->error(sprintf(
                'Failed to get expected delivery date, response code %s, body:\n%s',
                $responseCode,
                $responseBody
            ));
            return false;
        }

        $responseJson = json_decode($responseBody, true);
        $rawDate = $responseJson[$carrier]['Date'] ?? false;

        if (!$rawDate) {
            $this->_logger->error(sprintf(
                'Failed to get expected delivery date, body:\n%s',
                $responseBody
            ));
            return false;
        }

        return \DateTimeImmutable::createFromFormat('Y-m-d', $rawDate);
    }

    private function formatDeliveryDate(\DateTimeInterface $date)
    {
        $locale = $this->localeResolver->getLocale();
        return \IntlDateFormatter::formatObject($date, 'd MMMM', $locale);
    }

    private function isBeforeLastShippingTime($rawLastTime): bool
    {
        if (!$rawLastTime) {
            return true;
        }

        try {
            $parsed = new DateTime($rawLastTime);
        } catch (\Exception $e) {
            $this->_logger->error(sprintf(
                'Failed to parse last shipping time (%s): %s',
                $rawLastTime,
                $e->getMessage()
            ));
            return true;
        }

        $now = new DateTime();
        return $now < $parsed;
    }

    /**
     * @param RateRequest $request
     * @return int
     */
    private function getFreeBoxesCount(RateRequest $request)
    {
        $freeBoxes = 0;
        if ($request->getAllItems()) {
            foreach ($request->getAllItems() as $item) {
                if ($item->getProduct()->isVirtual() || $item->getParentItem()) {
                    continue;
                }

                if ($item->getHasChildren() && $item->isShipSeparately()) {
                    $freeBoxes += $this->getFreeBoxesCountFromChildren($item);
                } elseif ($item->getFreeShipping()) {
                    $freeBoxes += $item->getQty();
                }
            }
        }
        return $freeBoxes;
    }

    /**
     * @param mixed $item
     * @return mixed
     */
    private function getFreeBoxesCountFromChildren($item)
    {
        $freeBoxes = 0;
        foreach ($item->getChildren() as $child) {
            if ($child->getFreeShipping() && !$child->getProduct()->isVirtual()) {
                $freeBoxes += $item->getQty() * $child->getQty();
            }
        }
        return $freeBoxes;
    }
}
