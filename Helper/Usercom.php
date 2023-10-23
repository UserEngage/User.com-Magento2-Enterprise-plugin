<?php

namespace Usercom\Analytics\Helper;

class Usercom extends \Magento\Framework\App\Helper\AbstractHelper
{
    const COOKIE_USERKEY = "__ca__chat";
//    const COOKIE_USER_ID = "userComUserId";
    const DEBUG_USERCOM = true;
    const PRODUCT_PREFIX = "m2ee_";
    const PRODUCT_EVENT_ADD_TO_CART = "add to cart";
    const PRODUCT_EVENT_PURCHASE = 'purchase';
    const PRODUCT_EVENT_LIKING = 'liking';
    const PRODUCT_EVENT_ADD_TO_OBSERVATION = 'add to observation';
    const PRODUCT_EVENT_ORDER = 'order';
    const PRODUCT_EVENT_RESERVATION = 'reservation';
    const PRODUCT_EVENT_RETURN = 'return';
    const PRODUCT_EVENT_VIEW = 'view';
    const PRODUCT_EVENT_CLICK = 'click';
    const PRODUCT_EVENT_DETAIL = 'detail';
    const PRODUCT_EVENT_ADD = 'add';
    const PRODUCT_EVENT_REMOVE = 'remove';
    const PRODUCT_EVENT_CHECKOUT = 'checkout';
    const PRODUCT_EVENT_CHECKOUT_OPTION = 'checkout option';
    const PRODUCT_EVENT_REFUND = 'refund';
    const PRODUCT_EVENT_PROMO_CLICK = 'promo click';

    const EVENT_PURCHASE = 'purchase_details';
    const EVENT_CHECKOUT = 'order_details';

    const EVENT_LOGIN = 'login';
    const EVENT_REGISTER = 'register';
    const EVENT_NEWSLETTER_SIGN_UP = 'newsletter_signup';

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
        \Magento\Framework\App\Helper\Context $context,
        \Psr\Log\LoggerInterface $logger
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
        $this->logger                   = $logger;
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

    public function syncUserById($userId, $data)
    {
        return $this->sendCurl(
            sprintf('users/%s/', $userId),
            'PUT',
            $this->mapDataForUsercom($data)
        );
    }

