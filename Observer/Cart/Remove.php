<?php

namespace Usercom\Analytics\Observer\Cart;

class Remove implements \Magento\Framework\Event\ObserverInterface
{

    protected \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurableProduct;
    protected \Magento\Framework\App\RequestInterface $request;
    protected \Magento\Framework\MessageQueue\PublisherInterface $publisher;
    protected \Magento\Customer\Model\Session $customerSession;
    protected \Usercom\Analytics\Helper\Usercom $helper;

    public function __construct(
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        \Usercom\Analytics\Helper\Usercom $helper,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurableProduct,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->customerSession     = $customerSession;
        $this->helper              = $helper;
        $this->request             = $request;
        $this->configurableProduct = $configurableProduct;
        $this->publisher           = $publisher;
    }

    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        $productId = $observer->getQuoteItem()->getProduct()->getId();
        if ($option = $observer->getQuoteItem()->getOptionByCode('simple_product')) {
            $productId = $option->getProduct()->getId();
        }

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
        $this->publisher->publish('usercom.cart.product.remove', json_encode($data));
    }
}
