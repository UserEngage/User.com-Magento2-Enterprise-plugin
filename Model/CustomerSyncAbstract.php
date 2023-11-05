<?php

namespace Usercom\Analytics\Model;

class CustomerSyncAbstract
{
    use DebugTrait;

    protected \Usercom\Analytics\Helper\Usercom $helper;
    protected \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository;
    protected \Usercom\Analytics\Helper\Data $dataHelper;
    protected \Magento\Sales\Api\OrderRepositoryInterface $orderRepository;
    protected \Magento\Framework\Api\SearchCriteria $searchCriteria;
    protected \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder;
    protected \Magento\Newsletter\Model\Subscriber $subscriber;

    protected string $eventType;

    public function __construct(
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Newsletter\Model\Subscriber $subscriber,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Api\SearchCriteria $searchCriteria,
        \Usercom\Analytics\Helper\Usercom $helper,
        \Usercom\Analytics\Helper\Data $dataHelper,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->customerRepository    = $customerRepository;
        $this->subscriber            = $subscriber;
        $this->orderRepository       = $orderRepository;
        $this->helper                = $helper;
        $this->dataHelper            = $dataHelper;
        $this->logger                = $logger;
        $this->searchCriteria        = $searchCriteria;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
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
        if ( ! $this->dataHelper->isModuleEnabled()) {
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
        $this->log('CustomerEvent' . $this->eventType, ['customerId' => $customerId]);

        if ($customerId !== null) {
            $customer                = $this->customerRepository->getById($customerId);
            $eventData['First name'] = $customer->getFirstname();
            $eventData['Last name']  = $customer->getLastname();
        }
        $this->debug(
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
        $this->debug("CustomerEvent: " . $this->eventType, [json_encode($eventResponse)]);
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
        if ( ! empty($time) && is_string($time) && ! is_numeric($time)) {
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
}
