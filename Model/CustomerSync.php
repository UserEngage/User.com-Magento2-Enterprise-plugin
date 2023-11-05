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
        $this->eventType = $this->helper::EVENT_REGISTER;
        $this->event($message);

        list($usercomUserId, $usercomKey, $messageData) = $this->extractParams($message);
        if (isset($messageData['customerId']) && ! empty($messageData['customerId'])) {
            $this->syncCustomerById($messageData['customerId']);
        }
    }

    /**
     * @param string $message
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function syncCustomerById(string $customerId): void
    {
        $this->debug("CustomerSync", ['customerId:' => $customerId]);
        $customerId = $customerId ?? null;
        if ($customerId !== null) {
            $customer = $this->customerRepository->getById($customerId);
            $data     = $customer->__toArray();

            $customerUsercomUserId   = $data['custom_attributes']['usercom_user_id']['value'] ?? null;
            $customerUsercomKey      = $data['custom_attributes']['usercom_key']['value'] ?? null;
            $data['usercom_user_id'] = $customerUsercomUserId;
            $data['usercom_key']     = $customerUsercomKey;

            $this->debug("CustomerSync customAttrId:", $data);

            $customerEmail = $customer->getEmail();
            $customerId    = $customer->getId();

            if (empty($customerUsercomUserId) || empty($customerUsercomKey)) {
                $users = $this->helper->getUsersByEmail($customerEmail);
                $user  = null;
                foreach ($users ?? [] as $u) {
                    if ($u->custom_id == $customerUsercomUserId) {
                        $user = $u;
                    }
                }
                unset($u);

                if ($user === null) {
                    $user = $this->helper->getCustomerByCustomId($customerUsercomUserId);
                }
                if ($user === null && $users) {
                    $user = $users[0];
                }
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
                if ($user !== null) {
                    $customer->setCustomAttribute(
                        'usercom_key',
                        $user->user_key
                    );
                }
                $this->customerRepository->save($customer);
//                if ($user !== null) {
//                    $this->helper->syncUserById($user->id, $data);
//                }
            }

            $this->mapDataForUserCom($data, $customer);
            $this->helper->syncUserHash($data);
            $this->debug("CustomerSync EOF", $data);
        }
        $this->debug("CustomerSync EOF", []);
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

        $this->logger->info("CustomerSync getOrdersLtv", $this->orders[$customer->getId()]);

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
