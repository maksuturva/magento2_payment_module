<?php
namespace Piimega\MaksuturvaMasterpass\Controller\Index;

class Initialize extends \Piimega\MaksuturvaMasterpass\Controller\AbstractController
{
    protected $invalidField;

    protected $authMandatoryFields = array(
        "pmt_version",
        "pmt_id",
        "pmt_reference",
        "pmt_amount",
        "pmt_currency",
        "pmt_paymenturl"
    );

    public function execute()
    {
        if(!$this->canMasterpassCheckout()){
            return false;
        }

        if (! $this->_scopeConfig->getValue('payment/maksuturva_masterpass/active')) {
            $this->messageManager->addError(__('Masterpass checkout is disabled.'));
            $this->_redirect('checkout/cart');
            return;
        }

        $quote = $this->getQuote();

        if (! $quote->validateMinimumAmount()) {
            $error = $this->scopeConfig->getValue('sales/minimum_order/error_message') ?
                $this->scopeConfig->getValue('sales/minimum_order/error_message') :
                __('Subtotal must exceed minimum order amount');

            $this->messageManager->addError($error);
            $this->_redirect('checkout/cart');
            return;
        }

        if (! $quote->hasItems() || $quote->getHasError()) {
            $this->_redirect('checkout/cart');
            return;
        }

        $responseXml = null;
        try {
            $responseXml = $this->_initializePayment();
        } catch (\Exception $e) {
            $this->_redirect('masterpass/authorize/error', array(
                'type' => \Piimega\MaksuturvaMasterpass\Model\Masterpass::ERROR_COMMUNICATION_FAILED
            ));
            $this->messageManager->addError(__('Communication with Maksuturva failed.'));
        }

        if ($responseXml instanceof \SimpleXMLElement) {
            $paymentUrl = (string)$responseXml->{'pmt_paymenturl'};
            $this->_redirect($paymentUrl);
        }
    }

    protected function _initializePayment()
    {
        $quote = $this->getQuote();
        if (empty($quote)) {
            $this->_redirect('checkout/cart');
            return false;
        }

        $this->prepareQuoteBeforeInitializeMasterpass($quote);
        if(!$responseXml = $this->processMasterpassInitialize()){
          return false;
        }

        return $responseXml;
    }

    protected function validateAuthResponse($xmlObject, $requestFields)
    {
        foreach ($this->authMandatoryFields as $field) {
            if ($field == 'pmt_paymenturl') {
                if (! $xmlObject->{$field}) {
                    return false;
                }
                continue;
            }

            if ($xmlObject->{$field} != $requestFields[$field]) {
                $this->invalidField = $field;
                return false;
            }
        }
        return true;
    }

    protected function prepareQuoteBeforeInitializeMasterpass($quote)
    {
        $method = $this->getPaymentMethod();
        // Order id has to be same in initialization request as in final payment request
        $quote->reserveOrderId();

        // Set payment method and apply possible discount
        $payment = $quote->getPayment();
        $additional_data = $this->getHelper()->getPaymentAdditionData($payment);

        $additional_data[\Piimega\Maksuturva\Model\PaymentAbstract::MAKSUTURVA_PRESELECTED_PAYMENT_METHOD] = 'FI55';
        $additional_data[\Piimega\Maksuturva\Model\PaymentAbstract::MAKSUTURVA_PRESELECTED_PAYMENT_METHOD_DESCRIPTION] = 'Masterpass';
        $payment->setAdditionalData($this->getHelper()->getSerializer()->serialize($additional_data));
        $payment->setMethod($method->getCode())->save();
        $quote->collectTotals()->save();
        return $quote;
    }

    protected function processMasterpassInitialize()
    {
        $requestFields = $this->getPaymentMethod()->getFormFields();
        $this->getHelper()->masterPassLogger('masterpassRequest.log')->info(json_encode($requestFields));

        try {
            $responseXml = $this->getGateWay()->paymentPost($requestFields, true);
        } catch (\Exception $e) {
            $this->_redirect('masterpass/authorize/error', array(
                'type' => \Piimega\MaksuturvaMasterpass\Model\Masterpass::ERROR_COMMUNICATION_FAILED
            ));
            return false;
        }

        if ($responseXml->error) {
            $this->_redirect('masterpass/authorize/error', array(
                'type' => \Piimega\MaksuturvaMasterpass\Model\Masterpass::ERROR_MAKSUTURVA_RETURN
            ));
            return false;
        }

        if (!$this->validateAuthResponse($responseXml, $requestFields)) {
            $this->_redirect('masterpass/authorize/error', array(
                'type' => \Piimega\Maksuturva\Model\PaymentAbstract::ERROR_EMPTY_FIELD,
                'field' => $this->invalidField
            ));
            return false;
        }

        $this->getHelper()->masterPassLogger('maksuturvaResponse.log')->info($responseXml);

        return $responseXml;
    }
}