<?php

namespace Svea\OrderComment\Model\ResourceModel;

use Exception;

class QuoteAttribute extends \Magento\Quote\Model\ResourceModel\Quote
{
    /**
     * @param \Magento\Framework\Model\AbstractExtensibleModel $object
     * @param string                                           $attributeCode
     *
     * @return $this
     * @throws \Exception
     */
    public function saveAttribute(\Magento\Framework\Model\AbstractExtensibleModel $object, string $attributeCode)
    {
        if (!empty($attributeCode)) {
            $resource = $object->getResource();
            $connection = $resource->getConnection();
            $connection->beginTransaction();
            $data = [
                $attributeCode => $object->getData($attributeCode),
            ];

            try {
                if (!empty($data) && $object->getId()) {
                    $connection->update(
                        $resource->getMainTable(),
                        $data,
                        [$resource->getIdFieldName() . '= ?' => (int)$object->getId()]
                    );

                    $object->addData($data);
                }

                $connection->commit();
            } catch (Exception $e) {
                $connection->rollBack();
                throw $e;
            }
        }

        return $this;
    }
}
