<?php
namespace Parcelpro\Shipment\Observer;

use Magento\Framework\Event\ObserverInterface;
use Parcelpro\Shipment\Model\ParcelproFactory;

class Trackinfo implements ObserverInterface {

    /** @var \Magento\Framework\Logger\Monolog */
    protected $logger;
    protected $scopeConfig;
    protected $_modelParcelproFactory;

    public function __construct(
        \Psr\Log\LoggerInterface $loggerInterface,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        ParcelproFactory $modelParcelproFactory
    ) {
        $this->logger = $loggerInterface;
        $this->scopeConfig = $scopeConfig;
        $this->_modelParcelproFactory = $modelParcelproFactory;
    }

    public function execute( \Magento\Framework\Event\Observer $observer ) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $shipment = $observer->getEvent()->getShipment();
        $order = $shipment->getOrder();
        $order->getIncrementId();
        $order_id = $order->getIncrementId();

        $collection = $objectManager->create('Parcelpro\Shipment\Model\Resource\Parcelpro\CollectionFactory');
        $collection = $collection->create()->addFieldToFilter('order_id', $order_id)->getFirstItem();

        $result = $collection->getData();

        if($result && !is_null($result['barcode'])){
            $shipment->setZendingId($result['zending_id']);

            $track = $objectManager->create('Magento\Sales\Model\Order\Shipment\Track');
            $track->setNumber($result['barcode'])
                ->setCarrierCode('custom')
                ->setTitle($result['carrier']);

            $track->setDescription($result['url']);

            ($result) ? $shipment->addTrack($track) : null;
        }
    }
}
