<?php
namespace MageMaclean\RestrictAccess\Plugin;


class LoginPost
{
    protected $_customerSession;

    public function __construct(\Magento\Customer\Model\Session $customerSession)
    {
        $this->_customerSession = $customerSession;
    }

    /**
     * @param \Magento\Customer\Controller\Account\LoginPost $subject
     * @param mixed $result
     */
    public function afterExecute(\Magento\Customer\Controller\Account\LoginPost $subject, $result)
    {
        if ($result instanceof \Magento\Framework\Controller\Result\Redirect && $this->_customerSession->getRestrictedRedirectUrl()) {
            $result->setPath($this->_customerSession->getRestrictedRedirectUrl());
        }
        
        return $result;
    }
}