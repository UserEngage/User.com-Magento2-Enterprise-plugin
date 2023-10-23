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
            'time'            => time()
        ];
        $this->publisher->publish('usercom.customer.register', json_encode($data));

//        if( !$this->helper->isModuleEnabled() || !($usercomCustomerId = $this->usercom->getUsercomCustomerId($customerId)) )
//            return;

//        $postData = $this->request->getParams();
//        unset($postData["firstname"]);
//        unset($postData["lastname"]);
//        unset($postData["is_subscribed"]);
//        unset($postData["email"]);
//        unset($postData["password"]);
//        unset($postData["password_confirmation"]);

        $data = [
//            'user_hash' => $this->getUserHash($customerId),
//            "user_id" => $usercomCustomerId,
//            "name"      => "registration",
//            "timestamp" => time(),
//            "data"      => array_merge($this->usercom->getCustomerData($customerId), $postData)
        ];
//        $this->helper->set
//        $this->usercom->createEvent($data);
    }
}
