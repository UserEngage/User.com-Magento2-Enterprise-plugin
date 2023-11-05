<?php

namespace Usercom\Analytics\Controller\Adminhtml\System\Config;

class SyncProductsPurchase extends \Magento\Backend\App\Action
{
    protected $resultJsonFactory;
    protected $syncTimeArray;
    protected $orderCollectionFactory;
    protected $usercom;
    protected $addressConfig;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Usercom\Analytics\Block\System\Config\SyncTime $syncTime,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher
    ) {
        $this->resultJsonFactory      = $resultJsonFactory;
        $this->syncTimeArray          = $syncTime->toOptionArray();
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->publisher              = $publisher;
        parent::__construct($context);
    }

    public function execute()
    {
        $errorMessage = "";
        $key          = $_POST["time"] ?? null;
        $lastDay      = $_POST["lastDat"] ?? null;
        if (empty($key)) {
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
        $orders = $this->orderCollectionFactory->create()
                                               ->addAttributeToFilter('created_at', ['from' => $from])
                                               ->addAttributeToFilter('state', 'complete')
                                               ->load();

        try {
            foreach ($orders as $order) {
                $data = [
                    'order_id'        => $order->getId(),
                    'usercom_user_id' => null,
                    'user_key'        => null,
                    'time'            => $order->getCreatedAt(),
                    'step'            => 'purchase',
                    'source'          => 'SYNC'
                ];
                $this->publisher->publish('usercom.order.purchase', json_encode($data));
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
        }

        return ($errorMessage) ? $this->result($errorMessage, 409) : $this->result("Success", 200);
    }

    public function result($message, $code)
    {
        $result = $this->resultJsonFactory->create();
        $result->setHttpResponseCode($code);

        return $result->setData(['status' => $message]);
    }
}
