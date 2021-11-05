<?php
namespace MageMaclean\RestrictAccess\Block;

class CustomBlock extends \Magento\Framework\View\Element\Template
{
    protected $_helper;
    protected $_request;
    protected $_storeManager;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \MageMaclean\RestrictAccess\Helper\Data $helper,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->_helper = $helper;
        $this->_request = $request;
        $this->_storeManager = $storeManager;
        
        parent::__construct($context, $data);
    }

    protected function getRestrictType() {
        return $this->_request->getParam('restrict', false);
    }

    public function canShowCustomBlock() {
        
        if(!$this->_helper->isEnabled() || !$this->getRestrictType()) return false;
        
        return $this->_helper->getConfigData($this->getRestrictType(), "message_type", $this->_storeManager->getStore()->getId()) == 'custom_block';
    }

    public function getRestrictMessage() {
        return $this->_helper->getConfigData($this->getRestrictType(), "message", $this->_storeManager->getStore()->getId());
    }
}
