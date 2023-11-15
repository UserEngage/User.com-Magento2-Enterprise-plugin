<?php

namespace Usercom\Analytics\Model;

class CustomerSync extends CustomerSyncAbstract
{
    private array $orders = [];

    /**
     * @param string $message
     *
     * @return void
     */
    public function register(string $message): void
    {
        list($usercomUserId, $usercomKey, $messageData) = $this->extractParams($message);
        if (isset($messageData['customerId']) && ! empty($messageData['customerId'])) {
            list($usercomUserId, $usercomKey) = $this->syncCustomerById($messageData['customerId']);
            $messageData['usercom_user_id'] = $usercomUserId;
            $messageData['user_key']        = $usercomKey;
            $message                        = json_encode($messageData);
        }

        $this->eventType = $this->helper::EVENT_REGISTER;
        $this->event($message);
    }

    /**
     * @param string $message
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function syncCustomerById(string $customerId): array
    {
        $this->debug("CustomerSync", ['customerId:' => $customerId]);
        $customerId = $customerId ?? null;
        $user       = null;
        if ($customerId !== null) {
            $customer = $this->customerRepository->getById($customerId);
            $data     = $customer->__toArray();

            $customerUsercomUserId = $data['usercom_user_id'] = $data['custom_attributes']['usercom_user_id']['value'] ?? null;
            $customerUsercomKey    = $data['usercom_key'] = $data['custom_attributes']['usercom_key']['value'] ?? null;

            $customerEmail = $customer->getEmail();
            $customerId    = $customer->getId();
            $this->debug('CustomerData:', [json_encode($data)]);

            if (empty($customerUsercomKey)) {
                $users = $this->helper->getUsersByEmail($customerEmail);
                $this->debug('$users:', [json_encode($users)]);

                foreach ($users ?? [] as $u) {
                    if ($u->custom_id == $customerUsercomUserId) {
                        $user = $u;
                    }
                }
                unset($u);

                if ($user === null) {
                    $user = $this->helper->getCustomerByCustomId($customerUsercomUserId);
                }
                $this->debug('$user:', [json_encode($user)]);
                if ($user === null && $users) {
                    $user = $users[0];
                }
                if (empty($customerUsercomUserId)) {
                    $hash = null;
                    if ($user !== null) {
                        $hash = $user->custom_id ?? null;
                    }
                    if (empty($hash)) {
                        $hash = $this->helper->getUserHash($customerId);
                    }
                    $data['usercom_user_id'] = $hash;
                    $customer->setCustomAttribute(
                        'usercom_user_id',
                        $hash
                    );
                    $this->customerRepository->save($customer);
                }
                if ($user !== null && isset($user->user_key) && ! empty($user->user_key)) {
                    $customer->setCustomAttribute(
                        'usercom_key',
                        $user->user_key
                    );
                    $this->customerRepository->save($customer);
                }
            }

            $this->mapDataForUserCom($data, $customer);

            if ( ! empty($data['usercom_key'])) {
                $userByKey = $this->helper->getUserByUserKey($data['usercom_key'] ?? null);
                $this->helper->syncUserById($userByKey->id ?? null, $data);
            } else {
                $this->helper->syncUserHash($data);
            }
            if (empty($data['usercom_key'])) {
                $userSynced = $this->helper->getCustomerByCustomId($data['usercom_user_id']);
                $this->debug('$userSynced:', [json_encode($userSynced)]);
                if ($userSynced && isset($userSynced->user_key) && ! empty($userSynced->user_key)) {
                    $customer->setCustomAttribute(
                        'usercom_key',
                        $userSynced->user_key
                    );
                    $this->customerRepository->save($customer);
                }
            }
            $this->debug("CustomerSync EOF", $data);

            return [$data['usercom_user_id'] ?? null, $data['usercom_key'] ?? null];
        }

        return [];
    }

    public function mapDataForUserCom(&$customerData, $customer)
    {
        $fieldsMap = $this->dataHelper->getFieldMapping();
        $this->calculateCustomer($customer);
        foreach ($fieldsMap as $field) {
            $fieldName = $field["name"];
            if (isset($field["mapping"]) && $field["mapping"] == "automatic") {
                switch ($fieldName) {
                    case "orders_ltv":
                        $customerData[$fieldName] = $this->getOrdersLtv($customer);
                        break;
                    case "orders_aov":
                        $customerData[$fieldName] = $this->getOrdersAov($customer);
                        break;
                    case "orders_count":
                        $customerData[$fieldName] = $this->getOrdersCount($customer);
                        break;
                    case "marketing_allow":
                        $customerData[$fieldName]     = $this->getMarketingAllow($customer);
                        $customerData['unsubscribed'] = $this->getMarketingAllow($customer) == 0;
                        break;
                }
            }
        }
        $this->debug("CustomerSync mapDataForUserCom", $customerData);
    }

    public function calculateCustomer(&$customer): float
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('customer_id', $customer->getId())
            ->addFilter('state', 'complete')
            ->create();
        $customerOrders = $this->orderRepository->getList($searchCriteria);
        $ltv            = 0;
        $cnt            = 0;
        foreach ($customerOrders as $customerOrder) {
            $orderData = $customerOrder->getData();
            $ltv       += floatval($orderData["total_invoiced"]);
            $cnt++;
        }
        $this->orders[$customer->getId()]['id']  = $customer->getId();
        $this->orders[$customer->getId()]['ltv'] = $ltv;
        $this->orders[$customer->getId()]['cnt'] = $cnt;
        $this->orders[$customer->getId()]['aov'] = ($cnt > 0) ? round($ltv / $cnt, 2) : 0;

        $this->logger->info("CustomerSync calculateCustomer", $this->orders[$customer->getId()]);

        return $ltv;
    }

    public function getOrdersLtv(&$customer): float
    {
        return $this->orders[$customer->getId()]['ltv'];
    }

    public function getOrdersAov(&$customer): float
    {
        return $this->orders[$customer->getId()]['aov'];
    }

    public function getOrdersCount(&$customer): float
    {
        return $this->orders[$customer->getId()]['cnt'];
    }

    public function getMarketingAllow(&$customer): float
    {
        /** @var \Magento\Customer\Model\Customer $customer */
        $checkSubscriber = $this->subscriber->loadByCustomer($customer->getId(), $customer->getWebsiteId());

        return $checkSubscriber->isSubscribed() ? 1 : 0;
    }

    /**
     * @param string $message
     *
     * @return void
     */
    public function login(string $message): void
    {
        $this->eventType = $this->helper::EVENT_LOGIN;
        $this->event($message);
    }

    /**
     * @param string $message
     *
     * @return void
     */
    public function newsletter(string $message): void
    {
        $this->eventType = $this->helper::EVENT_NEWSLETTER_SIGN_UP;
        $this->event($message);

        list($usercomUserId, $usercomKey, $messageData) = $this->extractParams($message);
        if (isset($messageData['customerId']) && ! empty($messageData['customerId'])) {
            $this->syncCustomerById($messageData['customerId']);
        }
    }
}
