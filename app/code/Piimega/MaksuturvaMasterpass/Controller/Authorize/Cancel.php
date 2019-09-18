<?php
namespace Piimega\MaksuturvaMasterpass\Controller\Authorize;

class Cancel extends \Piimega\MaksuturvaMasterpass\Controller\AbstractController
{
    public function execute()
    {
        $pmt_id = $this->getRequest()->getParam('pmt_id');
        if (empty($pmt_id)) {
            $this->messageManager->addError(__('Unknown error on maksuturva payment module.'));
            $this->_redirect('masterpass/authorize/error', array('type' => \Piimega\Maksuturva\Model\PaymentAbstract::ERROR_VALUES_MISMATCH));
            return;
        }

        $payment = $this->getQuote()->getPayment();
        $additional_data = $this->getHelper()->getPaymentAdditionData($payment);
        if ($additional_data[\Piimega\Maksuturva\Model\PaymentAbstract::MAKSUTURVA_TRANSACTION_ID] !== $pmt_id) {
            $this->messageManager->addError(__('Unknown error on maksuturva payment module.'));
            $this->_redirect('checkout/cart');
            return;
        }

        $this->messageManager->addWarning(__('You have cancelled your payment in Maksuturva.'));
        $this->_redirect('checkout/cart');
    }

}