<?php

namespace Usercom\Analytics\Model;

class CustomerSyncAbstract
{
    protected \Usercom\Analytics\Helper\Usercom $helper;
    protected \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository;
    protected \Usercom\Analytics\Helper\Data $dataHelper;

    protected string $eventType;

    public function __construct(
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Usercom\Analytics\Helper\Usercom $helper,
        \Usercom\Analytics\Helper\Data $dataHelper,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->customerRepository = $customerRepository;
        $this->helper             = $helper;
        $this->dataHelper         = $dataHelper;
        $this->logger             = $logger;
    }

    /**
     * @param string $message
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function event(string $message): void
    {
        if (! $this->dataHelper->isModuleEnabled()) {
            return;
        }

        $eventData = [];
        list($usercomUserId, $usercomKey, $messageData) = $this->extractParams($message);

        $subStatus = $messageData['subscribeStatus'] ?? null;
        if ($subStatus !== null) {
            $subscribeStatus = ($messageData['subscribeStatus'] == 1);
        }
        if ($subStatus !== null) {
            $eventData['unsubscribed'] = ! $subscribeStatus;
        }
        $eventData['email'] = $messageData['email'] ?? null;

        $customerId = $messageData['customerId'] ?? null;
        if ($customerId !== null) {
            $customer                = $this->customerRepository->getById($customerId);
            $eventData['First name'] = $customer->getFirstname();
            $eventData['Last name']  = $customer->getLastname();
        }
        $this->logger->info(
            "CustomerEvent: " . $this->eventType,
            [
                'usercom_user_id' => $usercomUserId ?? null,
                'usercom_key'     => $usercomKey ?? null,
                'moduleEnabled'   => $this->dataHelper->isModuleEnabled(),
                'eventData'       => $eventData ?? []
            ]
        );

        $this->helper->updateCustomer($usercomUserId, $eventData);

        $data          = $this->getEventData($usercomKey, $eventData);
        $eventResponse = $this->helper->createEvent($data);
//        $eventResponse = $this->helper->createEventByCustomId($usercomUserId,$data);
        $this->logger->info("CustomerEvent: " . $this->eventType, [json_encode($eventResponse)]);
    }

    /**
     * @param string $message
     *
     * @return array
     */
    protected function extractParams(string $message): array
    {
        $messageData   = json_decode($message, true);
        $usercomUserId = $messageData['usercom_user_id'];
        $usercomKey    = $messageData['user_key'];

        return [$usercomUserId, $usercomKey, $messageData];
    }

    /**
     * @param string|null $usercomKey
     * @param $data
     *
     * @return array
     */
    protected function getEventData(string $usercomKey = null, $data, $time = null): array
    {
        if (! empty($time) && is_string($time) && ! is_numeric($time)) {
            $time = strtotime($time);
        }
        $userObject = $this->helper->getUserByUserKey($usercomKey);

        return [
            "name"      => $this->eventType,
            "timestamp" => $time ?? time(),
            "user_key"  => $usercomKey ?? null,
            "user_id"   => $userObject->id ?? null,
            "data"      => $data
        ];
    }

    /**
     * @param string $message
     *
     * @return void
     */
    protected function log(string $message)
    {
        $this->logger->info("CustomerSyncLog:", [$message]);
    }
}
