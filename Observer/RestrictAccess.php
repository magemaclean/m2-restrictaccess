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
    protected $_resultRedirectFactory;
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
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
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
        $this->_resultRedirectFactory = $resultRedirectFactory;
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
        $response = $observer->getControllerAction()->getResponse();

        $actionFullName = strtolower($request->getFullActionName());
        if($actionFullName === 'cms_noroute_index') {
            $noroutePage = $this->_helper->getNoroutePage($storeId);
            if(!$this->_helper->canAccessCmsPage($noroutePage, $storeId)) {
                return $this->_restrictAccessRedirect($response, "cms");
            }
        } else if($actionFullName === 'cms_index_index') {
            if(!$this->_helper->canAccessHomepage($storeId)) {
                return $this->_restrictAccessRedirect($response, "cms");
            }
        } else if($actionFullName === 'cms_page_view') {
            $pageId = $request->getParam('page_id', false);
            if($pageId && !$this->_helper->canAccessCmsPageId($pageId, $storeId)) {
                return $this->_restrictAccessRedirect($response, "cms");
            }
        } else {
            $restrictRoutes = [];
            if($customRoutes = $this->_helper->getCustomRoutes($storeId)) {
                foreach($customRoutes as $customRoute) {
                    $restrictRoutes[] = $customRoute['value'];
                }
                if (in_array($actionFullName, $restrictRoutes)) {
                    return $this->_restrictAccessRedirect($response, "custom");
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
                return $this->_restrictAccessRedirect($response, "catalog");
            }
        }
    }

    protected function _restrictAccessRedirect($response, $type) {
        $this->_setRestrictMessage($type);
        $this->_setRestrictRedirect($response, $type);
        return;
    }

    protected function _setRestrictMessage($type) {
        $storeId = $this->_storeManager->getStore()->getId();
        $messageType = $this->_helper->getConfigData($type, "message_type", $storeId);
        $message = $this->_helper->getConfigData($type, "message", $storeId);
        $message = __($message);

        switch($messageType) {
            case 'custom_block':
            case 'none':
                break;
            case 'notice':
                $this->_messageManager->addNoticeMessage($message);
                break;
            case 'warning':
                $this->_messageManager->addWarningMessage($message);
                break;
            case 'success':
                $this->_messageManager->addSuccessMessage($message);
                break;
            case 'error':
            default:
                $this->_messageManager->addErrorMessage($message);
                break;
        }
    }
    
    protected function _setRestrictRedirect($response, $type) {
        $storeId = $this->_storeManager->getStore()->getId();
        $messageType = $this->_helper->getConfigData($type, "message_type", $storeId);
        if($messageType == 'custom_block') {
            $this->_customerSession->setBeforeAuthUrl($this->_urlInterface->getCurrentUrl());
            $this->_actionFlag->set('', \Magento\Framework\App\Action\Action::FLAG_NO_DISPATCH, true);
            $response->setRedirect($this->_urlInterface->getUrl('customer/account/login', array('restrict' => $type)));
        } else {
            $this->_customerSession->setBeforeAuthUrl($this->_urlInterface->getCurrentUrl());
            $this->_customerSession->authenticate();
        }
    }
}