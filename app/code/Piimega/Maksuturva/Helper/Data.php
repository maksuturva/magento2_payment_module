<?php
namespace Piimega\Maksuturva\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper implements \Piimega\Maksuturva\Api\MaksuturvaHelperInterface
{
    const CONFIG_PRESELECT_PAYMENT_METHOD = "maksuturva_config\maksuturva_payment\preselect_payment_method";
    const CONFIG_PATH_GENERATE_INVOICE_AUTOMATICALLY = "maksuturva_config/maksuturva_payment/generate_invoice";

    protected $_loggerHandler;
    protected $_checkoutSession;
    protected $serializer;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Checkout\Model\Session $session,
        \Magento\Framework\Serialize\Serializer\Json $serializer
    ) {
        parent::__construct($context);
        $this->_checkoutSession = $session;
        $this->serializer = $serializer;
    }

    public static function generatePaymentId()
    {
        return sprintf('%04x%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
    }

    public function maksuturvaLogger($info)
    {
        $this->getMaksuturvaLoggerHandler()->info(json_encode($info));
    }

    protected function getMaksuturvaLoggerHandler()
    {
        if(!$this->_loggerHandler){
            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/maksuturva.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $this->_loggerHandler = $logger;
        }
        return $this->_loggerHandler;
    }

    public function getPmtReferenceNumber($number)
    {
        if ($number < 100) {
            throw new \Piimega\Maksuturva\Model\Gateway\Exception(array("Cannot generate reference numbers for an ID smaller than 100"), \Piimega\Maksuturva\Model\Gateway\Base::EXCEPTION_CODE_REFERENCE_NUMBER_UNDER_100);
        }

        // Painoarvot
        $tmpMultip = array(7, 3, 1);
        // Muutetaan parametri merkkijonoksi
        $tmpStr = (string)$number;
        $tmpSum = 0;
        $tmpIndex = 0;
        for ($i = strlen($tmpStr) - 1; $i >= 0; $i--) {
            $tmpSum += intval(substr($tmpStr, $i, 1)) * intval($tmpMultip[$tmpIndex % 3]);
            $tmpIndex++;
        }

        // Laskettua summaa vastaava seuraava täysi kymmenluku:
        $nextTen = ceil(intval($tmpSum) / 10) * 10;

        return $tmpStr . (string)(abs($nextTen - $tmpSum));
    }

    public function statusQuery(\Magento\Sales\Model\Order $order){
        $model = $order->getPayment()->getMethodInstance();

        $implementation = $model->getGatewayImplementation();
        $implementation->setOrder($order);

        $config = $model->getConfigs();
        $data = array('pmtq_keygeneration' => $config['keyversion']);

        try {
            $response = $implementation->statusQuery($data);
            $result = $implementation->ProcessStatusQueryResult($response);
            $this->maksuturvaLogger($result['message']);
        } catch (\Exception $e) {
            //do nothing
        }
    }

    public function getSerializer()
    {
        return $this->serializer;
    }

    public function getCheckoutSession()
    {
        return $this->_checkoutSession;
    }

    public function getPaymentAdditionData($payment)
    {
        $additional_data = $payment->getAdditionalData();
        if($additional_data && !is_array($additional_data)){
            $additional_data = $this->getSerializer()->unserialize($additional_data);
        }
        return $additional_data;
    }

    public function generateInvoiceAutomatically()
    {
        return (bool)$this->scopeConfig->getValue(self::CONFIG_PATH_GENERATE_INVOICE_AUTOMATICALLY);
    }
}

