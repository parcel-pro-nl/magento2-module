<?php

namespace Parcelpro\Shipment\Controller\Adminhtml\Order;


use Parcelpro\Shipment\Model\ParcelproFactory;
/**
 * Responsible for loading page content.
 *
 * This is a basic controller that only loads the corresponding layout file. It may duplicate other such
 * controllers, and thus it is considered tech debt. This code duplication will be resolved in future releases.
 */
class UpdateOrder extends \Magento\Backend\App\Action {

    protected $pageFactory;
    protected $scopeConfig;
    protected $_modelParcelproFactory;
    protected $url = 'https://login.parcelpro.nl';

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        ParcelproFactory $modelParcelproFactory
    )
    {
        $this->pageFactory = $pageFactory;
        $this->scopeConfig = $scopeConfig;
        $this->_modelParcelproFactory = $modelParcelproFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $post = $this->getRequest()->getPostValue();
        $data = $post["pp"];

        $order = $objectManager->create('Magento\Sales\Model\Order')->load($data["order_id"]);

        $collection = $objectManager->create('Parcelpro\Shipment\Model\Resource\Parcelpro\CollectionFactory');
        $collection = $collection->create()->addFieldToFilter('order_id', $order->getIncrementId())->addFieldToSelect('*')->load();
        $pp_result = $collection->getData();
        if($pp_result)
            $pp_result = $pp_result[count($pp_result)-1];

        // Controleren of de zending al is aangemeld.
        if($pp_result && $pp_result["barcode"] != ""){
            $this->messageManager->addErrorMessage(__("Actie niet mogelijk: zending al aangemeld."));
            $this->_redirect($this->_redirect->getRefererUrl());
            return;
        }

        if(in_array(strtolower($order->getState()), array("complete","closed", "canceled") ) ){
            $this->messageManager->addErrorMessage(__("Actie niet mogelijk: zending al verwerkt."));
            $this->_redirect($this->_redirect->getRefererUrl());
            return;
        }

        if($data) {

            $shipping_description = null;
            $carrier = null;
            if($data["verzendmethode"]!="") {
                // Verzendmethode opslaan in de order.
                $shipping_option = $this->scopeConfig->getValue(
                    sprintf('carriers/parcelpro/%s',$data["verzendmethode"]),
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $order->getStoreId());

                if(!$shipping_option || $shipping_option == "[]" ){
                    $parcelpro = $objectManager->create('\Parcelpro\Shipment\Model\Carrier\Parcelpro');
                    foreach($parcelpro->getAllowedMethods() as $k => $v){
                        $parts = explode("_", $k);
                        $carrier = $parts[0];
                        if($k == $data["verzendmethode"]) $shipping_description = ucfirst($carrier). ' ' .$v;
                    }
                }else{
                    $serialize = $objectManager->create('Magento\Framework\Serialize\Serializer\Json');
                    $serialized = $serialize->unserialize($shipping_option);
                    $shipping_description = $serialized[key($serialized)]["titel"];
                }

                $order->setShippingMethod("parcelpro_".$data["verzendmethode"]);
                $order->setShippingDescription($shipping_description);
                $order->save();
            }


            // Aantal pakketten opslaan in de parcelpro_shipment tabel
            if($data["aantalPakketten"]){
                $data = array_merge($pp_result,array('order_id' => $order->getIncrementId(), 'carrier' => ($carrier ? $carrier : ''), 'aantal_pakketten' => $data["aantalPakketten"]));
                $parcelproModel = $this->_modelParcelproFactory->create();
                $parcelproModel->setData($data);
                $parcelproModel->save();
            }
            $this->messageManager->addSuccessMessage(__("Instellingen opgeslagen"));
            $this->_redirect($this->_redirect->getRefererUrl());
        }
    }
}
