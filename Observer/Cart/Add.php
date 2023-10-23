<?php

namespace Usercom\Analytics\Observer\Cart;

class Add implements \Magento\Framework\Event\ObserverInterface
{
    private \Usercom\Analytics\Helper\Usercom $helper;
    private \Magento\Framework\App\RequestInterface $request;
    private \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurableProduct;
    private \Magento\Framework\MessageQueue\PublisherInterface $publisher;
    private \Magento\Customer\Model\Session $customerSession;

    public function __construct(
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        \Usercom\Analytics\Helper\Usercom $helper,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurableProduct,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->helper              = $helper;
        $this->request             = $request;
        $this->configurableProduct = $configurableProduct;
        $this->publisher           = $publisher;
        $this->customerSession     = $customerSession;
    }

    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        $product     = $observer->getEvent()->getData('product');
        $productData = $this->request->getParams();
        $productId   = $product->getId();
        if ($product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
            $attributes = $productData['super_attribute'];
            $productId  = $this->configurableProduct->getProductByAttributes($attributes, $product)->getId();
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
        $this->publisher->publish('usercom.cart.product.add', json_encode($data));
    }
}
