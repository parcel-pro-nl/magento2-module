<?php

namespace Parcelpro\Shipment\Model\Resource;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Parcelpro extends AbstractDb
{
    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init('parcelpro_shipment', 'id');
    }
}