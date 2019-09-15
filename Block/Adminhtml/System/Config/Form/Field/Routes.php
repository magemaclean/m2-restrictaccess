<?php
namespace MageMaclean\RestrictAccess\Block\Adminhtml\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

class Routes extends AbstractFieldArray
{
    protected function _prepareToRender()
    {
        $this->addColumn('route', ['label' => __('Route'), 'class' => 'required-entry']);
        $this->addColumn('name', ['label' => __('Name'), 'class' => 'required-entry']);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Custom Route');
    }
}