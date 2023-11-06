<?php

namespace Usercom\Analytics\Observer\Customer;

class RegisterSuccess extends EventAbstract implements \Magento\Framework\Event\ObserverInterface
{

    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        $this->generateUserComUserID($observer);
    }
}
