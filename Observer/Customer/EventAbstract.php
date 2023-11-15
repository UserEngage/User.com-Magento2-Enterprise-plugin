<?php

namespace Usercom\Analytics\Observer\Customer;

use Magento\Customer\Model\CustomerRegistry;
use Magento\Customer\Model\ResourceModel\CustomerRepository;

abstract class EventAbstract
{
    protected $helper;
    protected $usercom;
    protected $publisher;
    protected CustomerRegistry $customerRegistry;
    protected CustomerRepository $customerRepository;
    protected \Psr\Log\LoggerInterface $logger;

    public function __construct(
        CustomerRegistry $customerRegistry,
        CustomerRepository $customerRepository,
        \Usercom\Analytics\Helper\Data $helper,
        \Usercom\Analytics\Helper\Usercom $usercom,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->customerRegistry   = $customerRegistry;
        $this->customerRepository = $customerRepository;
        $this->helper             = $helper;
        $this->usercom            = $usercom;
        $this->publisher          = $publisher;
        $this->logger             = $logger;
    }

    protected function generateUserComUserID($observer)
    {
        $customerModel = $observer->getEvent()->getCustomer();
        $userUserId    = null;
        $userComKey    = null;
        if ($customerModel instanceof \Magento\Customer\Model\Data\Customer) {
            $userUserIdObject = $customerModel->getCustomAttribute('usercom_user_id');
            if ( ! is_null($userUserIdObject)) {
                $userUserId = $userUserIdObject->getValue() ?? null;
            }
        }

        if ($customerModel instanceof \Magento\Customer\Model\Customer\Interceptor) {
            $userUserId = $customerModel->getData('usercom_user_id') ?? null;
        }
        if ($customerModel instanceof \Magento\Customer\Model\Customer\Interceptor) {
            $userComKey = $customerModel->getData('usercom_key') ?? null;
        }
        $needSave = false;
        /** @var \Magento\Customer\Model\Data\Customer $customerModel */
        if (is_null($userUserId)) {
            /** @var \Magento\Customer\Api\Data\CustomerInterface $customerEntity */
            $customerEntity = $this->customerRepository->getById($customerModel->getId());
            $userUserId     = $this->usercom->getUserHash(
                $customerModel->getId()
            );
            $customerEntity->setCustomAttribute(
                'usercom_user_id',
                $userUserId
            );
            $needSave = true;
        }


        /** @var \Magento\Customer\Model\Data\Customer $customerModel */
        if (is_null($userComKey)) {
            $userComKeyFromCookie = $this->usercom->getFrontUserKey();
            /** @var \Magento\Customer\Api\Data\CustomerInterface $customerEntity */
            $customerEntity->setCustomAttribute(
                'usercom_key',
                $userComKeyFromCookie
            );
            $needSave = true;
        }
        if ($needSave) {
            $this->customerRepository->save($customerEntity);
        }

        return $userUserId;
    }
}
