<?php
namespace MageMaclean\RestrictAccess\Model\Config\Source;

class Messagetype implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'notice', 'label' => __('Notice')],
            ['value' => 'warning', 'label' => __('Warning')],
            ['value' => 'error', 'label' => __('Error')],
            ['value' => 'success', 'label' => __('Success')],
            ['value' => 'custom_block', 'label' => __('Custom Block')],
            ['value' => 'none', 'label' => __('None')]
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'notice' => __('Yes'),
            'warning' => __('Warning'),
            'error' => __('Error'),
            'success' => __('Success'),
            'custom_block' => __('Custom Block'),
            'none' => __('None')
        ];
    }
}
