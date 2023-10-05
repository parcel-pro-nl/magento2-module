<?php

namespace Parcelpro\Shipment\Block\Adminhtml\Order\View\Tab;


class Custom extends \Magento\Backend\Block\Template implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * Template
     *
     * @var string
     */
    protected $_template = 'order/view/tab/custom.phtml';

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry = null;

    protected $statusCollectionFactory;

    protected $_shippingConfig;

    protected $_storeManager;

    protected $_parcelpro;

    protected $_modelParcelproFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory $statusCollectionFactory,
        \Magento\Shipping\Model\Config $shippingConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Parcelpro\Shipment\Model\Carrier\Parcelpro $parcelpro,
        \Parcelpro\Shipment\Model\ParcelproFactory $modelParcelproFactory,
        \Magento\Framework\Serialize\Serializer\Json $serialize,
        array $data = []
    ) {
        $this->statusCollectionFactory = $statusCollectionFactory;
        $this->coreRegistry = $registry;
        $this->_shippingConfig = $shippingConfig;
        $this->_storeManager = $storeManager;
        $this->_parcelpro = $parcelpro;
        $this->_modelParcelproFactory = $modelParcelproFactory;
        $this->serialize = $serialize;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve order model instance
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->coreRegistry->registry('current_order');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Parcel Pro');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Parcel Pro');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        // For me, I wanted this tab to always show
        // You can play around with the ACL settings
        // to selectively show later if you want
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        // For me, I wanted this tab to always show
        // You can play around with conditions to
        // show the tab later
        return false;
    }

    /**
     * Get Tab Class
     *
     * @return string
     */
    public function getTabClass()
    {
        // I wanted mine to load via AJAX when it's selected
        // That's what this does
        return 'ajax only';
    }

    /**
     * Get Class
     *
     * @return string
     */
    public function getClass()
    {
        return $this->getTabClass();
    }

    /**
     * Get Tab Url
     *
     * @return string
     */
    public function getTabUrl()
    {
        return $this->getUrl('pp_shipment/order/customTab', ['_current' => true]);
    }

    public function getSaveUrl(){
        return $this->getUrl('pp_shipment/order/updateOrder', ['_current' => true]);
    }

    public function currentValues(){
        $orderId = $this->getRequest()->getParam('order_id');
        $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->create('Magento\Sales\Model\Order')->load($orderId);

        $data = array();
        $data["order_id"] = $orderId;
        if($order->getShippingMethod()){
            $data["custom_shipping_method"] = str_replace("parcelpro_", "", $order->getShippingMethod());
        }

        $collection = $objectManager->create('Parcelpro\Shipment\Model\Resource\Parcelpro\CollectionFactory');
        $collection = $collection->create()->addFieldToFilter('order_id', $order->getIncrementId())->getFirstItem();
        $result = $collection->getData();

        $data["aantal_pakketten"] = 1;
        $data["reeds_aangemeld"] = false;
        if($result){
            $data["aantal_pakketten"] = $result["aantal_pakketten"];
            $data["reeds_aangemeld"] = true;
        }

        return $data;
    }

    public function shippingOptions(){
        $store = $this->_storeManager->getStore()->getId();
        $carriers = [];

        foreach($this->_parcelpro->getAllowedMethods() as $k => $v){
            if(!$this->_parcelpro->getConfigData($k) || $this->_parcelpro->getConfigData($k) == "[]")continue;
            $parts = explode("_", $k);
            if(strtolower($v) == 'pricerule') $v = 'Buitenland';

            $carriers[$k] = ucfirst($parts[0]). ' ' .$v;
        }

        $pricerules = $this->_scopeConfig->getValue(
            'carriers/parcelpro/custom_pricerule',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store);
        // Custom rules
        if($pricerules){
            $counter = 0;
            $pricerules = $this->serialize->unserialize($pricerules);
            foreach ($pricerules as $pricerule) {
                $carriers["custom_pricerule_".$counter] = $pricerule["titel"];
                $counter++;
            }
            unset($carriers["custom_pricerule"]);
        }

        return $carriers;
    }
}
