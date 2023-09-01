<?php

namespace Usercom\Analytics\Helper;

class Usercom extends \Magento\Framework\App\Helper\AbstractHelper
{
    const COOKIE_USERKEY = "userKey";
//    const COOKIE_USER_ID = "userComUserId";
    const DEBUG_USERCOM = false;
    protected $helper;
    protected $cookieManager;
    protected $storeManager;
    protected $productRepositoryFactory;
    protected $subscriber;
    protected $customerSession;
    protected $customer;
    protected $product;
    protected $resourceConnection;

    public function __construct(
        \Usercom\Analytics\Helper\Data $helper,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Api\ProductRepositoryInterfaceFactory $productRepositoryFactory,
        \Magento\Newsletter\Model\Subscriber $subscriber,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Model\Customer $customer,
        \Magento\Catalog\Model\Product $product,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->helper                   = $helper;
        $this->cookieManager            = $cookieManager;
        $this->storeManager             = $storeManager;
        $this->productRepositoryFactory = $productRepositoryFactory;
        $this->subscriber               = $subscriber;
        $this->customerSession          = $customerSession;
        $this->customer                 = $customer;
        $this->product                  = $product;
        $this->resourceConnection       = $resourceConnection;
        parent::__construct($context);
    }

    /**
     * @param $customerId
     *
     * @return string
     */
    public function getUserHash($customerId): string
    {
        return $customerId . '_' . hash('sha256', $customerId . '-' . date('Y-m-d H:i:s') . $this->salt());
    }

    private function salt()
    {
        return 'usercom_salt';
    }

    private function logError(string $name, $url, string $err, $response)
    {
        if (self::DEBUG_USERCOM) {
            file_put_contents(
                '/var/www/var/log/usercom.log',
                $name . 'Error: ' . "\n" . $url . "\n" . json_encode($err) . "\n" . json_encode($response) . "\n",
                FILE_APPEND
            );
        }
    }
}
