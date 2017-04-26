<?php
namespace Piimega\Maksuturva\Setup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\SalesSequence\Model\Builder;
use Magento\SalesSequence\Model\Config as SequenceConfig;

/**
 * Upgrade Data script
 * @codeCoverageIgnore
 */
class UpgradeData implements UpgradeDataInterface
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

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        if(version_compare($context->getVersion(), '1.0.2', '<')) {

            if(!$setup->getConnection()->tableColumnExists('sales_order', 'maksuturva_preselected_payment_method')){
                $setup->getConnection()->addColumn(
                    $setup->getTable('sales_order'),
                    'maksuturva_preselected_payment_method',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 255,
                        'comment' => 'Maksuturva Preselected Payment Method',
                    ]
                );
            }

            if(!$setup->getConnection()->tableColumnExists('sales_order_grid', 'maksuturva_preselected_payment_method')){
                $setup->getConnection()->addColumn(
                    $setup->getTable('sales_order_grid'),
                    'maksuturva_preselected_payment_method',
                    [
                        'type' => 'text',
                        'comment' => 'Maksuturva Preselected Payment Method',
                    ]
                );
            }

        }
        $setup->endSetup();
    }
}
