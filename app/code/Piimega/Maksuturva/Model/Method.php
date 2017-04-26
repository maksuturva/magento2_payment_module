<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Piimega\Maksuturva\Model;

class Method extends \Magento\Framework\Model\AbstractModel
{
    public function _construct()
    {
        $this->_init('Piimega\Maksuturva\Model\ResourceModel\Method');
    }

    public function insert()
    {
        $this->getResource()->insert($this);
        return $this;
    }
}