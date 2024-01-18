<?php

namespace Usercom\Analytics\Block\System\Config;

class SyncTime implements \Magento\Framework\Data\OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            1 => ['value' => '1', 'label' => __('3 months'), "time" => "-3 month"],
            2 => ['value' => '2', 'label' => __('6 months'), "time" => "-6 month"],
            3 => ['value' => '3', 'label' => __('12 months'), "time" => "-12 month"],
            4 => ['value' => '4', 'label' => __('All customers'), "time" => "all"]
        ];
    }
}
