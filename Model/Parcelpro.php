<?php

namespace Parcelpro\Shipment\Model;

use Magento\Framework\Model\AbstractModel;

class Parcelpro extends AbstractModel
{
    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init('Parcelpro\Shipment\Model\Resource\Parcelpro');
    }
}
