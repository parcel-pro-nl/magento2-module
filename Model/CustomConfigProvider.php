<?php

namespace Parcelpro\Shipment\Model;

use Magento\Checkout\Model\ConfigProviderInterface;

class CustomConfigProvider implements ConfigProviderInterface
{
    protected $scopeConfig;

    private function getScopeConfig()
    {
        if ($this->scopeConfig === null) {
            $this->scopeConfig = \Magento\Framework\App\ObjectManager::getInstance()->get(
                'Magento\Framework\App\Config\ScopeConfigInterface'
            );
        }
        return $this->scopeConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $config = $this->getScopeConfig()->getValue('carriers/parcelpro', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        if (isset($config["gebruiker_id"])) {
            $gebruikerid = $config["gebruiker_id"];
            $config = [
                'config' => [
                    'gebruikerID' => $gebruikerid
                ]
            ];
        }

        return $config;
    }
}
