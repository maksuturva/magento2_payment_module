<?php
namespace Svea\MaksuturvaCollated\Plugin\Block\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessor as CheckoutLayoutProcessor;

/**
 * Class LayoutProcessor
 *
 * @package Svea\MaksuturvaCollated\Plugin\Block\Checkout
 */
class LayoutProcessor
{
    /**
     * @param CheckoutLayoutProcessor $subject
     * @param array $jsLayout
     * @return array
     */
    public function afterProcess(
        CheckoutLayoutProcessor $subject,
        array $jsLayout
    ): array
    {
        $comment = $jsLayout['components']['checkout']['children']['steps']
            ['children']['billing-step']['children']['comment'] ?? null;

        if (isset($comment)) {
            $jsLayout['components']['checkout']['children']['steps']
            ['children']['billing-step']['children']['payment']['children']
            ['beforeMethods']['children']['comment']
                = $comment;

            unset($jsLayout['components']['checkout']['children']['steps']
                ['children']['billing-step']['children']['comment']);
        }

        $paymentLayout = $jsLayout['components']['checkout']['children']['steps']
        ['children']['billing-step']['children']['payment']['children'];

        if (isset($paymentLayout['afterMethods']['children']['billing-address-form'])) {
            $jsLayout['components']['checkout']['children']['steps']
            ['children']['billing-step']['children']['payment']['children']
            ['beforeMethods']['children']['billing-address-form']
                = $paymentLayout['afterMethods']['children']['billing-address-form'];

            unset($jsLayout['components']['checkout']['children']['steps']
                ['children']['billing-step']['children']['payment']
                ['children']['afterMethods']['children']['billing-address-form']);
        }

        return $jsLayout;
    }
}