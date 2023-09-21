<?php

namespace Usercom\Analytics\Observer\Customer;

class Login extends EventAbstract implements \Magento\Framework\Event\ObserverInterface
{

    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        $this->generateUserComUserID($observer);

//        $customer = $observer->getEvent()->getData('customer');
//
//        if( !$this->helper->isModuleEnabled() || !($usercomCustomerId = $this->usercom->getUsercomCustomerId($customer->getId())) )
//            return;
//
//        $this->usercom->updateCustomer($usercomCustomerId,$this->usercom->getCustomerData());
//
//        $data = array(
//            "user_id" => $usercomCustomerId,
//            "name" => "login",
//            "timestamp" => time(),
//            "data" => array(
//                "email" => $customer->getEmail()
//            )
//        );
//
//        $this->usercom->createEvent($data);
    }

    /**
     * @param $customerId
     *
     * @return string
     */
    private function getUserHash($customerId): string
    {
        return $customerId . '_' . hash('sha256', $customerId . '-' . date('Y-m-d H:i:s') . $this->salt());
    }

    private function salt()
    {
        return 'usercom_salt';
    }
}
