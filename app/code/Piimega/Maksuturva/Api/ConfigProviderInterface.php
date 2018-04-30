<?php
namespace Piimega\Maksuturva\Api;

interface ConfigProviderInterface
{
    /**
     * Get attribute set ids by product ids
     *
     * @param array $productIds
     * @return array
     * @since 101.0.0
     */
    public function getConfig();
}
