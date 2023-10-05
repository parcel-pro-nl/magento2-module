<?php
namespace Parcelpro\Shipment\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements  UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup,
                            ModuleContextInterface $context){
        $setup->startSetup();

        // Get module table
        $tableName = $setup->getTable('parcelpro_shipment');

        // Check if the table already exists
        if ($setup->getConnection()->isTableExists($tableName) == true) {

            $connection = $setup->getConnection();
            $connection->addColumn($tableName, "aantal_pakketten", [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'length' => 11,
                'comment' => 'aantal pakketten',
            ]);

        }

        $setup->endSetup();
    }
}