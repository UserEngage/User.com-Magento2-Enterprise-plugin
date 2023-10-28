<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Usercom\Analytics\Plugin\Magento\Sales\Model\Service;

class OrderService
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

    public function afterPlace(
        \Magento\Sales\Model\Service\OrderService $subject,
        $result,
        $order
    ) {
        $this->logger->debug('OrderService afterPlace');
        $userComUserId = null;
        if ($this->customerSession->isLoggedIn()) {
            $userComUserId = $this->customerSession->getCustomer()->getAttribute('usercom_user_id');
        }
        $data = [
            'order_id'        => $order->getId(),
            'usercom_user_id' => $userComUserId,
            'user_key'        => $this->helper->getFrontUserKey(),
            'time'            => $order->getCreatedAt(),
            'step'            => '2'
        ];
        try {
            $this->publisher->publish('usercom.order.purchase', json_encode($data));
        } catch (\Exception $e) {
            $this->logger->warning($e->getMessage());
        }

        //Your plugin code
        return $result;
    }
}
