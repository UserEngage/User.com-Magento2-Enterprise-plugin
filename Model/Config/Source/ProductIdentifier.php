<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Usercom\Analytics\Model\Config\Source;


/**
 *
 */
class ProductIdentifier implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => 'sku', 'label' => __('SKU')], ['value' => 'id', 'label' => __('ID')]];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return ['sku' => __('SKU'), 'id' => __('ID')];
    }
}
