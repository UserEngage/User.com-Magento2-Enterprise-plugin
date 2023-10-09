<?php

namespace Usercom\Analytics\Helper;

use Modules\Identity\Command\App\Support\Helpers\UserUniqueIdCreator;

class Usercom extends \Magento\Framework\App\Helper\AbstractHelper
{
    const COOKIE_USERKEY = "userKey";
//    const COOKIE_USER_ID = "userComUserId";
    const DEBUG_USERCOM = true;
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

        $this->logRequest('sendCurl' . $method, $url, [], $me - $ms, $response);
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
            file_put_contents(
                '/var/www/var/log/usercom.log',
                $name . ': ' . $mt . "\n" . $url . "\nREQUEST:   " . json_encode($data) . "\nRESPONSE:   " . $response . "\n\n\n\n\n",
                FILE_APPEND
            );
        }
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

    public function mapDataForUsercom($data): array
    {
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
        $mappedData['account_is_active']  = $data['is_active'];
        $mappedData['First name']         = $data['firstname'];
        $mappedData['Last name']          = $data['lastname'];

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
}
