<?php

namespace Svea\Maksuturva\Setup;

use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;

class UpgradeSchema implements \Magento\Framework\Setup\UpgradeSchemaInterface {

    const TABLE_ORDER_PAYMENT = 'sales_order_payment';
    const COLUMN_MAKSUTURVA_PMT_ID = 'maksuturva_pmt_id';

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

        if (version_compare($context->getVersion(), '1.0.3', '<')) {
            $this->addMaksuturvaIdForPayment($setup);
        }


        if (\version_compare($context->getVersion(), '1.0.4') < 0) {
            $this->version104($setup);
        }

        $setup->endSetup();
    }

    /**
     * Add maksuturva_pmt_id for sales_order_payment
     *
     * @param SchemaSetupInterface $setup
     */
    protected function addMaksuturvaIdForPayment($setup)
    {
        $connection = $setup->getConnection();

        $paymentTable = $setup->getTable(self::TABLE_ORDER_PAYMENT);
        if (!$connection->tableColumnExists($paymentTable, self::COLUMN_MAKSUTURVA_PMT_ID)) {
            $connection->addColumn(
                $paymentTable,
                self::COLUMN_MAKSUTURVA_PMT_ID,
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'Maksuturva payment id'
                ]
            );

            $setup->getConnection()->addIndex(
                $setup->getTable(self::TABLE_ORDER_PAYMENT),
                $setup->getIdxName($setup->getTable(self::TABLE_ORDER_PAYMENT), [self::COLUMN_MAKSUTURVA_PMT_ID]),
                self::COLUMN_MAKSUTURVA_PMT_ID,
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX
            );

        }
    }


    /**
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup
     */
    public function version104(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $tables = ['quote', 'quote_address', 'sales_order'];

        foreach ($tables as $table) {
            $connection->addColumn(
                $setup->getTable($table),
                'handling_fee',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    'length' => '12,4',
                    'default' => null,
                    'nullable' => true,
                    'comment' => 'Handling Fee Amount',
                ]
            );
            $connection->addColumn(
                $setup->getTable($table),
                'base_handling_fee',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    'length' => '12,4',
                    'default' => null,
                    'nullable' => true,
                    'comment' => 'Base Handling Fee Amount',
                ]
            );
        }

        $connection->addColumn(
            $setup->getTable('sales_order'),
            'refunded_handling_fee',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'length' => '12,4',
                'default' => null,
                'nullable' => true,
                'comment' => 'Refunded Handling Fee',
            ]
        );
    }


}