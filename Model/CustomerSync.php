<?php

namespace Usercom\Analytics\Model;

class CustomerSync
{
    private \Usercom\Analytics\Helper\Usercom $helper;
    private \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository;
    private \Usercom\Analytics\Helper\Data $dataHelper;

    public function __construct(
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Usercom\Analytics\Helper\Usercom $helper,
        \Usercom\Analytics\Helper\Data $dataHelper,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->customerRepository = $customerRepository;
        $this->helper             = $helper;
        $this->dataHelper         = $dataHelper;
        $this->loggger            = $logger;
    }

    /**
     * @param string $message
     *
     * @return void
     */
    public function log(string $message)
    {
        $this->loggger->info("CustomerSync", [$message]);
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
        $this->loggger->info("CustomerSync", ['customerId:' => $customerId]);
        $customerId = $customerId ?? null;
        if ($customerId !== null) {
            $customer = $this->customerRepository->getById($customerId);
            $data     = $customer->__toArray();

            $customerUsercomUserId   = $data['custom_attributes']['usercom_user_id']['value'] ?? null;
            $customerUsercomKey      = $data['custom_attributes']['usercom_key']['value'] ?? null;
            $data['usercom_user_id'] = $customerUsercomUserId;
            $data['usercom_key']     = $customerUsercomKey;

            $this->loggger->info("CustomerSync customAttrId:", $data);

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
            $this->loggger->info("CustomerSync EOF", $data);
        }
        $this->loggger->info("CustomerSync EOF",);
    }

    public function mapDataForUserCom(&$customerData, $customer)
    {
        $fieldsMap = $this->dataHelper->getFieldMapping();
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
                        $customerData[$fieldName] = $this->getMarketingAllow($customer);
                        break;
                }
            }
        }
    }

    public function getOrdersLtv(&$customer): float
    {
        //TODO implement getOrdersLtv
        return 0;
    }

    public function getOrdersAov(&$customer): float
    {
        //TODO implement getOrdersAov
        return 0;
    }

    public function getOrdersCount(&$customer): float
    {
        //TODO implement getOrdersCount
        return 0;
    }

    public function getMarketingAllow(&$customer): float
    {
        //TODO implement getMarketingAllow
        return 1;
    }
}
