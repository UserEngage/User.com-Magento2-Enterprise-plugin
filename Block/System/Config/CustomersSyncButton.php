<?php

namespace Usercom\Analytics\Block\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;

class CustomersSyncButton extends \Magento\Config\Block\System\Config\Form\Field
{
    const BUTTON_ID = "syncCustomers";
    const TIME_ID = "usercom_sync_customersSyncTime";

    protected $_template = 'Usercom_Analytics::system/config/syncButton.phtml';

    /**
     * @param Context $context
     * @param array $data
     */
    public function __construct(Context $context, array $data = [])
    {
        parent::__construct($context, $data);
    }

    /**
     * Remove scope label
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element): string
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }

    /**
     * @return string
     */
    public function getAjaxUrl(): string
    {
        return $this->getUrl('usercom_analytics/system_config/synccustomer');
    }

    /**
     * @return string
     */
    public function getButtonId(): string
    {
        return self::BUTTON_ID;
    }

    /**
     * @return string
     */
    public function getTimeId(): string
    {
        return self::TIME_ID;
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            [
                'id'    => self::BUTTON_ID,
                'label' => __('Synchronize Customers'),
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
    protected function _getElementHtml(AbstractElement $element): string
    {
        return $this->_toHtml();
    }
}
