<?php

namespace Svea\MaksuturvaCollated\Setup;

use Magento\Framework\App\Config\ConfigResource\ConfigInterface as ResourceConfig;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\SalesSequence\Model\Builder;
use Magento\SalesSequence\Model\Config as SequenceConfig;

/**
 * Class UpgradeData
 *
 * @package Svea\MaksuturvaCollated\Setup
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
        if(version_compare($context->getVersion(), '1.0.1', '<')) {

        }

        $setup->endSetup();
    }
}
