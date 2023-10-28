<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Usercom\Analytics\Plugin\Magento\Catalog\Helper;

class Product
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

    public function afterInitProduct(
        \Magento\Catalog\Helper\Product $subject,
        $result,
        $productId,
        $controller,
        $params = null
    ) {

        $userComUserId = null;
        if ($this->customerSession->isLoggedIn()) {
            $userComUserId = $this->customerSession->getCustomer()->getAttribute('usercom_user_id');
        }

        $data = [
            'productId'       => $productId,
            'usercom_user_id' => $userComUserId,
            'user_key'        => $this->helper->getFrontUserKey(),
            'time'            => time()
        ];
        try {
            $this->publisher->publish('usercom.catalog.product.event', json_encode($data));
        } catch (\Exception $e) {
            $this->logger->warning($e->getMessage());
        }

        //Your plugin code
        return $result;
    }
}
