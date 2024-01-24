<?php

namespace Parcelpro\Shipment\Model\Resource\Parcelpro;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(
            'Parcelpro\Shipment\Model\Parcelpro',
            'Parcelpro\Shipment\Model\Resource\Parcelpro'
        );
    }
}
