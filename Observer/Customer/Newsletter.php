<?php

namespace Usercom\Analytics\Observer\Customer;

class Newsletter extends EventAbstract implements \Magento\Framework\Event\ObserverInterface
{
    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        $subscriber      = $observer->getEvent()->getSubscriber();
        $customerId      = $subscriber->getCustomerId();
        $subscribeStatus = ($subscriber->getStatus() == 1);

        $data = [
            'usercom_user_id' => $userComUserId ?? null,
            'user_key'        => $this->usercom->getFrontUserKey(),
            "email"           => $subscriber->getSubscriberEmail(),
            "customerId"      => $customerId,
            "subscribeStatus" => $subscribeStatus,
            'time'            => time()
        ];
        try {
            $this->publisher->publish('usercom.customer.newsletter', json_encode($data));
        } catch (\Exception $e) {
            $this->logger->warning($e->getMessage());
        }
    }
}
