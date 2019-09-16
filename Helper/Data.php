<?php
namespace MageMaclean\RestrictAccess\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use MageMaclean\CustomerShipping\Model\Carrier;

class Data extends AbstractHelper
{
    protected $serializer;
    
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Serialize\SerializerInterface $serializer
    ) {
        $this->serializer = $serializer;
        parent::__construct($context);
    }

    public function isEnabled()
    {
        return $this->scopeConfig->isSetFlag('restrictaccess/general/enabled');
    }

    public function canAccessCategory() {
        return !$this->getConfigData('catalog', 'category');
    }

    public function canAccessProduct() {
        return !$this->getConfigData('catalog', 'product');
    }

    public function canAccessSearch() {
        return !$this->getConfigData('catalog', 'search');
    }

    public function canAccessCmsPage($pageId) {
        $pages = $this->getConfigData('cms', 'pages');
        if($pages && !empty($pages)) {
            $pageIds = explode(",", $pages);
            return (in_array($pageId, $pageIds)) ? true : false;
        }

        return true;
    }

    public function getConfigData($group, $field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            'restrictaccess/' . $group . '/' . $field,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getCustomRoutes($storeId = null)
    {
        $configValue = $this->getConfigData('custom', 'routes', $storeId);
        if(!$configValue) {
            return array(
                array('value' => 'unset', 'label' => "No custom routes have been set")
            );
        }

        $routes = $this->serializer->unserialize($configValue);
        $items = array();
        if($routes && sizeof($routes)) {
            foreach($routes as $item) {
                $items[] = array('value' => $item['route'], 'label' => $item['name']);
            }
        }

        return $items;
    }
}
