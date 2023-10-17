<?php

namespace Parcelpro\Shipment\Plugin;

use Parcelpro\Shipment\Model\ParcelproFactory;

class PluginBefore
{
    protected $_url = 'https://login.parcelpro.nl';

    /**
     * @SuppressWarnings("UnusedFormalParameter")
     */
    public function beforePushButtons(
        \Magento\Backend\Block\Widget\Button\Toolbar\Interceptor $subject,
        \Magento\Framework\View\Element\AbstractBlock $context,
        \Magento\Backend\Block\Widget\Button\ButtonList $buttonList
    ) {
        if (!$context instanceof \Magento\Sales\Block\Adminhtml\Order\View) {
            return [$context, $buttonList];
        }
        $request = $context->getRequest();
        if ($request->getFullActionName() == 'sales_order_view') {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

            $order_id = $request->getParams()['order_id'];
            $order = $objectManager->create('Magento\Sales\Model\Order')->load($order_id);
            $order_id = $order->getIncrementId();
            $collection = $objectManager->create('Parcelpro\Shipment\Model\Resource\Parcelpro\CollectionFactory');
            $collection = $collection->create()->addFieldToFilter('order_id', $order_id)->addFieldToSelect('*')->load();
            $result = $collection->getData();
            if ($result) {
                $result = $result[count($result) - 1];
            }

            if ($result && !is_null($result["barcode"])) {
                $buttonList->add(
                    'print_label',
                    ['label' => __('Print label'), 'onclick' => 'setLocation(\'' . $context->getUrl("pp_shipment/shipment/printlabel") . '\')', 'class' => 'save'],
                    -1
                );
            } else {
                $buttonList->add(
                    'zendingAanmelden',
                    ['label' => __('Zending aanmelden'), 'onclick' => 'setLocation(\'' . $context->getUrl("pp_shipment/shipment/index") . '\')', 'class' => 'reset'],
                    -1
                );
            }
        }
    }

    public function getLabelUrl($zendingId)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $config = $objectManager->create('Magento\Framework\App\Config');
        $config = $config->getValue('carriers/parcelpro', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $gebruikerid = $config["gebruiker_id"];
        $apikey = $config["api_key"];

        $hmacSha256 = hash_hmac("sha256", $gebruikerid . $zendingId, $apikey);

        $parameters = array(
            'GebruikerId' => $gebruikerid,
            'ZendingId' => $zendingId,
            'HmacSha256' => $hmacSha256
        );

        $queryString = http_build_query($parameters);

        $labelUrl = $this->_url . '/API/label.php?' . $queryString;

        return $labelUrl;
    }
}
