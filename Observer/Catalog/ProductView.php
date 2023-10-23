<?php

namespace Usercom\Analytics\Observer\Catalog;

class ProductView implements \Magento\Framework\Event\ObserverInterface
{
    protected $customerSession;
    private \Magento\Framework\MessageQueue\PublisherInterface $publisher;
    private \Usercom\Analytics\Helper\Usercom $helper;

    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        \Usercom\Analytics\Helper\Usercom $helper
    ) {
        $this->customerSession = $customerSession;
        $this->publisher       = $publisher;
        $this->helper          = $helper;
    }

    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        $userComUserId = null;
        if ($this->customerSession->isLoggedIn()) {
            $userComUserId = $this->customerSession->getCustomer()->getAttribute('usercom_user_id');
        }

        $data = [
            'productId'       => $observer->getEvent()->getRequest()->getParam('id'),
            'usercom_user_id' => $userComUserId,
            'user_key'        => $this->helper->getFrontUserKey()
        ];
        $this->publisher->publish('usercom.catalog.product.event', json_encode($data));
    }
}
