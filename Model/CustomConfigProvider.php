<?php
namespace Parcelpro\Shipment\Model;

use Magento\Checkout\Model\ConfigProviderInterface;


class CustomConfigProvider implements ConfigProviderInterface
{
    protected $scopeConfig;

    private function getScopeConfig()
    {
        if ($this->scopeConfig === NULL) {
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

        $gebruikerid = $config["gebruiker_id"];
        $apikey = $config["api_key"];

        $config = [
            'config' => [
                'gebruikerID' => $gebruikerid
            ]
        ];
        return $config;
    }

}
