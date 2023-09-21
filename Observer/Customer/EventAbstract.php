<?php

namespace Usercom\Analytics\Observer\Customer;

use Magento\Customer\Model\CustomerRegistry;
use Magento\Customer\Model\ResourceModel\CustomerRepository;

abstract class EventAbstract
{
    protected $helper;
    protected $usercom;
    protected CustomerRegistry $customerRegistry;
    protected CustomerRepository $customerRepository;

    public function __construct(
        CustomerRegistry $customerRegistry,
        CustomerRepository $customerRepository,
        \Usercom\Analytics\Helper\Data $helper,
        \Usercom\Analytics\Helper\Usercom $usercom
    ) {
        $this->customerRegistry   = $customerRegistry;
        $this->customerRepository = $customerRepository;
        $this->helper             = $helper;
        $this->usercom            = $usercom;
    }

    protected function generateUserComUserID($observer)
    {
        $customerModel = $observer->getEvent()->getCustomer();
        $userUserId    = null;
        if ($customerModel instanceof \Magento\Customer\Model\Data\Customer) {
            $userUserIdObject = $customerModel->getCustomAttribute('usercom_user_id');
            if ( ! is_null($userUserIdObject)) {
                $userUserId = $userUserIdObject->getValue() ?? null;
            }
        }

        if ($customerModel instanceof \Magento\Customer\Model\Customer\Interceptor) {
            $userUserId = $customerModel->getData('usercom_user_id') ?? null;
        }

        /** @var \Magento\Customer\Model\Data\Customer $customerModel */
        if (is_null($userUserId)) {
            /** @var \Magento\Customer\Api\Data\CustomerInterface $customerEntity */
            $customerEntity = $this->customerRepository->getById($customerModel->getId());
            $hash           = $this->usercom->getUserHash(
                $customerModel->getId()
            );
            $customerEntity->setCustomAttribute(
                'usercom_user_id',
                $hash
            );

            $this->customerRepository->save($customerEntity);
        }
    }
}