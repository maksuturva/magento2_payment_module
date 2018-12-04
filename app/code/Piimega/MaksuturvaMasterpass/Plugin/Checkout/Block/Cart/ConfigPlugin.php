<?php
namespace Piimega\MaksuturvaMasterpass\Plugin\Checkout\Block\Cart;

use Magento\Framework\UrlInterface;

class ConfigPlugin
{
    protected $url;
    protected $_assetRepo;
    protected $masterpassHelper;

    public function __construct(
        UrlInterface $url,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Piimega\MaksuturvaMasterpass\Helper\Data $masterpassHelper
    ) {
        $this->url = $url;
        $this->_assetRepo = $assetRepo;
        $this->masterpassHelper = $masterpassHelper;
    }

    public function afterGetConfig(
        \Magento\Checkout\Block\Cart\Sidebar $subject,
        array $result
    ) {
        $result['newMasterpassUrl'] = $this->url->getUrl('masterpass/index/Initialize', ['_secure' => true, 'timestamp'=>time()]);
        $result['masterpassImageUrl'] = $this->getMasterpassImageUrl();
        $result['isMasterpassEnabled'] = $this->isMasterpassEnabled();
        return $result;
    }

    public function getMasterpassImageUrl()
    {
        return $this->_assetRepo->getUrl('Piimega_MaksuturvaMasterpass::images/mp_chk_btn_147x034px.svg');
    }

    public function isMasterpassEnabled()
    {
        return $this->masterpassHelper->isMasterpassEnabled();
    }
}