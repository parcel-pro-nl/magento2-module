<?php

namespace Parcelpro\Shipment\Controller\Adminhtml\Shipment;

use Magento\Framework\Setup\Exception;
use Parcelpro\Shipment\Model\ParcelproFactory;

/**
 * Responsible for loading page content.
 *
 * This is a basic controller that only loads the corresponding layout file. It may duplicate other such
 * controllers, and thus it is considered tech debt. This code duplication will be resolved in future releases.
 */
class Index extends \Magento\Backend\App\Action
{
    protected $pageFactory;
    protected $scopeConfig;
    protected $_modelParcelproFactory;
    protected $serialize;
    protected $url = 'https://login.parcelpro.nl';

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        ParcelproFactory $modelParcelproFactory,
        \Magento\Framework\Serialize\Serializer\Json $serialize
    ) {
        $this->pageFactory = $pageFactory;
        $this->scopeConfig = $scopeConfig;
        $this->_modelParcelproFactory = $modelParcelproFactory;
        $this->serialize = $serialize;
        parent::__construct($context);
    }

    public function execute()
    {
        $order_id = $this->getRequest()->getParam('order_id');
        if (!$order_id) {
            $order_id = $this->getRequest()->getParam('selected');
        }

        $message = null;
        if (is_null($order_id)) {
            $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
            $collectionFactory = $objectManager->create('Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');
            $filter = $objectManager->create('Magento\Ui\Component\MassAction\Filter');

            $pp_collection = $objectManager->create('Parcelpro\Shipment\Model\Resource\Parcelpro\CollectionFactory');
            $result = $pp_collection->create()->getColumnValues('order_id');

            $collection = $filter->getCollection($collectionFactory->create());

            foreach ($collection->getItems() as $order) {
                if (!in_array($order->getId(), $result)) {
                    $message = $this->postToParcelPro($order->getId());
                }
            }
        } else {
            if ($order_id) {
                if (is_array($order_id)) {
                    foreach ($order_id as $k => $v) {
                        $message .= $this->postToParcelPro($v);
                        $message .= "<br>";
                    }
                } else {
                    $message = $this->postToParcelPro($order_id);
                }
            }
        }

        if (!$message) {
            $this->messageManager->addError(__('Er is een fout opgetreden, probeer opnieuw'));
        }
        if ($message) {
            $this->messageManager->addNotice(__($message));
        }
        $this->_redirect($this->_redirect->getRefererUrl());
    }


    public function postToParcelPro($order_id)
    {
        try {
            $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
            $order = $objectManager->create('Magento\Sales\Model\Order')->load($order_id);
            $order_id = $order->getIncrementId();
            $data = $order->getData();

            $config = $this->scopeConfig->getValue('carriers/parcelpro', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $order->getStoreId());

            $collection = $objectManager->create('Parcelpro\Shipment\Model\Resource\Parcelpro\CollectionFactory');
            $collection = $collection->create()->addFieldToFilter('order_id', $order->getIncrementId())->getFirstItem();
            $pp_result = $collection->getData();

            $shipping_method = $this->getShippingMethod($order->getShippingMethod(), $order);

            if ($shipping_method) {
                $data["custom_shipping_method"] = $shipping_method;
            }

            $parts = explode('_', $data["shipping_method"]);
            $parts[count($parts) - 1] = "title";
            if ($parts) {
                $shipping_title = str_replace('parcelpro_', '', implode('_', $parts));
            }

            $data['created_at'] = date('Y-m-d H:i:s');
            $data["billing_address"] = $order->getBillingAddress()->getData();

            $totalOrders = 0;
            $totalOrdersVirtual = 0;
            foreach ($order->getAllItems() as $orderItem) {
                $totalOrders++;
                if ($orderItem->getProduct()->getIsVirtual()) {
                    $totalOrdersVirtual++;
                }
            }

            if ($totalOrders == $totalOrdersVirtual) {
                return true;
            }

            $data["shipping_address"] = $order->getShippingAddress()->getData();
            $data["aantal_pakketten"] = $pp_result && $pp_result["aantal_pakketten"] ? $pp_result["aantal_pakketten"] : 1;

            $json = json_encode($data);

            $config = $this->scopeConfig->getValue('carriers/parcelpro', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $order->getStoreId());
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
                throw new \Magento\Framework\Exception\LocalizedException(__(sprintf("De zending is niet succesvol aangemeld bij ParcelPro foutcode: %s, melding: %s", $response_code, $response_body['omschrijving']), 10));
            }

            if (isset($response_body['Barcode'])) {
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
                    } else {
                        $carrier = $response_body['Carrier'];
                    }
                }

                $data = ['zending_id' => $response_body['Id'], 'order_id' => $order_id, 'barcode' => $response_body['Barcode'], 'carrier' => $carrier, 'url' => $response_body['TrackingUrl'], 'label_url' => $response_body['LabelUrl']];


                $collection = $objectManager->create('Parcelpro\Shipment\Model\Resource\Parcelpro\CollectionFactory');
                $collection = $collection->create()->addFieldToFilter('order_id', $order_id)->getFirstItem();

                $result = $collection->getData();
                if ($result && isset($result['id'])) {
                    $data['id'] = $result['id'];
                }

                $parcelproModel = $this->_modelParcelproFactory->create();
                $parcelproModel->setData($data);
                $parcelproModel->save();

                return sprintf("Order %s succesvol aangemaakt", $order_id);
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return sprintf("Order %s geeft een error", $order_id);
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
