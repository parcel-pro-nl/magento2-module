<?php
namespace Parcelpro\Shipment\Controller\Adminhtml\Shipment;

use Parcelpro\Shipment\Model\ParcelproFactory;
use Magento\Sales\Model\Order;
/**
 * Responsible for loading page content.
 *
 * This is a basic controller that only loads the corresponding layout file. It may duplicate other such
 * controllers, and thus it is considered tech debt. This code duplication will be resolved in future releases.
 */
class Printlabel extends \Magento\Backend\App\Action{

    protected $pageFactory;
    protected $scopeConfig;
    protected $_modelParcelproFactory;
    protected $orderConverter;
    protected $transactionFactory;
    protected $shipmentSender;
    protected $url = 'https://login.parcelpro.nl';

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Model\Convert\OrderFactory $convertOrderFactory,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Sales\Model\Order\Email\Sender\ShipmentSender $shipmentSender,
        ParcelproFactory $modelParcelproFactory)
    {
        $this->pageFactory = $pageFactory;
        $this->scopeConfig = $scopeConfig;
        $this->_modelParcelproFactory = $modelParcelproFactory;
        $this->orderConverter = $convertOrderFactory->create();
        $this->transactionFactory = $transactionFactory;
        $this->shipmentSender = $shipmentSender;
        parent::__construct($context);
    }

    public function execute()
    {
        $order_id = $this->getRequest()->getParam('order_id');
        if(!$order_id) $order_id = $this->getRequest()->getParam('selected');

        $message = null;
        if(is_null($order_id)){
            $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
            $collectionFactory = $objectManager->create('Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');
            $filter = $objectManager->create('Magento\Ui\Component\MassAction\Filter');

            $collection = $filter->getCollection($collectionFactory->create());

            foreach ($collection->getItems() as $order) {
               $this->printlabelAction($order->getId(), false ,null);
            }
        }else{
            if($order_id){
                if(is_array($order_id)){
                    $url = null;
                    foreach($order_id as $k => $v) {
                        $res = $this->printlabelAction($v, true, null);
                        if(strpos($res, 'error') !== false){
                            $message .= $res."<br>";
                        }else{
                            $url .= "&selected[]=".$res;
                        }
                    }
                    $message = $this->printlabelAction($v, false, $url);
                }else {
                    $message = $this->printlabelAction($order_id, false, null);
                }
            }
        }

        if($message) $this->messageManager->addNotice(__($message));
        //$this->_redirect($this->_redirect->getRefererUrl());
    }


    public function printlabelAction($orderId, $multiple=false, $url=null){
        $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->create('Magento\Sales\Model\Order')->load($orderId);
        $collection = $objectManager->create('Parcelpro\Shipment\Model\Resource\Parcelpro\CollectionFactory');
        $collection = $collection->create()->addFieldToFilter('order_id', $order->getIncrementId())->addFieldToSelect('*')->load();
        $result = $collection->getData();

        if($result && !empty($result)) {

            $result = $result[count($result) - 1];
            $labelURL = $result["label_url"];

            try {
                $this->changeState($orderId, $order);

                if($multiple){
                    return $result['zending_id'];
                }else{
                    $url = (!empty($url) ? $url : '');
                    echo "<script>";
                    echo "var win = window.open('$this->url$labelURL&PrintPdf=true$url');";
                    echo "if(!win){ this.location = ('$this->url$labelURL&PrintPdf=true$url') }";
                    echo "var timer = setInterval(function() {";
                    echo "if(!win || win.closed) { ";
                    echo "clearInterval(timer);";
                    echo "location.href = '" . $this->_redirect->getRefererUrl() . "'";
                    echo "}";
                    echo "}, 1500);";
                    echo "</script>";
                }
            } catch (Exception $ex) {
                return sprintf("Order %s geeft een error", $orderId);
            }
        }else{
            return sprintf("Order %s bevat geen labelurl, onjuist aangemaakt bij ParcelPro", $orderId);
        }
    }

    public function changeState($orderId, $order){
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->create('\Magento\Sales\Model\Order') ->load($orderId);

        $config = $this->scopeConfig->getValue('carriers/parcelpro', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $order->getStoreId());

        if($config["afdrukken_status"]) {
            $state = $this->defineOrderStateConstant($config["afdrukken_status"]);
            $order->setState($state)->setStatus($config["afdrukken_status"]);
            $order->save();
        }
    }

    protected function defineOrderStateConstant($status){
        switch($status){
            case 'new':
                return Order::STATE_NEW;
                break;
            case 'payment_review':
                return Order::STATE_PAYMENT_REVIEW;
                break;
            case 'pending':
                return Order::STATE_PENDING_PAYMENT;
                break;
            case 'processing':
                return Order::STATE_PROCESSING;
                break;
            case 'complete':
                return Order::STATE_COMPLETE;
                break;
            case 'closed':
                return Order::STATE_CLOSED;
                break;
            case 'canceled':
                return Order::STATE_CANCELED;
                break;
            case 'holded':
                return Order::STATE_HOLDED;
                break;
        }
    }
}
