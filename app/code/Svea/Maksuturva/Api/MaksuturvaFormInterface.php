<?php
namespace Svea\Maksuturva\Api;

interface MaksuturvaFormInterface
{
    /**
     * Get attribute set ids by product ids
     *
     * @param array $productIds
     * @return array
     * @since 101.0.0
     */
    public function setConfig($args);
}