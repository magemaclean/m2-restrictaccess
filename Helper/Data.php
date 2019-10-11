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

    public function canAccessCmsPage($page) {
        $pages = $this->getConfigData('cms', 'pages');
        if($pages && !empty($pages)) {
            $pages = explode(",", $pages);
            return (in_array($page, $pages)) ? false : true;
        }

        return true;
    }

    public function canAccessCmsPageId($pageId) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $cmsPage = $objectManager->get('\Magento\Cms\Model\Page');
        $cmsPage->load($pageId);

        return $this->canAccessCmsPage($cmsPage->getIdentifier());
    }

    public function getNoroutePage($storeId = null) {
        return $this->scopeConfig->getValue(
            'web/default/cms_no_route',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
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
