<?php
 
namespace MageMaclean\RestrictAccess\Observer;
 
use Magento\Customer\Model\Context;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

use MageMaclean\RestrictAccess\Helper\Data as Helper;
 
class RestrictAccess implements ObserverInterface
{
    protected $_logger;
    protected $_response;
    protected $_urlInterface;
    protected $_urlFactory;
    protected $_context;
    protected $_actionFlag;
    protected $_messageManagaer;
    protected $_customerSession;
    protected $_storeManager;
    protected $_helper;
    
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Response\Http $response,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Framework\UrlFactory $urlFactory,
        \Magento\Framework\App\Http\Context $context,
        \Magento\Framework\App\ActionFlag $actionFlag,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        Helper $helper
    )
    {
        $this->_logger = $logger;
        $this->_response = $response;
        $this->_urlFactory = $urlFactory;
        $this->_urlInterface = $urlInterface;
        $this->_context = $context;
        $this->_actionFlag = $actionFlag;
        $this->_messageManager = $messageManager;
        $this->_customerSession = $customerSession;
        $this->_storeManager = $storeManager;
        $this->_helper = $helper;
    }

    public function execute(Observer $observer)
    {
        $storeId = $this->_storeManager->getStore()->getId();
        if(!$this->_helper->isEnabled($storeId)) {
            return;
        }
        
        $isCustomerLoggedIn = $this->_context->getValue(Context::CONTEXT_AUTH);
        if($isCustomerLoggedIn) {
            return;
        }
        
        $request = $observer->getEvent()->getRequest();
        $actionFullName = strtolower($request->getFullActionName());
        if($actionFullName === 'cms_noroute_index') {
            $noroutePage = $this->_helper->getNoroutePage($storeId);
            if(!$this->_helper->canAccessCmsPage($noroutePage, $storeId)) {
                return $this->_restrictAccessRedirect($this->_helper->getConfigData("cms", "message", $storeId));
            }
        } else if($actionFullName === 'cms_index_index') {
            if(!$this->_helper->canAccessHomepage($storeId)) {
                return $this->_restrictAccessRedirect($this->_helper->getConfigData("cms", "message", $storeId));
            }
        } else if($actionFullName === 'cms_page_view') {
            $pageId = $request->getParam('page_id', false);
            if($pageId && !$this->_helper->canAccessCmsPageId($pageId, $storeId)) {
                return $this->_restrictAccessRedirect($this->_helper->getConfigData("cms", "message", $storeId));
            }
        } else {
            $restrictRoutes = [];
            if($customRoutes = $this->_helper->getCustomRoutes($storeId)) {
                foreach($customRoutes as $customRoute) {
                    $restrictRoutes[] = $customRoute['value'];
                }
                if (in_array($actionFullName, $restrictRoutes)) {
                    return $this->_restrictAccessRedirect($this->_helper->getConfigData("custom", "message", $storeId));
                }
            }

            $restrictRoutes = [];
            if(!$this->_helper->canAccessCategory($storeId)) {
                $restrictRoutes[] = 'catalog_category_view';
                $restrictRoutes[] = 'catalog_category_index';
            }

            if(!$this->_helper->canAccessProduct($storeId)) {
                $restrictRoutes[] = 'catalog_product_view';
            }

            if(!$this->_helper->canAccessSearch($storeId)) {
                $restrictRoutes[] = 'catalogsearch_result_index';
            }

            if (in_array($actionFullName, $restrictRoutes)) {
                return $this->_restrictAccessRedirect($this->_helper->getConfigData("catalog", "message", $storeId));
            }
        }
    }

    protected function _restrictAccessRedirect($message) {
        $this->_messageManager->addError($message);
        $this->_customerSession->setAfterAuthUrl($this->_urlInterface->getCurrentUrl());
        $this->_customerSession->authenticate();
        return;
    }
}