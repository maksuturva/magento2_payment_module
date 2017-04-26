<?php

namespace Piimega\Maksuturva\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\SalesSequence\Model\Builder;
use Magento\SalesSequence\Model\Config as SequenceConfig;

class InstallData implements InstallDataInterface
{

    protected $salesSetupFactory;
    protected $sequenceBuilder;
    protected $sequenceConfig;

    public function __construct(
        \Magento\Sales\Setup\SalesSetupFactory $salesSetupFactory,
        Builder $sequenceBuilder,
        SequenceConfig $sequenceConfig
    ) {
        $this->salesSetupFactory = $salesSetupFactory;
        $this->sequenceBuilder = $sequenceBuilder;
        $this->sequenceConfig = $sequenceConfig;
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        if(!$installer->getConnection()->tableColumnExists('sales_order', 'order_reference_number')){
            $installer->getConnection()->addColumn(
                $installer->getTable('sales_order'),
                'order_reference_number',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'comment' => 'Order Reference Number',
                ]
            );
        }

        if(!$installer->getConnection()->tableColumnExists('sales_order_grid', 'order_reference_number')){
            $installer->getConnection()->addColumn(
                $installer->getTable('sales_order_grid'),
                'order_reference_number',
                [
                    'type' => 'text',
                    'comment' => 'Order Reference Number',
                ]
            );
        }

        $setup->endSetup();
    }
}
