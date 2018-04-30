<?php
namespace Piimega\Maksuturva\Model\Observer;

use Magento\Framework\Event\ObserverInterface;

class PlaceShipmentAfter implements ObserverInterface
{
    protected $_maksuturvaHelper;
    protected $_registry;
    protected $_request;
    protected $_maksuturvaModel;

    public function __construct (
        \Piimega\Maksuturva\Helper\Data $maksuturvaHelper,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Request\Http $request,
        \Piimega\Maksuturva\Model\PaymentAbstract $maksuturvaModel
    ) {
        $this->_registry = $registry;
        $this->_request = $request;
        $this->_maksuturvaHelper = $maksuturvaHelper;
        $this->_maksuturvaModel = $maksuturvaModel;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getData('order');
        if($order && $order->getId()){
            $order->getPayment()->getMethodInstance()->getGatewayImplementation()->addDeliveryInfo($order->getPayment());
        }
    }
}