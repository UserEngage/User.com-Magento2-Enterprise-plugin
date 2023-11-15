<?php

namespace Usercom\Analytics\Observer\Cart;

class Checkout implements \Magento\Framework\Event\ObserverInterface
{
    protected \Usercom\Analytics\Helper\Usercom $helper;
    protected \Magento\Framework\App\RequestInterface $request;
    protected \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurableProduct;
    protected \Magento\Framework\MessageQueue\PublisherInterface $publisher;
    protected \Magento\Customer\Model\Session $customerSession;
    protected \Magento\Checkout\Model\Session $checkoutSession;
    protected \Psr\Log\LoggerInterface $logger;

    public function __construct(
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        \Usercom\Analytics\Helper\Usercom $helper,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurableProduct,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->helper              = $helper;
        $this->request             = $request;
        $this->configurableProduct = $configurableProduct;
        $this->publisher           = $publisher;
        $this->customerSession     = $customerSession;
        $this->checkoutSession     = $checkoutSession;
        $this->logger              = $logger;
    }

    public function execute(
        \Magento\Framework\Event\Observer $observer
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
    }

    /**
     * Format product item for output to json
     *
     * @param \Magento\Quote\Model\Quote\Item $item
     *
     * @return array
     */
    protected function _formatProduct($item): array
    {
        $product              = [];
        $product['productId'] = $item->getId();
//        if ($item->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
//            $attributes = $productData['super_attribute'];
//            $productId  = $this->configurableProduct->getProductByAttributes($attributes, $item)->getId();
//        }
        $product['id']    = $item->getSku();
        $product['name']  = $item->getName();
        $product['price'] = $item->getPrice();
        $product['qty']   = $item->getQty();

        return $product;
    }
}
