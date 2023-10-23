<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Usercom\Analytics\Plugin\Magento\Checkout\Model;

class ShippingInformationManagement
{
    private \Usercom\Analytics\Helper\Usercom $helper;
    private \Magento\Framework\MessageQueue\PublisherInterface $publisher;
    private \Magento\Customer\Model\Session $customerSession;
    private \Psr\Log\LoggerInterface $logger;

    public function __construct(
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        \Usercom\Analytics\Helper\Usercom $helper,
        \Magento\Customer\Model\Session $customerSession,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->helper          = $helper;
        $this->publisher       = $publisher;
        $this->customerSession = $customerSession;
        $this->logger          = $logger;
    }

    public function afterSaveAddressInformation(
        \Magento\Checkout\Model\ShippingInformationManagement $subject,
        $result,
        $cartId,
        $addressInformation
    ) {
        $this->logger->info('cartId', ['cartId' => $cartId]);

        $userComUserId = null;
        if ($this->customerSession->isLoggedIn()) {
            $userComUserId = $this->customerSession->getCustomer()->getAttribute('usercom_user_id');
        }
        $data = [
            'quote_id'            => $cartId,
            'usercom_user_id'    => $userComUserId,
            'user_key'           => $this->helper->getFrontUserKey(),
            'time'               => time(),
            'step'               => '2',
            'addressInformation' => $addressInformation
        ];
        $this->publisher->publish('usercom.cart.checkout', json_encode($data));

        //Your plugin code
        return $result;
    }
}
