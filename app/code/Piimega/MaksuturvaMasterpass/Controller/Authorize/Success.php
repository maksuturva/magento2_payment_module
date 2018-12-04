<?php
namespace Piimega\MaksuturvaMasterpass\Controller\Authorize;

class Success extends \Piimega\MaksuturvaMasterpass\Controller\AbstractController
{
    public function execute()
    {
        $pmt_id = $this->getRequest()->getParam('pmt_id');
        $pmt_paymentmethod = $this->getRequest()->getParam('pmt_paymentmethod');

        if ($pmt_paymentmethod != \Piimega\MaksuturvaMasterpass\Model\Masterpass::MAKSUTURVA_MASTERPASS_METHOD_CODE) {
            $this->_redirect('masterpass/authorize/error');
            return;
        }

        $quote = $this->getQuote();
        $quotePmtId = $this->getHelper()->getSerializedMaksuturvaPaymentId($quote->getPayment());

        if ($quotePmtId != $pmt_id) {
            $this->_redirect('masterpass/authorize/error');
            return;
        }

        $gateway = $this->getGateWay();

        try {
            $responseXml = $gateway->statusQuery(false);
        } catch (\Exception $e) {
            $this->_redirect('masterpass/authorize/error', array(
                'type' => \Piimega\MaksuturvaMasterpass\Model\Masterpass::ERROR_COMMUNICATION_FAILED
            ));
        }

        if ($responseXml->pmtq_externalcode1 != 'OK') {
            $this->_redirect('masterpass/authorize/error');
        }

        if ($responseXml->pmtq_paymentmethod != \Piimega\MaksuturvaMasterpass\Model\Masterpass::MAKSUTURVA_MASTERPASS_METHOD_CODE) {
            $this->_redirect('masterpass/authorize/error');
            return false;
        }

        switch ($responseXml->pmtq_returncode) {
            case \Piimega\Maksuturva\Model\Gateway\Implementation::STATUS_QUERY_NOT_FOUND:
                break; // OK
            case \Piimega\Maksuturva\Model\Gateway\Implementation::STATUS_QUERY_PAID:
            case \Piimega\Maksuturva\Model\Gateway\Implementation::STATUS_QUERY_PAID_DELIVERY:
                $this->messageManager->addSuccess(__('Your order is already paid'));
                break;
            default:
                $this->_redirect('masterpass/authorize/error');
        }
        //update Quote address data
        $this->getPaymentMethod()->setAddressesFromXml($responseXml);

        //redirect to checkout page, and prepare shipping address form
        $this->_redirect('masterpass/checkout');
    }

}