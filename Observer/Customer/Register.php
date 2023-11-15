<?php

namespace Usercom\Analytics\Observer\Customer;

class Register extends EventAbstract implements \Magento\Framework\Event\ObserverInterface
{

    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        $userComUserId = $this->generateUserComUserID($observer);

        $data = [
            'usercom_user_id' => $userComUserId ?? null,
            'user_key'        => $this->usercom->getFrontUserKey(),
            'time'            => time(),
            'email'           => $observer->getEvent()->getCustomer()->getEmail(),
            'customerId'      => $observer->getEvent()->getCustomer()->getId()
        ];
        try {
            $this->publisher->publish('usercom.customer.register', json_encode($data));
        } catch (\Exception $e) {
            $this->logger->warning($e->getMessage());
        }
    }
}
