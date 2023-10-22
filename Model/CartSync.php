<?php

namespace Usercom\Analytics\Model;

class CartSync extends ProductSyncAbstract
{

    /**
     * @param string $message
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function add(string $message): void
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

    /**
     * @param string $message
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function checkout(string $message): void
    {
        $this->eventType = $this->helper::PRODUCT_EVENT_CHECKOUT;
        $this->productEvent($message);
    }

    /**
     * @param string $message
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function remove(string $message): void
    {
        $this->eventType = $this->helper::PRODUCT_EVENT_REMOVE;
        $this->productEvent($message);
    }


}
