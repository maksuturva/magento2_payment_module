<?php
namespace Svea\MaksuturvaMasterpass\Observer;

use Magento\Framework\Event\ObserverInterface;

class IsMasterpassCheckout implements ObserverInterface
{

    protected $_checkoutSession;
    protected $request;
    protected $redirect;

    public function __construct (
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\App\Response\RedirectInterface $redirect
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->request = $request;
        $this->redirect = $redirect;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $fullActionName = $this->request->getFullActionName();
        if($fullActionName == "masterpass_checkout_index"){
            //switch on masterpass checkout
            $this->_checkoutSession->setData('isMasterpassInUse', true);
        }else if($fullActionName == "checkout_index_index"){
            //switch off masterpass checkout
            $this->_checkoutSession->setData('isMasterpassInUse', false);
        }
    }
}