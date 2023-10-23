<?php

namespace Usercom\Analytics\Observer\Cart;

class PlaceOrder implements \Magento\Framework\Event\ObserverInterface
{
    private \Usercom\Analytics\Helper\Usercom $helper;
    private \Magento\Framework\MessageQueue\PublisherInterface $publisher;
    private \Magento\Customer\Model\Session $customerSession;

    public function __construct(
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        \Usercom\Analytics\Helper\Usercom $helper,
        \Magento\Customer\Model\Session $customerSession,
    ) {
        $this->helper          = $helper;
        $this->publisher       = $publisher;
        $this->customerSession = $customerSession;
    }

    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        $order = $observer->getEvent()->getOrder();

        $userComUserId = null;
        if ($this->customerSession->isLoggedIn()) {
            $userComUserId = $this->customerSession->getCustomer()->getAttribute('usercom_user_id');
        }

        $data = [
            'order_id'        => $order->getId(),
            'usercom_user_id' => $userComUserId,
            'user_key'        => $this->helper->getFrontUserKey()
        ];
        $this->publisher->publish('usercom.order.purchase', json_encode($data));
    }
}
