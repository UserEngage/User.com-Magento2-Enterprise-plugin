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
    public function syncCustomerById(string $message): void
    {
        $message    = json_decode($message, true);
        $customerId = $message['customerId'];
        $data       = $message['data'];
//         Process the message
        $customerEntity = $this->customerRepository->getById($customerId);
        $this->mapDataForUserCom($data, $customerEntity);
        $this->helper->syncUserHash($data);
    }


    public function mapDataForUserCom(&$customerData, $customerEntity)
    {
        $fieldsMap = $this->dataHelper->getFieldMapping();
        foreach ($fieldsMap as $field) {
            $fieldName = $field["name"];
            if (isset($field["mapping"]) && $field["mapping"] == "automatic") {
                switch ($fieldName) {
                    case "orders_ltv":
                        $customerData[$fieldName] = $this->getOrdersLtv($customerEntity);
                        break;
                    case "orders_aov":
                        $customerData[$fieldName] = $this->getOrdersAov($customerEntity);
                        break;
                    case "orders_count":
                        $customerData[$fieldName] = $this->getOrdersCount($customerEntity);
                        break;
                    case "marketing_allow":
                        $customerData[$fieldName] = $this->getMarketingAllow($customerEntity);
                        break;
                }
            }
        }
    }

    public function getOrdersLtv(&$customerEntity): float
    {
        //TODO implement getOrdersLtv
        return 0;
    }

    public function getOrdersAov(&$customerEntity): float
    {
        //TODO implement getOrdersAov
        return 0;
    }

    public function getOrdersCount(&$customerEntity): float
    {
        //TODO implement getOrdersCount
        return 0;
    }

    public function getMarketingAllow(&$customerEntity): float
    {
        //TODO implement getMarketingAllow
        return 1;
    }
}
