<?php

namespace Usercom\Analytics\Block\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;

class AttributesSyncButton extends \Magento\Config\Block\System\Config\Form\Field
{
    const BUTTON_ID = "syncAttributes";
    const TIME_ID = "usercom_sync_customersSyncTime";
    const BASE_FIELDS = [
        [
            "name"         => 'orders_ltv',
            'content_type' => "user",
            "value_type"   => "float",
            "mapping"      => "automatic"
        ],
        [
            "name"         => 'orders_aov',
            'content_type' => "user",
            "value_type"   => "float",
            "mapping"      => "automatic"
        ],
        [
            "name"         => 'orders_count',
            'content_type' => "user",
            "value_type"   => "integer",
            "mapping"      => "automatic"
        ],
        [
            "name"         => 'marketing_allow',
            'content_type' => "user",
            "value_type"   => "boolean",
            "mapping"      => "automatic"
        ]
    ];

    protected $_template = 'Usercom_Analytics::system/config/attributesButton.phtml';

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
        return $this->getUrl('usercom_analytics/system_config/listAttributes');
    }

    /**
     * @return string
     */
    public function getSyncAjaxUrl(): string
    {
        return $this->getUrl('usercom_analytics/system_config/syncattributes');
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
    public function getFields(): array
    {
        return self::BASE_FIELDS;
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
                'label' => __('Configure Custom Attributes'),
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
