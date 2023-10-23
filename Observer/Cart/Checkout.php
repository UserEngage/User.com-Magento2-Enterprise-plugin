<?php

namespace Usercom\Analytics\Observer\Cart;

class Checkout implements \Magento\Framework\Event\ObserverInterface
{
    private \Usercom\Analytics\Helper\Usercom $helper;
    private \Magento\Framework\App\RequestInterface $request;
    private \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurableProduct;
    private \Magento\Framework\MessageQueue\PublisherInterface $publisher;
    private \Magento\Customer\Model\Session $customerSession;
    private \Magento\Checkout\Model\Session $checkoutSession;

    public function __construct(
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        \Usercom\Analytics\Helper\Usercom $helper,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurableProduct,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->helper              = $helper;
        $this->request             = $request;
        $this->configurableProduct = $configurableProduct;
        $this->publisher           = $publisher;
        $this->customerSession     = $customerSession;
        $this->checkoutSession     = $checkoutSession;
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
            'quoteId'         => $quote->getId(),
            'usercom_user_id' => $userComUserId,
            'user_key'        => $this->helper->getFrontUserKey(),
            'time'            => time()
        ];
        $this->publisher->publish('usercom.cart.checkout', json_encode($data));
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
