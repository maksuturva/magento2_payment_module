<?php
namespace Piimega\MaksuturvaMasterpass\Helper;

class Data extends \Piimega\Maksuturva\Helper\Data
{
    const MASTERPASS_STATUS_CONFIG_PATH = "payment/maksuturva_masterpass/active";

    public function parseName($fullname, &$firstname, &$lastname)
    {
        if (empty($fullname)) {
            return false;
        }
        $names = explode(' ', $fullname);
        $nameCount = count($names);

        if ($nameCount == 1) {
            $firstname = $names[0];
            $lastname = '';
        } else if ($nameCount == 2) {
            $firstname = $names[0];
            $lastname = $names[1];
        } else {
            $firstname = implode(' ', array_splice($names, $nameCount - 1));
            $lastname = $names[$nameCount - 1];
        }
        return true;
    }

    public function getSerializedMaksuturvaPaymentId($payment)
    {
        $additional_data = $this->getPaymentAdditionData($payment);
        if (isset($additional_data[\Piimega\Maksuturva\Model\PaymentAbstract::MAKSUTURVA_TRANSACTION_ID])) {
            return $additional_data[\Piimega\Maksuturva\Model\PaymentAbstract::MAKSUTURVA_TRANSACTION_ID];
        }
        return false;
    }

    public function setPaymentMaksuturvaPmtId($payment, $pmt_id)
    {
        $additional_data = $this->getPaymentAdditionData($payment);
        $additional_data[\Piimega\Maksuturva\Model\PaymentAbstract::MAKSUTURVA_TRANSACTION_ID] = $pmt_id;
        $payment->setAdditionalData($this->getSerializer()->serialize($additional_data));
        $payment->setMaksuturvaPmtId($pmt_id);
        $payment->save();
    }

    public function serializeAddress($address)
    {
        return serialize(
            array(
                'firstname' => $address->getFirstname(),
                'lastname' => $address->getLastname(),
                'street' => $address->getStreet(),
                'city' => $address->getCity(),
                'postcode' => $address->getPostcode()
            )
        );
    }

    public function masterPassLogger($logName = "masterpass.log")
    {
        if(!$this->_loggerHandler){
            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/'.$logName);
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $this->_loggerHandler = $logger;
        }
        return $this->_loggerHandler;
    }

    public function isMasterpassEnabled()
    {
        return (bool)$this->scopeConfig->getValue(self::MASTERPASS_STATUS_CONFIG_PATH);
    }
}