    /**
     * @param $url
     * @param string $method
     * @param null $data
     *
     * @return mixed
     */
    public function sendCurl($url, string $method = 'GET', $data = null)
    {
        $ms   = microtime(true);
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL            => 'https://' . $this->helper->getSubdomain() . '/api/public/' . $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => [
                "Accept: */*; version=2",
                "authorization: Token " . $this->helper->getToken()
            ],
        ]);

        if ( ! empty($data)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($curl, CURLOPT_HTTPHEADER, [
                "Accept: */*; version=2",
                "authorization: Token " . $this->helper->getToken(),
                "content-type: application/json"
            ]);
        }

        $response = curl_exec($curl);
        $err      = curl_error($curl);
        $me       = microtime(true);

        $this->logRequest('sendCurl' . $method, $url, $data, $me - $ms, $response);
        if ( ! empty($err)) {
            $this->logError('sendCurl' . $method, $url, $err, $response);
        }
        curl_close($curl);

        return ($err) ? null : json_decode($response);
    }

    /**
     * @param $data
     *
     * @return void
     */
    private function logRequest($name, $url, $data, $mt, $response): void
    {
        if (self::DEBUG_USERCOM) {
            $this->logger->info(
                "Usercom",
                [$name . ': ' . $mt . "\n" . $url . "\nREQUEST:   " . json_encode($data) . "\nRESPONSE:   " . $response . "\n\n\n\n\n"]
            );
        }
    }

    private function logError(string $name, $url, string $err, $response)
    {
        if (self::DEBUG_USERCOM) {
            $this->logger->error(
                "Usercom Error",
                [$name . 'Error: ' . "\n" . $url . "\n" . json_encode($err) . "\n" . json_encode($response) . "\n"]
            );
        }
    }

    public function mapDataForUsercom($data): array
    {
        $fieldsMap               = $this->helper->getFieldMapping();
        $mappedData              = [];
        $mappedData['email']     = $data['email'];
        $mappedData['user_id']   = $data['usercom_user_id'];
        $mappedData['custom_id'] = $data['usercom_user_id'];
//        $mappedData['paywall']                = $user->hasSubscription();
//        $mappedData['paywall_type']           = $sub->type ?? null;
//        $mappedData['paywall_period']         = $sub->period ?? null;
//        $mappedData['paywall_active']         = $daysLeft > 0 ?? false;
//        $mappedData['paywall_active_payment'] = $sub->is_active ?? false;
//        $mappedData['paywall_days_left']      = $daysLeft;
//        $mappedData['account_ltv']            = $data['total'];
        $mappedData['account_created_at'] = $data['created_at'];
        $mappedData['account_is_active']  = ! empty($data['confirmation']);
        $mappedData['First name']         = $data['firstname'];
        $mappedData['Last name']          = $data['lastname'];
        foreach ($fieldsMap ?? [] as $field) {
            if (isset($data[$field['name']])) {
                if ($field['mapping'] === 'automatic') {
                    $mappedData[$field['name']] = $data[$field['name']];
                }
            }
        }

        return $mappedData;
    }

    public function syncUserHash($data)
    {
        return $this->sendCurl(
            sprintf('users/update_or_create/'),
            'POST',
            $this->mapDataForUsercom($data)
        );
    }

    public function getCustomerByCustomId($custom_id)
    {
        return $this->sendCurl('users-by-id/' . $custom_id . '/', 'GET');
    }

    /**
     * @param $email
     *
     * @return mixed|null
     */
    public function getUserByEmail($email)
    {
        $user = $this->sendCurl('users/search/?email=' . $email, 'GET');
        if ( ! empty($user)) {
            return $user;
        }

        return null;
    }

    /**
     * @param $email
     *
     * @return mixed
     */
    public function getUsersByEmail($email): array
    {
        $users = $this->sendCurl('users/search/?email=' . $email . '&many=true', 'GET');
        if ( ! empty($users)) {
            return $users;
        }

        return [];
    }

    public function listAttributes()
    {
        $attributes = $this->sendCurl('attributes/', 'GET');
        if ( ! empty($attributes)) {
            return $attributes->results ?? [];
        }

        return [];
    }

    public function syncAttribute()
    {
        $params    = [];
        $name      = $_POST['name'] ?? false;
        $valueType = $_POST['value_type'] ?? false;
        if ($name === false || $valueType === false) {
            return [];
        }
        $params['name']         = $name;
        $params['value_type']   = $valueType;
        $params['content_type'] = 'clientuser';
        $attributes             = $this->sendCurl('attributes/', 'POST', $params);
        if ( ! empty($attributes)) {
            return $attributes->results ?? [];
        }

        return [];
    }

    public function createProduct($data)
    {
        return $this->sendCurl("products/", 'POST', $data);
    }

    public function getUsercomProductId($productId = null)
    {
        if ( ! $productId) {
            return false;
        }
        $this->logger->info("Product Custom ID:", ['custom_id' => $productId]);

        $usercomProduct = $this->getProductByCustomId($productId);
        $this->logger->info("Usercom Product ID:", ['usercomProductId' => $usercomProduct->id ?? null]);

        return $usercomProduct->id ?? null;
    }

    public function getProductByCustomId($custom_id)
    {
        return $this->sendCurl("products-by-id/$custom_id/details/");
    }

    public function getFrontUserKey()
    {
        return $this->cookieManager->getCookie(self::COOKIE_USERKEY);
    }

    public function createProductEvent($id, $data)
    {
        return $this->sendCurl("products/$id/product_event/", 'POST', $data);
    }

    public function createEvent($data)
    {
        if ($this->helper->sendStoreSource()) {
            $this->logger->info("SERVER:", ['server' => $_SERVER]);
            $host                         = $_SERVER["BASE_SECURE_URL"] ?? $_SERVER["BASE_URL"];
            $data["data"]["store_source"] = $host . "/";
        }
        $this->logger->info("Event Data:", ['data' => $data]);

        return $this->sendCurl("events/", 'POST', $data);
    }

    public function createEventByCustomId($userCustomId, $data)
    {
        if ($this->helper->sendStoreSource()) {
            $host                         = $_SERVER["BASE_SECURE_URL"] ?? $_SERVER["BASE_URL"];
            $data["data"]["store_source"] = $host . "/";
        }

        return $this->sendCurl("/users-by-id/$userCustomId/events/", 'POST', $data);
    }

    public function getUserByUserKey(string $usercomKey)
    {
        return $this->sendCurl("users/search/?key=" . $usercomKey, 'GET');
    }

    public function updateCustomer($usercomId, array $data)
    {
        return $this->sendCurl("users/$usercomId/", "PUT", $data);
    }
}
