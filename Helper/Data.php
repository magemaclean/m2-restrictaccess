<?php
namespace MageMaclean\RestrictAccess\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const XML_PATH_SECTION = 'restrictaccess';

    protected $serializer;
    
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Serialize\SerializerInterface $serializer
    ) {
        $this->serializer = $serializer;
        parent::__construct($context);
    }

    public function isEnabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_SECTION . '/general/enabled',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function canAccessCategory($storeId = null) {
        return !$this->getConfigData('catalog', 'category', $storeId);
    }

    public function canAccessProduct($storeId = null) {
        return !$this->getConfigData('catalog', 'product', $storeId);
    }

    public function canAccessSearch($storeId = null) {
        return !$this->getConfigData('catalog', 'search', $storeId);
    }

    public function canAccessCmsPage($page, $storeId = null) {
        $pages = $this->getConfigData('cms', 'pages', $storeId);
        if($pages && !empty($pages)) {
            $pages = explode(",", $pages);
            return (in_array($page, $pages)) ? false : true;
        }

        return true;
    }

    public function canAccessCmsPageId($pageId, $storeId = null) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $cmsPage = $objectManager->get('\Magento\Cms\Model\Page');
        $cmsPage->load($pageId);

        return $this->canAccessCmsPage($cmsPage->getIdentifier(), $storeId);
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
            self::XML_PATH_SECTION . '/' . $group . '/' . $field,
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
