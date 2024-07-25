<?php

namespace Parcelpro\Shipment\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Parcelpro\Shipment\Model\ParcelproFactory;

class Shipment implements ObserverInterface
{
    /** @var \Magento\Framework\Logger\Monolog */
    protected $logger;
    protected $scopeConfig;
    protected $_modelParcelproFactory;
    protected $serialize;
    protected $url = 'https://login.parcelpro.nl';

    public function __construct(
        \Psr\Log\LoggerInterface $loggerInterface,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        ParcelproFactory $modelParcelproFactory,
        \Magento\Framework\Serialize\Serializer\Json $serialize
    ) {
        $this->logger = $loggerInterface;
        $this->scopeConfig = $scopeConfig;
        $this->_modelParcelproFactory = $modelParcelproFactory;
        $this->serialize = $serialize;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();

            $order = $observer->getEvent()->getOrder();
            if (!$order) {
                $shipment = $observer->getEvent()->getShipment();
                $order = $shipment->getOrder();
                $order->getState();
            }

            $config = $this->scopeConfig->getValue('carriers/parcelpro', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $order->getStoreId());

            if ($config["auto"]) {
                $collection = $objectManager->create('Parcelpro\Shipment\Model\Resource\Parcelpro\CollectionFactory');
                $collection = $collection->create()->addFieldToFilter('order_id', $order->getIncrementId())->getFirstItem();
                $pp_result = $collection->getData();

                // Controleren of de zending al is aangemeld en de auto aanmelden functie aanstaat.
                if (!$pp_result && $order) {
                    if (strtolower($config["auto_status"]) == 'pending') {
                        $config["auto_status"] = 'new';
                    }
                    if (strtolower($order->getState()) == strtolower($config["auto_status"])) {
                        $order_id = $order->getIncrementId();
                        $data = $order->getData();

                        $shipping_method = $this->getShippingMethod($order->getShippingMethod(), $order);
                        if ($shipping_method) {
                            $data["custom_shipping_method"] = $shipping_method;
                        }

                        $parts = explode('_', $data["shipping_method"]);
                        if ($parts) {
                            $parts[count($parts) - 1] = "title";
                            $shipping_title = str_replace('parcelpro_', '', implode('_', $parts));
                        }

                        $totalOrders = 0;
                        $totalOrdersVirtual = 0;
                        foreach ($order->getAllItems() as $orderItem) {
                            $totalOrders++;
                            if ($orderItem->getProduct()->getIsVirtual()) {
                                $totalOrdersVirtual++;
                            }
                        }

                        if ($totalOrders == $totalOrdersVirtual) {
                            $this->messageManager->addError(__('De zending niet succesvol bij ParcelPro omdat de producten virtueel zijn.'));
                            return;
                        }

                        $data['created_at'] = date('Y-m-d H:i:s');
                        $data["billing_address"] = $order->getBillingAddress()->getData();
                        $data["shipping_address"] = $order->getShippingAddress()->getData();
                        $data["aantal_pakketten"] = $pp_result && $pp_result["aantal_pakketten"] ? $pp_result["aantal_pakketten"] : 1;

                        $json = json_encode($data);

                        $gebruikerid = $config["gebruiker_id"];
                        $apikey = $config["api_key"];

                        $referer = $objectManager->get('Magento\Store\Model\StoreManagerInterface')
                            ->getStore($data['store_id'])
                            ->getBaseUrl();

                        $curl_options = [
                            CURLOPT_URL => $this->url . '/api/magento/order-created.php',
                            CURLOPT_HTTPHEADER => [
                                'Content-Type: application/json',
                                sprintf('X-Mo-Webhook-Accountid: %s', $gebruikerid),
                                sprintf('X-Mo-Webhook-Signature: %s', hash_hmac("sha256", $json, $apikey)),
                                sprintf('X-Mo-Webhook-Referer: %s', $referer),
                            ],
                            CURLOPT_CUSTOMREQUEST => "POST",
                            CURLOPT_POSTFIELDS => $json,
                        ];

                        $curl_options += [
                            CURLOPT_HEADER => false,
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_SSL_VERIFYPEER => false,
                        ];

                        $curl_handler = curl_init();

                        curl_setopt_array($curl_handler, $curl_options);

                        $response_body = curl_exec($curl_handler);
                        $response_code = curl_getinfo($curl_handler, CURLINFO_HTTP_CODE);

                        curl_close($curl_handler);

                        $response_body = json_decode($response_body, true);

                        if ($response_code != 200) {
                            throw new LocalizedException(__(sprintf("De zending is niet succesvol aangemeld bij ParcelPro foutcode: %s, melding: %s", $response_code, $response_body['omschrijving']), 10));
                        }

                        $carrier = $response_body['Carrier'];
                        $data = ['zending_id' => $response_body['Id'], 'order_id' => $order_id, 'carrier' => $carrier, 'label_url' => $response_body['LabelUrl']];

                        if (isset($response_body['Barcode']) && $response_body['Barcode']) {
                            $firstTwoCharOfBarcode = substr($response_body['Barcode'], 0, 2);
                            $carrier = false;
                            if (isset($shipping_title) && array_key_exists($shipping_title, $config)) {
                                $carrier = $config[$shipping_title];
                            }

                            if (!$carrier) {
                                if ($firstTwoCharOfBarcode === "3S") {
                                    $carrier = "PostNL via Parcel Pro";
                                } elseif ($firstTwoCharOfBarcode === "JJ") {
                                    $carrier = "DHL via Parcel Pro";
                                }
                            }

                            $data = ['zending_id' => $response_body['Id'], 'order_id' => $order_id, 'barcode' => $response_body['Barcode'], 'carrier' => $carrier, 'url' => $response_body['TrackingUrl'], 'label_url' => $response_body['LabelUrl']];
                        }

                        $parcelproModel = $this->_modelParcelproFactory->create();
                        $parcelproModel->setData($data);
                        $parcelproModel->save();
                    }
                }
            }
        } catch (LocalizedException $e) {
            $this->logger->debug($e);
        }
    }

    public function getShippingMethod($key, $order)
    {
        if (strpos($key, 'custom_pricerule') !== false) {
            $pieces = explode("parcelpro_", $key);
            $pieces = explode("custom_pricerule_", $pieces[1]);

            $config = $this->scopeConfig->getValue('carriers/parcelpro', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $order->getStoreId());
            $pricerules = $this->serialize->unserialize($config["custom_pricerule"]);

            if ($pricerules) {
                $counter = 0;
                foreach ($pricerules as $pricerule) {
                    if ($counter == $pieces[1]) {
                        return $pricerule;
                    }
                    $counter++;
                }
            }
            return null;
        }
        return null;
    }
}
