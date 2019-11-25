<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Svea\Maksuturva\Model\ResourceModel\Method;
 
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init('Svea\Maksuturva\Model\Method', 'Svea\Maksuturva\Model\ResourceModel\Method');
    }
}
