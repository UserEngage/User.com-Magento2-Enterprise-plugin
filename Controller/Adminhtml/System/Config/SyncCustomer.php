<?php

namespace Usercom\Analytics\Controller\Adminhtml\System\Config;

class SyncCustomer extends \Magento\Backend\App\Action
{
    protected \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory;
    protected array $syncTimeArray;
    protected \Magento\Customer\Model\CustomerFactory $customerFactory;
    protected \Usercom\Analytics\Helper\Usercom $userComHelper;
    protected \Magento\Customer\Model\ResourceModel\CustomerRepository $customerRepository;
    protected \Usercom\Analytics\Helper\Data $helper;

    public function __construct(
        \Usercom\Analytics\Helper\Data $helper,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Usercom\Analytics\Block\System\Config\SyncTime $syncTime,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Usercom\Analytics\Helper\Usercom $userComHelper,
        \Magento\Customer\Model\ResourceModel\CustomerRepository $customerRepository
    ) {
        $this->helper             = $helper;
        $this->resultJsonFactory  = $resultJsonFactory;
        $this->syncTimeArray      = $syncTime->toOptionArray();
        $this->customerFactory    = $customerFactory;
        $this->userComHelper      = $userComHelper;
        $this->customerRepository = $customerRepository;
        parent::__construct($context);
    }

    public function execute()
    {
        $key     = $_POST["time"] ?? null;
        $lastDay = $_POST["lastDat"] ?? null;

        if (is_null($key)) {
            return $this->result("Error: missing param", 400);
        }
        $key = (int)$key;
        if ( ! isset($this->syncTimeArray[$key])) {
            return $this->result("Error: bad time", 400);
        }
        $optionValue = $this->syncTimeArray[$key]["value"];
        $optionTime  = $this->syncTimeArray[$key]["time"];
        if ($optionValue == 4) {
            $from = null;
        } else {
            $from = date('Y-m-d h:i:s', strtotime($optionTime));
        }

        $API       = $this->helper->getApi();
        $customersQuery = $this->customerFactory->create()
                                                ->getCollection()
                                                ->addAttributeToSelect("created_at")
                                                ->addAttributeToSelect("id")
                                                ->addAttributeToSelect("usercom_user_id")
                                                ->addAttributeToSelect("usercom_user_key");
        if ($from !== null) {
            $customersQuery->addAttributeToFilter('created_at', ['from' => $from]);
        }
        if ($lastDay !== null) {
            $customersQuery->addAttributeToFilter('updated_at ', ['from' => $from]);
        }
        $customers    = $customersQuery->load();
        $errorMessage = "";

        foreach ($customers as $customer) {
            $customerData          = $customer->getData();
            $customerUsercomUserId = $customerData['usercom_user_id'] ?? null;
            $customerUsercomKey    = $customerData['usercom_key'] ?? null;
            $customerEmail         = $customerData['email'];
            $customerId            = $customer->getId();
//            var_dump($customerEmail);
            if (empty($customerUsercomUserId) || empty($customerUsercomKey)) {
                $customerEntity = $this->customerRepository->getById($customerId);
                $users          = $this->userComHelper->getUsersByEmail($customerEmail);
                $user           = null;
                foreach ($users ?? [] as $u) {
                    if ($u->custom_id == $customerUsercomUserId) {
                        $user = $u;
                    }
                }
                unset($u);

                if ($user === null) {
                    $user = $this->userComHelper->getCustomerByCustomId($customerUsercomUserId);
                }
                if ($user === null && $users) {
                    $user = $users[0];
                }
                $hash = null;
                if ($user !== null) {
                    $hash = $user->custom_id ?? null;
                }
                if (empty($hash)) {
                    $hash = $this->userComHelper->getUserHash($customerId);
                }
                $customerData['usercom_user_id'] = $hash;
                $customerEntity->setCustomAttribute(
                    'usercom_user_id',
                    $hash
                );
                if ($user !== null) {
                    $customerEntity->setCustomAttribute(
                        'usercom_key',
                        $user->user_key
                    );
                }
                $this->customerRepository->save($customerEntity);
//                if ($user !== null) {
//                    $this->userComHelper->syncUserById($user->id, $customerData);
//                }
            }
            $this->mapDataForUserCom($customerData, $customerEntity);
            $this->userComHelper->syncUserHash($customerData);
        }

        return ( ! empty($errorMessage)) ? $this->result($errorMessage, 409) : $this->result("Success", 200);
    }

    public function result($message, $code)
    {
        $result = $this->resultJsonFactory->create();
        $result->setHttpResponseCode($code);

        return $result->setData(['status' => $message]);
    }

    public function mapDataForUserCom(&$customerData, $customerEntity)
    {
        $fieldsMap = $this->helper->getFieldMapping();
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
