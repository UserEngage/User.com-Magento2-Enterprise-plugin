<?php

namespace Usercom\Analytics\Block\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;

class ProductsPurchaseSyncButton extends \Magento\Config\Block\System\Config\Form\Field
{

    const BUTTON_ID = "syncProductsPurchase";
    const TIME_ID = "usercom_sync_customersSyncTime";

    protected $_template = 'Usercom_Analytics::system/config/syncButton.phtml';

    /**
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Remove scope label
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }

    public function getAjaxUrl()
    {
        return $this->getUrl('usercom_analytics/system_config/syncproductspurchase');
    }

    public function getButtonId()
    {
        return self::BUTTON_ID;
    }

    public function getTimeId()
    {
        return self::TIME_ID;
    }

    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            [
                'id'    => self::BUTTON_ID,
                'label' => __('Synchronize Completed Orders'),
            ]
        );

        return $button->toHtml();
    }

    /**
     * Return element html
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return $this->_toHtml();
    }
}
