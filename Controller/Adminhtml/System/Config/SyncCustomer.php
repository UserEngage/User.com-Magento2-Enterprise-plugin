<?php

namespace Usercom\Analytics\Controller\Adminhtml\System\Config;

class SyncCustomer extends \Magento\Backend\App\Action
{
    protected \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory;
    protected array $syncTimeArray;
    protected \Magento\Customer\Model\CustomerFactory $customerFactory;
    protected \Usercom\Analytics\Helper\Usercom $userComHelper;
    protected \Magento\Customer\Model\ResourceModel\CustomerRepository $customerRepository;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Usercom\Analytics\Block\System\Config\SyncTime $syncTime,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Usercom\Analytics\Helper\Usercom $userComHelper,
        \Magento\Customer\Model\ResourceModel\CustomerRepository $customerRepository
    ) {
        $this->resultJsonFactory  = $resultJsonFactory;
        $this->syncTimeArray      = $syncTime->toOptionArray();
        $this->customerFactory    = $customerFactory;
        $this->userComHelper      = $userComHelper;
        $this->customerRepository = $customerRepository;
        parent::__construct($context);
    }

    public function execute()
    {
        $key = $_POST["time"] ?? null;

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
        $customersQuery = $this->customerFactory->create()
                                                ->getCollection()
                                                ->addAttributeToSelect("created_at")
                                                ->addAttributeToSelect("id")
                                                ->addAttributeToSelect("usercom_user_id")
                                                ->addAttributeToSelect("usercom_user_key");
        if ($from !== null) {
            $customersQuery->addAttributeToFilter('created_at', ['from' => $from]);
        }
        $customers    = $customersQuery->load();
        $errorMessage = "";

        foreach ($customers as $customer) {
            $customerData          = $customer->getData();
            $customerUsercomUserId = $customerData['usercom_user_id'] ?? null;
            $customerUsercomKey    = $customerData['usercom_user_key'] ?? null;
            $customerEmail         = $customerData['email'];
            $customerId = $customer->getId();
            if (empty($customerUsercomUserId) || empty($customerUsercomKey)) {
                $customerEntity = $this->customerRepository->getById($customerId);
                $users = $this->userComHelper->getUsersByEmail($customerEmail);
                $user  = null;
                foreach ($users ?? [] as $u) {
                    if ($u->custom_id == $customerUsercomUserId) {
                        $user = $u;
                    }
                }
                if ($user === null && $users) {
                    $user = $users[0];
                }
                unset($u);
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
                        'usercom_user_key',
                        $user->user_key
                    );
                }
                $this->customerRepository->save($customerEntity);
                if ($user !== null) {
                    $this->userComHelper->syncUserById($user->id, $customerData);
                }
            }

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
}
