<?php
namespace Svea\MaksuturvaMasterpass\Controller\Index;

class PlaceOrder extends \Svea\MaksuturvaMasterpass\Controller\AbstractController
{
    protected $msg;
    protected $paymentMandatoryFields = array(
        "pmt_action",
        "pmt_version",
        "pmt_id",
        "pmt_reference",
        "pmt_amount",
        "pmt_currency",
        "pmt_sellercosts",
        "pmt_paymentmethod"
    );

    public function execute()
    {
        $order = $this->getOrder();

        if(!$this->validateOrder($order)){
            $this->_redirect('checkout/cart');
            return false;
        }

        $this->activeQuote($order);

        if(!$order = $this->processMasterPassOrder($order)){
            return false;
        }

        try {
            $this->_createInvoice($order);
            if (!$order->getEmailSent()) {
                try {
                    $order->sendNewOrderEmail();
                    $order->setEmailSet(true);
                } catch (\Exception $e) {
                    $this->getHelper()->maksuturvaLogger($e->getMessage());
                }
            }

            //set order to paid status
            $order = $this->setOrderStatus($order);
            $order->save();
            $this->disableQuote($order);

            $this->_redirect('checkout/onepage/success', array('_secure' => true));
        } catch (\Exception $e) {
            $this->_redirect('masterpass/authorize/error');
        }
    }

    private function validatePaymentResponse($xmlObject, $requestFields)
    {
        $invalidField = "";
        foreach ($this->paymentMandatoryFields as $field) {
            if ($xmlObject->{$field} != $requestFields[$field]) {
                $invalidField = $field;
            }
        }
        return $invalidField;
    }

    protected function validateOrder($order)
    {
        $isValid = true;
        if (! $order->getId()) {
            $this->messageManager->addError(__('Your order is not valid.'));
            $isValid = false;
        }

        if (! $order->canInvoice()) {
            $this->messageManager->addSuccess(__('Your order is already paid.'));
            $isValid = false;
        }
        if ($order->getState() != \Magento\Sales\Model\Order::STATE_NEW) {
            $this->messageManager->addSuccess(__('Your order is already authorized'));
            $isValid = false;
        }

        return $isValid;
    }


    protected function processMasterPassOrder($order)
    {
        $gateway = $this->getGateWay();
        $requestFields = $gateway->getFormFields();

        $this->getHelper()->masterPassLogger('masterpassRequest.log')->info(json_encode($requestFields));

        //prevent program crash because of invalid response xml
        try {
            $responseXml = $gateway->paymentPost($requestFields, true);
        } catch (\Exception $e) {
            $this->_redirect('masterpass/authorize/error', array(
                'type' => \Svea\MaksuturvaMasterpass\Model\Masterpass::ERROR_COMMUNICATION_FAILED
            ));
            return false;
        }


        if(!$msg = $this->validateResponse($responseXml, $requestFields, $order)){
            $canceledStatus = \Magento\Sales\Model\Order::STATE_CANCELED;
            $order->setStatus($canceledStatus, true, $this->msg, false)
                ->setState($canceledStatus, true, $this->msg, false)
                ->save();
            return false;
        }

        //set msg variable
        $this->setMsg($msg);
        $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING, true, $this->msg, false);
        return $order;
    }

    protected function validateResponse($responseXml, $requestFields, $order)
    {
        if ($responseXml->error) {
            $this->setMsg(__('Authentication error'));
            $pmt_id = $this->getHelper()->getSerializedMaksuturvaPaymentId($order->getPayment());
            $this->_redirect('masterpass/authorize/error', array('pmt_id' => $pmt_id));
            return false;
        }

        if ($invalidField = $this->validatePaymentResponse($responseXml, $requestFields)) {
            $this->_redirect('masterpass/authorize/error', array('type' => \Svea\Maksuturva\Model\PaymentAbstract::ERROR_EMPTY_FIELD, 'field' => $invalidField));
            return false;
        }

        if ($responseXml->{'pmt_sellercosts'} != $requestFields['pmt_sellercosts']) {
            if ($responseXml->{'pmt_sellercosts'} < $requestFields['pmt_sellercosts']) {
                $this->_redirect('masterpass/index/error', array('type' => \Svea\Maksuturva\Model\PaymentAbstract::ERROR_SELLERCOSTS_VALUES_MISMATCH));
                return false;
            } else {
                $sellercosts_change = $requestFields['pmt_sellercosts'] - $responseXml->{'pmt_sellercosts'};
                $msg = __("Payment captured by Maksuturva. NOTE: Change in the sellercosts + %1 EUR.", $sellercosts_change);
            }
        } else {
            $msg = __("Payment captured by Maksuturva");
        }
        return $msg;
    }

    protected function setOrderStatus($order)
    {
        if($this->getConfigData('paid_order_status')){
            $processStatus = $this->getConfigData('paid_order_status');
        }else{
            $processStatus = \Magento\Sales\Model\Order::STATE_PROCESSING;
        }
        $processState = \Magento\Sales\Model\Order::STATE_PROCESSING;
        $order->setState($processState, true, $this->msg, false);
        $order->setStatus($processStatus, true, $this->msg, false);

        return $order;
    }

    protected function setMsg($msg)
    {
        $this->msg = $msg;
    }
}