<?php
namespace Svea\Maksuturva\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;

class HandlingFeeResource
{
    const QUOTE_ID = 'entity_id';
    const HANDLING_FEE = 'handling_fee';
    const BASE_HANDLING_FEE = 'base_handling_fee';
    const PAYMENT_CODE = 'payment_code';
    const CARD_PAYMENT = 'maksuturva_card_payment';
    const COD_PAYMENT = 'maksuturva_cod_payment';
    const GENERIC_PAYMENT = 'maksuturva_generic_payment';
    const INVOICE_PAYMENT = 'maksuturva_invoice_payment';
    const PART_PAYMENT = 'maksuturva_part_payment_payment';
    const COLLATED_PAYMENT = 'maksuturva_collated_payment';
    const COLLATED_LATER_PAYMENT = 'pay_later';
    const COLLATED_NOW_PAYMENT = 'pay_now_other';
    const COLLATED_BANK_PAYMENT = 'pay_now_bank';

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private $connection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param int $quoteId
     * @return float
     */
    public function getHandlingFee(int $quoteId)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from('quote', self::HANDLING_FEE)
            ->where('entity_id = ?', $quoteId);

        return (float)$connection->fetchOne($select);
    }

    /**
     * @param int $quoteId
     * @param float $handlingFee
     * @return void
     */
    public function setHandlingFee(int $quoteId, float $handlingFee)
    {
        $connection = $this->getConnection();
        $connection->update(
            'quote',
            [
                self::HANDLING_FEE => $handlingFee,
            ],
            \sprintf('entity_id = \'%s\'', $quoteId)
        );
    }

    /**
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private function getConnection()
    {
        if ($this->connection === null) {
            $this->connection = $this->resourceConnection->getConnection();
        }

        return $this->connection;
    }
}
