<?php
namespace Usercom\Analytics\Helper;

class Usercom extends \Magento\Framework\App\Helper\AbstractHelper
{
    const COOKIE_USERKEY = "__ca__chat";
//    const COOKIE_USER_ID = "userComUserId";
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
    public bool $debug = false;
    public string $prefix = "";
    private Data $helper;
    private \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager;
    private \Psr\Log\LoggerInterface $logger;

    public function __construct(
        \Usercom\Analytics\Helper\Data $helper,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\App\Helper\Context $context,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->helper        = $helper;
        $this->cookieManager = $cookieManager;
        $this->logger        = $logger;
        $this->prefix        = $this->helper->getPrefix() ?? '';
        if (defined('USER_COM_DEBUG')) {
            $this->debug = USER_COM_DEBUG;
        }
        parent::__construct($context);
    }

    public function getPrefix(): string
    {
        return $this->prefix;
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

        $this->debug('sendCurl' . $method, $data, $response, $url, $me - $ms,);
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
    private function debug($name, $data, $response = [], $url = "", $mt = null): void
    {
        if ($this->debug) {
            $this->logger->debug(
                "UsercomPluginDebug: " . $name,
                ['time' => $mt, 'url' => $url, 'REQUEST' => json_encode($data), "RESPONSE" => $response]
            );
        }
    }

    private function logError(string $name, $url, string $err, $response)
    {
        $this->logger->error(
            "UsercomPluginError: " . $name,
            ['url' => $url, 'error' => json_encode($err), 'response' => json_encode($response)]
        );
    }

    public function mapDataForUsercom($data): array
    {
        $fieldsMap                        = $this->helper->getFieldMapping();
        $mappedData                       = [];
        $mappedData['email']              = $data['email'];
        $mappedData['user_id']            = $data['usercom_user_id'];
        $mappedData['custom_id']          = $data['usercom_user_id'];
        $mappedData['account_created_at'] = $data['created_at'];
        $mappedData['account_is_active']  = ! empty($data['confirmation']);
        $mappedData['First name']         = $data['firstname'];
        $mappedData['Last name']          = $data['lastname'];
        if (isset($data['unsubscribed'])) {
            $mappedData['unsubscribed'] = $data['unsubscribed'];
        }
        foreach ($fieldsMap ?? [] as $field) {
            if (isset($data[$field['name']])) {
                if ($field['mapping'] === 'automatic') {
                    $mappedData[$field['name']] = $data[$field['name']];
                }
            }
        }
        $this->debug('mapDataForUsercom', $mappedData);

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
        $this->debug("Product Custom ID:", ['custom_id' => $productId]);
        $usercomProduct = $this->getProductByCustomId($productId);
        $this->debug("Usercom Product ID:", ['usercomProductId' => $usercomProduct->id ?? null]);

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
        $this->debug("Create ProductEvent " . $data['event_type'] . ':', ['id' => $data['id']]);

        return $this->sendCurl("products/$id/product_event/", 'POST', $data);
    }

    public function createEvent($data)
    {
        if ($this->helper->sendStoreSource()) {
            $this->debug("SERVER:", ['server' => $_SERVER]);
            $host                         = $_SERVER["BASE_SECURE_URL"] ?? $_SERVER["BASE_URL"];
            $data["data"]["store_source"] = $host . "/";
        }
        $this->debug("Create Event " . ($data['name'] ?? '') . ':', $data);

        return $this->sendCurl("events/", 'POST', $data);
    }

    public function createEventByCustomId($userCustomId, $data)
    {
        if ($this->helper->sendStoreSource()) {
            $host                         = $_SERVER["BASE_SECURE_URL"] ?? $_SERVER["BASE_URL"];
            $data["data"]["store_source"] = $host . "/";
        }

        return $this->sendCurl("users-by-id/$userCustomId/events/", 'POST', $data);
    }

    public function getUserByUserKey(string $usercomKey)
    {
        return $this->sendCurl("users/search/?key=" . $usercomKey, 'GET');
    }

    public function updateCustomer($usercomId, array $data)
    {
        if (empty($usercomId)) {
            return;
        }

        return $this->sendCurl("users-by-id/$usercomId/", "PUT", $data);
    }

    /**
     * @param $data
     *
     * @return void
     */
    private function log($name, $data, $response = [], $url = "", $mt = null): void
    {
        $dataToSend = [];

        $dataToSend['request'] = $data;

        if ( ! empty($url)) {
            $dataToSend['url'] = $url;
        }
        if ( ! empty($mt)) {
            $dataToSend['mt'] = $mt;
        }
        if ( ! empty($response)) {
            $dataToSend['response'] = $response;
        }

        $this->logger->info("UserComPlugin: " . $name, $dataToSend);
    }
}
