<?php

namespace Usercom\Analytics\Controller\Adminhtml\System\Config;

class SyncCustomer extends \Magento\Backend\App\Action
{
    protected \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory;
    protected array $syncTimeArray;
    protected \Magento\Framework\MessageQueue\PublisherInterface $publisher;
    protected \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $collectionFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Usercom\Analytics\Block\System\Config\SyncTime $syncTime,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $collectionFactory
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->syncTimeArray     = $syncTime->toOptionArray();
        $this->publisher         = $publisher;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\Controller\Result\Json
    {
        $errorMessage = "";
        $key          = $_POST["time"] ?? null;
        $lastDay      = $_POST["lastDat"] ?? null;

        if (is_null($key)) {
            return $this->result("Error: missing param", 400);
        }
        $key = (int)$key;
        if (! isset($this->syncTimeArray[$key])) {
            return $this->result("Error: bad time", 400);
        }
        $optionValue = $this->syncTimeArray[$key]["value"];
        $optionTime  = $this->syncTimeArray[$key]["time"];
        if ($optionValue == 4) {
            $from = null;
        } else {
            $from = date('Y-m-d h:i:s', strtotime($optionTime));
        }

        $customersQuery = $this->collectionFactory->create()
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
        $customers = $customersQuery->load();
        try {
            foreach ($customers ?? [] as $customer) {
                $this->publisher->publish('usercom.customer.sync', $customer->getId());
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
        }

        return ( ! empty($errorMessage)) ? $this->result($errorMessage, 409) : $this->result("Success", 200);
    }

    public function result($message, $code): \Magento\Framework\Controller\Result\Json
    {
        $result = $this->resultJsonFactory->create();
        $result->setHttpResponseCode($code);

        return $result->setData(['status' => $message]);
    }
}
