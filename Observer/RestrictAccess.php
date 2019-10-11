<?php
 
namespace MageMaclean\RestrictAccess\Observer;
 
use Magento\Customer\Model\Context;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;

use MageMaclean\RestrictAccess\Helper\Data as Helper;
 
class RestrictAccess implements ObserverInterface
{
    protected $_helper;
    
    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\App\Response\Http $response,
        \Magento\Framework\UrlFactory $urlFactory,
        \Magento\Framework\App\Http\Context $context,
        \Magento\Framework\App\ActionFlag $actionFlag,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        Helper $helper
    )
    {
        $this->_response = $response;
        $this->_urlFactory = $urlFactory;
        $this->_context = $context;
        $this->_actionFlag = $actionFlag;
        $this->_messageManager = $messageManager;
        $this->_helper = $helper;
    }

    public function execute(Observer $observer)
    {
        if(!$this->_helper->isEnabled()) {
            return;
        }
        
        $isCustomerLoggedIn = $this->_context->getValue(Context::CONTEXT_AUTH);
        if($isCustomerLoggedIn) {
            return;
        }
        
        $request = $observer->getEvent()->getRequest();
        $actionFullName = strtolower($request->getFullActionName());

        if($actionFullName === 'cms_noroute_index') {
            $noroutePage = $this->_helper->getNoroutePage();
            if(!$this->_helper->canAccessCmsPage($noroutePage)) {
                $this->_messageManager->addError($this->_helper->getConfigData("cms", "message"));
                $this->_response->setRedirect($this->_urlFactory->create()->getUrl('customer/account/login'));
                return;
            }
        } else if($actionFullName === 'cms_page_view') {
            $pageId = $request->getParam('page_id', false);
            if($pageId && !$this->_helper->canAccessCmsPageId($pageId)) {
                $this->_messageManager->addError($this->_helper->getConfigData("cms", "message"));
                $this->_response->setRedirect($this->_urlFactory->create()->getUrl('customer/account/login'));
            }
        } else {
            $restrictRoutes = [];
            if($customRoutes = $this->_helper->getCustomRoutes()) {
                foreach($customRoutes as $customRoute) {
                    $restrictRoutes[] = $customRoute['route'];
                }
                if (in_array($actionFullName, $restrictRoutes)) {
                    $this->_messageManager->addError($this->_helper->getConfigData("custom", "message"));
                    $this->_response->setRedirect($this->_urlFactory->create()->getUrl('customer/account/login'));
                    return;
                }
            }

            $restrictRoutes = [];
            if(!$this->_helper->canAccessCategory()) {
                $restrictRoutes[] = 'catalog_category_view';
                $restrictRoutes[] = 'catalog_category_index';
            }

            if(!$this->_helper->canAccessProduct()) {
                $restrictRoutes[] = 'catalog_product_view';
            }

            if(!$this->_helper->canAccessSearch()) {
                $restrictRoutes[] = 'catalogsearch_result_index';
            }

            if (in_array($actionFullName, $restrictRoutes)) {
                $this->_messageManager->addError($this->_helper->getConfigData("catalog", "message"));
                $this->_response->setRedirect($this->_urlFactory->create()->getUrl('customer/account/login'));
            }
        }
    }
}