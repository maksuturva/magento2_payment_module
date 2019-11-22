<?php
namespace Svea\MaksuturvaMasterpass\Block\Onepage;

class Link extends \Magento\Checkout\Block\Onepage\Link
{
    const MASTERPASS_STATUS_CONFIG_PATH = "payment/maksuturva_masterpass/active";

    public function getNewMasterpassUrl()
    {
        return $this->getUrl('masterpass/index/Initialize', ['_secure' => true, 'timestamp'=>time()]);
    }

    public function getMasterpassImageUrl()
    {
        return $this->_assetRepo->getUrl('Svea_MaksuturvaMasterpass::images/mp_chk_btn_147x034px.svg');
    }

    public function isMasterpassEnabled()
    {
        return $this->_scopeConfig->getValue(self::MASTERPASS_STATUS_CONFIG_PATH);
    }
}