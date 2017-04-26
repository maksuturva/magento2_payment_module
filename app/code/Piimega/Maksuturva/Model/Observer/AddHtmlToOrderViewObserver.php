<?php
/**
 * Created by PhpStorm.
 * User: eugen
 * Date: 27.11.2015
 * Time: 17:55
 */

namespace Piimega\Maksuturva\Model\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;


class AddHtmlToOrderViewObserver implements ObserverInterface
{

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;
    protected $_maksuturvaPaymentMethodRef;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectmanager
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectmanager,
                                \Piimega\Maksuturva\Model\MethodFactory $methodModelFactory)
    {
        $this->_objectManager = $objectmanager;
        $this->_maksuturvaPaymentMethodRef = $methodModelFactory;
    }

    public function execute(EventObserver $observer)
    {
        if($observer->getElementName() == 'order_shipping_view')
        {
            $orderShippingViewBlock = $observer->getLayout()->getBlock($observer->getElementName());
            $order = $orderShippingViewBlock->getOrder();
            if($order->getData('maksuturva_preselected_payment_method')){
                $maksuturvaPaymentMethodRef = $this->_maksuturvaPaymentMethodRef->create();
                $maksuturvaPaymentMethodObj = $maksuturvaPaymentMethodRef->load($order->getData('maksuturva_preselected_payment_method'), 'code');
                $block = $this->_objectManager->create('Magento\Framework\View\Element\Template');
                $order->setData('maksuturva_preselected_payment_method_name', $maksuturvaPaymentMethodObj->getDisplayname());
                $block->setOrder($order);
                $block->setTemplate('Piimega_Maksuturva::maksuturva_payment_info.phtml');
                $html = $observer->getTransport()->getOutput() . $block->toHtml();
                $observer->getTransport()->setOutput($html);
            }
        }
    }
}