<?php

namespace Piimega\Maksuturva\Setup;

use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;

class UpgradeSchema implements \Magento\Framework\Setup\UpgradeSchemaInterface{

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context){
        ini_set('display_errors', 1);
        $setup->startSetup();
        if (version_compare($context->getVersion(), '1.0.1') < 0) {
            $fileTable = $setup->getConnection()
                ->newTable($setup->getTable('maksuturva_payment_method'))
                ->addColumn('code', Table::TYPE_TEXT, 255, array(
                    'nullable' => false,
                    'unique' => true,
                    'primary' => true
                ), 'Maksuturva Payment Method Code')
                ->addColumn('displayname', Table::TYPE_TEXT, 255, array(
                    'nullable' => false
                ), 'Displayname')
                ->addColumn('imageurl', Table::TYPE_TEXT, 255, array(
                    'nullable' => false
                ), 'Imageurl');

            $setup->getConnection()->createTable($fileTable);
        }
        $setup->endSetup();
    }
}