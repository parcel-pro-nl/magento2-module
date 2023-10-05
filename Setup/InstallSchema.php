<?php

namespace Parcelpro\Shipment\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $table = $setup->getConnection()->newTable(
            $setup->getTable('parcelpro_shipment')
        )->addColumn(
            'id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            11,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Id'
        )->addColumn(
            'order_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Order Id'
        )->addColumn(
            'zending_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            255,
            ['unsigned' => true, 'nullable' => false],
            'Zending Id'
        )->addColumn(
            'barcode',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Barcode'
        )->addColumn(
            'carrier',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Carrier'
        )->addColumn(
            'url',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Url'
        )->addColumn(
            'label_url',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Label url'
        );
        $setup->getConnection()->createTable($table);

        $setup->getConnection()->addColumn(
            $setup->getTable('sales_shipment'),
            'zending_id',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'length'    => 24,
                'unsigned' => true,
                'comment' => 'Zending ID van Verzendsoftware'
            ]
        );

        $setup->endSetup();
    }
}