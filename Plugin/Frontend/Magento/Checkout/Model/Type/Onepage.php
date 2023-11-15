<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Usercom\Analytics\Plugin\Frontend\Magento\Checkout\Model\Type;

class Onepage
{
    private \Usercom\Analytics\Helper\Usercom $helper;
    private \Magento\Framework\MessageQueue\PublisherInterface $publisher;
    private \Magento\Customer\Model\Session $customerSession;
    private \Psr\Log\LoggerInterface $logger;
    private \Magento\Checkout\Model\Session $checkoutSession;

    public function __construct(
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        \Usercom\Analytics\Helper\Usercom $helper,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->helper          = $helper;
        $this->publisher       = $publisher;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->logger          = $logger;
    }

    public function afterInitCheckout(
        \Magento\Checkout\Model\Type\Onepage $subject,
        $result
    ) {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->checkoutSession->getQuote();

        $userComUserId = null;
        if ($this->customerSession->isLoggedIn()) {
            $userComUserId = $this->customerSession->getCustomer()->getAttribute('usercom_user_id');
        }

        $data = [
            'quote_id'        => $quote->getId(),
            'usercom_user_id' => $userComUserId,
            'user_key'        => $this->helper->getFrontUserKey(),
            'time'            => time(),
            'step'            => '1'
        ];
        try {
            $this->publisher->publish('usercom.cart.checkout', json_encode($data));
        } catch (\Exception $e) {
            $this->logger->warning($e->getMessage());
        }

        //Your plugin code
        return $result;
    }
}
