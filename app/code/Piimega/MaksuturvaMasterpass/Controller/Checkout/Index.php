<?php
namespace Svea\MaksuturvaMasterpass\Controller\Checkout;

class Index extends \Magento\Checkout\Controller\Index\Index
{
    const MASTERPASS_STATUS_CONFIG_PATH = "payment/maksuturva_masterpass/title";

    public function execute()
    {
        $resultPage = parent::execute();
        //the $resultPage could also instanceof from \Magento\Framework\Controller\Result\RedirectFactory
        //when onepage checkout has been disabled
        if($resultPage instanceof \Magento\Framework\View\Result\Page){
            $resultPage->getConfig()->getTitle()->set(__($this->scopeConfig->getValue(self::MASTERPASS_STATUS_CONFIG_PATH)));
        }
        return $resultPage;
    }
}