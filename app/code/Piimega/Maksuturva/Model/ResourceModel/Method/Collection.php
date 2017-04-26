<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Piimega\Maksuturva\Model\ResourceModel\Method;
 
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init('Piimega\Maksuturva\Model\Method', 'Piimega\Maksuturva\Model\ResourceModel\Method');
    }
}
