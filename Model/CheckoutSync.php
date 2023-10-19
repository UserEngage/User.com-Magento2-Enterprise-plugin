<?php

namespace Usercom\Analytics\Model;

class CheckoutSync extends ProductSyncAbstract
{

    /**
     * @param string $message
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function addToCart(string $message): void
    {
        $this->eventType = $this->helper::PRODUCT_EVENT_ADD_TO_CART;
        $this->productEvent($message);
    }

    /**
     * @param string $message
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function purchase(string $message): void
    {
        $this->eventType = $this->helper::PRODUCT_EVENT_PURCHASE;
        $this->productEvent($message);
    }


}
